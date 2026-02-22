import type { Express } from "express";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, sql } from "drizzle-orm";
import pg from "pg";

// CMS database connection (server 107 - where course content lives)
let cmsPool: pg.Pool | null = null;
function getCmsPool(): pg.Pool {
  if (!cmsPool) {
    cmsPool = new pg.Pool({
      host: "127.0.0.1",
      port: 5432,
      user: "tutor81",
      password: "tutor81pass",
      database: "tutor81",
      max: 3,
      connectionTimeoutMillis: 5000,
    });
  }
  return cmsPool;
}

const CF_MONTH_MAP: Record<string, number> = { A: 1, B: 2, C: 3, D: 4, E: 5, H: 6, L: 7, M: 8, P: 9, R: 10, S: 11, T: 12 };

const MONTH_NAMES = ["", "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno", "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"];

function parseCF(cf: string) {
  if (!cf || cf.length < 16) return null;
  const upper = cf.toUpperCase();
  const yearPart = parseInt(upper.substring(6, 8), 10);
  const monthLetter = upper.charAt(8);
  const dayPart = parseInt(upper.substring(9, 11), 10);
  const month = CF_MONTH_MAP[monthLetter];
  if (!month) return null;
  const isFemale = dayPart > 40;
  const day = isFemale ? dayPart - 40 : dayPart;
  const year = yearPart > 30 ? 1900 + yearPart : 2000 + yearPart;
  const gender = isFemale ? "F" : "M";
  return { year, month, day, gender };
}

type QuestionType = "birth_day" | "birth_month" | "birth_year" | "gender";

interface CfQuestion {
  id: QuestionType;
  label: string;
  type: "select";
  options: { value: string; label: string }[];
}

function generateRandomQuestions(cfData: { year: number; month: number; day: number; gender: string }): CfQuestion[] {
  const pool: CfQuestion[] = [
    {
      id: "birth_day",
      label: "In che giorno sei nato/a?",
      type: "select",
      options: Array.from({ length: 31 }, (_, i) => ({ value: String(i + 1), label: String(i + 1) })),
    },
    {
      id: "birth_month",
      label: "In che mese sei nato/a?",
      type: "select",
      options: MONTH_NAMES.slice(1).map((m, i) => ({ value: String(i + 1), label: m })),
    },
    {
      id: "birth_year",
      label: "In che anno sei nato/a?",
      type: "select",
      options: Array.from({ length: 71 }, (_, i) => ({ value: String(2010 - i), label: String(2010 - i) })),
    },
    {
      id: "gender",
      label: "Qual è il tuo sesso?",
      type: "select",
      options: [{ value: "M", label: "Maschio" }, { value: "F", label: "Femmina" }],
    },
  ];
  // Shuffle and pick 2
  for (let i = pool.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [pool[i], pool[j]] = [pool[j], pool[i]];
  }
  return pool.slice(0, 2);
}

function getExpectedAnswer(cfData: { year: number; month: number; day: number; gender: string }, questionId: QuestionType): string {
  switch (questionId) {
    case "birth_day": return String(cfData.day);
    case "birth_month": return String(cfData.month);
    case "birth_year": return String(cfData.year);
    case "gender": return cfData.gender;
  }
}

