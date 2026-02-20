import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, sql, desc, or, ilike } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";

export function registerStudentsRoutes(app: Express) {
  app.get("/api/students", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      const results = await db.select({
        id: schema.students.id,
        companyId: schema.students.companyId,
        email: schema.students.email,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        fiscalCode: schema.students.fiscalCode,
        phone: schema.students.phone,
        isActive: schema.students.isActive,
        createdAt: schema.students.createdAt,
        companyName: schema.companies.businessName,
        tutorId: schema.companies.tutorId,
      }).from(schema.students)
        .innerJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .where(role >= 1000 ? sql`1=1` : tutorId ? eq(schema.companies.tutorId, tutorId) : sql`1=0`)
        .orderBy(desc(schema.students.createdAt));

      res.json(results);
    } catch (error) {
      console.error("Students list error:", error);
      res.status(500).json({ error: "Failed to fetch students" });
    }
  });

  app.post("/api/students", isAuthenticated, async (req, res) => {
    try {
      const [student] = await db.insert(schema.students).values(req.body).returning();
      res.json(student);
    } catch (error) {
      console.error("Create student error:", error);
      res.status(500).json({ error: "Failed to create student" });
    }
  });

  app.patch("/api/students/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      const [student] = await db.update(schema.students).set(req.body).where(eq(schema.students.id, id)).returning();
      res.json(student);
    } catch (error) {
      res.status(500).json({ error: "Failed to update student" });
    }
  });

  app.delete("/api/students/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      await db.delete(schema.students).where(eq(schema.students.id, id));
      res.json({ success: true });
    } catch (error) {
      res.status(500).json({ error: "Failed to delete student" });
    }
  });

  app.get("/api/users/search", isAuthenticated, async (req, res) => {
    try {
      const q = (req.query.q as string || "").trim();
      if (q.length < 2) return res.json([]);
      const results = await db.select({
        id: schema.students.id,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        email: schema.students.email,
        fiscalCode: schema.students.fiscalCode,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
      }).from(schema.students)
        .innerJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .where(or(ilike(schema.students.firstName, `%${q}%`), ilike(schema.students.lastName, `%${q}%`), ilike(schema.students.email, `%${q}%`), ilike(schema.students.fiscalCode, `%${q}%`)))
        .limit(20);
      res.json(results);
    } catch (error) {
      res.status(500).json({ error: "Search failed" });
    }
  });

  app.get("/api/user-enrollments/:studentId", isAuthenticated, async (req, res) => {
    try {
      const studentId = parseInt(req.params.studentId as string);
      const enrollments = await db.select({
        id: schema.enrollments.id,
        courseId: schema.enrollments.courseId,
        courseTitle: schema.courses.title,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        completedAt: schema.enrollments.completedAt,
      }).from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.studentId, studentId))
        .orderBy(desc(schema.enrollments.createdAt));
      res.json(enrollments);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch enrollments" });
    }
  });
}
