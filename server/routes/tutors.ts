import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, sql, and, desc } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";
import multer from "multer";
import path from "path";
import fs from "fs";

const UPLOADS_DIR = path.resolve(process.cwd(), "uploads", "logos");
if (!fs.existsSync(UPLOADS_DIR)) fs.mkdirSync(UPLOADS_DIR, { recursive: true });

const logoUpload = multer({
  storage: multer.diskStorage({
    destination: (_req, _file, cb) => cb(null, UPLOADS_DIR),
    filename: (_req, file, cb) => cb(null, `tutor-${Date.now()}${path.extname(file.originalname)}`),
  }),
  limits: { fileSize: 2 * 1024 * 1024 },
  fileFilter: (_req, file, cb) => {
    const allowed = [".jpg", ".jpeg", ".png", ".gif", ".svg", ".webp"];
    cb(null, allowed.includes(path.extname(file.originalname).toLowerCase()));
  },
});

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

  const sanitizeTutorBody = (body: any) => {
    const b = { ...body };
    delete b.id;
    delete b.createdAt;
    if (b.subscriptionStart === "") b.subscriptionStart = null;
    if (b.subscriptionEnd === "") b.subscriptionEnd = null;
    if (b.discountPercentage === "") b.discountPercentage = 60;
    if (b.annualFee === "") b.annualFee = 0;
    return b;
  };

  app.post("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      const values = sanitizeTutorBody(req.body);
      const [tutor] = await db.insert(schema.tutors).values(values).returning();
      res.json(tutor);
    } catch (error) {
      console.error("Create tutor error:", error);
      res.status(500).json({ error: "Failed to create tutor" });
    }
  });

  app.patch("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      const values = sanitizeTutorBody(req.body);
      const [tutor] = await db.update(schema.tutors).set(values).where(eq(schema.tutors.id, id)).returning();
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

  app.post("/api/tutors/send-admin-email", isAuthenticated, async (req, res) => {
    try {
      const { email, name, username, tutorName } = req.body;
      if (!email || !name) return res.status(400).json({ error: "Email e nome richiesti" });

      const { sendAdminWelcomeEmail } = require("../email");
      const result = await sendAdminWelcomeEmail({ to: email, name, username, tutorName });
      if (result.success) {
        res.json({ success: true });
      } else {
        res.status(500).json({ error: result.error || "Invio fallito" });
      }
    } catch (error) {
      console.error("Send admin email error:", error);
      res.status(500).json({ error: "Failed to send email" });
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

  // Logo upload
  app.post("/api/tutors/:id/logo", isAuthenticated, logoUpload.single("logo"), async (req: any, res) => {
    try {
      const id = parseInt(req.params.id);
      if (!req.file) return res.status(400).json({ error: "Nessun file caricato" });
      const logoUrl = `/uploads/logos/${req.file.filename}`;
      await db.update(schema.tutors).set({ logoUrl }).where(eq(schema.tutors.id, id));
      res.json({ success: true, logoUrl });
    } catch (error) {
      console.error("Logo upload error:", error);
      res.status(500).json({ error: "Upload fallito" });
    }
  });

  // Serve uploaded logos
  const express = require("express");
  app.use("/uploads", express.static(path.resolve(process.cwd(), "uploads")));

  app.get("/api/stats", isAuthenticated, async (req, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      const allTutors = await db.select({ id: schema.tutors.id }).from(schema.tutors).where(eq(schema.tutors.isActive, true));

      let companiesQuery;
      let studentsQuery;
      let salesQuery;
      if (role >= 1000) {
        companiesQuery = await db.select({ id: schema.companies.id }).from(schema.companies).where(eq(schema.companies.isActive, true));
        studentsQuery = await db.select({ id: schema.students.id }).from(schema.students);
        salesQuery = await db.select({ id: schema.tutorsPurchases.id }).from(schema.tutorsPurchases);
      } else if (tutorId) {
        companiesQuery = await db.select({ id: schema.companies.id }).from(schema.companies).where(and(eq(schema.companies.tutorId, tutorId), eq(schema.companies.isActive, true)));
        const companyIds = companiesQuery.map((c) => c.id);
        studentsQuery = companyIds.length > 0
          ? await db.select({ id: schema.students.id }).from(schema.students).where(sql`${schema.students.companyId} IN (${sql.join(companyIds.map(id => sql`${id}`), sql`,`)})`)
          : [];
        salesQuery = await db.select({ id: schema.tutorsPurchases.id }).from(schema.tutorsPurchases).where(eq(schema.tutorsPurchases.tutorId, tutorId));
      } else {
        companiesQuery = [];
        studentsQuery = [];
        salesQuery = [];
      }

      res.json({
        tutors: allTutors.length,
        clients: companiesQuery.length,
        sales: salesQuery.length,
        users: studentsQuery.length,
      });
    } catch (error) {
      console.error("Stats error:", error);
      res.json({ tutors: 0, clients: 0, sales: 0, users: 0 });
    }
  });
}
