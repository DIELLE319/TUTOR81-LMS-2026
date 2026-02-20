import type { Express } from "express";
import { isAuthenticated } from "../auth";
import { db } from "../db";
import * as schema from "@shared/schema";
import { eq, sql } from "drizzle-orm";
import { getAuthenticatedUserTutorId, getAuthenticatedDbUser, getPlatformTutorIdsCached } from "./helpers";

export function registerCompaniesRoutes(app: Express) {
  // GET /api/clients — lista aziende raggruppate per tutor
  app.get("/api/clients", isAuthenticated, async (req, res) => {
    try {
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);

      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }

      const tutorIdFilter = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);

      let query = db.select({
        id: schema.companies.id,
        businessName: schema.companies.businessName,
        city: schema.companies.city,
        email: schema.companies.email,
        phone: schema.companies.phone,
        address: schema.companies.address,
        vatNumber: schema.companies.vatNumber,
        tutorId: schema.companies.tutorId,
        tutorName: schema.tutors.businessName,
      })
        .from(schema.companies)
        .leftJoin(schema.tutors, eq(schema.companies.tutorId, schema.tutors.id));

      let companies = tutorIdFilter
        ? await query.where(eq(schema.companies.tutorId, tutorIdFilter)).orderBy(schema.companies.businessName)
        : await query.orderBy(schema.tutors.businessName, schema.companies.businessName);

      const tutorMap = new Map<number | null, { tutorId: number | null; tutorName: string; clients: any[] }>();

      for (const c of companies) {
        const key = c.tutorId;
        if (!tutorMap.has(key)) {
          tutorMap.set(key, { tutorId: c.tutorId, tutorName: c.tutorName || 'Senza Ente', clients: [] });
        }
        tutorMap.get(key)!.clients.push({
          id: c.id,
          businessName: c.businessName,
          city: c.city,
          email: c.email,
          phone: c.phone,
          address: c.address,
          vatNumber: c.vatNumber,
        });
      }

      const tutorGroups: any[] = [];
      tutorMap.forEach(group => tutorGroups.push(group));
      res.json(tutorGroups);
    } catch (error) {
      console.error("Clients error:", error);
      res.status(500).json({ error: "Failed to fetch clients" });
    }
  });

  // GET /api/me/company — la propria azienda (Company admin)
  app.get("/api/me/company", isAuthenticated, async (req, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      if (!dbUser) return res.status(401).json({ error: "Unauthorized" });

      if (dbUser.role !== 2 || !dbUser.idcompany) {
        return res.status(403).json({ error: "Access denied" });
      }

      const [company] = await db.select().from(schema.companies)
        .where(eq(schema.companies.id, dbUser.idcompany)).limit(1);

      if (!company) return res.status(404).json({ error: "Company not found" });
      res.json(company);
    } catch (error) {
      console.error("Me company error:", error);
      res.status(500).json({ error: "Failed to fetch company" });
    }
  });

  // GET /api/me/company/users — utenti della propria azienda
  app.get("/api/me/company/users", isAuthenticated, async (req, res) => {
    try {
      const dbUser = await getAuthenticatedDbUser(req);
      if (!dbUser) return res.status(401).json({ error: "Unauthorized" });

      if (dbUser.role !== 2 || !dbUser.idcompany) {
        return res.status(403).json({ error: "Access denied" });
      }

      const students = await db.select({
        id: schema.students.id,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        email: schema.students.email,
        fiscalCode: schema.students.fiscalCode,
        companyId: schema.students.companyId,
        isActive: schema.students.isActive,
      })
        .from(schema.students)
        .where(eq(schema.students.companyId, dbUser.idcompany))
        .orderBy(schema.students.lastName, schema.students.firstName);

      res.json(students);
    } catch (error) {
      console.error("Me company users error:", error);
      res.status(500).json({ error: "Failed to fetch company users" });
    }
  });

  // GET /api/companies — lista aziende (filtrate per ruolo)
  app.get("/api/companies", isAuthenticated, async (req, res) => {
    try {
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);

      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }

      const tutorId = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      const search = (req.query.search as string) || "";

      const companies = await db.select({
        id: schema.companies.id,
        tutorId: schema.companies.tutorId,
        businessName: schema.companies.businessName,
        vatNumber: schema.companies.vatNumber,
        address: schema.companies.address,
        city: schema.companies.city,
        email: schema.companies.email,
        phone: schema.companies.phone,
        isActive: schema.companies.isActive,
        createdAt: schema.companies.createdAt,
        tutorName: schema.tutors.businessName,
      })
        .from(schema.companies)
        .leftJoin(schema.tutors, eq(schema.companies.tutorId, schema.tutors.id))
        .orderBy(schema.companies.businessName);

      let filtered = companies;
      if (tutorId) {
        filtered = filtered.filter(c => c.tutorId === tutorId);
      }
      if (search) {
        const searchLower = search.toLowerCase();
        filtered = filtered.filter(c => c.businessName.toLowerCase().includes(searchLower));
      }

      res.json(filtered);
    } catch (error) {
      console.error("Companies error:", error);
      res.status(500).json({ error: "Failed to fetch companies" });
    }
  });

  // GET /api/companies/tutors — lista enti formativi per dropdown
  app.get("/api/companies/tutors", isAuthenticated, async (req, res) => {
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

  // GET /api/companies/:id — dettaglio azienda
  app.get("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid company ID" });

      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      const dbUser = role === 2 ? await getAuthenticatedDbUser(req) : null;

      if (role === 2) {
        if (!dbUser?.idcompany || dbUser.idcompany !== id) {
          return res.status(403).json({ error: "Access denied" });
        }
      }

      const [company] = await db.select().from(schema.companies).where(eq(schema.companies.id, id));
      if (!company) return res.status(404).json({ error: "Company not found" });

      if (role === 1 && authTutorId && company.tutorId !== authTutorId) {
        return res.status(403).json({ error: "Access denied" });
      }

      res.json(company);
    } catch (error) {
      console.error("Company error:", error);
      res.status(500).json({ error: "Failed to fetch company" });
    }
  });

  // GET /api/companies/:id/users — utenti di un'azienda
  app.get("/api/companies/:id/users", isAuthenticated, async (req, res) => {
    try {
      const companyId = parseInt(req.params.id as string);
      if (isNaN(companyId)) return res.status(400).json({ error: "Invalid company ID" });

      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      const dbUser = role === 2 ? await getAuthenticatedDbUser(req) : null;

      if (role === 2) {
        if (!dbUser?.idcompany || dbUser.idcompany !== companyId) {
          return res.status(403).json({ error: "Access denied" });
        }
      }

      if (role === 1) {
        if (!authTutorId) {
          return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
        }
        const [company] = await db.select({ id: schema.companies.id, tutorId: schema.companies.tutorId })
          .from(schema.companies).where(eq(schema.companies.id, companyId)).limit(1);
        if (!company) return res.status(404).json({ error: "Company not found" });
        if (company.tutorId !== authTutorId) {
          return res.status(403).json({ error: "Access denied" });
        }
      }

      const students = await db.select({
        id: schema.students.id,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        email: schema.students.email,
        fiscalCode: schema.students.fiscalCode,
        companyId: schema.students.companyId,
        isActive: schema.students.isActive,
      })
        .from(schema.students)
        .where(eq(schema.students.companyId, companyId))
        .orderBy(schema.students.lastName, schema.students.firstName);

      res.json(students);
    } catch (error) {
      console.error("Company users error:", error);
      res.status(500).json({ error: "Failed to fetch company users" });
    }
  });

  // POST /api/companies — crea azienda
  app.post("/api/companies", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertCompanySchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newCompany] = await db.insert(schema.companies).values(result.data).returning();
      res.status(201).json(newCompany);
    } catch (error) {
      console.error("Create company error:", error);
      res.status(500).json({ error: "Failed to create company" });
    }
  });

  // PUT /api/companies/:id — modifica azienda
  app.put("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid company ID" });

      const [updated] = await db.update(schema.companies)
        .set(req.body)
        .where(eq(schema.companies.id, id))
        .returning();

      if (!updated) return res.status(404).json({ error: "Company not found" });
      res.json(updated);
    } catch (error) {
      console.error("Update company error:", error);
      res.status(500).json({ error: "Failed to update company" });
    }
  });

  // DELETE /api/companies/:id
  app.delete("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id as string);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid company ID" });

      const [hasStudents] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.students).where(eq(schema.students.companyId, id));

      if (Number(hasStudents.count) > 0) {
        return res.status(400).json({ error: "Cannot delete company with associated students" });
      }

      await db.delete(schema.companies).where(eq(schema.companies.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete company error:", error);
      res.status(500).json({ error: "Failed to delete company" });
    }
  });

  // GET /api/companies-list — lista semplificata per dropdown
  app.get("/api/companies-list", isAuthenticated, async (req, res) => {
    try {
      const companiesData = await db.select({
        id: schema.companies.id,
        businessName: schema.companies.businessName,
      }).from(schema.companies).orderBy(schema.companies.businessName);
      res.json(companiesData);
    } catch (error) {
      console.error("Companies-list error:", error);
      res.status(500).json({ error: "Failed to fetch companies list" });
    }
  });

  // GET /api/check-fiscal-code — verifica CF in tempo reale
  app.get("/api/check-fiscal-code", isAuthenticated, async (req, res) => {
    try {
      const fiscalCode = (req.query.fiscalCode as string || '').toUpperCase().trim();
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;

      if (!fiscalCode) {
        return res.json({ exists: false });
      }

      const existingStudent = await db.select({
        id: schema.students.id,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
      })
        .from(schema.students)
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .where(sql`UPPER(${schema.students.fiscalCode}) = ${fiscalCode}`)
        .limit(1);

      if (existingStudent.length > 0) {
        const student = existingStudent[0];
        const sameCompany = companyId ? student.companyId === companyId : false;

        return res.json({
          exists: true,
          sameCompany,
          student: {
            id: student.id,
            firstName: student.firstName,
            lastName: student.lastName,
            companyName: student.companyName,
          },
          message: sameCompany
            ? `Corsista già esistente: ${student.firstName} ${student.lastName} - verrà aggiunta nuova iscrizione`
            : `Corsista già esistente in altra azienda: ${student.companyName}`,
        });
      }

      res.json({ exists: false });
    } catch (error) {
      console.error("Check fiscal code error:", error);
      res.json({ exists: false });
    }
  });
}
