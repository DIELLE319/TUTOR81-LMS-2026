import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql } from "drizzle-orm";
import { sendCourseEmail, sendReminderEmail, isEmailConfigured } from "../email";
import { getAuthenticatedDbUser } from "./helpers";

export function registerEnrollmentsRoutes(app: Express) {
  app.post("/api/check-fiscal-code", isAuthenticated, async (req, res) => {
    try {
      const { fiscalCode } = req.body;
      if (!fiscalCode || fiscalCode.length < 10) return res.json({ exists: false });
      const cf = fiscalCode.toUpperCase().trim();

      const localMatch = await db.select({ id: schema.students.id, firstName: schema.students.firstName, lastName: schema.students.lastName, companyId: schema.students.companyId })
        .from(schema.students).where(eq(schema.students.fiscalCode, cf)).limit(1);
      if (localMatch.length > 0) {
        return res.json({ exists: true, source: "local", student: localMatch[0] });
      }

      res.json({ exists: false });
    } catch (error) {
      console.error("Check fiscal code error:", error);
      res.json({ exists: false });
    }
  });

  app.get("/api/enrollments", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      const results = await db.select({
        id: schema.enrollments.id,
        studentId: schema.enrollments.studentId,
        courseId: schema.enrollments.courseId,
        courseTitle: schema.courses.title,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        lastAccessAt: schema.enrollments.lastAccessAt,
        completedAt: schema.enrollments.completedAt,
        enrollmentTutorId: schema.enrollments.tutorId,
        studentEmail: schema.students.email,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
        tutorName: schema.tutors.businessName,
      }).from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .innerJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .innerJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.tutors, eq(schema.enrollments.tutorId, schema.tutors.id))
        .where(role >= 1000 ? sql`1=1` : tutorId ? eq(schema.enrollments.tutorId, tutorId) : sql`1=0`)
        .orderBy(desc(schema.enrollments.createdAt));

      const mapped = results.map((e) => ({
        id: e.id,
        companyName: e.companyName || "",
        userName: `${e.studentLastName || ""} ${e.studentFirstName || ""}`.trim(),
        userEmail: e.studentEmail || "",
        courseName: e.courseTitle || "",
        startDate: e.startDate,
        endDate: e.endDate,
        lastAccessAt: e.lastAccessAt,
        progress: e.progress || 0,
        status: e.status || "active",
        emailSentAt: null,
        emailOpenedAt: null,
        licenseCode: e.licenseCode,
        tutorId: e.enrollmentTutorId || null,
        tutorName: e.tutorName || "",
      }));
      res.json(mapped);
    } catch (error) {
      console.error("Enrollments list error:", error);
      res.status(500).json({ error: "Failed to fetch enrollments" });
    }
  });

  app.post("/api/enrollments/activate", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.status(503).json({ error: "Database not available" });
      const { companyId, courseId, corsisti } = req.body;
      if (!companyId || !courseId || !corsisti?.length) return res.status(400).json({ error: "Missing required fields" });

      const company = await db.select().from(schema.companies).where(eq(schema.companies.id, companyId)).limit(1);
      if (!company.length) return res.status(404).json({ error: "Company not found" });

      const course = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId)).limit(1);
      if (!course.length) return res.status(404).json({ error: "Course not found" });

      const tutorId = company[0].tutorId;
      let created = 0;
      const results: { studentId: number; licenseCode: string; email: string; firstName: string; lastName: string; fiscalCode: string; username: string }[] = [];

      const dbUser = await getAuthenticatedDbUser(req);
      let adminTutorId = 1;
      if (dbUser?.tutor_id) adminTutorId = dbUser.tutor_id as number;

      for (const corsista of corsisti) {
        const fiscalCode = (corsista.fiscalCode || "").toUpperCase().trim();
        const firstName = (corsista.firstName || "").trim();
        const lastName = (corsista.lastName || "").trim();
        if (!fiscalCode || !firstName || !lastName) continue;

        const email = corsista.email || `${fiscalCode.toLowerCase()}@corsista.tutor81.com`;
        const username = `${firstName}.${lastName}`.toLowerCase().replace(/\s+/g, "");

        let studentId: number;
        const existing = await db.select().from(schema.students).where(and(eq(schema.students.fiscalCode, fiscalCode), eq(schema.students.companyId, companyId))).limit(1);

        if (existing.length > 0) {
          studentId = existing[0].id;
        } else {
          const [newStudent] = await db.insert(schema.students).values({ companyId, email, firstName, lastName, fiscalCode, isActive: true }).returning();
          studentId = newStudent.id;
        }

        const licenseCode = `${tutorId}-${studentId}-${Math.random().toString(36).substring(2, 10).toUpperCase()}`;
        const startDate = corsista.startDate ? new Date(corsista.startDate) : new Date();
        const endDate = corsista.endDate ? new Date(corsista.endDate) : new Date(Date.now() + 90 * 24 * 60 * 60 * 1000);

        await db.insert(schema.enrollments).values({ studentId, courseId, tutorId, licenseCode, startDate, endDate, status: "active", progress: 0 });

        await db.insert(schema.tutorsPurchases).values({ tutorId: adminTutorId, customerCompanyId: companyId, userCompanyRef: adminTutorId, learningProjectId: courseId, qta: 1, price: course[0].listPrice || "0", code: licenseCode });

        const student = await db.select().from(schema.students).where(eq(schema.students.id, studentId)).limit(1);
        results.push({ studentId, licenseCode, email, firstName: student[0]?.firstName || firstName, lastName: student[0]?.lastName || lastName, fiscalCode: student[0]?.fiscalCode || fiscalCode, username });
        created++;
      }

      let emailsSent = 0;
      if (isEmailConfigured() && results.length > 0) {
        let tutorName = "Tutor81";
        let tutorEmail = "";
        let tutorAddress = "";
        if (tutorId) {
          const [tutor] = await db.select({ businessName: schema.tutors.businessName, email: schema.tutors.email, address: schema.tutors.address }).from(schema.tutors).where(eq(schema.tutors.id, tutorId)).limit(1);
          if (tutor) { tutorName = tutor.businessName || "Tutor81"; tutorEmail = tutor.email || ""; tutorAddress = tutor.address || ""; }
        }
        const startDateStr = corsisti[0]?.startDate || new Date().toLocaleDateString("it-IT");
        const endDateStr = corsisti[0]?.endDate || "";

        for (const r of results) {
          if (r.email && !r.email.endsWith("@corsista.tutor81.com")) {
            const emailResult = await sendCourseEmail({ to: r.email, userName: `${r.firstName} ${r.lastName}`, username: r.username, courseName: course[0].title || "", startDate: startDateStr, endDate: endDateStr, tutorName, tutorEmail, tutorAddress });
            if (emailResult.success) emailsSent++;
          }
        }
        console.log(`[Enrollments] ${emailsSent}/${results.length} email inviate`);
      }

      res.json({ success: true, created, emailsSent, message: `${created} iscrizioni create, ${emailsSent} email inviate`, courseTitle: course[0].title, enrollments: results });
    } catch (error) {
      console.error("Activate enrollment error:", error);
      res.status(500).json({ error: "Failed to activate enrollment" });
    }
  });

  app.post("/api/enrollments/send-emails", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body;
      if (!enrollmentIds || !Array.isArray(enrollmentIds)) return res.status(400).json({ error: "enrollmentIds required" });
      if (!isEmailConfigured()) return res.status(503).json({ error: "Servizio email non configurato" });

      let sent = 0;
      let failed = 0;
      for (const enrollmentId of enrollmentIds) {
        const [enrollment] = await db.select().from(schema.enrollments).where(eq(schema.enrollments.id, enrollmentId)).limit(1);
        if (!enrollment) { failed++; continue; }
        const [student] = await db.select().from(schema.students).where(eq(schema.students.id, enrollment.studentId)).limit(1);
        const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, enrollment.courseId)).limit(1);
        if (!student || !course || !student.email) continue;

        let tutorName = "Tutor81";
        let tutorEmail = "";
        let tutorAddress = "";
        if (enrollment.tutorId) {
          const [tutor] = await db.select({ businessName: schema.tutors.businessName, email: schema.tutors.email, address: schema.tutors.address }).from(schema.tutors).where(eq(schema.tutors.id, enrollment.tutorId)).limit(1);
          if (tutor) { tutorName = tutor.businessName || "Tutor81"; tutorEmail = tutor.email || ""; tutorAddress = tutor.address || ""; }
        }
        const username = `${student.firstName}.${student.lastName}`.toLowerCase().replace(/\s+/g, "");
        const result = await sendCourseEmail({ to: student.email, userName: `${student.firstName} ${student.lastName}`, username, courseName: course.title || "", startDate: enrollment.startDate ? new Date(enrollment.startDate).toLocaleDateString("it-IT") : "", endDate: enrollment.endDate ? new Date(enrollment.endDate).toLocaleDateString("it-IT") : "", tutorName, tutorEmail, tutorAddress });
        if (result.success) sent++;
        else failed++;
      }
      res.json({ success: true, sent, failed });
    } catch (error) {
      console.error("Send emails error:", error);
      res.status(500).json({ error: "Failed to send emails" });
    }
  });

  app.patch("/api/enrollments/update-end-date", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds, endDate } = req.body;
      if (!enrollmentIds?.length || !endDate) return res.status(400).json({ error: "Missing fields" });
      for (const id of enrollmentIds) {
        await db.update(schema.enrollments).set({ endDate: new Date(endDate) }).where(eq(schema.enrollments.id, id));
      }
      res.json({ success: true, updated: enrollmentIds.length });
    } catch (error) {
      res.status(500).json({ error: "Failed to update end dates" });
    }
  });

  app.delete("/api/enrollments", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body;
      if (!enrollmentIds?.length) return res.status(400).json({ error: "enrollmentIds required" });
      for (const id of enrollmentIds) {
        await db.delete(schema.enrollments).where(eq(schema.enrollments.id, id));
      }
      res.json({ success: true, deleted: enrollmentIds.length });
    } catch (error) {
      res.status(500).json({ error: "Failed to delete enrollments" });
    }
  });

  app.post("/api/enrollments/send-reminder", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body;
      if (!enrollmentIds?.length) return res.status(400).json({ error: "enrollmentIds required" });
      if (!isEmailConfigured()) return res.status(503).json({ error: "Servizio email non configurato" });

      let sent = 0;
      for (const enrollmentId of enrollmentIds) {
        const [enrollment] = await db.select().from(schema.enrollments).where(eq(schema.enrollments.id, enrollmentId)).limit(1);
        if (!enrollment) continue;
        const [student] = await db.select().from(schema.students).where(eq(schema.students.id, enrollment.studentId)).limit(1);
        const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, enrollment.courseId)).limit(1);
        if (!student || !course || !student.email) continue;

        let tutorName = "Tutor81";
        if (enrollment.tutorId) {
          const [tutor] = await db.select({ businessName: schema.tutors.businessName }).from(schema.tutors).where(eq(schema.tutors.id, enrollment.tutorId)).limit(1);
          if (tutor) tutorName = tutor.businessName || "Tutor81";
        }
        const result = await sendReminderEmail(student.email, `${student.firstName} ${student.lastName}`, course.title || "", enrollment.endDate ? new Date(enrollment.endDate).toLocaleDateString("it-IT") : "", tutorName);
        if (result.success) sent++;
      }
      res.json({ success: true, sent });
    } catch (error) {
      res.status(500).json({ error: "Failed to send reminders" });
    }
  });
}
