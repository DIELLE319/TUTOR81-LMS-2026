import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, sql, desc } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";

export function registerCompaniesRoutes(app: Express) {
  // Clients grouped by tutor
  app.get("/api/clients", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      let tutors: any[];
      if (role >= 1000) {
        tutors = await db.select().from(schema.tutors).where(eq(schema.tutors.isActive, true)).orderBy(schema.tutors.businessName);
      } else if (tutorId) {
        tutors = await db.select().from(schema.tutors).where(eq(schema.tutors.id, tutorId));
      } else {
        return res.json([]);
      }

      const result = [];
      for (const tutor of tutors) {
        const clients = await db.select().from(schema.companies).where(and(eq(schema.companies.tutorId, tutor.id), eq(schema.companies.isActive, true))).orderBy(schema.companies.businessName);
        result.push({ tutor: { id: tutor.id, businessName: tutor.businessName, subscriptionType: tutor.subscriptionType }, clients });
      }
      res.json(result);
    } catch (error) {
      console.error("Clients error:", error);
      res.status(500).json({ error: "Failed to fetch clients" });
    }
  });

  app.get("/api/me/company", isAuthenticated, async (req: any, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      if (!dbUser?.idcompany) return res.status(404).json({ error: "No company" });
      const [company] = await db.select().from(schema.companies).where(eq(schema.companies.id, dbUser.idcompany as number));
      res.json(company || null);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch company" });
    }
  });

  app.get("/api/me/company/users", isAuthenticated, async (req: any, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      if (!dbUser?.idcompany) return res.json([]);
      const users = await db.select().from(schema.students).where(eq(schema.students.companyId, dbUser.idcompany as number));
      res.json(users);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch company users" });
    }
  });

  app.get("/api/companies", isAuthenticated, async (req: any, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      let companiesList;
      if (role >= 1000) {
        companiesList = await db.select().from(schema.companies).where(eq(schema.companies.isActive, true)).orderBy(schema.companies.businessName);
      } else if (tutorId) {
        companiesList = await db.select().from(schema.companies).where(and(eq(schema.companies.tutorId, tutorId), eq(schema.companies.isActive, true))).orderBy(schema.companies.businessName);
      } else {
        companiesList = [];
      }
      res.json(companiesList);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch companies" });
    }
  });

  app.get("/api/companies-list", isAuthenticated, async (req: any, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      let list;
      if (role >= 1000) {
        list = await db.select({ id: schema.companies.id, businessName: schema.companies.businessName, tutorId: schema.companies.tutorId }).from(schema.companies).where(eq(schema.companies.isActive, true)).orderBy(schema.companies.businessName);
      } else if (tutorId) {
        list = await db.select({ id: schema.companies.id, businessName: schema.companies.businessName, tutorId: schema.companies.tutorId }).from(schema.companies).where(and(eq(schema.companies.tutorId, tutorId), eq(schema.companies.isActive, true))).orderBy(schema.companies.businessName);
      } else {
        list = [];
      }
      res.json(list);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch companies list" });
    }
  });

  app.get("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      const [company] = await db.select().from(schema.companies).where(eq(schema.companies.id, id));
      if (!company) return res.status(404).json({ error: "Company not found" });
      res.json(company);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch company" });
    }
  });

  app.get("/api/companies/:id/users", isAuthenticated, async (req, res) => {
    try {
      const companyId = parseInt(req.params.id as string);
      const users = await db.select().from(schema.students).where(eq(schema.students.companyId, companyId));
      res.json(users);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch company users" });
    }
  });

  app.post("/api/companies", isAuthenticated, async (req: any, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      const tutorId = dbUser?.tutor_id as number | null;
      const body = { ...req.body };
      if (!body.tutorId && tutorId) body.tutorId = tutorId;
      const [company] = await db.insert(schema.companies).values(body).returning();
      res.json(company);
    } catch (error) {
      console.error("Create company error:", error);
      res.status(500).json({ error: "Failed to create company" });
    }
  });

  app.patch("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      const [company] = await db.update(schema.companies).set(req.body).where(eq(schema.companies.id, id)).returning();
      res.json(company);
    } catch (error) {
      res.status(500).json({ error: "Failed to update company" });
    }
  });

  app.delete("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      await db.update(schema.companies).set({ isActive: false }).where(eq(schema.companies.id, id));
      res.json({ success: true });
    } catch (error) {
      res.status(500).json({ error: "Failed to delete company" });
    }
  });

  app.get("/api/tutors-for-companies", isAuthenticated, async (_req, res) => {
    try {
      const tutors = await db.select({ id: schema.tutors.id, businessName: schema.tutors.businessName }).from(schema.tutors).where(eq(schema.tutors.isActive, true)).orderBy(schema.tutors.businessName);
      res.json(tutors);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  app.post("/api/check-fiscal-code", isAuthenticated, async (req, res) => {
    try {
      const { fiscalCode } = req.body;
      if (!fiscalCode) return res.status(400).json({ error: "Fiscal code required" });
      const existing = await db.select({ id: schema.students.id, firstName: schema.students.firstName, lastName: schema.students.lastName, email: schema.students.email }).from(schema.students).where(eq(schema.students.fiscalCode, fiscalCode.toUpperCase())).limit(1);
      res.json({ exists: existing.length > 0, student: existing[0] || null });
    } catch (error) {
      res.status(500).json({ error: "Failed to check fiscal code" });
    }
  });
}
