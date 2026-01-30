import type { Express } from "express";
import { createServer, type Server } from "http";
import { setupAuth, registerAuthRoutes, isAuthenticated } from "./replit_integrations/auth";
import { db } from "./db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql, ilike, or } from "drizzle-orm";
import { z } from "zod";

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {
  await setupAuth(app);
  registerAuthRoutes(app);

  app.get("/api/stats", isAuthenticated, async (req, res) => {
    try {
      const [tutorsResult] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.companies)
        .where(eq(schema.companies.isTutor, true));
      
      const [clientsResult] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.companies)
        .where(eq(schema.companies.isTutor, false));
      
      const [salesResult] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.tutorsPurchases);
      
      const [usersResult] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.users);

      res.json({
        tutors: Number(tutorsResult?.count ?? 0),
        clients: Number(clientsResult?.count ?? 0),
        sales: Number(salesResult?.count ?? 0),
        users: Number(usersResult?.count ?? 0),
      });
    } catch (error) {
      console.error("Stats error:", error);
      res.json({ tutors: 0, clients: 0, sales: 0, users: 0 });
    }
  });

  app.get("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      const tutors = await db.select()
        .from(schema.companies)
        .where(eq(schema.companies.isTutor, true))
        .orderBy(schema.companies.businessName);
      
      res.json(tutors);
    } catch (error) {
      console.error("Tutors error:", error);
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  app.get("/api/clients", isAuthenticated, async (req, res) => {
    try {
      const search = req.query.search as string | undefined;
      
      let query = db.select()
        .from(schema.companies)
        .where(eq(schema.companies.isTutor, false))
        .orderBy(schema.companies.businessName);
      
      if (search) {
        const clients = await db.select()
          .from(schema.companies)
          .where(and(
            eq(schema.companies.isTutor, false),
            or(
              ilike(schema.companies.businessName, `%${search}%`),
              ilike(schema.companies.city, `%${search}%`)
            )
          ))
          .orderBy(schema.companies.businessName);
        return res.json(clients);
      }
      
      const clients = await query;
      res.json(clients);
    } catch (error) {
      console.error("Clients error:", error);
      res.status(500).json({ error: "Failed to fetch clients" });
    }
  });

  app.post("/api/companies", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertCompanySchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }
      const companyData = result.data;
      
      const [newCompany] = await db.insert(schema.companies).values({
        businessName: companyData.businessName,
        address: companyData.address || null,
        city: companyData.city || null,
        cap: companyData.cap || null,
        province: companyData.province || null,
        vatNumber: companyData.vatNumber || null,
        fiscalCode: companyData.fiscalCode || null,
        phone: companyData.phone || null,
        email: companyData.email || null,
        pec: companyData.pec || null,
        website: companyData.website || null,
        regionalAuthorization: companyData.regionalAuthorization || null,
        licenseType: companyData.licenseType || null,
        isTutor: companyData.isTutor || false,
        contactPerson: companyData.contactPerson || null,
        notes: companyData.notes || null,
      }).returning();

      res.status(201).json(newCompany);
    } catch (error) {
      console.error("Create company error:", error);
      res.status(500).json({ error: "Failed to create company" });
    }
  });

  app.get("/api/catalog", isAuthenticated, async (req, res) => {
    try {
      const courses = await db.select()
        .from(schema.learningProjects)
        .where(eq(schema.learningProjects.isPublished, true))
        .orderBy(schema.learningProjects.title);
      
      res.json(courses);
    } catch (error) {
      console.error("Catalog error:", error);
      res.json([]);
    }
  });

  app.post("/api/catalog", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertLearningProjectSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }
      const courseData = result.data;
      
      const [newCourse] = await db.insert(schema.learningProjects).values({
        title: courseData.title,
        description: courseData.description || null,
        category: courseData.category || null,
        riskLevel: courseData.riskLevel || null,
        sector: courseData.sector || null,
        language: courseData.language || 'IT',
        hours: courseData.hours || 0,
        modality: courseData.modality || 'Online',
        listPrice: courseData.listPrice || '0',
        tutorCost: courseData.tutorCost || '0',
        isPublished: courseData.isPublished ?? true,
      }).returning();

      res.status(201).json(newCourse);
    } catch (error) {
      console.error("Create course error:", error);
      res.status(500).json({ error: "Failed to create course" });
    }
  });

  app.get("/api/sales", isAuthenticated, async (req, res) => {
    try {
      const sales = await db.select({
        id: schema.tutorsPurchases.id,
        date: schema.tutorsPurchases.createdAt,
        qty: schema.tutorsPurchases.qta,
        listPrice: schema.tutorsPurchases.price,
        status: schema.tutorsPurchases.status,
        clientId: schema.tutorsPurchases.customerCompanyId,
        courseId: schema.tutorsPurchases.learningProjectId,
        tutorId: schema.tutorsPurchases.tutorId,
      })
        .from(schema.tutorsPurchases)
        .orderBy(desc(schema.tutorsPurchases.createdAt))
        .limit(1000);
      
      const enrichedSales = await Promise.all(sales.map(async (sale) => {
        let client = 'N/A';
        let course = 'N/A';
        let tutorName = 'N/A';
        
        if (sale.clientId) {
          const [clientData] = await db.select({ name: schema.companies.businessName })
            .from(schema.companies)
            .where(eq(schema.companies.id, sale.clientId));
          client = clientData?.name || 'N/A';
        }
        
        if (sale.courseId) {
          const [courseData] = await db.select({ title: schema.learningProjects.title })
            .from(schema.learningProjects)
            .where(eq(schema.learningProjects.id, sale.courseId));
          course = courseData?.title || 'N/A';
        }
        
        if (sale.tutorId) {
          const [tutorData] = await db.select({ name: schema.companies.businessName })
            .from(schema.companies)
            .where(eq(schema.companies.id, sale.tutorId));
          tutorName = tutorData?.name || 'N/A';
        }
        
        return {
          id: sale.id,
          user: 'N/A',
          client,
          date: sale.date,
          course,
          qty: sale.qty,
          listPrice: parseFloat(String(sale.listPrice || 0)),
          tutorName,
        };
      }));

      res.json(enrichedSales);
    } catch (error) {
      console.error("Sales error:", error);
      res.json([]);
    }
  });

  app.get("/api/platform-users", isAuthenticated, async (req, res) => {
    try {
      const users = await db.select()
        .from(schema.users)
        .orderBy(desc(schema.users.createdAt))
        .limit(500);
      
      res.json(users);
    } catch (error) {
      console.error("Users error:", error);
      res.json([]);
    }
  });

  app.get("/api/certificates", isAuthenticated, async (req, res) => {
    try {
      const certificates = await db.select()
        .from(schema.certificates)
        .orderBy(desc(schema.certificates.completedAt))
        .limit(500);
      
      res.json(certificates);
    } catch (error) {
      console.error("Certificates error:", error);
      res.json([]);
    }
  });

  async function seedSampleData() {
    const existingCourses = await db.select().from(schema.learningProjects).limit(1);
    
    if (existingCourses.length === 0) {
      console.log("Seeding sample courses...");
      
      await db.insert(schema.learningProjects).values([
        {
          title: "Sicurezza sul lavoro - Formazione Generale",
          description: "Corso base sulla sicurezza nei luoghi di lavoro",
          category: "SICUREZZA",
          riskLevel: "basso",
          sector: "Tutti",
          hours: 4,
          modality: "Online",
          listPrice: "50.00",
          tutorCost: "25.00",
        },
        {
          title: "HACCP - Alimentaristi",
          description: "Corso di formazione per operatori alimentari",
          category: "HACCP",
          riskLevel: "medio",
          sector: "Alimentare",
          hours: 8,
          modality: "Online",
          listPrice: "80.00",
          tutorCost: "40.00",
        },
        {
          title: "Primo Soccorso - Gruppo B/C",
          description: "Formazione addetti primo soccorso",
          category: "SICUREZZA",
          riskLevel: "alto",
          sector: "Tutti",
          hours: 12,
          modality: "Aula",
          listPrice: "150.00",
          tutorCost: "75.00",
        },
        {
          title: "Antincendio - Rischio Basso",
          description: "Corso per addetti antincendio",
          category: "SICUREZZA",
          riskLevel: "basso",
          sector: "Uffici",
          hours: 4,
          modality: "Online",
          listPrice: "60.00",
          tutorCost: "30.00",
        },
        {
          title: "Privacy GDPR",
          description: "Formazione sulla protezione dei dati personali",
          category: "INFORMATICA",
          riskLevel: "basso",
          sector: "Tutti",
          hours: 2,
          modality: "Online",
          listPrice: "40.00",
          tutorCost: "20.00",
        },
      ]);
      
      console.log("Sample courses seeded!");
    }
  }

  seedSampleData().catch(console.error);

  return httpServer;
}
