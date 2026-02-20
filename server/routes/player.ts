import type { Express } from "express";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, sql } from "drizzle-orm";

export function registerPlayerRoutes(app: Express) {
  // GET /api/player/course/:id/structure — struttura corso per player (no auth)
  app.get("/api/player/course/:id/structure", async (req, res) => {
    try {
      const courseId = parseInt(req.params.id as string);
      if (isNaN(courseId)) return res.status(400).json({ error: "Invalid course ID" });

      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId));
      if (!course) return res.status(404).json({ error: "Course not found" });

      const cms = await db.select().from(schema.courseModules)
        .where(eq(schema.courseModules.courseId, courseId))
        .orderBy(schema.courseModules.position);

      const moduleIds = cms.map(cm => cm.moduleId);
      if (moduleIds.length === 0) return res.json({ ...course, modules: [] });

      const mods = await db.select().from(schema.modules)
        .where(sql`${schema.modules.id} IN ${moduleIds}`);

      const mls = await db.select().from(schema.moduleLessons)
        .where(sql`${schema.moduleLessons.moduleId} IN ${moduleIds}`)
        .orderBy(schema.moduleLessons.position);

      const lessonIds = mls.map(ml => ml.lessonId);
      const lsns = lessonIds.length > 0
        ? await db.select().from(schema.lessons).where(sql`${schema.lessons.id} IN ${lessonIds}`)
        : [];

      const llos = lessonIds.length > 0
        ? await db.select().from(schema.lessonLearningObjects)
            .where(sql`${schema.lessonLearningObjects.lessonId} IN ${lessonIds}`)
            .orderBy(schema.lessonLearningObjects.position)
        : [];

      const loIds = llos.map(llo => llo.learningObjectId);
      const los = loIds.length > 0
        ? await db.select().from(schema.learningObjects).where(sql`${schema.learningObjects.id} IN ${loIds}`)
        : [];

      const structure = cms.map(cm => {
        const mod = mods.find(m => m.id === cm.moduleId);
        const modLessons = mls.filter(ml => ml.moduleId === cm.moduleId);
        return {
          ...mod,
          position: cm.position,
          lessons: modLessons.map(ml => {
            const lesson = lsns.find(l => l.id === ml.lessonId);
            const lessonLOs = llos.filter(llo => llo.lessonId === ml.lessonId);
            return {
              ...lesson,
              position: ml.position,
              learningObjects: lessonLOs.map(llo => {
                const lo = los.find(l => l.id === llo.learningObjectId);
                return { ...lo, position: llo.position };
              }),
            };
          }),
        };
      });

      res.json({ ...course, modules: structure });
    } catch (error) {
      console.error("Player course structure error:", error);
      res.status(500).json({ error: "Failed to fetch course structure" });
    }
  });

  // GET /api/learning-objects/:id/interruptions — quiz per LO
  app.get("/api/learning-objects/:id/interruptions", async (req, res) => {
    try {
      const loId = parseInt(req.params.id as string);
      if (isNaN(loId)) return res.status(400).json({ error: "Invalid learning object ID" });

      const questions = await db.select().from(schema.quizQuestions)
        .where(eq(schema.quizQuestions.learningObjectId, loId))
        .orderBy(schema.quizQuestions.sortOrder);

      const interruptionPoints: { triggerTime: number; questions: any[] }[] = [];

      for (const question of questions) {
        const answers = await db.select().from(schema.quizAnswers)
          .where(eq(schema.quizAnswers.questionId, question.id))
          .orderBy(schema.quizAnswers.sortOrder);

        let point = interruptionPoints.find(p => p.triggerTime === question.timeSeconds);
        if (!point) {
          point = { triggerTime: question.timeSeconds, questions: [] };
          interruptionPoints.push(point);
        }

        point.questions.push({
          id: question.id,
          text: question.questionText,
          answers: answers.map(a => ({ id: a.id, text: a.answerText, isCorrect: a.isCorrect })),
        });
      }

      interruptionPoints.sort((a, b) => a.triggerTime - b.triggerTime);
      res.json(interruptionPoints);
    } catch (error) {
      console.error("Interruptions error:", error);
      res.status(500).json({ error: "Failed to fetch interruptions" });
    }
  });

  // POST /api/player/validate-license
  app.post("/api/player/validate-license", async (req, res) => {
    try {
      const { licenseCode } = req.body;
      if (!licenseCode) return res.status(400).json({ error: "License code required" });

      if (hasDatabase) {
        const enrollment = await db.select({
          id: schema.enrollments.id,
          courseId: schema.enrollments.courseId,
          studentId: schema.enrollments.studentId,
          licenseCode: schema.enrollments.licenseCode,
          progress: schema.enrollments.progress,
          status: schema.enrollments.status,
          studentName: schema.students.firstName,
          studentSurname: schema.students.lastName,
          studentEmail: schema.students.email,
          courseTitle: schema.courses.title,
        })
          .from(schema.enrollments)
          .innerJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
          .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
          .where(eq(schema.enrollments.licenseCode, licenseCode))
          .limit(1);

        if (enrollment.length > 0) {
          return res.json({ valid: true, enrollment: enrollment[0] });
        }
      }

      return res.status(404).json({ error: "Invalid license code" });
    } catch (error) {
      console.error("License validation error:", error);
      res.status(500).json({ error: "Failed to validate license" });
    }
  });

  // ============================================================
  // CF PARSER — estrae data nascita e sesso dal codice fiscale
  // ============================================================
  const CF_MONTH_MAP: Record<string, number> = {
    A: 1, B: 2, C: 3, D: 4, E: 5, H: 6,
    L: 7, M: 8, P: 9, R: 10, S: 11, T: 12,
  };

  function parseCF(cf: string) {
    if (!cf || cf.length < 16) return null;
    const upper = cf.toUpperCase();
    const yearPart = parseInt(upper.substring(6, 8), 10);
    const monthLetter = upper.charAt(8);
    const dayPart = parseInt(upper.substring(9, 11), 10);

    const month = CF_MONTH_MAP[monthLetter];
    if (!month) return null;

    const day = dayPart > 40 ? dayPart - 40 : dayPart;
    const sex = dayPart > 40 ? 'F' : 'M';
    // Guess century: if yearPart > 30 assume 1900s, else 2000s
    const year = yearPart > 30 ? 1900 + yearPart : 2000 + yearPart;

    return { year, month, day, sex };
  }

  // POST /api/player/check-username — step 1: verifica che lo username esista
  app.post("/api/player/check-username", async (req, res) => {
    try {
      const { username } = req.body;
      if (!username) return res.status(400).json({ error: "Username richiesto" });

      const parts = username.toLowerCase().trim().split(".");
      if (parts.length < 2) {
        return res.status(400).json({ error: "Username deve essere nel formato nome.cognome" });
      }
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      const studentResults = await db.select({
        id: schema.students.id,
        fiscalCode: schema.students.fiscalCode,
      }).from(schema.students)
        .where(and(
          sql`LOWER(${schema.students.firstName}) = ${firstName}`,
          sql`LOWER(${schema.students.lastName}) = ${lastName}`,
        ))
        .limit(1);

      if (studentResults.length === 0) {
        return res.status(404).json({ success: false, error: "Utente non trovato. Verifica il nome utente." });
      }

      const student = studentResults[0];
      const cfData = parseCF(student.fiscalCode || '');

      if (!cfData) {
        return res.status(400).json({ success: false, error: "Dati utente incompleti. Contatta l'assistenza." });
      }

      // Return success but DON'T return the CF answers — just confirm the user exists
      res.json({ success: true, hasQuestions: true });
    } catch (error) {
      console.error("Check username error:", error);
      res.status(500).json({ error: "Errore durante la verifica" });
    }
  });

  // POST /api/player/verify-identity — step 2: verifica risposte sul CF
  app.post("/api/player/verify-identity", async (req, res) => {
    try {
      const { username, birthDay, birthMonth, birthYear } = req.body;
      if (!username || !birthDay || !birthMonth || !birthYear) {
        return res.status(400).json({ error: "Tutti i campi sono obbligatori" });
      }

      const parts = username.toLowerCase().trim().split(".");
      if (parts.length < 2) {
        return res.status(400).json({ error: "Username non valido" });
      }
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      const studentResults = await db.select().from(schema.students)
        .where(and(
          sql`LOWER(${schema.students.firstName}) = ${firstName}`,
          sql`LOWER(${schema.students.lastName}) = ${lastName}`,
        ))
        .limit(1);

      if (studentResults.length === 0) {
        return res.status(401).json({ success: false, error: "Utente non trovato." });
      }

      const student = studentResults[0];
      const cfData = parseCF(student.fiscalCode || '');

      if (!cfData) {
        return res.status(400).json({ success: false, error: "Dati utente incompleti." });
      }

      // Verify answers
      const inputDay = parseInt(birthDay, 10);
      const inputMonth = parseInt(birthMonth, 10);
      const inputYear = parseInt(birthYear, 10);

      if (inputDay !== cfData.day || inputMonth !== cfData.month || inputYear !== cfData.year) {
        return res.status(401).json({ success: false, error: "Le risposte non corrispondono. Riprova." });
      }

      // Find active enrollment
      const enrollmentResults = await db.select({
        id: schema.enrollments.id,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        courseTitle: schema.courses.title,
      })
        .from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(and(
          eq(schema.enrollments.studentId, student.id),
          eq(schema.enrollments.status, "active")
        ))
        .limit(1);

      if (enrollmentResults.length === 0) {
        return res.status(404).json({ success: false, error: "Nessun corso attivo trovato." });
      }

      const enrollment = enrollmentResults[0];

      await db.update(schema.enrollments)
        .set({ lastAccessAt: new Date() })
        .where(eq(schema.enrollments.id, enrollment.id));

      res.json({
        success: true,
        enrollment: {
          id: enrollment.id,
          courseName: enrollment.courseTitle,
          licenseCode: enrollment.licenseCode,
        },
      });
    } catch (error) {
      console.error("Verify identity error:", error);
      res.status(500).json({ error: "Errore durante la verifica" });
    }
  });

  // POST /api/player/login — login corsista (username + CF)
  app.post("/api/player/login", async (req, res) => {
    try {
      const { username, fiscalCode } = req.body;
      if (!username || !fiscalCode) {
        return res.status(400).json({ error: "Username e codice fiscale richiesti" });
      }

      const parts = username.toLowerCase().split(".");
      if (parts.length < 2) {
        return res.status(400).json({ error: "Username deve essere nel formato nome.cognome" });
      }
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      const studentResults = await db.select().from(schema.students)
        .where(and(
          sql`LOWER(${schema.students.firstName}) = ${firstName}`,
          sql`LOWER(${schema.students.lastName}) = ${lastName}`,
          sql`${schema.students.fiscalCode} = ${fiscalCode}`
        ))
        .limit(1);

      if (studentResults.length === 0) {
        return res.status(401).json({ success: false, error: "Credenziali non valide. Verifica username e codice fiscale." });
      }

      const student = studentResults[0];

      const enrollmentResults = await db.select({
        id: schema.enrollments.id,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        courseTitle: schema.courses.title,
      })
        .from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(and(
          eq(schema.enrollments.studentId, student.id),
          eq(schema.enrollments.status, "active")
        ))
        .limit(1);

      if (enrollmentResults.length === 0) {
        return res.status(404).json({ success: false, error: "Nessun corso attivo trovato per questo utente." });
      }

      const enrollment = enrollmentResults[0];

      const companyResults = await db.select().from(schema.companies)
        .where(eq(schema.companies.id, student.companyId)).limit(1);
      const company = companyResults[0];

      await db.update(schema.enrollments)
        .set({ lastAccessAt: new Date() })
        .where(eq(schema.enrollments.id, enrollment.id));

      res.json({
        success: true,
        user: {
          id: student.id,
          firstName: student.firstName,
          lastName: student.lastName,
          fiscalCode: student.fiscalCode,
          company: company?.businessName || "",
        },
        enrollment: {
          id: enrollment.id,
          learningProjectId: enrollment.courseId,
          courseName: enrollment.courseTitle,
          licenseCode: enrollment.licenseCode,
          startDate: enrollment.startDate,
          endDate: enrollment.endDate,
          progress: enrollment.progress || 0,
          status: enrollment.status,
        },
      });
    } catch (error) {
      console.error("Player login error:", error);
      res.status(500).json({ error: "Errore durante l'accesso" });
    }
  });

  // POST /api/player/save-progress
  app.post("/api/player/save-progress", async (req, res) => {
    try {
      const { enrollmentId, progress } = req.body;
      if (!enrollmentId) return res.status(400).json({ error: "Enrollment ID required" });

      const status = progress >= 100 ? "completed" : "active";
      const completedAt = progress >= 100 ? new Date() : null;

      await db.update(schema.enrollments)
        .set({ progress, status, completedAt, lastAccessAt: new Date() })
        .where(eq(schema.enrollments.id, enrollmentId));

      res.json({ success: true });
    } catch (error) {
      console.error("Save progress error:", error);
      res.status(500).json({ error: "Failed to save progress" });
    }
  });

  // POST /api/player/session/start
  app.post("/api/player/session/start", async (req, res) => {
    try {
      const { enrollmentId, studentId, courseId } = req.body;
      if (!enrollmentId || !studentId || !courseId) {
        return res.status(400).json({ error: "Missing required fields" });
      }

      const ipAddress = req.headers["x-forwarded-for"] as string || req.socket.remoteAddress || "";

      const [session] = await db.insert(schema.sessionLogs)
        .values({ enrollmentId, studentId, courseId, loginAt: new Date(), ipAddress })
        .returning();

      res.json({ success: true, sessionId: session.id });
    } catch (error) {
      console.error("Session start error:", error);
      res.status(500).json({ error: "Failed to start session" });
    }
  });

  // POST /api/player/session/end
  app.post("/api/player/session/end", async (req, res) => {
    try {
      const { sessionId, lastLessonIndex, lastLoIndex, lastLoId } = req.body;
      if (!sessionId) return res.status(400).json({ error: "Session ID required" });

      const [session] = await db.select().from(schema.sessionLogs)
        .where(eq(schema.sessionLogs.id, sessionId)).limit(1);

      if (!session) return res.status(404).json({ error: "Session not found" });

      const now = new Date();
      const durationSeconds = session.loginAt
        ? Math.floor((now.getTime() - new Date(session.loginAt).getTime()) / 1000)
        : 0;

      await db.update(schema.sessionLogs)
        .set({
          logoutAt: now,
          durationSeconds,
          lastLoId: lastLoId || null,
          lastLessonIndex: lastLessonIndex ?? null,
          lastLoIndex: lastLoIndex ?? null,
        })
        .where(eq(schema.sessionLogs.id, sessionId));

      res.json({ success: true, durationSeconds });
    } catch (error) {
      console.error("Session end error:", error);
      res.status(500).json({ error: "Failed to end session" });
    }
  });

  // POST /api/player/quiz/answer
  app.post("/api/player/quiz/answer", async (req, res) => {
    try {
      const { enrollmentId, studentId, questionId, answerId, isCorrect, timedOut, responseTimeSeconds, learningObjectId, sessionLogId } = req.body;
      if (!enrollmentId || !studentId || !questionId) {
        return res.status(400).json({ error: "Missing required fields" });
      }

      const [response] = await db.insert(schema.quizResponses)
        .values({
          enrollmentId,
          studentId,
          questionId,
          answerId: answerId || null,
          isCorrect: isCorrect || false,
          timedOut: timedOut || false,
          responseTimeSeconds: responseTimeSeconds || null,
          learningObjectId: learningObjectId || null,
          sessionLogId: sessionLogId || null,
        })
        .returning();

      res.json({ success: true, responseId: response.id });
    } catch (error) {
      console.error("Quiz answer save error:", error);
      res.status(500).json({ error: "Failed to save quiz answer" });
    }
  });

  // GET /api/player/quiz/results/:enrollmentId
  app.get("/api/player/quiz/results/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      if (isNaN(enrollmentId)) return res.status(400).json({ error: "Invalid enrollment ID" });

      const responses = await db.select().from(schema.quizResponses)
        .where(eq(schema.quizResponses.enrollmentId, enrollmentId));

      const total = responses.length;
      const correct = responses.filter(r => r.isCorrect).length;
      const wrong = responses.filter(r => !r.isCorrect && !r.timedOut).length;
      const timedOut = responses.filter(r => r.timedOut).length;
      const percentage = total > 0 ? Math.round((correct / total) * 100) : 0;

      res.json({ total, correct, wrong, timedOut, percentage });
    } catch (error) {
      console.error("Quiz results error:", error);
      res.status(500).json({ error: "Failed to get quiz results" });
    }
  });

  // POST /api/player/lo-progress — salva progresso LO
  app.post("/api/player/lo-progress", async (req, res) => {
    try {
      const { enrollmentId, learningObjectId, watchedSeconds, completed } = req.body;
      if (!enrollmentId || !learningObjectId) {
        return res.status(400).json({ error: "Missing required fields" });
      }

      const existing = await db.select().from(schema.enrollmentProgress)
        .where(and(
          eq(schema.enrollmentProgress.enrollmentId, enrollmentId),
          eq(schema.enrollmentProgress.learningObjectId, learningObjectId)
        ))
        .limit(1);

      if (existing.length > 0) {
        await db.update(schema.enrollmentProgress)
          .set({
            watchedSeconds: watchedSeconds || existing[0].watchedSeconds,
            completed: completed ?? existing[0].completed,
            completedAt: completed ? new Date() : existing[0].completedAt,
          })
          .where(eq(schema.enrollmentProgress.id, existing[0].id));
      } else {
        await db.insert(schema.enrollmentProgress).values({
          enrollmentId,
          learningObjectId,
          watchedSeconds: watchedSeconds || 0,
          completed: completed || false,
          completedAt: completed ? new Date() : null,
        });
      }

      res.json({ success: true });
    } catch (error) {
      console.error("LO progress error:", error);
      res.status(500).json({ error: "Failed to save LO progress" });
    }
  });

  // GET /api/player/lo-progress/:enrollmentId
  app.get("/api/player/lo-progress/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId as string);
      if (isNaN(enrollmentId)) return res.status(400).json({ error: "Invalid enrollment ID" });

      const progress = await db.select().from(schema.enrollmentProgress)
        .where(eq(schema.enrollmentProgress.enrollmentId, enrollmentId));

      res.json(progress);
    } catch (error) {
      console.error("LO progress fetch error:", error);
      res.status(500).json({ error: "Failed to fetch LO progress" });
    }
  });
}
