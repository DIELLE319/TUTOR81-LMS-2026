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

      const [company] = await db.select().from(schema.companies).where(eq(schema.companies.id, student.companyId)).limit(1);
      const [tutor] = enrollment.tutorId
        ? await db.select().from(schema.tutors).where(eq(schema.tutors.id, enrollment.tutorId)).limit(1)
        : [null];

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

      // Fetch answer texts for quiz responses
      const answerTexts: Record<number, string> = {};
      for (const q of quizRows) {
        if (q.answerId) {
          const [ans] = await db.select().from(schema.quizAnswers).where(eq(schema.quizAnswers.id, q.answerId)).limit(1);
          if (ans) answerTexts[q.answerId] = ans.answerText;
        }
      }

      // Fetch course structure from CMS for LO list
      const { getCmsPool } = await import("../cmsDb");
      const cms = getCmsPool();
      let loList: { id: number; title: string; duration: number }[] = [];
      try {
        const { rows: mods } = await cms.query(
          `SELECT cm.module_id FROM course_modules cm WHERE cm.course_id = $1 ORDER BY cm.position, cm.id`, [course.id]
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

      const certNumber = enrollmentId;
      const tracciato = `${certNumber} T81`;
      const courseTitle = course.title || "";
      const studentName = `${(student.firstName || "").toUpperCase()} ${(student.lastName || "").toUpperCase()}`.trim();
      const fiscalCode = (student.fiscalCode || "").toUpperCase();
      const companyName = company?.businessName || "";
      const tutorName = tutor?.businessName || companyName;
      const tutorAddress = tutor ? `${tutor.address || ""}, ${tutor.city || ""}` : "";
      const courseHours = course.hours || 0;
      const maxExecutionDays = (course as any).maxExecutionTime || 30;
      const percentageToPass = (course as any).percentageToPass || 80;
      const startDate = enrollment.startDate ? new Date(enrollment.startDate).toLocaleDateString("it-IT") : "";
      const endDate = enrollment.completedAt ? new Date(enrollment.completedAt).toLocaleDateString("it-IT") : (enrollment.endDate ? new Date(enrollment.endDate).toLocaleDateString("it-IT") : "");

      // Build description with LO list
      const loDescParts = loList.map(lo => `(${lo.id})${lo.title}(${lo.duration} min)`);
      const courseDesc = `${course.description || ""}. ${loDescParts.join(" ")}`;

      // Build programma list
      const programmaItems = loList.map(lo => `(${lo.id})${lo.title}(${lo.duration} min)`);

      const totalPages = 5;
      const PDFDocument = (await import("pdfkit")).default;
      const doc = new PDFDocument({ size: "A4", margins: { top: 40, bottom: 40, left: 50, right: 50 } });
      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `attachment; filename="attestato_${certNumber}.pdf"`);
      doc.pipe(res);

      const W = 495; // usable width
      const LM = 50; // left margin

      // === HELPER FUNCTIONS ===
      function header(pageNum: number) {
        doc.fontSize(10).font("Helvetica-Bold").fillColor("#888")
          .text("tutor", LM, 15, { continued: true }).fillColor("#000").text("81");
        doc.fontSize(8).font("Helvetica").fillColor("#000")
          .text(`TRACCIATO N°  ${tracciato}`, LM + 150, 18, { width: 250, align: "center" });
        doc.moveTo(LM, 38).lineTo(LM + W, 38).lineWidth(0.5).stroke("#000");
      }
      function footer(pageNum: number) {
        doc.fontSize(8).font("Helvetica").fillColor("#000")
          .text(`Pagina ${pageNum}/${totalPages}`, LM, 780, { width: W, align: "center" });
      }
      function drawTableRow(y: number, cols: { x: number; w: number; text: string; bold?: boolean; color?: string }[], h: number) {
        cols.forEach(col => {
          doc.rect(col.x, y, col.w, h).lineWidth(0.5).stroke("#000");
          doc.font(col.bold ? "Helvetica-Bold" : "Helvetica")
            .fontSize(8).fillColor(col.color || "#000")
            .text(col.text, col.x + 4, y + 4, { width: col.w - 8, height: h - 8 });
        });
      }
      function fieldRow(label: string, value: string, y: number, labelW = 130): number {
        doc.font("Helvetica-Bold").fontSize(8).text(label, LM, y, { width: labelW, align: "right" });
        const valH = doc.heightOfString(value, { width: W - labelW - 15, fontSize: 8 });
        doc.font("Helvetica").fontSize(8).text(value, LM + labelW + 10, y, { width: W - labelW - 15 });
        return Math.max(14, valH + 6);
      }

      // =====================================================
      // PAGE 1: TRACCIATO + SCHEDA PROGETTUALE
      // =====================================================
      header(1);
      let y = 55;
      doc.font("Helvetica-Bold").fontSize(12).text(`TRACCIATO N° ${tracciato}`, LM, y, { width: W, align: "center" });
      y += 16;
      doc.fontSize(10).text("DI AVVENUTA FORMAZIONE IN ELEARNING", LM, y, { width: W, align: "center" });
      y += 20;
      doc.font("Helvetica").fontSize(7.5)
        .text("L'infrastruttura tecnologica TUTOR81 LMS in conformità all'Accordo tra Stato e Regioni del 7 luglio 2016 certifica il completamento del corso in e-learning da parte di:", LM, y, { width: W });
      y += 25;

      // Identity table
      const tableData = [
        ["Nominativo", studentName],
        ["Codice fiscale", fiscalCode],
        ["In qualità di", "LAVORATORE"],
        ["Organizzatore del corso", companyName.toUpperCase()],
        ["Soggetto formatore\nautorizzato", tutorAddress || tutorName],
      ];
      const col1W = 150;
      const col2W = W - col1W;
      for (const [label, val] of tableData) {
        const h = Math.max(18, doc.heightOfString(val, { width: col2W - 8, fontSize: 8 }) + 8);
        drawTableRow(y, [
          { x: LM, w: col1W, text: label },
          { x: LM + col1W, w: col2W, text: val },
        ], h);
        y += h;
      }

      y += 25;
      doc.font("Helvetica-Bold").fontSize(12).text("Scheda progettuale del corso in e-learning", LM, y, { width: W, align: "center" });
      y += 25;

      const fields1: [string, string][] = [
        ["Titolo del corso:", courseTitle],
        ["Rivolto a:", ""],
        ["Riferimento normativo:", "Decreto 81 art. 37 - Accordo Stato-Regioni del 17/04/2025"],
        ["Validità corso:", "Quinquennale"],
        ["Descrizione del corso:", courseDesc],
        [`Durata del corso in e-\nlearning:`, `${courseHours}`],
        [`Tempo massimo per la\nconclusione:`, `${maxExecutionDays}`],
        ["Relatori e Docenti:", 'I relatori/docenti che hanno contribuito alla redazione dei contenuti di ciascuna unità didattica sono in possesso dei requisiti previsti dal decreto interministeriale del 6 marzo 2013 "Criteri di qualificazione della figura del formatore per la salute e sicurezza nei luoghi di lavoro".'],
        ["Requisiti minimi per\naccedere al corso:", course.prerequisites || "conclusione del corso di formazione generale"],
      ];
      for (const [label, val] of fields1) {
        const h = fieldRow(label, val, y, 130);
        y += h;
        if (y > 730) { footer(1); doc.addPage(); header(2); y = 55; }
      }
      footer(1);

      // =====================================================
      // PAGE 2: VERIFICA + PIATTAFORMA + PROGRAMMA
      // =====================================================
      doc.addPage();
      header(2);
      y = 55;

      doc.font("Helvetica-Bold").fontSize(9).text("Verifica di apprendimento:", LM, y, { width: 140 });
      const verificaText = `La verifica di apprendimento principale privilegiata nell'ambiente Tutor81 è la verifica in in itinere. Si tratta di test a tempo trasmessi frequentemente e con lo scopo non solo di controllare la presenza del partecipante ma di stimolarne l'attenzione. Il corsista riceve immediato riscontro alla risposta rilasciata. Ogni quesito viene tracciato dal sistema e riportato nell'attestato finale. I test sono trasmessi in modalità random, ciò significa che per la stessa domanda esistono varie alternative. In caso il risultato finale dei test, sia inferiore alla soglia minima prevista dei test corretti, l'attestato non viene generato dal sistema. Il soggetto formatore valuterà la modalità che riterrà più idonee per approfondire gli errori e rivalutarne l'apprendimento.`;
      doc.font("Helvetica").fontSize(8).text(verificaText, LM + 145, y, { width: W - 145 });
      y += doc.heightOfString(verificaText, { width: W - 145, fontSize: 8 }) + 15;

      y += fieldRow("Soglia minima per il\nsuperamento del corso:", `${percentageToPass} %`, y, 140);
      y += 5;

      const piattaformaText = `TUTOR81 LMS è una piattaforma LMS con sistema di tracciamento proprietario, conforme alla normativa attualmente in vigore (Accordo Stato Regioni 7 luglio 2016) in tema di formazione e-learning riguardante la tutela della sicurezza e della salute dei lavoratori. Ogni corso con Tutor81 è monitorato rispettando i requisiti previsti dall'Allegato 2 Accordo Stato Regioni 07.07.2016 al termine di ogni corso è quindi possibile certificare e documentare quanto segue: • Lo svolgimento e il completamento delle attività didattiche di ciascun utente • Le modalità e il superamento delle valutazioni di apprendimento • La partecipazione attiva del discente; • La tracciabilità di ogni attività svolta durante il collegamento al sistema e la durata; • La tracciabilità dell'utilizzo anche delle singole unità didattiche. la regolarità e la progressività di utilizzo del sistema da parte dell'utente;`;
      y += fieldRow("Caratteristiche tecniche\ndella piattaforma:", piattaformaText, y, 140);
      y += 10;

      doc.font("Helvetica-Bold").fontSize(9).text("Programma del corso:", LM, y, { width: 140 });
      doc.font("Helvetica").fontSize(8).text(course.description || "", LM + 145, y, { width: W - 145 });
      y += doc.heightOfString(course.description || " ", { width: W - 145, fontSize: 8 }) + 10;

      for (const item of programmaItems) {
        if (y > 730) { footer(2); doc.addPage(); header(2); y = 55; }
        doc.font("Helvetica").fontSize(7.5).text(`°  ${item}`, LM + 160, y, { width: W - 170 });
        y += 12;
      }
      footer(2);

      // =====================================================
      // PAGE 3: TRACCIAMENTO DEL PERCORSO FORMATIVO
      // =====================================================
      doc.addPage();
      header(3);
      y = 55;
      doc.font("Helvetica-Bold").fontSize(14).text("Tracciamento del percorso formativo", LM, y, { width: W, align: "center" });
      y += 30;

      // Table header
      const tc1 = 130, tc2 = 250, tc3 = W - tc1 - tc2;
      drawTableRow(y, [
        { x: LM, w: tc1, text: "Collegamento", bold: true },
        { x: LM + tc1, w: tc2, text: "Materiale didattico svolto", bold: true },
        { x: LM + tc1 + tc2, w: tc3, text: "Termine", bold: true },
      ], 16);
      y += 16;

      // Map progress to LO names
      for (const lo of loList) {
        const prog = progressRows.find(p => p.learningObjectId === lo.id);
        if (!prog || !prog.completed) continue;
        const completedDate = prog.completedAt ? new Date(prog.completedAt) : null;
        const startTime = completedDate ? new Date(completedDate.getTime() - (prog.watchedSeconds || 0) * 1000) : null;
        const collegamento = startTime ? startTime.toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "";
        const termine = completedDate ? completedDate.toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "";

        if (y > 730) { doc.addPage(); header(3); y = 55; }
        const rh = Math.max(14, doc.heightOfString(lo.title, { width: tc2 - 8, fontSize: 7.5 }) + 6);
        doc.font("Helvetica").fontSize(7.5);
        doc.rect(LM, y, tc1, rh).stroke("#000");
        doc.text(collegamento, LM + 4, y + 3, { width: tc1 - 8 });
        doc.rect(LM + tc1, y, tc2, rh).stroke("#000");
        doc.text(lo.title, LM + tc1 + 4, y + 3, { width: tc2 - 8 });
        doc.rect(LM + tc1 + tc2, y, tc3, rh).stroke("#000");
        doc.text(termine, LM + tc1 + tc2 + 4, y + 3, { width: tc3 - 8 });
        y += rh;
      }
      footer(3);

      // =====================================================
      // PAGE 4: VALUTAZIONE DELL'APPRENDIMENTO
      // =====================================================
      doc.addPage();
      header(4);
      y = 55;
      doc.font("Helvetica-Bold").fontSize(14).text("Valutazione dell'apprendimento", LM, y, { width: W, align: "center" });
      y += 30;

      const qc1 = 100, qc2 = 170, qc3 = 140, qc4 = W - qc1 - qc2 - qc3;
      drawTableRow(y, [
        { x: LM, w: qc1, text: "Data/ora", bold: true },
        { x: LM + qc1, w: qc2, text: "Quesito", bold: true },
        { x: LM + qc1 + qc2, w: qc3, text: "Risposta corsista", bold: true },
        { x: LM + qc1 + qc2 + qc3, w: qc4, text: "Valutazione", bold: true },
      ], 16);
      y += 16;

      for (const q of quizRows) {
        if (y > 700) { doc.addPage(); header(4); y = 55; }
        const dateStr = q.createdAt ? new Date(q.createdAt).toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "";
        const answerText = q.answerId ? (answerTexts[q.answerId] || "") : "";
        const valutation = q.isCorrect ? "CORRETTA" : "SBAGLIATA";
        const valColor = q.isCorrect ? "#16a34a" : "#dc2626";

        const rh = Math.max(16, doc.heightOfString(q.questionText || "", { width: qc2 - 8, fontSize: 7 }) + 6);
        doc.font("Helvetica").fontSize(7);
        doc.rect(LM, y, qc1, rh).stroke("#000");
        doc.text(dateStr, LM + 3, y + 3, { width: qc1 - 6 });
        doc.rect(LM + qc1, y, qc2, rh).stroke("#000");
        doc.text(q.questionText || "", LM + qc1 + 3, y + 3, { width: qc2 - 6 });
        doc.rect(LM + qc1 + qc2, y, qc3, rh).stroke("#000");
        doc.text(answerText, LM + qc1 + qc2 + 3, y + 3, { width: qc3 - 6 });
        doc.rect(LM + qc1 + qc2 + qc3, y, qc4, rh).stroke("#000");
        doc.font("Helvetica-Bold").fillColor(valColor).text(valutation, LM + qc1 + qc2 + qc3 + 3, y + 3, { width: qc4 - 6 });
        doc.fillColor("#000");
        y += rh;
      }

      y += 25;
      doc.font("Helvetica-Bold").fontSize(9).text(`Data: ${endDate}`, LM, y);
      y += 20;
      doc.rect(LM + 50, y, 250, 50).lineWidth(0.5).stroke("#000");
      doc.font("Helvetica-Bold").fontSize(9).text("Firma del corsista", LM + 55, y + 5, { width: 240, align: "center" });
      footer(4);

      // =====================================================
      // PAGE 5: ATTESTATO DI FREQUENZA
      // =====================================================
      doc.addPage();
      y = 60;

      // Logo top right
      doc.fontSize(12).font("Helvetica-Bold").fillColor("#888")
        .text("tutor", LM + W - 60, 20, { continued: true }).fillColor("#000").text("81");

      doc.font("Helvetica-Bold").fontSize(16).text("ATTESTATO DI FREQUENZA", LM, y, { width: W, align: "center" });
      y += 22;
      doc.fontSize(11).text(`corso e-learning N° ${certNumber}`, LM, y, { width: W, align: "center" });
      y += 16;
      doc.font("Helvetica").fontSize(8)
        .text("(ai sensi dell'art. 37 del decreto legislativo 9 aprile 2008 n. 81)", LM, y, { width: W, align: "center" });
      y += 12;
      doc.text("Il documento è valido su tutto il territorio nazionale", LM, y, { width: W, align: "center" });
      y += 25;

      // Horizontal line
      doc.moveTo(LM, y).lineTo(LM + W, y).lineWidth(0.5).stroke("#000");
      y += 15;

      doc.font("Helvetica").fontSize(9).text("si attesta che:", LM, y);
      doc.font("Helvetica-Bold").fontSize(11).text(studentName, LM + 130, y);
      y += 16;
      doc.font("Helvetica").fontSize(9).text("Codice fiscale:", LM, y);
      doc.font("Helvetica-Bold").fontSize(11).text(fiscalCode, LM + 130, y);
      y += 25;

      doc.font("Helvetica").fontSize(9)
        .text("ha superato il corso di formazione e ha superato la prova finale di apprendimento del corso:", LM, y, { width: W, align: "center" });
      y += 25;

      // Course title bar
      doc.rect(LM, y, W, 28).fill("#e5e7eb");
      doc.font("Helvetica-Bold").fontSize(10).fillColor("#000")
        .text(courseTitle.toUpperCase(), LM + 10, y + 8, { width: W - 20, align: "center" });
      y += 40;

      // Details
      const detailFields: [string, string][] = [
        ["Percent. test validazione corso:", `${percentageToPass}%`],
        ["Riferimento normativo:", "Decreto 81 art. 37 - Accordo Stato-Regioni del 17/04/2025"],
        ["Durata del corso in elearning:", `${courseHours} ore`],
        ["Periodo di svolgimento:", `dal ${startDate} a ${endDate}`],
        ["Organizzato da:", tutorName.toUpperCase()],
        ["Settore Ateco:", (tutor as any)?.atecoCode || ""],
      ];
      for (const [label, val] of detailFields) {
        doc.font("Helvetica").fontSize(8).text(label, LM, y);
        doc.font("Helvetica-Bold").fontSize(8).text(val, LM + 160, y);
        y += 13;
      }
      y += 15;

      // Ente di formazione + Formatore
      doc.moveTo(LM, y).lineTo(LM + W, y).lineWidth(0.3).stroke("#999");
      y += 10;

      doc.font("Helvetica").fontSize(8).text("Ente di formazione accreditato", LM, y, { width: W / 2, underline: true, align: "center" });
      doc.text("Formatore", LM + W / 2, y, { width: W / 2, underline: true, align: "center" });
      y += 15;

      doc.font("Helvetica-Bold").fontSize(8).text(tutorName.toUpperCase(), LM, y, { width: W / 2 });
      doc.font("Helvetica").fontSize(8).text("il responsabile del soggetto", LM + W / 2 + 30, y, { width: W / 2 - 30, align: "center" });
      y += 11;
      if (tutorAddress) doc.font("Helvetica").fontSize(7.5).text(tutorAddress, LM, y, { width: W / 2 });
      doc.text("formatore", LM + W / 2 + 30, y, { width: W / 2 - 30, align: "center" });
      y += 50;

      // Footer legal text
      doc.moveTo(LM, y).lineTo(LM + W, y).lineWidth(0.3).stroke("#999");
      y += 8;
      doc.font("Helvetica").fontSize(7)
        .text("L'attestato rilasciato ai sensi dell'Accordo del 17 aprile 2025 sancito in conferenza permanente per i rapporti tra lo Stato, le Regioni e le Province Autonome di Trento e Bolzano è valido su tutto il territorio nazionale", LM, y, { width: W, align: "center" });

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
