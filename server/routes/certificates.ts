import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";
import { generateAttestatoPdf, type AttData } from "../generateAttestatoPdf";
import { getCmsPool } from "../cmsDb";

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
      if (!student) return res.status(404).json({ error: "Student not found" });

      const [company] = await db.select().from(schema.companies).where(eq(schema.companies.id, student.companyId)).limit(1);
      const [tutor] = enrollment.tutorId
        ? await db.select().from(schema.tutors).where(eq(schema.tutors.id, enrollment.tutorId)).limit(1)
        : [null];

      // Fetch course details from CMS (has all fields)
      const cms = getCmsPool();
      const { rows: cmsRows } = await cms.query(`SELECT * FROM courses WHERE id = $1`, [enrollment.courseId]);
      const crs = cmsRows[0] || {} as any;

      // Fetch LO progress (tracciamento)
      const progressRows = await db.select().from(schema.enrollmentProgress)
        .where(eq(schema.enrollmentProgress.enrollmentId, enrollmentId));

      // Fetch quiz responses
      const quizRows = await db.select({
        id: schema.quizResponses.id,
        questionId: schema.quizResponses.questionId,
        isCorrect: schema.quizResponses.isCorrect,
        createdAt: schema.quizResponses.createdAt,
        questionText: schema.quizQuestions.questionText,
        answerId: schema.quizResponses.answerId,
      }).from(schema.quizResponses)
        .innerJoin(schema.quizQuestions, eq(schema.quizResponses.questionId, schema.quizQuestions.id))
        .where(eq(schema.quizResponses.enrollmentId, enrollmentId))
        .orderBy(schema.quizResponses.createdAt);

      // Fetch answer texts
      const answerTexts: Record<number, string> = {};
      for (const q of quizRows) {
        if (q.answerId) {
          const [ans] = await db.select().from(schema.quizAnswers).where(eq(schema.quizAnswers.id, q.answerId)).limit(1);
          if (ans) answerTexts[q.answerId] = ans.answerText;
        }
      }

      // Fetch course structure from CMS for LO list
      let loList: { id: number; title: string; duration: number }[] = [];
      try {
        const { rows: mods } = await cms.query(
          `SELECT cm.module_id FROM course_modules cm WHERE cm.course_id = $1 ORDER BY cm.position, cm.id`, [enrollment.courseId]
        );
        for (const mod of mods) {
          const { rows: lessons } = await cms.query(
            `SELECT ml.lesson_id FROM module_lessons ml WHERE ml.module_id = $1 ORDER BY ml.position, ml.id`, [mod.module_id]
          );
          for (const lesson of lessons) {
            const { rows: los } = await cms.query(
              `SELECT llo.learning_object_id, lo.title, lo.duration
               FROM lesson_learning_objects llo JOIN learning_objects lo ON lo.id = llo.learning_object_id
               WHERE llo.lesson_id = $1 ORDER BY llo.position, llo.id`, [lesson.lesson_id]
            );
            for (const lo of los) loList.push({ id: lo.learning_object_id, title: lo.title, duration: lo.duration || 0 });
          }
        }
      } catch (e) { console.error("CMS LO fetch error:", e); }

      // Determine ente vs tutor_vendor (same logic as PHP)
      const hasRegAuth = tutor?.hasRegionalAuth || !!tutor?.regionalAuthorization;
      let enteName = "", enteAddress = "", enteCity = "", enteProvince = "", enteTelephone = "", enteEmail = "", enteRegAuth = "";
      let tvName = "", tvAddress = "", tvCity = "", tvProvince = "", tvTelephone = "", tvEmail = "";

      if (hasRegAuth && tutor) {
        // Tutor IS the accredited ente
        enteName = tutor.businessName || "";
        enteAddress = tutor.address || "";
        enteCity = tutor.city || "";
        enteProvince = tutor.province || "";
        enteTelephone = tutor.phone || "";
        enteEmail = tutor.email || "";
        enteRegAuth = tutor.regionalAuthorization || "";
        // tutor_vendor empty
      } else {
        // Ente = Prometeo (hardcoded), tutor_vendor = tutor
        enteName = "PROMETEO S.R.L.";
        enteAddress = "Via G. Giusti n. 2";
        enteCity = "Campi Bisenzio";
        enteProvince = "FI";
        enteTelephone = "055 8954895";
        enteEmail = "info@prometeoformazione.it";
        enteRegAuth = "Decreto Dirigenziale n.10228 del 29/06/2016";
        if (tutor) {
          tvName = tutor.businessName || "";
          tvAddress = tutor.address || "";
          tvCity = tutor.city || "";
          tvProvince = tutor.province || "";
          tvTelephone = tutor.phone || "";
          tvEmail = tutor.email || "";
        }
      }

      // Determine trainer
      const trainer = tutor?.authorizedTrainer || enteName;

      // Dates
      const fmtDate = (d: any) => d ? new Date(d).toLocaleDateString("it-IT") : "";
      const startDate = fmtDate(enrollment.startDate);
      const endDate = fmtDate(enrollment.completedAt || enrollment.endDate);
      const printDate = new Date().toLocaleDateString("it-IT");

      // Course fields from CMS (Italian column names)
      const courseTitle = crs.title || "";
      const courseLawRef = crs.riferimento_normativo || crs.law_reference || "";
      const courseValidity = crs.validita || crs.course_validity || "";
      const courseDesc = crs.description || "";
      const courseTotalElearning = crs.durata_minima_elearning || crs.total_elearning || crs.durata_totale || "";
      const courseMaxExec = crs.tempo_massimo_conclusione || crs.max_execution_time || "";
      const courseProfessors = crs.relatori_docenti || crs.course_professors || "";
      const courseRequirements = crs.requisiti || crs.requirements || crs.prerequisites || "";
      const courseChecking = crs.profili_competenze || crs.checking || "";
      const coursePercentage = crs.soglia_superamento || crs.percentage_correct_answer_to_pass || crs.percentage_to_pass || "80";
      const courseDidactics = crs.verifica_apprendimento || crs.didactics || "";
      const courseTargetAudience = crs.rivolto_a || crs.target_audience || "";
      const courseProgram = crs.programma_corso || crs.course_program || crs.course_contents || "";

      // Is DL81 course?
      const cat = (crs.sottocategoria || crs.subcategory || crs.categoria || "").toUpperCase();
      const isDL81 = courseLawRef.includes("81") || cat.includes("LAVORATOR") || cat.includes("PREPOSTO") || cat.includes("DIRIGENT") || cat.includes("RLS") || cat.includes("DATORE");

      // Build program with LO list
      let programHtml = courseProgram ? courseProgram.replace(/\n/g, "<br/>") : "";
      if (loList.length > 0) {
        programHtml += "<br/>";
        for (const lo of loList) {
          programHtml += `° (${lo.id}) ${lo.title} (${lo.duration} min)<br/>`;
        }
      }

      // Build events
      const events: AttData["events"] = [];
      for (const lo of loList) {
        const prog = progressRows.find(p => p.learningObjectId === lo.id);
        if (!prog || !prog.completed) continue;
        const completedDate = prog.completedAt ? new Date(prog.completedAt) : null;
        const startTime = completedDate ? new Date(completedDate.getTime() - (prog.watchedSeconds || 0) * 1000) : null;
        const fmtDT = (d: Date | null) => d ? d.toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "";
        events.push({ start: fmtDT(startTime), title: lo.title, end: fmtDT(completedDate) });
      }

      // Build questions
      const questions: AttData["questions"] = [];
      for (const q of quizRows) {
        const dateStr = q.createdAt ? new Date(q.createdAt).toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "";
        const ansText = q.answerId ? (answerTexts[q.answerId] || "") : "";
        questions.push({
          dateTime: dateStr,
          text: q.questionText || "",
          answer: ansText,
          isCorrect: q.isCorrect ?? false,
          hasAnswer: !!q.answerId,
        });
      }

      // Logo files - tutor81LogoFile depends on certificate type
      // certificateType: 1 = logo azienda + Prometeo, 2 = solo azienda, 3 = Prometeo + firma
      const certType = tutor?.certificateType ?? 1;
      let tutorLogoFile = "2"; // Prometeo logo (header left)
      let tutor81LogoFile = tutor?.logoUrl ? tutor.logoUrl.replace(/\.(png|jpg|jpeg)$/i, "") : "";
      let enteLogoFile = "1333"; // Prometeo ente logo
      let firmaEnteFile = "firma.1333";
      let firmaTutorFile = "";

      // If tutor has own logo uploaded, use it
      if (tutor?.logoUrl) {
        const logoName = tutor.logoUrl.replace(/\.(png|jpg|jpeg)$/i, "");
        if (certType === 1) {
          // Logo azienda + Logo Prometeo
          tutorLogoFile = "2";
          tutor81LogoFile = logoName;
          enteLogoFile = "1333";
          firmaEnteFile = "firma.1333";
        } else if (certType === 2) {
          // Solo logo azienda
          tutorLogoFile = logoName;
          tutor81LogoFile = logoName;
          enteLogoFile = logoName;
          firmaEnteFile = "";
        } else if (certType === 3) {
          // Prometeo + firma
          tutorLogoFile = "2";
          tutor81LogoFile = "1333";
          enteLogoFile = "1333";
          firmaEnteFile = "firma.1333";
        }
      }

      const attData: AttData = {
        certNumber: enrollmentId,
        abrvCompany: (tutor?.businessName || "").substring(0, 10).toUpperCase(),
        learnerName: `${(student.lastName || "").toUpperCase()} ${(student.firstName || "").toUpperCase()}`.trim(),
        learnerTaxcode: (student.fiscalCode || "").toUpperCase(),
        learnerBusinessFunction: "LAVORATORE",
        companyName: (company?.businessName || "").toUpperCase(),
        companyAteco: tutor?.atecoCode || "",
        trainer,
        courseTitle,
        courseDescription: courseDesc,
        courseTargetAudience,
        courseLawReference: courseLawRef,
        courseValidity,
        courseTotalElearning,
        courseMaxExecutionTime: courseMaxExec,
        courseProfessors,
        courseRequirements,
        courseChecking,
        coursePercentageToPass: String(coursePercentage),
        courseDidactics,
        courseProgram: programHtml,
        enteName, enteAddress, enteCity, enteProvince, enteTelephone, enteEmail, enteRegionalAuth: enteRegAuth,
        tvName, tvAddress, tvCity, tvProvince, tvTelephone, tvEmail,
        startCourseDate: startDate,
        endCourseDate: endDate,
        printDate,
        isDL81,
        tutorLogoFile, tutor81LogoFile, enteLogoFile, firmaEnteFile, firmaTutorFile,
        events,
        questions,
      };

      const pdfBuffer = await generateAttestatoPdf(attData);

      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `attachment; filename="attestato_${enrollmentId}.pdf"`);
      res.setHeader("Content-Length", pdfBuffer.length);
      res.end(pdfBuffer);
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
