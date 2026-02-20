import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, sql, asc } from "drizzle-orm";

export function registerCoursesRoutes(app: Express) {
  app.get("/api/courses", isAuthenticated, async (_req, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const courses = await db.select().from(schema.courses).where(eq(schema.courses.isPublished, true)).orderBy(asc(schema.courses.sortOrder), asc(schema.courses.title));
      res.json(courses);
    } catch (error) {
      console.error("Courses list error:", error);
      res.status(500).json({ error: "Failed to fetch courses" });
    }
  });

  app.get("/api/learning-projects", isAuthenticated, async (_req, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const courses = await db.select().from(schema.courses).where(eq(schema.courses.isPublished, true)).orderBy(asc(schema.courses.sortOrder), asc(schema.courses.title));
      res.json(courses);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch courses" });
    }
  });

  app.get("/api/courses/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, id));
      if (!course) return res.status(404).json({ error: "Course not found" });
      res.json(course);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch course" });
    }
  });

  app.post("/api/courses", isAuthenticated, async (req, res) => {
    try {
      const [course] = await db.insert(schema.courses).values(req.body).returning();
      res.json(course);
    } catch (error) {
      console.error("Create course error:", error);
      res.status(500).json({ error: "Failed to create course" });
    }
  });

  app.get("/api/courses/:id/structure", isAuthenticated, async (req, res) => {
    try {
      const courseId = parseInt(req.params.id as string);
      const cms = await db.select().from(schema.courseModules).where(eq(schema.courseModules.courseId, courseId)).orderBy(asc(schema.courseModules.position));
      const structure = [];
      for (const cm of cms) {
        const [mod] = await db.select().from(schema.modules).where(eq(schema.modules.id, cm.moduleId));
        if (!mod) continue;
        const mls = await db.select().from(schema.moduleLessons).where(eq(schema.moduleLessons.moduleId, mod.id)).orderBy(asc(schema.moduleLessons.position));
        const lessons = [];
        for (const ml of mls) {
          const [lesson] = await db.select().from(schema.lessons).where(eq(schema.lessons.id, ml.lessonId));
          if (!lesson) continue;
          const llos = await db.select().from(schema.lessonLearningObjects).where(eq(schema.lessonLearningObjects.lessonId, lesson.id)).orderBy(asc(schema.lessonLearningObjects.position));
          const los = [];
          for (const llo of llos) {
            const [lo] = await db.select().from(schema.learningObjects).where(eq(schema.learningObjects.id, llo.learningObjectId));
            if (lo) los.push(lo);
          }
          lessons.push({ ...lesson, learningObjects: los });
        }
        structure.push({ ...mod, lessons });
      }
      res.json(structure);
    } catch (error) {
      console.error("Course structure error:", error);
      res.status(500).json({ error: "Failed to fetch course structure" });
    }
  });
}
