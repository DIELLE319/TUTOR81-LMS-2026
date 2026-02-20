import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db } from "../db";
import * as schema from "@shared/schema";
import { eq, desc, sql } from "drizzle-orm";
import { getAuthenticatedUserTutorId } from "./helpers";

export function registerCertificatesRoutes(app: Express) {
  // GET /api/certificates — lista certificati
  app.get("/api/certificates", isAuthenticated, async (req, res) => {
    try {
      const certificates = await db.select({
        id: schema.certificates.id,
        enrollmentId: schema.certificates.enrollmentId,
        certificateNumber: schema.certificates.certificateNumber,
        issuedAt: schema.certificates.issuedAt,
        pdfUrl: schema.certificates.pdfUrl,
      })
        .from(schema.certificates)
        .orderBy(desc(schema.certificates.issuedAt));

      res.json(certificates);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch certificates" });
    }
  });

  // GET /api/attestati — lista attestati (legacy + nuovi)
  app.get("/api/attestati", isAuthenticated, async (req, res) => {
    try {
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);

      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }

      const tutorIdFilter = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);

      let query = sql`
        SELECT 
          e.id,
          e.legacy_id,
          e.student_id,
          e.license_code,
          e.start_date,
          e.end_date,
          s.first_name as user_first_name,
          s.last_name as user_last_name,
          s.email as user_email,
          s.fiscal_code as user_fiscal_code,
          c.title as course_title,
          c.hours as course_hours,
          t.business_name as tutor_name,
          t.id as tutor_id
        FROM enrollments e
        LEFT JOIN students s ON e.student_id = s.id
        LEFT JOIN courses c ON e.course_id = c.id
        LEFT JOIN tutors t ON e.tutor_id = t.id
      `;

      if (tutorIdFilter) {
        query = sql`${query} WHERE t.id = ${tutorIdFilter}`;
      }

      query = sql`${query} ORDER BY e.end_date DESC NULLS LAST LIMIT 1000`;

      const attestati = await db.execute(query);
      res.json(attestati.rows);
    } catch (error) {
      console.error("Attestati error:", error);
      res.status(500).json({ error: "Failed to fetch attestati" });
    }
  });

  // GET /api/attestato/:legacyId/download — scarica attestato da FTP (legacy)
  app.get("/api/attestato/:legacyId/download", isAuthenticated, async (req, res) => {
    const ftp = require("basic-ftp");
    const client = new ftp.Client();
    const legacyId = req.params.legacyId;

    try {
      await client.access({
        host: "95.179.207.157",
        user: process.env.FTP_USERNAME,
        password: process.env.FTP_PASSWORD,
        secure: false,
      });

      const filename = `attestato_licenza_${legacyId}.pdf`;
      const remotePath = `/media/media/attestati/${filename}`;

      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `attachment; filename="${filename}"`);

      await client.downloadTo(res, remotePath);
    } catch (error) {
      console.error("FTP download error:", error);
      res.status(500).json({ error: "Failed to download attestato" });
    } finally {
      client.close();
    }
  });

  // GET /api/attestato/:enrollmentId/generate — genera PDF attestato
  app.get("/api/attestato/:enrollmentId/generate", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      if (isNaN(enrollmentId)) return res.status(400).json({ error: "Invalid enrollment ID" });

      const enrollmentData = await db.select({
        id: schema.enrollments.id,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        completedAt: schema.enrollments.completedAt,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        studentId: schema.students.id,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        studentFiscalCode: schema.students.fiscalCode,
        companyId: schema.companies.id,
        companyName: schema.companies.businessName,
        courseId: schema.courses.id,
        courseTitle: schema.courses.title,
        courseHours: schema.courses.hours,
        courseLawReference: schema.courses.lawReference,
        courseValidity: schema.courses.courseValidity,
        courseTargetAudience: schema.courses.targetAudience,
      })
        .from(schema.enrollments)
        .innerJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .innerJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.id, enrollmentId))
        .limit(1);

      if (enrollmentData.length === 0) {
        return res.status(404).json({ error: "Enrollment not found" });
      }

      const enrollment = enrollmentData[0];

      if (enrollment.status !== "completed" && (enrollment.progress ?? 0) < 100) {
        return res.status(400).json({ error: "Il corso non è ancora completato" });
      }

      const tutorData = await db.select().from(schema.tutors)
        .where(eq(schema.tutors.id, enrollment.companyId)).limit(1);
      const tutor = tutorData[0] || { businessName: "Tutor81", regionalAuthorization: "" };

      const courseModulesData = await db.select({
        id: schema.modules.id,
        title: schema.modules.title,
        duration: schema.modules.duration,
      })
        .from(schema.courseModules)
        .innerJoin(schema.modules, eq(schema.courseModules.moduleId, schema.modules.id))
        .where(eq(schema.courseModules.courseId, enrollment.courseId))
        .orderBy(schema.courseModules.position);

      const certificateNumber = `T81-${enrollment.id}-${new Date().getFullYear()}`;

      const existingCert = await db.select().from(schema.certificates)
        .where(eq(schema.certificates.enrollmentId, enrollmentId)).limit(1);

      if (existingCert.length === 0) {
        await db.insert(schema.certificates).values({
          enrollmentId,
          certificateNumber,
          issuedAt: new Date(),
        }).returning({ id: schema.certificates.id });
      }

      const PDFDocument = (await import("pdfkit")).default;
      const doc = new PDFDocument({
        size: "A4",
        margin: 50,
        info: {
          Title: "Attestato di Formazione",
          Author: "Tutor81 LMS",
          Subject: `Attestato corso: ${enrollment.courseTitle}`,
        },
      });

      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `inline; filename="attestato_${enrollment.licenseCode}.pdf"`);
      doc.pipe(res);

      // Header
      doc.fontSize(24).fillColor("#1a1a1a").text("tutor", 50, 50, { continued: true });
      doc.fillColor("#eab308").text("81");

      doc.moveDown(0.5);
      doc.fontSize(10).fillColor("#666666").text(`TRACCIATO N° ${certificateNumber}`, { align: "center" });

      doc.moveDown(1);
      doc.fontSize(16).fillColor("#1a1a1a").text("ATTESTATO DI AVVENUTA FORMAZIONE IN E-LEARNING", { align: "center" });

      doc.moveDown(1);
      doc.fontSize(10).fillColor("#333333").text(
        "L'infrastruttura tecnologica TUTOR81 LMS certifica il completamento del corso in e-learning da parte di:",
        { align: "center" }
      );

      // Student info box
      doc.moveDown(1.5);
      const boxY = doc.y;
      doc.rect(50, boxY, 495, 120).stroke("#cccccc");

      doc.fontSize(11).fillColor("#1a1a1a");
      const labelX = 60;
      const valueX = 200;
      let currentY = boxY + 15;

      doc.text("Nominativo:", labelX, currentY);
      doc.font("Helvetica-Bold").text(`${enrollment.studentFirstName || ""} ${enrollment.studentLastName || ""}`.toUpperCase(), valueX, currentY);
      doc.font("Helvetica");

      currentY += 22;
      doc.text("Codice Fiscale:", labelX, currentY);
      doc.font("Helvetica-Bold").text((enrollment.studentFiscalCode || "").toUpperCase(), valueX, currentY);
      doc.font("Helvetica");

      currentY += 22;
      doc.text("Organizzatore:", labelX, currentY);
      doc.text((enrollment.companyName || "").toUpperCase(), valueX, currentY);

      currentY += 22;
      doc.text("Ente Formatore:", labelX, currentY);
      doc.text((tutor.businessName || "Tutor81").toUpperCase(), valueX, currentY);

      // Course details
      doc.moveDown(4);
      doc.fontSize(14).fillColor("#1a1a1a").text("Scheda Progettuale del Corso", { align: "center" });

      doc.moveDown(1);
      doc.fontSize(10).fillColor("#333333");

      const courseInfoY = doc.y;
      doc.text("Titolo del corso:", labelX, courseInfoY);
      doc.font("Helvetica-Bold").text(enrollment.courseTitle || "", valueX, courseInfoY, { width: 340 });
      doc.font("Helvetica");

      doc.moveDown(1.5);
      doc.text("Durata:", labelX, doc.y);
      doc.text(`${enrollment.courseHours || 0} ore`, valueX, doc.y - 12);

      doc.moveDown(0.5);
      doc.text("Riferimento normativo:", labelX, doc.y);
      doc.text(enrollment.courseLawReference || "D.Lgs. 81/2008", valueX, doc.y - 12, { width: 340 });

      doc.moveDown(0.5);
      doc.text("Validità corso:", labelX, doc.y);
      doc.text(enrollment.courseValidity || "5 anni", valueX, doc.y - 12);

      // Modules
      if (courseModulesData.length > 0) {
        doc.moveDown(1.5);
        doc.fontSize(12).fillColor("#1a1a1a").text("Moduli completati:", labelX);
        doc.moveDown(0.5);
        doc.fontSize(10).fillColor("#333333");

        courseModulesData.forEach((mod, idx) => {
          doc.text(`${idx + 1}. ${mod.title} (${mod.duration || 0} min)`, labelX + 10);
        });
      }

      // Dates
      doc.moveDown(2);
      doc.fontSize(10);
      const startDateStr = enrollment.startDate ? new Date(enrollment.startDate).toLocaleDateString("it-IT") : "-";
      const endDateStr = enrollment.completedAt ? new Date(enrollment.completedAt).toLocaleDateString("it-IT") : "-";

      doc.text(`Data inizio: ${startDateStr}`, labelX);
      doc.text(`Data completamento: ${endDateStr}`, labelX);

      // Footer
      doc.moveDown(3);
      doc.fontSize(10).fillColor("#666666").text(
        `Documento generato automaticamente il ${new Date().toLocaleDateString("it-IT")}`,
        { align: "center" }
      );

      doc.moveDown(1);
      doc.text("Il presente attestato certifica l'avvenuta formazione secondo quanto previsto dalla normativa vigente.", { align: "center" });

      doc.end();
    } catch (error) {
      console.error("Certificate generation error:", error);
      res.status(500).json({ error: "Failed to generate certificate" });
    }
  });

  // GET /api/attestato/:enrollmentId — info certificato
  app.get("/api/attestato/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      if (isNaN(enrollmentId)) return res.status(400).json({ error: "Invalid enrollment ID" });

      const certData = await db.select().from(schema.certificates)
        .where(eq(schema.certificates.enrollmentId, enrollmentId)).limit(1);

      if (certData.length === 0) {
        return res.status(404).json({ error: "Certificate not found", exists: false });
      }

      res.json({ exists: true, certificate: certData[0] });
    } catch (error) {
      console.error("Certificate fetch error:", error);
      res.status(500).json({ error: "Failed to fetch certificate" });
    }
  });
}
