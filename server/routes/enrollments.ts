import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, isNotNull } from "drizzle-orm";
import { sendCourseEmail, sendReminderEmail, isEmailConfigured } from "../email";
import { getAuthenticatedUserTutorId } from "./helpers";

export function registerEnrollmentsRoutes(app: Express) {
  // GET /api/enrollments — lista iscrizioni (filtrate per ruolo)
  app.get("/api/enrollments", isAuthenticated, async (req, res) => {
    try {
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);

      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }

      const tutorId = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;

      const enrollmentsRaw = await db.select({
        id: schema.enrollments.id,
        studentId: schema.enrollments.studentId,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        createdAt: schema.enrollments.createdAt,
        completedAt: schema.enrollments.completedAt,
        lastAccessAt: schema.enrollments.lastAccessAt,
        enrollmentTutorId: schema.enrollments.tutorId,
        studentEmail: schema.students.email,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
        tutorId: schema.companies.tutorId,
        courseTitle: schema.courses.title,
        tutorName: schema.tutors.businessName,
      })
        .from(schema.enrollments)
        .leftJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .leftJoin(schema.tutors, eq(schema.enrollments.tutorId, schema.tutors.id))
        .where(isNotNull(schema.enrollments.tutorId))
        .orderBy(desc(schema.enrollments.startDate));

      let filtered = enrollmentsRaw;
      if (tutorId) {
        filtered = filtered.filter(e => e.tutorId === tutorId);
      }
      if (companyId) {
        filtered = filtered.filter(e => e.companyId === companyId);
      }

      const enrollments = filtered.map(e => ({
        id: e.id,
        companyName: e.companyName || '',
        userName: `${e.studentLastName || ''} ${e.studentFirstName || ''}`.trim(),
        userEmail: e.studentEmail || '',
        courseName: e.courseTitle || '',
        startDate: e.startDate,
        endDate: e.endDate,
        lastAccessAt: e.lastAccessAt,
        progress: e.progress || 0,
        status: e.status || 'active',
        emailSentAt: null,
        emailOpenedAt: null,
        licenseCode: e.licenseCode,
        tutorId: e.enrollmentTutorId || null,
        tutorName: e.tutorName || '',
      }));

      res.json(enrollments);
    } catch (error) {
      console.error("Enrollments error:", error);
      res.status(500).json({ error: "Failed to fetch enrollments" });
    }
  });

  // POST /api/enrollments — crea iscrizione singola
  app.post("/api/enrollments", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertEnrollmentSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newEnrollment] = await db.insert(schema.enrollments).values(result.data).returning();
      res.status(201).json(newEnrollment);
    } catch (error) {
      console.error("Create enrollment error:", error);
      res.status(500).json({ error: "Failed to create enrollment" });
    }
  });

  // POST /api/enrollments/activate — vendita corso (crea studenti + iscrizioni + email)
  app.post("/api/enrollments/activate", isAuthenticated, async (req, res) => {
    try {
      if (!hasDatabase) {
        return res.status(503).json({ error: "Database non configurato." });
      }

      const { courseId, companyId, corsisti } = req.body;

      if (!courseId || !companyId || !corsisti || !Array.isArray(corsisti) || corsisti.length === 0) {
        return res.status(400).json({ error: "Dati mancanti: courseId, companyId e corsisti sono obbligatori" });
      }

      const company = await db.select().from(schema.companies).where(eq(schema.companies.id, companyId)).limit(1);
      if (!company.length) {
        return res.status(404).json({ error: "Azienda non trovata" });
      }

      const course = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId)).limit(1);
      if (!course.length) {
        return res.status(404).json({ error: "Corso non trovato" });
      }

      const tutorId = company[0].tutorId;
      let created = 0;
      const results: { studentId: number; licenseCode: string; email: string; firstName: string; lastName: string; fiscalCode: string; username: string }[] = [];

      // Trova l'admin tutor per creare la vendita locale
      let adminTutorId = 1;
      if (tutorId) {
        const adminTutor = await db.select().from(schema.tutorAdmins)
          .where(eq(schema.tutorAdmins.tutorId, tutorId))
          .limit(1);
        if (adminTutor.length > 0) {
          adminTutorId = adminTutor[0].id;
        }
      }

      // Crea vendita locale
      const [localPurchase] = await db.insert(schema.tutorsPurchases).values({
        tutorId: adminTutorId,
        customerCompanyId: companyId,
        userCompanyRef: adminTutorId,
        learningProjectId: courseId,
        qta: corsisti.length,
        price: "0.00",
        executed: true,
      }).returning();
      console.log(`[Local] Vendita locale creata: ${localPurchase.id}`);

      for (const corsista of corsisti) {
        const { lastName, firstName, fiscalCode, startDate, endDate, daysToAlert } = corsista;

        if (!lastName || !firstName || !fiscalCode) {
          continue;
        }

        const email = corsista.email || `${fiscalCode.toLowerCase()}@corsista.tutor81.com`;
        const username = `${firstName}.${lastName}`.toLowerCase().replace(/\s+/g, '');

        let studentId: number;
        const existingStudent = await db.select()
          .from(schema.students)
          .where(and(
            eq(schema.students.fiscalCode, fiscalCode),
            eq(schema.students.companyId, companyId)
          ))
          .limit(1);

        if (existingStudent.length > 0) {
          studentId = existingStudent[0].id;
        } else {
          const [newStudent] = await db.insert(schema.students).values({
            companyId,
            email,
            firstName,
            lastName,
            fiscalCode,
            isActive: true,
          }).returning();
          studentId = newStudent.id;
        }

        const licenseCode = `${courseId}-${studentId}-${Date.now().toString(36).toUpperCase()}`;
        const enrollStartDate = startDate ? new Date(startDate) : new Date();
        const enrollEndDate = endDate ? new Date(endDate) : null;

        await db.insert(schema.enrollments).values({
          studentId,
          courseId,
          tutorId,
          licenseCode,
          startDate: enrollStartDate,
          endDate: enrollEndDate,
          daysToAlert: daysToAlert || 15,
          progress: 0,
          status: "active",
        }).returning();

        const student = await db.select().from(schema.students).where(eq(schema.students.id, studentId)).limit(1);
        results.push({
          studentId,
          licenseCode,
          email,
          firstName: student[0]?.firstName || firstName,
          lastName: student[0]?.lastName || lastName,
          fiscalCode: student[0]?.fiscalCode || fiscalCode,
          username,
        });
        created++;
      }

      // Invio email automatico
      let emailsSent = 0;
      if (isEmailConfigured() && results.length > 0) {
        let tutorName = 'Tutor81';
        let tutorEmail = '';
        let tutorAddress = '';
        if (tutorId) {
          const [tutor] = await db.select({
            businessName: schema.tutors.businessName,
            email: schema.tutors.email,
            address: schema.tutors.address,
          }).from(schema.tutors).where(eq(schema.tutors.id, tutorId)).limit(1);
          if (tutor) {
            tutorName = tutor.businessName || 'Tutor81';
            tutorEmail = tutor.email || '';
            tutorAddress = tutor.address || '';
          }
        }

        const startDateStr = corsisti[0]?.startDate || new Date().toISOString().split('T')[0];
        const endDateStr = corsisti[0]?.endDate || '';

        for (const r of results) {
          if (r.email && !r.email.endsWith('@corsista.tutor81.com')) {
            const emailResult = await sendCourseEmail({
              to: r.email,
              userName: `${r.firstName} ${r.lastName}`,
              username: r.username,
              courseName: course[0].title || '',
              startDate: startDateStr,
              endDate: endDateStr,
              tutorName,
              tutorEmail,
              tutorAddress,
            });
            if (emailResult.success) emailsSent++;
          }
        }
        console.log(`[Enrollments] ${emailsSent}/${results.length} email inviate`);
      }

      res.json({
        success: true,
        created,
        emailsSent,
        message: `${created} iscrizioni create, ${emailsSent} email inviate`,
        courseTitle: course[0].title,
        enrollments: results,
      });
    } catch (error) {
      console.error("Activate enrollments error:", error);
      res.status(500).json({ error: "Errore durante la creazione delle iscrizioni" });
    }
  });

  // POST /api/enrollments/send-emails — invio manuale email corso
  app.post("/api/enrollments/send-emails", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body;
      if (!enrollmentIds || !Array.isArray(enrollmentIds)) {
        return res.status(400).json({ error: "enrollmentIds required" });
      }

      if (!isEmailConfigured()) {
        return res.status(503).json({ error: "Servizio email non configurato (RESEND_API_KEY mancante)" });
      }

      let sent = 0;
      let failed = 0;

      for (const enrollmentId of enrollmentIds) {
        const [enrollment] = await db.select({
          id: schema.enrollments.id,
          studentId: schema.enrollments.studentId,
          courseId: schema.enrollments.courseId,
          startDate: schema.enrollments.startDate,
          endDate: schema.enrollments.endDate,
          tutorId: schema.enrollments.tutorId,
        }).from(schema.enrollments).where(eq(schema.enrollments.id, enrollmentId)).limit(1);

        if (!enrollment) continue;

        const [student] = await db.select().from(schema.students).where(eq(schema.students.id, enrollment.studentId)).limit(1);
        const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, enrollment.courseId)).limit(1);

        if (!student || !course || !student.email) continue;

        let tutorName = 'Tutor81';
        let tutorEmail = '';
        let tutorAddress = '';
        if (enrollment.tutorId) {
          const [tutor] = await db.select({
            businessName: schema.tutors.businessName,
            email: schema.tutors.email,
            address: schema.tutors.address,
          }).from(schema.tutors).where(eq(schema.tutors.id, enrollment.tutorId)).limit(1);
          if (tutor) {
            tutorName = tutor.businessName || 'Tutor81';
            tutorEmail = tutor.email || '';
            tutorAddress = tutor.address || '';
          }
        }

        const username = `${student.firstName}.${student.lastName}`.toLowerCase().replace(/\s+/g, '');
        const result = await sendCourseEmail({
          to: student.email,
          userName: `${student.firstName} ${student.lastName}`,
          username,
          courseName: course.title || '',
          startDate: enrollment.startDate ? new Date(enrollment.startDate).toLocaleDateString('it-IT') : '',
          endDate: enrollment.endDate ? new Date(enrollment.endDate).toLocaleDateString('it-IT') : '',
          tutorName,
          tutorEmail,
          tutorAddress,
        });

        if (result.success) sent++;
        else failed++;
      }

      console.log(`[Send-Emails] ${sent} inviate, ${failed} fallite su ${enrollmentIds.length} richieste`);
      res.json({ success: true, sent, failed });
    } catch (error) {
      console.error("Send emails error:", error);
      res.status(500).json({ error: "Failed to send emails" });
    }
  });

  // POST /api/enrollments/update-end-date
  app.post("/api/enrollments/update-end-date", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds, endDate } = req.body;
      if (!enrollmentIds || !Array.isArray(enrollmentIds) || !endDate) {
        return res.status(400).json({ error: "enrollmentIds and endDate required" });
      }
      for (const id of enrollmentIds) {
        await db.update(schema.enrollments)
          .set({ endDate: new Date(endDate) })
          .where(eq(schema.enrollments.id, id));
      }
      res.json({ success: true, updated: enrollmentIds.length });
    } catch (error) {
      console.error("Update end date error:", error);
      res.status(500).json({ error: "Failed to update end date" });
    }
  });

  // POST /api/enrollments/delete
  app.post("/api/enrollments/delete", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body;
      if (!enrollmentIds || !Array.isArray(enrollmentIds)) {
        return res.status(400).json({ error: "enrollmentIds required" });
      }
      for (const id of enrollmentIds) {
        await db.delete(schema.enrollments).where(eq(schema.enrollments.id, id));
      }
      res.json({ success: true, deleted: enrollmentIds.length });
    } catch (error) {
      console.error("Delete enrollments error:", error);
      res.status(500).json({ error: "Failed to delete enrollments" });
    }
  });

  // POST /api/enrollments/send-reminder — promemoria
  app.post("/api/enrollments/send-reminder", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body;
      if (!enrollmentIds || !Array.isArray(enrollmentIds)) {
        return res.status(400).json({ error: "enrollmentIds required" });
      }

      if (!isEmailConfigured()) {
        return res.status(503).json({ error: "Servizio email non configurato (RESEND_API_KEY mancante)" });
      }

      let sent = 0;
      let failed = 0;

      for (const enrollmentId of enrollmentIds) {
        const [enrollment] = await db.select({
          id: schema.enrollments.id,
          studentId: schema.enrollments.studentId,
          courseId: schema.enrollments.courseId,
          endDate: schema.enrollments.endDate,
          tutorId: schema.enrollments.tutorId,
        }).from(schema.enrollments).where(eq(schema.enrollments.id, enrollmentId)).limit(1);

        if (!enrollment) continue;

        const [student] = await db.select().from(schema.students).where(eq(schema.students.id, enrollment.studentId)).limit(1);
        const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, enrollment.courseId)).limit(1);

        if (!student || !course || !student.email) continue;

        let tutorName = 'Tutor81';
        if (enrollment.tutorId) {
          const [tutor] = await db.select({ businessName: schema.tutors.businessName })
            .from(schema.tutors).where(eq(schema.tutors.id, enrollment.tutorId)).limit(1);
          if (tutor) tutorName = tutor.businessName || 'Tutor81';
        }

        const result = await sendReminderEmail(
          student.email,
          `${student.firstName} ${student.lastName}`,
          course.title || '',
          enrollment.endDate ? new Date(enrollment.endDate).toLocaleDateString('it-IT') : '',
          tutorName
        );

        if (result.success) sent++;
        else failed++;
      }

      console.log(`[Send-Reminder] ${sent} inviate, ${failed} fallite su ${enrollmentIds.length} richieste`);
      res.json({ success: true, sent, failed });
    } catch (error) {
      console.error("Send reminder error:", error);
      res.status(500).json({ error: "Failed to send reminders" });
    }
  });
}
