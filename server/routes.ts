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
      
      const tutorsWithAdmins = await Promise.all(tutors.map(async (tutor) => {
        const admins = await db.select({
          id: schema.users.id,
          firstName: schema.users.firstName,
          lastName: schema.users.lastName,
          email: schema.users.email,
        })
          .from(schema.users)
          .where(eq(schema.users.idcompany, tutor.id));
        
        return { ...tutor, admins };
      }));
      
      res.json(tutorsWithAdmins);
    } catch (error) {
      console.error("Tutors error:", error);
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  app.get("/api/clients", isAuthenticated, async (req, res) => {
    try {
      // Get all clients with their tutor relationship from tutors_purchases
      const clientsWithTutors = await db.execute(sql`
        SELECT 
          c.id, c.business_name, c.city, c.email, c.phone, c.address, c.vat_number,
          t.id as tutor_id, t.business_name as tutor_name
        FROM companies c
        LEFT JOIN tutors_purchases tp ON tp.customer_company_id = c.id
        LEFT JOIN companies t ON t.id = tp.tutor_id
        WHERE c.is_tutor = false
        ORDER BY t.business_name NULLS LAST, c.business_name
      `);
      
      // Group by tutor
      const grouped: Record<string, { tutorId: number | null; tutorName: string; clients: any[] }> = {};
      
      for (const row of clientsWithTutors.rows) {
        const tutorKey = row.tutor_id ? String(row.tutor_id) : 'none';
        const tutorName = row.tutor_name as string || 'Senza Ente Formativo';
        
        if (!grouped[tutorKey]) {
          grouped[tutorKey] = {
            tutorId: row.tutor_id as number | null,
            tutorName,
            clients: []
          };
        }
        
        // Avoid duplicates
        if (!grouped[tutorKey].clients.find((c: any) => c.id === row.id)) {
          grouped[tutorKey].clients.push({
            id: row.id,
            businessName: row.business_name,
            city: row.city,
            email: row.email,
            phone: row.phone,
            address: row.address,
            vatNumber: row.vat_number
          });
        }
      }
      
      // Convert to array and sort by tutor name
      const result = Object.values(grouped).sort((a, b) => {
        if (a.tutorId === null) return 1;
        if (b.tutorId === null) return -1;
        return a.tutorName.localeCompare(b.tutorName);
      });
      
      res.json(result);
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

  app.delete("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) {
        return res.status(400).json({ error: "Invalid company ID" });
      }
      
      // Get all users belonging to this company
      const companyUsersList = await db.select({ id: schema.users.id })
        .from(schema.users)
        .where(eq(schema.users.idcompany, id));
      const userIds = companyUsersList.map(u => u.id);
      
      // Delete related records first (cascade manually)
      await db.delete(schema.enrollments).where(eq(schema.enrollments.companyId, id));
      await db.delete(schema.tutorsPurchases).where(eq(schema.tutorsPurchases.tutorId, id));
      await db.delete(schema.tutorsPurchases).where(eq(schema.tutorsPurchases.customerCompanyId, id));
      
      // Delete company_users and certificates for each user
      if (userIds.length > 0) {
        for (const userId of userIds) {
          await db.delete(schema.companyUsers).where(eq(schema.companyUsers.userId, userId));
          await db.delete(schema.certificates).where(eq(schema.certificates.userId, userId));
        }
      }
      
      // Also delete company_users by companyId
      await db.delete(schema.companyUsers).where(eq(schema.companyUsers.companyId, id));
      
      await db.delete(schema.users).where(eq(schema.users.idcompany, id));
      await db.delete(schema.companies).where(eq(schema.companies.id, id));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Delete company error:", error);
      res.status(500).json({ error: "Failed to delete company" });
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
      const tutorIdParam = req.query.tutorId as string | undefined;
      
      let query = db.select({
        id: schema.tutorsPurchases.id,
        date: schema.tutorsPurchases.createdAt,
        qty: schema.tutorsPurchases.qta,
        listPrice: schema.tutorsPurchases.price,
        status: schema.tutorsPurchases.status,
        clientId: schema.tutorsPurchases.customerCompanyId,
        courseId: schema.tutorsPurchases.learningProjectId,
        tutorId: schema.tutorsPurchases.tutorId,
        userCompanyRef: schema.tutorsPurchases.userCompanyRef,
      })
        .from(schema.tutorsPurchases);
      
      let sales;
      if (tutorIdParam) {
        sales = await query
          .where(eq(schema.tutorsPurchases.tutorId, parseInt(tutorIdParam)))
          .orderBy(desc(schema.tutorsPurchases.createdAt))
          .limit(2000);
      } else {
        sales = await query
          .orderBy(desc(schema.tutorsPurchases.createdAt))
          .limit(2000);
      }
      
      const enrichedSales = await Promise.all(sales.map(async (sale) => {
        let client = 'N/A';
        let course = 'N/A';
        let tutorName = 'N/A';
        let user = 'N/A';
        
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
        
        if (sale.userCompanyRef) {
          const [userData] = await db.select({ 
            firstName: schema.users.firstName,
            lastName: schema.users.lastName,
          })
            .from(schema.users)
            .where(eq(schema.users.id, sale.userCompanyRef));
          if (userData) {
            user = `${userData.firstName || ''} ${userData.lastName || ''}`.trim() || 'N/A';
          }
        }
        
        return {
          id: sale.id,
          user,
          client,
          date: sale.date,
          course,
          qty: sale.qty,
          listPrice: parseFloat(String(sale.listPrice || 0)),
          tutorId: sale.tutorId,
          tutorName,
        };
      }));

      res.json(enrichedSales);
    } catch (error) {
      console.error("Sales error:", error);
      res.json([]);
    }
  });

  // Invoice API - get sales summary for a tutor in a specific month
  app.get("/api/invoice", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.query.tutorId as string);
      const month = parseInt(req.query.month as string); // 1-12
      const year = parseInt(req.query.year as string);
      
      if (isNaN(tutorId) || isNaN(month) || isNaN(year)) {
        return res.status(400).json({ error: "tutorId, month and year are required" });
      }
      
      // Get tutor info
      const [tutor] = await db.select()
        .from(schema.companies)
        .where(eq(schema.companies.id, tutorId));
      
      if (!tutor) {
        return res.status(404).json({ error: "Tutor not found" });
      }
      
      // Calculate date range for the month
      const startDate = new Date(year, month - 1, 1);
      const endDate = new Date(year, month, 0, 23, 59, 59);
      
      // Get all sales for this tutor in the specified month
      const sales = await db.execute(sql`
        SELECT 
          tp.id,
          tp.creation_date,
          tp.qta,
          tp.price,
          tp.learning_project_id as course_id,
          c.business_name as client_name,
          c.id as client_id
        FROM tutors_purchases tp
        LEFT JOIN companies c ON c.id = tp.customer_company_id
        WHERE tp.tutor_id = ${tutorId}
          AND tp.creation_date >= ${startDate}
          AND tp.creation_date <= ${endDate}
        ORDER BY tp.id
      `);
      
      // Build list of individual orders
      const orders: Array<{ orderId: number; courseId: number; qty: number; price: number; total: number }> = [];
      let grandTotal = 0;
      
      for (const sale of sales.rows) {
        const orderId = (sale.id as number) || 0;
        const courseId = (sale.course_id as number) || 0;
        const qty = (sale.qta as number) || 1;
        const price = parseFloat(String(sale.price || 0));
        const lineTotal = qty * price;
        
        orders.push({ orderId, courseId, qty, price, total: lineTotal });
        grandTotal += lineTotal;
      }
      
      const monthNames = ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 
                          'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'];
      
      res.json({
        tutor: {
          id: tutor.id,
          businessName: tutor.businessName,
          address: tutor.address,
          city: tutor.city,
          vatNumber: tutor.vatNumber,
          email: tutor.email,
        },
        period: {
          month,
          year,
          monthName: monthNames[month - 1],
          label: `${monthNames[month - 1]} ${year}`
        },
        orders,
        totalSales: sales.rows.length,
        grandTotal,
        generatedAt: new Date().toISOString(),
      });
    } catch (error) {
      console.error("Invoice error:", error);
      res.status(500).json({ error: "Failed to generate invoice" });
    }
  });

  // Save invoice to archive
  app.post("/api/invoices", isAuthenticated, async (req, res) => {
    try {
      const { tutorId, tutorName, month, year, orderIds, totalAmount } = req.body;
      
      // Check if invoice already exists for this tutor/month/year
      const existing = await db.select()
        .from(schema.invoices)
        .where(and(
          eq(schema.invoices.tutorId, tutorId),
          eq(schema.invoices.month, month),
          eq(schema.invoices.year, year)
        ));
      
      if (existing.length > 0) {
        return res.status(400).json({ error: "Fattura già salvata per questo periodo" });
      }
      
      // Get count of invoices for this year to generate progressive number
      const yearInvoices = await db.select()
        .from(schema.invoices)
        .where(eq(schema.invoices.year, year));
      
      const nextNumber = yearInvoices.length + 1;
      const invoiceNumber = `FAT-${year}-${String(nextNumber).padStart(2, '0')}`;
      
      const [invoice] = await db.insert(schema.invoices).values({
        tutorId,
        tutorName,
        month,
        year,
        orderIds,
        totalAmount: totalAmount.toString(),
        invoiceNumber,
      }).returning();
      
      res.json(invoice);
    } catch (error) {
      console.error("Save invoice error:", error);
      res.status(500).json({ error: "Errore nel salvataggio della fattura" });
    }
  });

  // Get saved invoices
  app.get("/api/invoices", isAuthenticated, async (req, res) => {
    try {
      const { tutorId } = req.query;
      
      let query = db.select().from(schema.invoices);
      
      if (tutorId) {
        query = query.where(eq(schema.invoices.tutorId, parseInt(tutorId as string)));
      }
      
      const invoices = await query.orderBy(desc(schema.invoices.createdAt)).limit(100);
      res.json(invoices);
    } catch (error) {
      console.error("Get invoices error:", error);
      res.json([]);
    }
  });

  // Delete invoice
  app.delete("/api/invoices/:id", isAuthenticated, async (req, res) => {
    try {
      const { id } = req.params;
      await db.delete(schema.invoices).where(eq(schema.invoices.id, parseInt(id)));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete invoice error:", error);
      res.status(500).json({ error: "Errore nella cancellazione" });
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

  app.get("/api/enrollments", isAuthenticated, async (req, res) => {
    try {
      const statusFilter = req.query.status as string | undefined;
      const companyFilter = req.query.companyId as string | undefined;
      const search = req.query.search as string | undefined;

      const enrollments = await db.select({
        id: schema.enrollments.id,
        userId: schema.enrollments.userId,
        companyId: schema.enrollments.companyId,
        learningProjectId: schema.enrollments.learningProjectId,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        lastAccessAt: schema.enrollments.lastAccessAt,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
      })
        .from(schema.enrollments)
        .orderBy(desc(schema.enrollments.createdAt))
        .limit(500);

      const enrichedEnrollments = await Promise.all(enrollments.map(async (e) => {
        let companyName = '';
        let userName = '';
        let userEmail = '';
        let courseName = '';

        if (e.companyId) {
          const [company] = await db.select({ name: schema.companies.businessName })
            .from(schema.companies)
            .where(eq(schema.companies.id, e.companyId));
          companyName = company?.name || '';
        }

        if (e.userId) {
          const [user] = await db.select({ 
            firstName: schema.users.firstName,
            lastName: schema.users.lastName,
            email: schema.users.email,
          })
            .from(schema.users)
            .where(eq(schema.users.id, e.userId));
          if (user) {
            userName = `${user.lastName || ''} ${user.firstName || ''}`.trim();
            userEmail = user.email || '';
          }
        }

        if (e.learningProjectId) {
          const [course] = await db.select({ title: schema.learningProjects.title })
            .from(schema.learningProjects)
            .where(eq(schema.learningProjects.id, e.learningProjectId));
          courseName = course?.title || '';
        }

        return {
          id: e.id,
          companyName,
          userName,
          userEmail,
          courseName,
          startDate: e.startDate,
          endDate: e.endDate,
          lastAccessAt: e.lastAccessAt,
          progress: e.progress || 0,
          status: e.status || 'not_started',
        };
      }));

      let filtered = enrichedEnrollments;
      
      if (statusFilter === 'active') {
        filtered = filtered.filter(e => e.progress > 0 && e.progress < 100);
      } else if (statusFilter === 'not_started') {
        filtered = filtered.filter(e => e.progress === 0);
      }
      
      if (companyFilter) {
        const companyId = parseInt(companyFilter);
        filtered = filtered.filter(e => e.companyName.toLowerCase().includes(companyFilter.toLowerCase()));
      }
      
      if (search) {
        const s = search.toLowerCase();
        filtered = filtered.filter(e => 
          e.userName.toLowerCase().includes(s) ||
          e.userEmail.toLowerCase().includes(s) ||
          e.companyName.toLowerCase().includes(s) ||
          e.courseName.toLowerCase().includes(s)
        );
      }

      res.json(filtered);
    } catch (error) {
      console.error("Enrollments error:", error);
      res.json([]);
    }
  });

  app.get("/api/companies-list", isAuthenticated, async (req, res) => {
    try {
      const companies = await db.select({ 
        id: schema.companies.id, 
        businessName: schema.companies.businessName 
      })
        .from(schema.companies)
        .where(eq(schema.companies.isTutor, false))
        .orderBy(schema.companies.businessName);
      res.json(companies);
    } catch (error) {
      console.error("Companies list error:", error);
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
