import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, sql } from "drizzle-orm";

export function registerCoursesRoutes(app: Express) {
  // GET /api/courses — lista catalogo corsi
  app.get("/api/courses", isAuthenticated, async (req, res) => {
    try {
      const courses = await db.select().from(schema.courses).orderBy(schema.courses.title);
      res.json(courses);
    } catch (error) {
      console.error("Courses error:", error);
      res.status(500).json({ error: "Failed to fetch courses" });
    }
  });

  // GET /api/learning-projects — alias (compatibilità)
  app.get("/api/learning-projects", isAuthenticated, async (req, res) => {
    try {
      const courses = await db.select().from(schema.courses).orderBy(schema.courses.title);
      res.json(courses);
    } catch (error) {
      console.error("Learning projects error:", error);
      res.status(500).json({ error: "Failed to fetch learning projects" });
    }
  });

  // GET /api/courses/:id — dettaglio corso
  app.get("/api/courses/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid course ID" });

      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, id));
      if (!course) return res.status(404).json({ error: "Course not found" });

      res.json(course);
    } catch (error) {
      console.error("Course error:", error);
      res.status(500).json({ error: "Failed to fetch course" });
    }
  });

  // POST /api/courses — crea corso
  app.post("/api/courses", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertCourseSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newCourse] = await db.insert(schema.courses).values(result.data).returning();
      res.status(201).json(newCourse);
    } catch (error) {
      console.error("Create course error:", error);
      res.status(500).json({ error: "Failed to create course" });
    }
  });

  // GET /api/courses/:id/structure — struttura corso (moduli, lezioni, LO)
  app.get("/api/courses/:id/structure", isAuthenticated, async (req, res) => {
    try {
      const courseId = parseInt(req.params.id as string);
      if (isNaN(courseId)) return res.status(400).json({ error: "Invalid course ID" });

      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId));
      if (!course) return res.status(404).json({ error: "Course not found" });

      const cms = await db.select().from(schema.courseModules)
        .where(eq(schema.courseModules.courseId, courseId))
        .orderBy(schema.courseModules.position);

      const moduleIds = cms.map(cm => cm.moduleId);
      if (moduleIds.length === 0) {
        return res.json({ ...course, modules: [] });
      }

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
      console.error("Course structure error:", error);
      res.status(500).json({ error: "Failed to fetch course structure" });
    }
  });

  // GET /api/modules, /api/lessons, /api/learning-objects — liste base
  app.get("/api/modules", isAuthenticated, async (_req, res) => {
    try {
      const data = await db.select().from(schema.modules);
      res.json(data);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch modules" });
    }
  });

  app.get("/api/lessons", isAuthenticated, async (_req, res) => {
    try {
      const data = await db.select().from(schema.lessons);
      res.json(data);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch lessons" });
    }
  });

  app.get("/api/learning-objects", isAuthenticated, async (_req, res) => {
    try {
      const data = await db.select().from(schema.learningObjects);
      res.json(data);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch learning objects" });
    }
  });
}
