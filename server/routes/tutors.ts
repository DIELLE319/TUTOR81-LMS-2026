import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, sql, and, desc } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";

export function registerTutorsRoutes(app: Express) {
  app.get("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = dbUser?.role ?? 0;
      let tutorsList;
      if (role >= 1000) {
        tutorsList = await db.select().from(schema.tutors).orderBy(schema.tutors.businessName);
      } else if (dbUser?.tutor_id) {
        tutorsList = await db.select().from(schema.tutors).where(eq(schema.tutors.id, dbUser.tutor_id));
      } else {
        tutorsList = [];
      }
      res.json(tutorsList);
    } catch (error) {
      console.error("Tutors list error:", error);
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  app.get("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const [tutor] = await db.select().from(schema.tutors).where(eq(schema.tutors.id, id));
      if (!tutor) return res.status(404).json({ error: "Tutor not found" });
      res.json(tutor);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch tutor" });
    }
  });

  app.post("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      const [tutor] = await db.insert(schema.tutors).values(req.body).returning();
      res.json(tutor);
    } catch (error) {
      console.error("Create tutor error:", error);
      res.status(500).json({ error: "Failed to create tutor" });
    }
  });

  app.patch("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const [tutor] = await db.update(schema.tutors).set(req.body).where(eq(schema.tutors.id, id)).returning();
      res.json(tutor);
    } catch (error) {
      res.status(500).json({ error: "Failed to update tutor" });
    }
  });

  app.delete("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      await db.delete(schema.tutors).where(eq(schema.tutors.id, id));
      res.json({ success: true });
    } catch (error) {
      res.status(500).json({ error: "Failed to delete tutor" });
    }
  });

  app.get("/api/tutors/:id/admins", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.params.id);
      const admins = await db.select().from(schema.tutorAdmins).where(eq(schema.tutorAdmins.tutorId, tutorId));
      res.json(admins);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch tutor admins" });
    }
  });

  app.post("/api/tutors/:id/admins", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.params.id);
      const [admin] = await db.insert(schema.tutorAdmins).values({ ...req.body, tutorId }).returning();
      res.json(admin);
    } catch (error) {
      res.status(500).json({ error: "Failed to create tutor admin" });
    }
  });

  app.delete("/api/tutor-admins/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      await db.delete(schema.tutorAdmins).where(eq(schema.tutorAdmins.id, id));
      res.json({ success: true });
    } catch (error) {
      res.status(500).json({ error: "Failed to delete tutor admin" });
    }
  });

  app.get("/api/stats", isAuthenticated, async (req, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      const role = dbUser?.role ?? 0;
      const tutorId = dbUser?.tutor_id;

      let tutorFilter = sql`1=1`;
      if (role < 1000 && tutorId) tutorFilter = sql`tutor_id = ${tutorId}`;

      const tutorsRes = await db.execute(sql`SELECT COUNT(*)::int as count FROM tutors WHERE is_active = true`);
      const companiesRes = await db.execute(sql`SELECT COUNT(*)::int as count FROM companies WHERE is_active = true AND ${tutorFilter}`);
      const salesRes = await db.execute(sql`SELECT COUNT(*)::int as count FROM tutors_purchases WHERE ${tutorFilter}`);
      const usersRes = await db.execute(sql`SELECT COUNT(*)::int as count FROM students WHERE ${tutorFilter}`);

      res.json({
        tutors: (tutorsRes.rows[0] as any)?.count || 0,
        clients: (companiesRes.rows[0] as any)?.count || 0,
        sales: (salesRes.rows[0] as any)?.count || 0,
        users: (usersRes.rows[0] as any)?.count || 0,
      });
    } catch (error) {
      console.error("Stats error:", error);
      res.json({ tutors: 0, clients: 0, sales: 0, users: 0 });
    }
  });
}