export function registerPlayerRoutes(app: Express) {

  // ==========================================
  // STANDALONE PLAYER LOGIN (no React needed)
  // ==========================================
  app.get("/player-login", (_req, res) => {
    res.set("Content-Type", "text/html; charset=utf-8").send(`<!DOCTYPE html>
<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accedi al Corso — Tutor81</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#030712;font-family:system-ui,-apple-system,sans-serif;color:#e2e8f0}
.wrap{width:min(420px,calc(100vw - 32px))}
.card{background:#111827;border-radius:24px;padding:40px;box-shadow:0 25px 80px rgba(0,0,0,.5)}
.logo{width:56px;height:56px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#0f172a;margin:0 auto 16px}
h1{font-size:20px;text-align:center;color:#fff;font-weight:700}
.sub{text-align:center;color:#64748b;font-size:12px;margin-top:4px;margin-bottom:28px;letter-spacing:2px;text-transform:uppercase;font-weight:600}
label{display:block;font-size:12px;margin-bottom:6px;color:#94a3b8;font-weight:600;letter-spacing:.5px;text-transform:uppercase}
input{width:100%;padding:12px 14px;border:2px solid #1e293b;border-radius:12px;background:#0f172a;color:#f1f5f9;font-size:15px;margin-bottom:18px;outline:none;transition:border-color .2s}
input:focus{border-color:#fbbf24}
input::placeholder{color:#475569}
button{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0f172a;font-size:15px;font-weight:800;cursor:pointer;transition:transform .15s}
button:hover{transform:translateY(-1px)}
button:disabled{opacity:.6;cursor:wait}
.error{background:rgba(127,29,29,.6);color:#fca5a5;padding:12px;border-radius:10px;margin-bottom:16px;font-size:13px;display:none;border:1px solid rgba(239,68,68,.2)}
.success{background:rgba(6,78,59,.6);color:#6ee7b7;padding:12px;border-radius:10px;margin-bottom:16px;font-size:13px;display:none;border:1px solid rgba(16,185,129,.2)}
.footer{text-align:center;margin-top:24px;font-size:12px;color:#475569}
.footer a{color:#fbbf24;text-decoration:none}
</style></head><body>
<div class="wrap"><div class="card">
<div class="logo">T</div>
<h1>TUTOR 81</h1>
<div class="sub">Accedi al Corso</div>
<div class="error" id="err"></div>
<div class="success" id="ok"></div>
<form id="f">
<label>Codice Licenza</label>
<input type="text" id="license" required placeholder="es: DEMO2026" style="text-transform:uppercase" autofocus>
<label>Codice Fiscale</label>
<input type="text" id="cf" required placeholder="es: RSSMRA80A01H501U" style="text-transform:uppercase">
<button type="submit" id="btn">Avvia Corso</button>
</form>
<div class="footer">Problemi? <a href="mailto:assistenza@tutor81.com">Contatta l'assistenza</a></div>
</div></div>
<script>
document.getElementById('f').onsubmit=async e=>{
  e.preventDefault();
  const err=document.getElementById('err'),ok=document.getElementById('ok'),btn=document.getElementById('btn');
  err.style.display='none'; ok.style.display='none';
  btn.textContent='Verifica in corso...'; btn.disabled=true;
  const license=document.getElementById('license').value.trim().toUpperCase();
  const cf=document.getElementById('cf').value.trim().toUpperCase();
  try{
    const r=await fetch('/api/player/login-simple',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({licenseCode:license,fiscalCode:cf})});
    const d=await r.json();
    if(r.ok&&d.success){
      ok.textContent='Accesso verificato! Caricamento corso...';
      ok.style.display='block';
      localStorage.setItem('playerUser',JSON.stringify(d.user));
      localStorage.setItem('playerEnrollment',JSON.stringify(d.enrollment));
      if(d.tutor) localStorage.setItem('playerTutor',JSON.stringify(d.tutor));
      setTimeout(()=>{window.location.href='/player/course/'+d.enrollment.courseId;},500);
    }else{
      err.textContent=d.error||'Credenziali non valide';
      err.style.display='block';
      btn.textContent='Avvia Corso'; btn.disabled=false;
    }
  }catch(x){
    err.textContent='Errore di connessione';
    err.style.display='block';
    btn.textContent='Avvia Corso'; btn.disabled=false;
  }
};
</script></body></html>`);
  });

  // ==========================================
  // STANDALONE COURSE PLAYER (no React needed)
  // ==========================================
  app.get("/player/course/:id", (_req, res) => {
    const fs = require("fs");
    const path = require("path");
    const candidates = [
      path.join(__dirname, "..", "player-v3.html"),
      path.join(process.cwd(), "player-v3.html"),
    ];
    for (const p of candidates) {
      if (fs.existsSync(p)) {
        return res.set("Content-Type", "text/html; charset=utf-8").sendFile(p);
      }
    }
    res.status(500).send("Player page not found");
  });

  // Simple login API for standalone player
  app.post("/api/player/login-simple", async (req, res) => {
    try {
      const { licenseCode, fiscalCode } = req.body;
      if (!licenseCode || !fiscalCode) return res.status(400).json({ error: "Codice licenza e codice fiscale obbligatori" });

      // Find enrollment by license code
      const enrollmentResults = await db.select({
        id: schema.enrollments.id,
        studentId: schema.enrollments.studentId,
        courseId: schema.enrollments.courseId,
        courseTitle: schema.courses.title,
        licenseCode: schema.enrollments.licenseCode,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
      }).from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.licenseCode, licenseCode.toUpperCase()))
        .limit(1);

      if (enrollmentResults.length === 0) return res.status(404).json({ error: "Codice licenza non trovato." });
      const enrollment = enrollmentResults[0];

      // Verify student fiscal code
      const [student] = await db.select().from(schema.students).where(eq(schema.students.id, enrollment.studentId));
      if (!student) return res.status(404).json({ error: "Studente non trovato." });
      if (!student.fiscalCode || student.fiscalCode.toUpperCase() !== fiscalCode.toUpperCase()) {
        return res.status(401).json({ error: "Codice fiscale non corrispondente." });
      }

      // Update last access
      await db.update(schema.enrollments).set({ lastAccessAt: new Date() }).where(eq(schema.enrollments.id, enrollment.id));

      // Get tutor (ente formativo) info
      const pool = getCmsPool();
      const { rows: tutorRows } = await pool.query(
        `SELECT t.business_name, t.address, t.city, t.cap, t.province, t.contact_person, t.phone, t.email
         FROM tutors t WHERE t.id = (SELECT tutor_id FROM enrollments WHERE id = $1)`, [enrollment.id]);
      const tutor = tutorRows[0] || {};

      // Get company (azienda) info
      const { rows: companyRows } = await pool.query(
        `SELECT c.business_name FROM companies c WHERE c.id = (SELECT company_id FROM students WHERE id = $1)`, [student.id]);
      const company = companyRows[0] || {};

      res.json({
        success: true,
        user: { id: student.id, firstName: student.firstName, lastName: student.lastName, fiscalCode: student.fiscalCode, company: company.business_name || '' },
        enrollment: { id: enrollment.id, courseId: enrollment.courseId, courseName: enrollment.courseTitle, licenseCode: enrollment.licenseCode, progress: enrollment.progress || 0, startDate: enrollment.startDate, endDate: enrollment.endDate },
        tutor: { name: tutor.business_name || '', contact: tutor.contact_person || '', address: tutor.address || '', city: tutor.city || '', cap: tutor.cap || '', province: tutor.province || '', phone: tutor.phone || '', email: tutor.email || '' },
      });
    } catch (error) {
      console.error("Simple login error:", error);
      res.status(500).json({ error: "Errore durante l'accesso" });
    }
  });

  // CSV export of learning objects from CMS
  app.get("/api/cms/learning-objects/csv", async (req, res) => {
    try {
      const cms = getCmsPool();
      const { rows } = await cms.query(
        `SELECT lo.id, lo.ovh_id, lo.title, lo.object_type, lo.jwplayer_code, lo.video_filename, 
                lo.slide_filename, lo.document_filename, lo.web_filename, lo.duration,
                lo.percentage_to_pass, lo.suspended, lo.status, lo.quality, lo.topic, lo.notes,
                lo.needs_review, lo.review_notes, lo.review_done, lo.description, lo.language,
                lo.level, lo.is_custom, lo.created_at,
                (SELECT COUNT(*) FROM lesson_slide_questions lsq WHERE lsq.learning_object_id = lo.id) as num_domande,
                (SELECT COUNT(DISTINCT cm.course_id) FROM lesson_learning_objects llo 
                 JOIN module_lessons ml ON ml.lesson_id = llo.lesson_id
                 JOIN course_modules cm ON cm.module_id = ml.module_id
                 WHERE llo.learning_object_id = lo.id) as num_corsi,
                (SELECT string_agg(DISTINCT c.title, ' | ' ORDER BY c.title) FROM lesson_learning_objects llo 
                 JOIN module_lessons ml ON ml.lesson_id = llo.lesson_id
                 JOIN course_modules cm ON cm.module_id = ml.module_id
                 JOIN courses c ON c.id = cm.course_id
                 WHERE llo.learning_object_id = lo.id) as corsi
         FROM learning_objects lo ORDER BY lo.id`
      );
      const esc = (v: any) => `"${String(v || "").replace(/"/g, '""')}"`;
      const header = "id;ovh_id;title;object_type;jwplayer_code;video_filename;slide_filename;document_filename;web_filename;duration;percentage_to_pass;suspended;status;quality;topic;notes;needs_review;review_notes;review_done;description;language;level;is_custom;num_domande;num_corsi;corsi;created_at";
      const csvRows = rows.map((r: any) => 
        [r.id, r.ovh_id || "", esc(r.title), r.object_type || "", r.jwplayer_code || "", 
         r.video_filename || "", r.slide_filename || "", r.document_filename || "", r.web_filename || "",
         r.duration || 0, r.percentage_to_pass || 0, r.suspended ? "1" : "0",
         esc(r.status), esc(r.quality), esc(r.topic), esc(r.notes),
         r.needs_review ? "1" : "0", esc(r.review_notes), r.review_done ? "1" : "0",
         esc(r.description), r.language || "", r.level || "", r.is_custom ? "1" : "0",
         r.num_domande || 0, r.num_corsi || 0, esc(r.corsi), r.created_at || ""].join(";")
      );
      const csv = [header, ...csvRows].join("\n");
      res.setHeader("Content-Type", "text/csv; charset=utf-8");
      res.setHeader("Content-Disposition", "attachment; filename=learning_objects.csv");
      res.send("\uFEFF" + csv);
    } catch (error) {
      console.error("CSV export error:", error);
      res.status(500).json({ error: "Failed to export" });
    }
  });
  app.get("/api/player/course/:id/structure", async (req, res) => {
    try {
      const courseId = parseInt(req.params.id as string);
      if (isNaN(courseId)) return res.status(400).json({ error: "Invalid course ID" });

      // Course info via raw SQL (column names are in Italian in DB)
      const cms = getCmsPool();
      const { rows: courseRows } = await cms.query(`SELECT id, title, description FROM courses WHERE id = $1`, [courseId]);
      if (courseRows.length === 0) return res.status(404).json({ error: "Course not found" });
      const course = courseRows[0];
      const { rows: moduleRows } = await cms.query(
        `SELECT cm.module_id, m.title, m.description
         FROM course_modules cm JOIN modules m ON m.id = cm.module_id
         WHERE cm.course_id = $1 ORDER BY cm.position, cm.id`, [courseId]
      );

      const structure = [];
      for (const mod of moduleRows) {
        // New CMS schema: LOs are directly in modules (no lessons layer)
        const { rows: loRows } = await cms.query(
          `SELECT mlo.learning_object_id, lo.title, lo.object_type, lo.jwplayer_code, lo.video_filename, lo.duration
           FROM module_learning_objects mlo JOIN learning_objects lo ON lo.id = mlo.learning_object_id
           WHERE mlo.module_id = $1 ORDER BY mlo.position, mlo.id`, [mod.module_id]
        );

        const los = [];
        for (const lo of loRows) {
          const { rows: questions } = await cms.query(
            `SELECT id, question_text, time_seconds, end_of_object, sort_order FROM lesson_slide_questions WHERE learning_object_id = $1 ORDER BY sort_order, id`, [lo.learning_object_id]
          );
          const questionsWithAnswers = [];
          for (const q of questions) {
            const { rows: answers } = await cms.query(
              `SELECT id, answer_text, is_correct, sort_order FROM lesson_slide_question_answers WHERE slide_question_id = $1 ORDER BY sort_order, id`, [q.id]
            );
            questionsWithAnswers.push({ ...q, answers });
          }
          los.push({
            id: lo.learning_object_id, title: lo.title, objectType: lo.object_type,
            jwplayerCode: lo.jwplayer_code, videoFilename: lo.video_filename,
            duration: lo.duration, questions: questionsWithAnswers,
          });
        }
        // Wrap LOs in a single "lesson" for backward compatibility with the player frontend
        structure.push({ id: mod.module_id, title: mod.title, lessons: [{ id: mod.module_id, title: mod.title, learningObjects: los }] });
      }

      console.log(`[Player] Course ${courseId}: ${structure.length} modules from CMS`);
      res.json({ course, modules: structure });
    } catch (error) {
      console.error("Player course structure error:", error);
      res.status(500).json({ error: "Failed to fetch course structure" });
    }
  });

  app.post("/api/player/validate-license", async (req, res) => {
    try {
      const { licenseCode } = req.body;
      if (!licenseCode) return res.status(400).json({ error: "License code required" });
      const enrollments = await db.select({ id: schema.enrollments.id, courseId: schema.enrollments.courseId, courseTitle: schema.courses.title, status: schema.enrollments.status }).from(schema.enrollments).innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id)).where(eq(schema.enrollments.licenseCode, licenseCode)).limit(1);
      if (enrollments.length > 0) {
        return res.json({ valid: true, enrollment: enrollments[0] });
      }
      return res.status(404).json({ error: "Invalid license code" });
    } catch (error) {
      res.status(500).json({ error: "Failed to validate license" });
    }
  });

  app.post("/api/player/check-username", async (req, res) => {
    try {
      const { username } = req.body;
      if (!username) return res.status(400).json({ error: "Username richiesto" });
      const parts = username.toLowerCase().trim().split(".");
      if (parts.length < 2) return res.status(400).json({ error: "Username deve essere nel formato nome.cognome" });
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      const studentResults = await db.select({ id: schema.students.id, fiscalCode: schema.students.fiscalCode }).from(schema.students).where(and(sql`LOWER(${schema.students.firstName}) = ${firstName}`, sql`LOWER(${schema.students.lastName}) = ${lastName}`)).limit(1);
      if (studentResults.length === 0) return res.status(404).json({ success: false, error: "Utente non trovato. Verifica il nome utente." });

      const cfData = parseCF(studentResults[0].fiscalCode || "");
      if (!cfData) return res.status(400).json({ success: false, error: "Dati utente incompleti. Contatta l'assistenza." });

      const questions = generateRandomQuestions(cfData);
      res.json({ success: true, questions });
    } catch (error) {
      console.error("Check username error:", error);
      res.status(500).json({ error: "Errore durante la verifica" });
    }
  });

  app.post("/api/player/verify-identity", async (req, res) => {
    try {
      const { username, answers } = req.body;
      if (!username || !answers || !Array.isArray(answers)) return res.status(400).json({ error: "Tutti i campi sono obbligatori" });

      const parts = username.toLowerCase().trim().split(".");
      if (parts.length < 2) return res.status(400).json({ error: "Username non valido" });
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      const studentResults = await db.select().from(schema.students).where(and(sql`LOWER(${schema.students.firstName}) = ${firstName}`, sql`LOWER(${schema.students.lastName}) = ${lastName}`)).limit(1);
      if (studentResults.length === 0) return res.status(401).json({ success: false, error: "Utente non trovato." });

      const student = studentResults[0];
      const cfData = parseCF(student.fiscalCode || "");
      if (!cfData) return res.status(400).json({ success: false, error: "Dati utente incompleti." });

      for (const ans of answers) {
        const expected = getExpectedAnswer(cfData, ans.questionId as QuestionType);
        if (String(ans.value) !== expected) {
          return res.status(401).json({ success: false, error: "Le risposte non corrispondono. Riprova." });
        }
      }

      const enrollmentResults = await db.select({ id: schema.enrollments.id, courseTitle: schema.courses.title, licenseCode: schema.enrollments.licenseCode }).from(schema.enrollments).innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id)).where(and(eq(schema.enrollments.studentId, student.id), eq(schema.enrollments.status, "active"))).limit(1);
      if (enrollmentResults.length === 0) return res.status(404).json({ success: false, error: "Nessun corso attivo trovato." });

      const enrollment = enrollmentResults[0];
      await db.update(schema.enrollments).set({ lastAccessAt: new Date() }).where(eq(schema.enrollments.id, enrollment.id));

      res.json({ success: true, student: { id: student.id, firstName: student.firstName, lastName: student.lastName, companyId: student.companyId }, enrollment: { id: enrollment.id, courseName: enrollment.courseTitle, licenseCode: enrollment.licenseCode } });
    } catch (error) {
      console.error("Verify identity error:", error);
      res.status(500).json({ error: "Errore durante la verifica" });
    }
  });

  app.post("/api/player/login", async (req, res) => {
    try {
      const { username, fiscalCode } = req.body;
      if (!username || !fiscalCode) return res.status(400).json({ error: "Username e codice fiscale richiesti" });
      const parts = username.toLowerCase().split(".");
      if (parts.length < 2) return res.status(400).json({ error: "Username deve essere nel formato nome.cognome" });
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      const studentResults = await db.select().from(schema.students).where(and(sql`LOWER(${schema.students.firstName}) = ${firstName}`, sql`LOWER(${schema.students.lastName}) = ${lastName}`, sql`${schema.students.fiscalCode} = ${fiscalCode}`)).limit(1);
      if (studentResults.length === 0) return res.status(401).json({ success: false, error: "Credenziali non valide." });

      const student = studentResults[0];
      const enrollmentResults = await db.select({ id: schema.enrollments.id, courseId: schema.enrollments.courseId, licenseCode: schema.enrollments.licenseCode, progress: schema.enrollments.progress, status: schema.enrollments.status, startDate: schema.enrollments.startDate, endDate: schema.enrollments.endDate, courseTitle: schema.courses.title }).from(schema.enrollments).innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id)).where(and(eq(schema.enrollments.studentId, student.id), eq(schema.enrollments.status, "active"))).limit(1);
      if (enrollmentResults.length === 0) return res.status(404).json({ success: false, error: "Nessun corso attivo trovato." });

      const enrollment = enrollmentResults[0];
      const companyResults = await db.select().from(schema.companies).where(eq(schema.companies.id, student.companyId)).limit(1);
      await db.update(schema.enrollments).set({ lastAccessAt: new Date() }).where(eq(schema.enrollments.id, enrollment.id));

      res.json({ success: true, user: { id: student.id, firstName: student.firstName, lastName: student.lastName, fiscalCode: student.fiscalCode, company: companyResults[0]?.businessName || "" }, enrollment: { id: enrollment.id, learningProjectId: enrollment.courseId, courseName: enrollment.courseTitle, licenseCode: enrollment.licenseCode, startDate: enrollment.startDate, endDate: enrollment.endDate, progress: enrollment.progress || 0, status: enrollment.status } });
    } catch (error) {
      console.error("Player login error:", error);
      res.status(500).json({ error: "Errore durante l'accesso" });
    }
  });

  app.post("/api/player/save-progress", async (req, res) => {
    try {
      const { enrollmentId, learningObjectId, watchedSeconds, completed, action, videoPosition } = req.body;
      if (!enrollmentId || !learningObjectId) return res.status(400).json({ error: "Missing fields" });
      const pool = getCmsPool();

      if (action === 'start') {
        // ALWAYS insert a new row for each entry
        await pool.query(
          `INSERT INTO enrollment_progress (enrollment_id, learning_object_id, started_at, video_position_seconds, watched_seconds, completed)
           VALUES ($1,$2,NOW(),$3,0,false)`,
          [enrollmentId, learningObjectId, videoPosition || 0]
        );
      } else if (action === 'leave') {
        // Update left_at on the LATEST row (most recent started_at) for this LO
        await pool.query(
          `UPDATE enrollment_progress SET left_at=NOW(), video_position_seconds=$1
           WHERE id = (SELECT id FROM enrollment_progress WHERE enrollment_id=$2 AND learning_object_id=$3 ORDER BY id DESC LIMIT 1)`,
          [videoPosition || 0, enrollmentId, learningObjectId]
        );
      } else if (completed) {
        // Mark completed on the LATEST row
        await pool.query(
          `UPDATE enrollment_progress SET completed=true, completed_at=NOW(), watched_seconds=$1, video_position_seconds=$2
           WHERE id = (SELECT id FROM enrollment_progress WHERE enrollment_id=$3 AND learning_object_id=$4 ORDER BY id DESC LIMIT 1)`,
          [watchedSeconds || 0, videoPosition || 0, enrollmentId, learningObjectId]
        );
      }

      res.json({ success: true });
    } catch (error) {
      console.error("Save progress error:", error);
      res.status(500).json({ error: "Failed to save progress" });
    }
  });

  app.post("/api/player/session/start", async (req, res) => {
    try {
      const { enrollmentId, studentId, courseId, ipAddress } = req.body;
      const [session] = await db.insert(schema.sessionLogs).values({ enrollmentId, studentId, courseId, ipAddress, loginAt: new Date() }).returning();
      res.json({ success: true, sessionId: session.id });
    } catch (error) {
      res.status(500).json({ error: "Failed to start session" });
    }
  });

  app.post("/api/player/session/end", async (req, res) => {
    try {
      const { sessionId, durationSeconds, lastLoId, lastLessonIndex, lastLoIndex } = req.body;
      if (!sessionId) return res.status(400).json({ error: "sessionId required" });
      await db.update(schema.sessionLogs).set({ logoutAt: new Date(), durationSeconds, lastLoId, lastLessonIndex, lastLoIndex }).where(eq(schema.sessionLogs.id, sessionId));
      res.json({ success: true });
    } catch (error) {
      res.status(500).json({ error: "Failed to end session" });
    }
  });

  app.post("/api/player/quiz/answer", async (req, res) => {
    try {
      const { enrollmentId, studentId, questionId, answerId, isCorrect, timedOut, responseTimeSeconds, learningObjectId, sessionLogId, questionText, answerText, videoSecond } = req.body;
      const pool = getCmsPool();
      await pool.query(`INSERT INTO quiz_responses (enrollment_id, student_id, question_id, answer_id, is_correct, timed_out, response_time_seconds, learning_object_id, session_log_id, question_text, answer_text, video_second) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)`,
        [enrollmentId, studentId, questionId, answerId, isCorrect||false, timedOut||false, responseTimeSeconds||0, learningObjectId, sessionLogId, questionText||'', answerText||'', videoSecond||0]);
      res.json({ success: true });
    } catch (error) {
      console.error("Quiz answer error:", error);
      res.status(500).json({ error: "Failed to save quiz answer" });
    }
  });

  app.get("/api/player/quiz/results/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      const responses = await db.select().from(schema.quizResponses).where(eq(schema.quizResponses.enrollmentId, enrollmentId));
      const total = responses.length;
      const correct = responses.filter((r) => r.isCorrect).length;
      res.json({ total, correct, percentage: total > 0 ? Math.round((correct / total) * 100) : 0, responses });
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch quiz results" });
    }
  });

  app.get("/api/player/lo-progress/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      const progress = await db.select().from(schema.enrollmentProgress).where(eq(schema.enrollmentProgress.enrollmentId, enrollmentId));
      res.json(progress);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch LO progress" });
    }
  });

  // Get all enrollments for a student (for player dashboard)
  app.get("/api/player/student/:studentId/enrollments", async (req, res) => {
    try {
      const studentId = parseInt(req.params.studentId as string);
      const results = await db
        .select({
          id: schema.enrollments.id,
          courseId: schema.enrollments.courseId,
          courseTitle: schema.courses.title,
          courseHours: schema.courses.hours,
          courseCategory: schema.courses.category,
          courseSubcategory: schema.courses.subcategory,
          licenseCode: schema.enrollments.licenseCode,
          progress: schema.enrollments.progress,
          status: schema.enrollments.status,
          startDate: schema.enrollments.startDate,
          endDate: schema.enrollments.endDate,
          lastAccessAt: schema.enrollments.lastAccessAt,
          completedAt: schema.enrollments.completedAt,
        })
        .from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.studentId, studentId))
        .orderBy(schema.enrollments.createdAt);
      res.json(results);
    } catch (error) {
      console.error("Student enrollments error:", error);
      res.status(500).json({ error: "Failed to fetch enrollments" });
    }
  });

  // Recalculate overall enrollment progress based on completed LOs
  app.post("/api/player/recalc-progress/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      const [enrollment] = await db.select().from(schema.enrollments).where(eq(schema.enrollments.id, enrollmentId));
      if (!enrollment) return res.status(404).json({ error: "Enrollment not found" });

      // Count total LOs for this course
      const courseId = enrollment.courseId;
      const cms = await db.select().from(schema.courseModules).where(eq(schema.courseModules.courseId, courseId));
      let totalLOs = 0;
      for (const cm of cms) {
        const mls = await db.select().from(schema.moduleLessons).where(eq(schema.moduleLessons.moduleId, cm.moduleId));
        for (const ml of mls) {
          const llos = await db.select().from(schema.lessonLearningObjects).where(eq(schema.lessonLearningObjects.lessonId, ml.lessonId));
          totalLOs += llos.length;
        }
      }

      // Count completed LOs
      const completedLOs = await db.select().from(schema.enrollmentProgress)
        .where(and(eq(schema.enrollmentProgress.enrollmentId, enrollmentId), eq(schema.enrollmentProgress.completed, true)));

      const progress = totalLOs > 0 ? Math.round((completedLOs.length / totalLOs) * 100) : 0;
      const isCompleted = progress >= 100;

      await db.update(schema.enrollments).set({
        progress,
        ...(isCompleted && !enrollment.completedAt ? { completedAt: new Date(), status: "completed" } : {}),
      }).where(eq(schema.enrollments.id, enrollmentId));

      res.json({ success: true, progress, completed: isCompleted, totalLOs, completedLOs: completedLOs.length });
    } catch (error) {
      console.error("Recalc progress error:", error);
      res.status(500).json({ error: "Failed to recalculate progress" });
    }
  });

  // Get full enrollment details for the player
  app.get("/api/player/enrollment/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      const [enrollment] = await db.select({
        id: schema.enrollments.id,
        studentId: schema.enrollments.studentId,
        courseId: schema.enrollments.courseId,
        courseTitle: schema.courses.title,
        courseDescription: schema.courses.description,
        courseHours: schema.courses.hours,
        courseCategory: schema.courses.category,
        maxExecutionTime: schema.courses.maxExecutionTime,
        percentageToPass: schema.courses.percentageToPass,
        licenseCode: schema.enrollments.licenseCode,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        lastAccessAt: schema.enrollments.lastAccessAt,
        completedAt: schema.enrollments.completedAt,
      }).from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.id, enrollmentId));

      if (!enrollment) return res.status(404).json({ error: "Enrollment not found" });
      res.json(enrollment);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch enrollment" });
    }
  });
}
