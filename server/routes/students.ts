import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, ilike, or, sql } from "drizzle-orm";
import { getAuthenticatedUserTutorId } from "./helpers";

export function registerStudentsRoutes(app: Express) {
  // GET /api/students — lista corsisti (filtrata per ruolo)
  app.get("/api/students", isAuthenticated, async (req, res) => {
    try {
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);

      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }

      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;
      const tutorIdFilter = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      const limit = req.query.limit ? parseInt(req.query.limit as string) : 500;

      const students = await db.select({
        id: schema.students.id,
        companyId: schema.students.companyId,
        email: schema.students.email,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        fiscalCode: schema.students.fiscalCode,
        phone: schema.students.phone,
        isActive: schema.students.isActive,
        companyName: schema.companies.businessName,
        tutorId: schema.companies.tutorId,
        tutorName: schema.tutors.businessName,
      })
        .from(schema.students)
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.tutors, eq(schema.companies.tutorId, schema.tutors.id))
        .orderBy(schema.students.lastName)
        .limit(limit);

      let filtered = students;
      if (tutorIdFilter) {
        filtered = filtered.filter(s => s.tutorId === tutorIdFilter);
      }
      if (companyId) {
        filtered = filtered.filter(s => s.companyId === companyId);
      }

      res.json(filtered);
    } catch (error) {
      console.error("Students error:", error);
      res.status(500).json({ error: "Failed to fetch students" });
    }
  });

  // POST /api/students — crea corsista
  app.post("/api/students", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertStudentSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newStudent] = await db.insert(schema.students).values(result.data).returning();
      res.status(201).json(newStudent);
    } catch (error) {
      console.error("Create student error:", error);
      res.status(500).json({ error: "Failed to create student" });
    }
  });

  // PATCH /api/students/:id — modifica corsista
  app.patch("/api/students/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid student ID" });

      const [updated] = await db.update(schema.students)
        .set(req.body)
        .where(eq(schema.students.id, id))
        .returning();

      if (!updated) return res.status(404).json({ error: "Student not found" });
      res.json(updated);
    } catch (error) {
      console.error("Update student error:", error);
      res.status(500).json({ error: "Failed to update student" });
    }
  });

  // DELETE /api/students/:id
  app.delete("/api/students/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid student ID" });

      await db.delete(schema.enrollments).where(eq(schema.enrollments.studentId, id));
      await db.delete(schema.students).where(eq(schema.students.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete student error:", error);
      res.status(500).json({ error: "Failed to delete student" });
    }
  });

  // GET /api/users/search — ricerca corsisti
  app.get("/api/users/search", isAuthenticated, async (req, res) => {
    try {
      const q = (req.query.q as string || '').trim();
      if (!q) return res.json([]);

      const results = await db.select({
        id: schema.students.id,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        email: schema.students.email,
        fiscalCode: schema.students.fiscalCode,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
      })
        .from(schema.students)
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .where(or(
          ilike(schema.students.lastName, `%${q}%`),
          ilike(schema.students.firstName, `%${q}%`),
          ilike(schema.students.email, `%${q}%`),
          ilike(schema.students.fiscalCode, `%${q}%`),
        ))
        .orderBy(schema.students.lastName)
        .limit(50);

      res.json(results);
    } catch (error) {
      console.error("Users search error:", error);
      res.status(500).json({ error: "Failed to search users" });
    }
  });

  // GET /api/user-enrollments — iscrizioni di un corsista
  app.get("/api/user-enrollments", isAuthenticated, async (req, res) => {
    try {
      const studentId = req.query.studentId ? parseInt(req.query.studentId as string) : null;
      if (!studentId) return res.status(400).json({ error: "studentId required" });

      const enrollments = await db.select({
        id: schema.enrollments.id,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        courseTitle: schema.courses.title,
      })
        .from(schema.enrollments)
        .leftJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.studentId, studentId))
        .orderBy(desc(schema.enrollments.startDate));

      res.json(enrollments);
    } catch (error) {
      console.error("User enrollments error:", error);
      res.status(500).json({ error: "Failed to fetch user enrollments" });
    }
  });
}
