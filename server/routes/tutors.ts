import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db } from "../db";
import * as schema from "@shared/schema";
import { eq, sql } from "drizzle-orm";
import { getPlatformTutorIdsCached } from "./helpers";

export function registerTutorsRoutes(app: Express) {
  // GET /api/tutors — lista enti formativi
  app.get("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      const platformTutorIds = await getPlatformTutorIdsCached();

      let tutors = await db.select().from(schema.tutors)
        .where(sql`
          coalesce(${schema.tutors.isActive}, true) = true
          and trim(coalesce(${schema.tutors.subscriptionType}, '')) <> ''
          and coalesce(${schema.tutors.subscriptionType}, '') not ilike '%nessuno%'
          and coalesce(${schema.tutors.subscriptionType}, '') not ilike '%nessun abbonamento%'
          and coalesce(${schema.tutors.discountPercentage}, 0) > 0
        `)
        .orderBy(schema.tutors.businessName);

      if (platformTutorIds.size > 0) {
        const platformTutors = await db.select().from(schema.tutors)
          .where(sql`
            coalesce(${schema.tutors.isActive}, true) = true
            and (
              ${schema.tutors.businessName} ilike '%tutor81%'
              or coalesce(${schema.tutors.email}, '') ilike '%tutor81%'
            )
          `)
          .orderBy(schema.tutors.businessName);

        const byId = new Map<number, (typeof tutors)[number]>();
        for (const t of tutors) byId.set(t.id, t);
        for (const t of platformTutors) byId.set(t.id, t);
        tutors = Array.from(byId.values()).sort((a, b) => a.businessName.localeCompare(b.businessName));
      }

      res.json(tutors);
    } catch (error) {
      console.error("Tutors error:", error);
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  // GET /api/tutors/:id
  app.get("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid tutor ID" });

      const [tutor] = await db.select().from(schema.tutors).where(eq(schema.tutors.id, id));
      if (!tutor) return res.status(404).json({ error: "Tutor not found" });

      res.json(tutor);
    } catch (error) {
      console.error("Tutor error:", error);
      res.status(500).json({ error: "Failed to fetch tutor" });
    }
  });

  // POST /api/tutors — crea ente formativo
  app.post("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertTutorSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newTutor] = await db.insert(schema.tutors).values(result.data).returning();
      res.status(201).json(newTutor);
    } catch (error) {
      console.error("Create tutor error:", error);
      res.status(500).json({ error: "Failed to create tutor" });
    }
  });

  // PUT /api/tutors/:id — modifica ente formativo
  app.put("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid tutor ID" });

      const [updated] = await db.update(schema.tutors)
        .set(req.body)
        .where(eq(schema.tutors.id, id))
        .returning();

      if (!updated) return res.status(404).json({ error: "Tutor not found" });
      res.json(updated);
    } catch (error) {
      console.error("Update tutor error:", error);
      res.status(500).json({ error: "Failed to update tutor" });
    }
  });

  // DELETE /api/tutors/:id
  app.delete("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid tutor ID" });

      const [hasCompanies] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.companies).where(eq(schema.companies.tutorId, id));

      if (Number(hasCompanies.count) > 0) {
        return res.status(400).json({ error: "Cannot delete tutor with associated companies" });
      }

      await db.delete(schema.tutors).where(eq(schema.tutors.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete tutor error:", error);
      res.status(500).json({ error: "Failed to delete tutor" });
    }
  });

  // GET /api/tutors/:tutorId/admins — admin di un ente
  app.get("/api/tutors/:tutorId/admins", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.params.tutorId as string);
      if (isNaN(tutorId)) return res.status(400).json({ error: "Invalid tutor ID" });

      const admins = await db.select().from(schema.tutorAdmins)
        .where(eq(schema.tutorAdmins.tutorId, tutorId))
        .orderBy(schema.tutorAdmins.name);

      res.json(admins);
    } catch (error) {
      console.error("Tutor admins error:", error);
      res.status(500).json({ error: "Failed to fetch tutor admins" });
    }
  });

  // POST /api/tutors/:tutorId/admins — crea admin
  app.post("/api/tutors/:tutorId/admins", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.params.tutorId as string);
      if (isNaN(tutorId)) return res.status(400).json({ error: "Invalid tutor ID" });

      const { name, email, phone } = req.body;
      if (!name) return res.status(400).json({ error: "Name is required" });

      const [newAdmin] = await db.insert(schema.tutorAdmins).values({
        tutorId,
        name,
        email: email || null,
        phone: phone || null,
      }).returning();

      res.status(201).json(newAdmin);
    } catch (error) {
      console.error("Create tutor admin error:", error);
      res.status(500).json({ error: "Failed to create tutor admin" });
    }
  });

  // DELETE /api/tutor-admins/:id
  app.delete("/api/tutor-admins/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid admin ID" });

      await db.delete(schema.tutorAdmins).where(eq(schema.tutorAdmins.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete tutor admin error:", error);
      res.status(500).json({ error: "Failed to delete tutor admin" });
    }
  });

  // GET /api/stats — statistiche dashboard
  app.get("/api/stats", isAuthenticated, async (req, res) => {
    try {
      const [tutorCount] = await db.select({ count: sql<number>`count(*)` }).from(schema.tutors);
      const [companyCount] = await db.select({ count: sql<number>`count(*)` }).from(schema.companies);
      const [studentCount] = await db.select({ count: sql<number>`count(*)` }).from(schema.students);
      const [enrollmentCount] = await db.select({ count: sql<number>`count(*)` }).from(schema.enrollments);
      const [courseCount] = await db.select({ count: sql<number>`count(*)` }).from(schema.courses);

      res.json({
        tutors: Number(tutorCount.count),
        companies: Number(companyCount.count),
        students: Number(studentCount.count),
        enrollments: Number(enrollmentCount.count),
        courses: Number(courseCount.count),
      });
    } catch (error) {
      console.error("Stats error:", error);
      res.status(500).json({ error: "Failed to fetch stats" });
    }
  });
}
