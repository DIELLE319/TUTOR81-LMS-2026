import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db, hasDatabase } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql } from "drizzle-orm";
import { getAuthenticatedDbUser } from "./helpers";

export function registerInvoicesRoutes(app: Express) {
  app.get("/api/sales", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      const results = await db.select({
        id: schema.tutorsPurchases.id,
        tutorId: schema.tutorsPurchases.tutorId,
        customerCompanyId: schema.tutorsPurchases.customerCompanyId,
        learningProjectId: schema.tutorsPurchases.learningProjectId,
        qta: schema.tutorsPurchases.qta,
        price: schema.tutorsPurchases.price,
        creationDate: schema.tutorsPurchases.creationDate,
        code: schema.tutorsPurchases.code,
        invoiced: schema.tutorsPurchases.invoiced,
        tutorName: schema.tutors.businessName,
        companyName: schema.companies.businessName,
        courseTitle: schema.courses.title,
      }).from(schema.tutorsPurchases)
        .leftJoin(schema.tutors, eq(schema.tutorsPurchases.tutorId, schema.tutors.id))
        .leftJoin(schema.companies, eq(schema.tutorsPurchases.customerCompanyId, schema.companies.id))
        .leftJoin(schema.courses, eq(schema.tutorsPurchases.learningProjectId, schema.courses.id))
        .where(role >= 1000 ? sql`1=1` : tutorId ? eq(schema.tutorsPurchases.tutorId, tutorId) : sql`1=0`)
        .orderBy(desc(schema.tutorsPurchases.creationDate));

      res.json(results);
    } catch (error) {
      console.error("Sales error:", error);
      res.status(500).json({ error: "Failed to fetch sales" });
    }
  });

  app.get("/api/invoices", isAuthenticated, async (req: any, res) => {
    try {
      if (!hasDatabase) return res.json([]);
      const dbUser = await getAuthenticatedDbUser(req);
      const role = (dbUser?.role as number) ?? 0;
      const tutorId = dbUser?.tutor_id as number | null;

      let invoices;
      if (role >= 1000) {
        invoices = await db.select().from(schema.invoices).orderBy(desc(schema.invoices.createdAt));
      } else if (tutorId) {
        invoices = await db.select().from(schema.invoices).where(eq(schema.invoices.tutorCompanyId, tutorId)).orderBy(desc(schema.invoices.createdAt));
      } else {
        invoices = [];
      }
      res.json(invoices);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch invoices" });
    }
  });

  app.post("/api/invoices", isAuthenticated, async (req, res) => {
    try {
      const [invoice] = await db.insert(schema.invoices).values(req.body).returning();
      res.json(invoice);
    } catch (error) {
      console.error("Create invoice error:", error);
      res.status(500).json({ error: "Failed to create invoice" });
    }
  });

  app.delete("/api/invoices/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      await db.delete(schema.invoices).where(eq(schema.invoices.id, id));
      res.json({ success: true });
    } catch (error) {
      res.status(500).json({ error: "Failed to delete invoice" });
    }
  });
}
