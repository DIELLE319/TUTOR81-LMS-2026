import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db } from "../db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql } from "drizzle-orm";
import { getAuthenticatedUserTutorId } from "./helpers";

export function registerInvoicesRoutes(app: Express) {
  // GET /api/sales — vendite/acquisti
  app.get("/api/sales", isAuthenticated, async (req, res) => {
    try {
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);

      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }

      const tutorId = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);

      const conditions = [];
      if (tutorId) {
        conditions.push(eq(schema.tutorsPurchases.tutorId, tutorId));
      }

      const salesRows = await db.select({
        id: schema.tutorsPurchases.id,
        tutorId: schema.tutorsPurchases.tutorId,
        tutorName: schema.tutors.businessName,
        clientId: schema.tutorsPurchases.customerCompanyId,
        client: schema.companies.businessName,
        date: schema.tutorsPurchases.creationDate,
        courseId: schema.tutorsPurchases.learningProjectId,
        courseName: schema.courses.title,
        qty: schema.tutorsPurchases.qta,
        unitPrice: schema.tutorsPurchases.price,
      })
        .from(schema.tutorsPurchases)
        .leftJoin(schema.tutors, eq(schema.tutorsPurchases.tutorId, schema.tutors.id))
        .leftJoin(schema.companies, eq(schema.tutorsPurchases.customerCompanyId, schema.companies.id))
        .leftJoin(schema.courses, eq(schema.tutorsPurchases.learningProjectId, schema.courses.id))
        .where(conditions.length > 0 ? and(...conditions) : undefined)
        .orderBy(desc(schema.tutorsPurchases.creationDate))
        .limit(500);

      const result = salesRows.map(r => ({
        ...r,
        totalCost: (Number(r.qty) * Number(r.unitPrice)).toFixed(2),
      }));

      res.json(result);
    } catch (error) {
      console.error("Sales error:", error);
      res.status(500).json({ error: "Failed to fetch sales" });
    }
  });

  // GET /api/invoices — lista fatture
  app.get("/api/invoices", isAuthenticated, async (req, res) => {
    try {
      const invoicesData = await db.select().from(schema.invoices).orderBy(desc(schema.invoices.createdAt));
      const result = invoicesData.map(inv => ({
        id: inv.id,
        tutorId: inv.tutorCompanyId,
        tutorName: inv.tutorCompanyName || '',
        month: inv.monthReference,
        year: inv.yearReference,
        orderIds: inv.orderIds || '',
        totalAmount: String(inv.totalAmount),
        invoiceNumber: inv.invoiceNumber ? `FAT-${inv.invoiceYear}-${String(inv.invoiceNumber).padStart(2, '0')}` : null,
        createdAt: inv.createdAt?.toISOString() || new Date().toISOString(),
      }));
      res.json(result);
    } catch (error) {
      console.error("Invoices GET error:", error);
      res.status(500).json({ error: "Failed to fetch invoices" });
    }
  });

  // GET /api/invoice?tutorId=X&month=Y&year=Z — genera dati fattura
  app.get("/api/invoice", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.query.tutorId as string);
      const month = parseInt(req.query.month as string);
      const year = parseInt(req.query.year as string);

      if (isNaN(tutorId) || isNaN(month) || isNaN(year)) {
        return res.status(400).json({ error: "tutorId, month, year required" });
      }

      const [tutor] = await db.select().from(schema.tutors).where(eq(schema.tutors.id, tutorId)).limit(1);
      if (!tutor) return res.status(404).json({ error: "Tutor not found" });

      const startDate = new Date(year, month - 1, 1);
      const endDate = new Date(year, month, 0, 23, 59, 59);

      const sales = await db.select({
        id: schema.tutorsPurchases.id,
        courseId: schema.tutorsPurchases.learningProjectId,
        qty: schema.tutorsPurchases.qta,
        price: schema.tutorsPurchases.price,
        creationDate: schema.tutorsPurchases.creationDate,
      })
        .from(schema.tutorsPurchases)
        .where(and(
          eq(schema.tutorsPurchases.tutorId, tutorId),
          sql`${schema.tutorsPurchases.creationDate} >= ${startDate}`,
          sql`${schema.tutorsPurchases.creationDate} <= ${endDate}`
        ))
        .orderBy(schema.tutorsPurchases.creationDate);

      const months = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
        'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];

      const orders = sales.map(s => ({
        orderId: s.id,
        courseId: s.courseId,
        qty: s.qty,
        price: Number(s.price),
        total: s.qty * Number(s.price),
      }));

      const grandTotal = orders.reduce((sum, o) => sum + o.total, 0);

      res.json({
        tutor: {
          id: tutor.id,
          businessName: tutor.businessName,
          address: tutor.address,
          city: tutor.city,
          vatNumber: tutor.vatNumber,
          email: tutor.email,
        },
        period: { month, year, monthName: months[month - 1] || '', label: `${months[month - 1] || ''} ${year}` },
        orders,
        totalSales: orders.length,
        grandTotal,
        generatedAt: new Date().toISOString(),
      });
    } catch (error) {
      console.error("Invoice generate error:", error);
      res.status(500).json({ error: "Failed to generate invoice" });
    }
  });

  // POST /api/invoices — salva fattura
  app.post("/api/invoices", isAuthenticated, async (req, res) => {
    try {
      const { tutorId, tutorName, month, year, orderIds, totalAmount } = req.body;

      if (!tutorId || !month || !year) {
        return res.status(400).json({ error: "Dati mancanti" });
      }

      const existing = await db.select().from(schema.invoices)
        .where(and(
          eq(schema.invoices.tutorCompanyId, tutorId),
          eq(schema.invoices.monthReference, month),
          eq(schema.invoices.yearReference, year)
        ))
        .limit(1);

      if (existing.length > 0) {
        return res.status(409).json({ error: "Fattura già esistente per questo periodo" });
      }

      const yearInvoices = await db.select({ count: sql<number>`count(*)` })
        .from(schema.invoices).where(eq(schema.invoices.invoiceYear, year));
      const nextNum = Number(yearInvoices[0]?.count ?? 0) + 1;

      const [tutor] = await db.select().from(schema.tutors).where(eq(schema.tutors.id, tutorId)).limit(1);

      const [newInvoice] = await db.insert(schema.invoices).values({
        invoiceNumber: nextNum,
        invoiceYear: year,
        tutorCompanyId: tutorId,
        tutorCompanyName: tutorName || tutor?.businessName || '',
        tutorVatNumber: tutor?.vatNumber || null,
        tutorAddress: tutor?.address || null,
        tutorCap: tutor?.cap || null,
        tutorEmail: tutor?.email || null,
        monthReference: month,
        yearReference: year,
        totalAmount: String(totalAmount || 0),
        orderIds: orderIds || '',
        status: "draft",
      }).returning();

      res.status(201).json(newInvoice);
    } catch (error) {
      console.error("Invoice create error:", error);
      res.status(500).json({ error: "Failed to create invoice" });
    }
  });

  // DELETE /api/invoices/:id
  app.delete("/api/invoices/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid invoice ID" });

      await db.delete(schema.invoices).where(eq(schema.invoices.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Invoice delete error:", error);
      res.status(500).json({ error: "Failed to delete invoice" });
    }
  });
}
