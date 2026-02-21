import type { Express } from "express";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, sql } from "drizzle-orm";

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
  app.get("/api/player/course/:id/structure", async (req, res) => {
    try {
      const courseId = parseInt(req.params.id as string);
      if (isNaN(courseId)) return res.status(400).json({ error: "Invalid course ID" });
      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId));
      if (!course) return res.status(404).json({ error: "Course not found" });

      const cms = await db.select().from(schema.courseModules).where(eq(schema.courseModules.courseId, courseId));
      const structure = [];
      for (const cm of cms) {
        const [mod] = await db.select().from(schema.modules).where(eq(schema.modules.id, cm.moduleId));
        if (!mod) continue;
        const mls = await db.select().from(schema.moduleLessons).where(eq(schema.moduleLessons.moduleId, mod.id));
        const lessons = [];
        for (const ml of mls) {
          const [lesson] = await db.select().from(schema.lessons).where(eq(schema.lessons.id, ml.lessonId));
          if (!lesson) continue;
          const llos = await db.select().from(schema.lessonLearningObjects).where(eq(schema.lessonLearningObjects.lessonId, lesson.id));
          const los = [];
          for (const llo of llos) {
            const [lo] = await db.select().from(schema.learningObjects).where(eq(schema.learningObjects.id, llo.learningObjectId));
            if (lo) {
              const questions = await db.select().from(schema.quizQuestions).where(eq(schema.quizQuestions.learningObjectId, lo.id));
              const questionsWithAnswers = [];
              for (const q of questions) {
                const answers = await db.select().from(schema.quizAnswers).where(eq(schema.quizAnswers.questionId, q.id));
                questionsWithAnswers.push({ ...q, answers });
              }
              los.push({ ...lo, questions: questionsWithAnswers });
            }
          }
          lessons.push({ ...lesson, learningObjects: los });
        }
        structure.push({ ...mod, lessons });
      }
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
      const { enrollmentId, learningObjectId, watchedSeconds, completed } = req.body;
      if (!enrollmentId || !learningObjectId) return res.status(400).json({ error: "Missing fields" });
      const existing = await db.select().from(schema.enrollmentProgress).where(and(eq(schema.enrollmentProgress.enrollmentId, enrollmentId), eq(schema.enrollmentProgress.learningObjectId, learningObjectId))).limit(1);
      if (existing.length > 0) {
        await db.update(schema.enrollmentProgress).set({ watchedSeconds: watchedSeconds || 0, completed: completed || false, completedAt: completed ? new Date() : null }).where(eq(schema.enrollmentProgress.id, existing[0].id));
      } else {
        await db.insert(schema.enrollmentProgress).values({ enrollmentId, learningObjectId, watchedSeconds: watchedSeconds || 0, completed: completed || false, completedAt: completed ? new Date() : null });
      }
      res.json({ success: true });
    } catch (error) {
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
      const { enrollmentId, studentId, questionId, answerId, isCorrect, timedOut, responseTimeSeconds, learningObjectId, sessionLogId } = req.body;
      await db.insert(schema.quizResponses).values({ enrollmentId, studentId, questionId, answerId, isCorrect, timedOut, responseTimeSeconds, learningObjectId, sessionLogId });
      res.json({ success: true });
    } catch (error) {
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
