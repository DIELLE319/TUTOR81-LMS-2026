import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";

export function registerCertificatesRoutes(app: Express) {
  app.get("/api/attestati", isAuthenticated, async (req: any, res) => {
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
        completedAt: schema.enrollments.completedAt,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        studentEmail: schema.students.email,
        studentFiscalCode: schema.students.fiscalCode,
        companyName: schema.companies.businessName,
        tutorId: schema.enrollments.tutorId,
        tutorName: schema.tutors.businessName,
      }).from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .innerJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .innerJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.tutors, eq(schema.enrollments.tutorId, schema.tutors.id))
        .where(and(
          eq(schema.enrollments.status, "completed"),
          role >= 1000 ? sql`1=1` : tutorId ? eq(schema.enrollments.tutorId, tutorId) : sql`1=0`
        ))
        .orderBy(desc(schema.enrollments.completedAt));

      const mapped = results.map((e) => ({
        id: e.id,
        userName: `${e.studentLastName || ""} ${e.studentFirstName || ""}`.trim(),
        userEmail: e.studentEmail || "",
        fiscalCode: e.studentFiscalCode || "",
        courseName: e.courseTitle || "",
        companyName: e.companyName || "",
        tutorName: e.tutorName || "",
        startDate: e.startDate,
        endDate: e.endDate,
        completedAt: e.completedAt,
        progress: e.progress ?? 0,
        licenseCode: e.licenseCode,
      }));
      res.json(mapped);
    } catch (error) {
      console.error("Certificates error:", error);
      res.status(500).json({ error: "Failed to fetch certificates" });
    }
  });

  app.get("/api/certificates/:enrollmentId/pdf", isAuthenticated, async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      const [enrollment] = await db.select().from(schema.enrollments).where(eq(schema.enrollments.id, enrollmentId)).limit(1);
      if (!enrollment) return res.status(404).json({ error: "Enrollment not found" });

      const [student] = await db.select().from(schema.students).where(eq(schema.students.id, enrollment.studentId)).limit(1);
      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, enrollment.courseId)).limit(1);
      if (!student || !course) return res.status(404).json({ error: "Data not found" });

      const PDFDocument = (await import("pdfkit")).default;
      const doc = new PDFDocument({ size: "A4", margin: 50 });
      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `attachment; filename="attestato-${enrollmentId}.pdf"`);
      doc.pipe(res);

      doc.fontSize(24).font("Helvetica-Bold").text("ATTESTATO DI FORMAZIONE", { align: "center" });
      doc.moveDown(2);
      doc.fontSize(14).font("Helvetica").text(`Si certifica che`, { align: "center" });
      doc.moveDown();
      doc.fontSize(18).font("Helvetica-Bold").text(`${student.firstName} ${student.lastName}`, { align: "center" });
      doc.moveDown();
      doc.fontSize(14).font("Helvetica").text(`C.F.: ${student.fiscalCode || "N/D"}`, { align: "center" });
      doc.moveDown(2);
      doc.fontSize(14).text(`ha completato il corso:`, { align: "center" });
      doc.moveDown();
      doc.fontSize(16).font("Helvetica-Bold").text(`${course.title}`, { align: "center" });
      doc.moveDown(2);
      if (course.hours) doc.fontSize(12).font("Helvetica").text(`Durata: ${course.hours} ore`, { align: "center" });
      doc.moveDown();
      doc.fontSize(12).text(`Completato il: ${enrollment.completedAt ? new Date(enrollment.completedAt).toLocaleDateString("it-IT") : "N/D"}`, { align: "center" });
      doc.moveDown(3);
      doc.fontSize(10).text(`Attestato n. ${enrollmentId}`, { align: "center" });

      doc.end();
    } catch (error) {
      console.error("PDF generation error:", error);
      res.status(500).json({ error: "Failed to generate PDF" });
    }
  });

  app.get("/api/certificates/:enrollmentId/info", isAuthenticated, async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      const [cert] = await db.select().from(schema.certificates).where(eq(schema.certificates.enrollmentId, enrollmentId)).limit(1);
      res.json(cert || null);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch certificate info" });
    }
  });
}
