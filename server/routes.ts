import type { Express } from "express";
import { createServer, type Server } from "http";
import { Writable } from "stream";
import { setupAuth, registerAuthRoutes, isAuthenticated } from "./replit_integrations/auth";
import { db } from "./db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql, ilike, or, isNotNull } from "drizzle-orm";
import { z } from "zod";
import * as ftp from "basic-ftp";
import { Resend } from "resend";

const resend = new Resend(process.env.RESEND_API_KEY);

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
          role: schema.users.role,
        })
          .from(schema.users)
          .where(and(
            eq(schema.users.idcompany, tutor.id),
            sql`${schema.users.role} > 0`
          ));
        
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

  app.get("/api/companies", isAuthenticated, async (req, res) => {
    try {
      // Filter companies under Tutor81 (parent_company_id = 2) and exclude tutors
      const allCompanies = await db.select().from(schema.companies)
        .where(and(
          eq(schema.companies.parentCompanyId, 2),
          eq(schema.companies.isTutor, false)
        ))
        .orderBy(schema.companies.businessName);
      res.json(allCompanies);
    } catch (error) {
      console.error("Error fetching companies:", error);
      res.status(500).json({ error: "Failed to fetch companies" });
    }
  });

  app.get("/api/companies/:id/users", isAuthenticated, async (req, res) => {
    try {
      const idParam = req.params.id as string;
      const companyId = parseInt(idParam);
      if (isNaN(companyId)) {
        return res.status(400).json({ error: "Invalid company ID" });
      }
      
      const companyUsers = await db.select({
        id: schema.users.id,
        firstName: schema.users.firstName,
        lastName: schema.users.lastName,
        fiscalCode: schema.users.fiscalCode,
        email: schema.users.email,
        role: schema.users.role,
      })
      .from(schema.users)
      .where(eq(schema.users.idcompany, companyId))
      .orderBy(schema.users.lastName);
      
      res.json(companyUsers);
    } catch (error) {
      console.error("Error fetching company users:", error);
      res.status(500).json({ error: "Failed to fetch company users" });
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
      const id = parseInt(req.params.id as string);
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

  app.get("/api/learning-projects", isAuthenticated, async (req, res) => {
    try {
      const projects = await db.select().from(schema.learningProjects);
      res.json(projects);
    } catch (error) {
      console.error("Learning projects error:", error);
      res.status(500).json({ error: "Failed to fetch learning projects" });
    }
  });

  // Struttura gerarchica di un corso (moduli > lezioni > learning objects)
  app.get("/api/learning-projects/:id/structure", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const useLegacy = req.query.legacy === 'true';
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID corso non valido" });
      }

      // Ottieni info del learning project
      const [project] = await db.select().from(schema.learningProjects).where(eq(schema.learningProjects.id, projectId));
      
      // Cerca per legacy_course_id o learning_project_id
      const searchId = useLegacy ? projectId : (project?.legacyCourseId || projectId);

      // Query per ottenere la struttura completa
      const structure = await db.execute(sql`
        SELECT 
          cm.id as course_module_id,
          cm.position as module_position,
          cm.legacy_course_id,
          cm.learning_project_id,
          m.id as module_id,
          m.title as module_title,
          m.description as module_description,
          m.duration as module_duration,
          ml.id as module_lesson_id,
          ml.position as lesson_position,
          l.id as lesson_id,
          l.title as lesson_title,
          l.duration as lesson_duration,
          llo.id as lesson_lo_id,
          llo.position as lo_position,
          lo.id as lo_id,
          lo.title as lo_title,
          lo.type as lo_type,
          lo.duration as lo_duration
        FROM course_modules cm
        JOIN modules m ON m.id = cm.module_id
        LEFT JOIN module_lessons ml ON ml.module_id = m.id
        LEFT JOIN lessons l ON l.id = ml.lesson_id
        LEFT JOIN lesson_learning_objects llo ON llo.lesson_id = l.id
        LEFT JOIN learning_objects lo ON lo.id = llo.learning_object_id
        WHERE cm.legacy_course_id = ${searchId} OR cm.learning_project_id = ${projectId}
        ORDER BY cm.position, ml.position, llo.position
      `);

      // Trasforma in struttura gerarchica
      const modulesMap = new Map<number, any>();
      
      for (const row of structure.rows) {
        const moduleId = row.module_id as number;
        
        if (!modulesMap.has(moduleId)) {
          modulesMap.set(moduleId, {
            id: moduleId,
            title: row.module_title,
            description: row.module_description,
            duration: row.module_duration,
            position: row.module_position,
            lessons: new Map()
          });
        }
        
        const module = modulesMap.get(moduleId);
        const lessonId = row.lesson_id as number;
        
        if (lessonId && !module.lessons.has(lessonId)) {
          module.lessons.set(lessonId, {
            id: lessonId,
            title: row.lesson_title,
            duration: row.lesson_duration,
            position: row.lesson_position,
            learningObjects: []
          });
        }
        
        if (lessonId && row.lo_id) {
          const lesson = module.lessons.get(lessonId);
          const loExists = lesson.learningObjects.some((lo: any) => lo.id === row.lo_id);
          if (!loExists) {
            lesson.learningObjects.push({
              id: row.lo_id,
              title: row.lo_title,
              type: row.lo_type,
              duration: row.lo_duration,
              position: row.lo_position
            });
          }
        }
      }
      
      // Converti in array
      const result = Array.from(modulesMap.values()).map(module => ({
        ...module,
        lessons: Array.from(module.lessons.values())
      }));

      res.json({
        projectId,
        project: project || null,
        modules: result,
        stats: {
          totalModules: result.length,
          totalLessons: result.reduce((sum, m) => sum + m.lessons.length, 0),
          totalLearningObjects: result.reduce((sum, m) => 
            sum + m.lessons.reduce((lsum: number, l: any) => lsum + l.learningObjects.length, 0), 0)
        }
      });
    } catch (error) {
      console.error("Course structure error:", error);
      res.status(500).json({ error: "Errore nel recupero della struttura" });
    }
  });

  // Pubblica un learning project (stato 1 = attivo)
  app.post("/api/learning-projects/:id/publish", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ isPublishedInEcommerce: 1, isPublished: true })
        .where(eq(schema.learningProjects.id, projectId));

      res.json({ success: true, message: "Corso pubblicato" });
    } catch (error) {
      console.error("Publish project error:", error);
      res.status(500).json({ error: "Errore durante la pubblicazione" });
    }
  });

  // Rimuovi dalla pubblicazione (stato 0 = non pubblicato)
  app.post("/api/learning-projects/:id/unpublish", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ isPublishedInEcommerce: 0, isPublished: false })
        .where(eq(schema.learningProjects.id, projectId));

      res.json({ success: true, message: "Corso rimosso dalla pubblicazione" });
    } catch (error) {
      console.error("Unpublish project error:", error);
      res.status(500).json({ error: "Errore durante la rimozione dalla pubblicazione" });
    }
  });

  // Sospendi un corso (stato 2 = sospeso)
  app.post("/api/learning-projects/:id/suspend", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ isPublishedInEcommerce: 2 })
        .where(eq(schema.learningProjects.id, projectId));

      res.json({ success: true, message: "Corso sospeso" });
    } catch (error) {
      console.error("Suspend project error:", error);
      res.status(500).json({ error: "Errore durante la sospensione" });
    }
  });

  app.get("/api/catalog", isAuthenticated, async (req, res) => {
    try {
      const courses = await db.select()
        .from(schema.learningProjects)
        .where(eq(schema.learningProjects.isPublishedInEcommerce, 1)) // 1 = attivo
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
      
      let query = db.select().from(schema.invoices).$dynamic();
      
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
      const idStr = req.params.id as string;
      const id = parseInt(idStr);
      await db.delete(schema.invoices).where(eq(schema.invoices.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete invoice error:", error);
      res.status(500).json({ error: "Errore nella cancellazione" });
    }
  });

  app.get("/api/platform-users", isAuthenticated, async (req, res) => {
    try {
      const users = await db.select({
        id: schema.users.id,
        email: schema.users.email,
        firstName: schema.users.firstName,
        lastName: schema.users.lastName,
        profileImageUrl: schema.users.profileImageUrl,
        role: schema.users.role,
        idcompany: schema.users.idcompany,
        fiscalCode: schema.users.fiscalCode,
        phone: schema.users.phone,
        createdAt: schema.users.createdAt,
      })
        .from(schema.users)
        .orderBy(desc(schema.users.createdAt))
        .limit(2000);
      
      // Get companies to map
      const companies = await db.select().from(schema.companies);
      const companyMap = new Map(companies.map(c => [c.id, c]));
      
      // Enrich with company data
      const enrichedUsers = users.map(u => {
        const company = u.idcompany ? companyMap.get(u.idcompany) : null;
        const tutorCompany = company?.parentCompanyId ? companyMap.get(company.parentCompanyId) : null;
        return {
          ...u,
          companyName: company?.businessName || null,
          tutorName: tutorCompany?.businessName || (company?.isTutor ? company.businessName : null),
        };
      });
      
      res.json(enrichedUsers);
    } catch (error) {
      console.error("Users error:", error);
      res.json([]);
    }
  });

  // Update user
  app.patch("/api/users/:id", isAuthenticated, async (req, res) => {
    try {
      const idStr = req.params.id as string;
      const { firstName, lastName, email, fiscalCode, phone, role, idcompany } = req.body;
      
      await db.update(schema.users)
        .set({
          firstName,
          lastName,
          email,
          fiscalCode,
          phone,
          role,
          idcompany,
          updatedAt: new Date(),
        })
        .where(eq(schema.users.id, idStr));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update user error:", error);
      res.status(500).json({ error: "Errore aggiornamento utente" });
    }
  });

  // Get user enrollments (from tutors_purchases linked to user's company)
  app.get("/api/user-enrollments", isAuthenticated, async (req, res) => {
    try {
      const userId = req.query.userId as string;
      if (!userId) return res.json([]);
      
      // Get user's company
      const user = await db.select().from(schema.users).where(eq(schema.users.id, userId)).limit(1);
      if (!user.length || !user[0].idcompany) return res.json([]);
      
      // Get purchases for user's company
      const purchases = await db.select({
        id: schema.tutorsPurchases.id,
        courseTitle: schema.learningProjects.title,
        startDate: schema.tutorsPurchases.startDate,
        endDate: schema.tutorsPurchases.endDate,
        status: schema.tutorsPurchases.status,
      })
        .from(schema.tutorsPurchases)
        .leftJoin(schema.learningProjects, eq(schema.tutorsPurchases.learningProjectId, schema.learningProjects.id))
        .where(eq(schema.tutorsPurchases.customerCompanyId, user[0].idcompany))
        .orderBy(desc(schema.tutorsPurchases.startDate))
        .limit(50);
      
      const enrollments = purchases.map(p => ({
        id: p.id,
        courseTitle: p.courseTitle || 'Corso senza titolo',
        startDate: p.startDate,
        status: p.status,
        completedAt: p.status === 'completed' ? p.endDate : null,
      }));
      
      res.json(enrollments);
    } catch (error) {
      console.error("User enrollments error:", error);
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

      // Get all enrollments ordered by most recent
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
        emailSentAt: schema.enrollments.emailSentAt,
        emailOpenedAt: schema.enrollments.emailOpenedAt,
      })
        .from(schema.enrollments)
        .orderBy(desc(schema.enrollments.id))
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
          emailSentAt: e.emailSentAt,
          emailOpenedAt: e.emailOpenedAt,
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

  // Activate course - create enrollments and send emails
  app.post("/api/enrollments/activate", isAuthenticated, async (req, res) => {
    try {
      const { courseId, companyId, corsisti } = req.body as {
        courseId: number;
        companyId: number;
        corsisti: Array<{
          email: string;
          startDate: string;
          endDate: string;
          daysToAlert: number;
          lastName: string;
          firstName: string;
          fiscalCode: string;
          userType: string;
        }>;
      };

      if (!courseId || !companyId || !corsisti || corsisti.length === 0) {
        return res.status(400).json({ message: "Dati mancanti" });
      }

      const now = new Date();
      let createdCount = 0;

      for (const corsista of corsisti) {
        // Check if user exists or create new one
        let [existingUser] = await db.select()
          .from(schema.users)
          .where(eq(schema.users.email, corsista.email.toLowerCase()));

        let usrId: string;

        if (existingUser) {
          usrId = existingUser.id;
        } else {
          // Create new user
          const [newUser] = await db.insert(schema.users)
            .values({
              email: corsista.email.toLowerCase(),
              firstName: corsista.firstName,
              lastName: corsista.lastName,
              fiscalCode: corsista.fiscalCode.toUpperCase(),
              idcompany: companyId,
              role: 0, // Lavoratore
            })
            .returning();
          usrId = newUser.id;
        }

        // Get course info for email
        const [course] = await db.select()
          .from(schema.learningProjects)
          .where(eq(schema.learningProjects.id, courseId));

        // Create enrollment
        const trackingId = `track_${usrId}_${courseId}_${Date.now()}`;
        const [enrollment] = await db.insert(schema.enrollments)
          .values({
            userId: usrId,
            companyId: companyId,
            learningProjectId: courseId,
            startDate: new Date(corsista.startDate),
            endDate: new Date(corsista.endDate),
            daysToAlert: corsista.daysToAlert,
            status: 'active',
            emailTrackingId: trackingId,
          })
          .returning();

        // Send email with Resend
        try {
          const appUrl = process.env.REPLIT_DEV_DOMAIN 
            ? `https://${process.env.REPLIT_DEV_DOMAIN}`
            : 'https://tutor81.replit.app';
          
          const trackingPixelUrl = `${appUrl}/api/email-track/${trackingId}`;
          const courseTitle = course?.title || 'Corso';
          
          await resend.emails.send({
            from: 'Tutor81 <corsi@tutor81.it>',
            to: corsista.email,
            subject: `Accesso al corso: ${courseTitle}`,
            html: `
              <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f5f5f5;">
                
                <!-- Header -->
                <div style="background-color: #1a365d; padding: 25px; text-align: center;">
                  <h1 style="color: #EAB308; margin: 0; font-size: 28px;">TUTOR81</h1>
                  <p style="color: #a0c4e8; margin: 15px 0 0 0; font-size: 16px;">Devi svolgere un corso obbligatorio</p>
                </div>
                
                <!-- Titolo corso -->
                <div style="padding: 25px; text-align: center;">
                  <p style="color: #1a365d; margin: 0; font-size: 14px;">Licenza per il corso:</p>
                  <h2 style="color: #0ea5e9; margin: 10px 0 0 0; font-size: 20px; font-weight: bold;">${courseTitle}</h2>
                </div>
                
                <!-- Box informazioni -->
                <div style="background-color: #ffffff; margin: 0 25px; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                  <p style="color: #333; font-size: 16px; margin: 0 0 35px 0;">
                    Buongiorno <span style="color: #0ea5e9; font-weight: bold;">${corsista.lastName} ${corsista.firstName}</span>,
                  </p>
                  
                  <p style="color: #333; font-size: 15px; margin: 0 0 30px 0; line-height: 2.2;">
                    Sei stato iscritto al seguente corso: <span style="color: #0ea5e9; font-weight: bold;">${courseTitle}</span>
                  </p>
                  
                  <p style="color: #333; font-size: 15px; margin: 0 0 25px 0; line-height: 2.2;">
                    Potrai iniziare a partire dal giorno: <span style="color: #ef4444; font-weight: bold;">${new Date(corsista.startDate).toLocaleDateString('it-IT')}</span>
                  </p>
                  
                  <p style="color: #333; font-size: 15px; margin: 0 0 25px 0; line-height: 2.2;">
                    E terminare entro il giorno: <span style="color: #ef4444; font-weight: bold;">${new Date(corsista.endDate).toLocaleDateString('it-IT')}</span>
                  </p>
                  
                  <p style="color: #333; font-size: 15px; margin: 0; line-height: 2.2;">
                    Il tuo referente per questo corso è: <span style="color: #0ea5e9; font-weight: bold;">Tutor81 - corsi@tutor81.it</span>
                  </p>
                </div>
                
                <!-- Box accesso -->
                <div style="background-color: #e8f4fc; margin: 25px; padding: 50px 30px; border-radius: 8px; text-align: center;">
                  <p style="color: #0ea5e9; font-size: 18px; margin: 0 0 40px 0; line-height: 1.8;">
                    Per accedere al corso clicca su avvia corso e<br>inserisci il tuo nome utente in questo modo:
                  </p>
                  <p style="color: #1a365d; font-size: 32px; font-weight: bold; margin: 0 0 50px 0;">
                    ${corsista.email.split('@')[0]}
                  </p>
                  <p style="color: #333; font-size: 14px; margin: 0 0 25px 0;">Se vuoi avviare il corso clicca qui</p>
                  <a href="https://avviacorso.tutor81.com" style="background-color: #22d3ee; color: #fff; padding: 18px 50px; text-decoration: none; border-radius: 0; font-weight: bold; display: inline-block; font-size: 20px;">
                    AVVIA CORSO
                  </a>
                </div>
                
                <!-- Link istruzioni -->
                <div style="padding: 20px; text-align: center; border-bottom: 1px solid #ddd;">
                  <a href="${appUrl}/istruzioni" style="color: #0ea5e9; font-size: 18px; font-weight: bold; text-decoration: underline;">
                    ISTRUZIONI PER IL CORSO
                  </a>
                </div>
                
                <!-- Box istruzioni dettagliate -->
                <div style="background-color: #f0f4f8; margin: 0; padding: 35px 40px;">
                  <p style="color: #333; font-size: 13px; margin: 0 0 25px 0; line-height: 1.8;">
                    Il tuo referente per questo corso può essere contattato per E-Mail scrivendo a <strong>TUTOR81</strong><br>
                    E-Mail: <a href="mailto:corsi@tutor81.it" style="color: #0ea5e9;">corsi@tutor81.it</a>
                  </p>
                  
                  <p style="color: #333; font-size: 13px; margin: 0 0 25px 0; line-height: 1.8;">
                    Al termine del corso potrai scaricare il tracciato di avvenuta formazione
                  </p>
                  
                  <p style="color: #333; font-size: 13px; margin: 0 0 25px 0; line-height: 1.8;">
                    <strong>IL CORSO PUÒ ESSERE INTERROTTO</strong> con il pulsante ESCI in alto a sinistra. Riaccendeno al corso questo ripartirà dall'ultimo punto utile.
                  </p>
                  
                  <p style="color: #333; font-size: 13px; margin: 0 0 25px 0; line-height: 1.8;">
                    <strong>PAUSA:</strong> puoi fermare temporaneamente il corso con il pulsante Ferma, ma solo per 30 secondi, terminati i quali il corso viene interrotto.
                  </p>
                  
                  <p style="color: #333; font-size: 13px; margin: 0; line-height: 1.8;">
                    <strong>ASSISTENZA TECNICA:</strong> In ogni momento è possibile inviare una segnalazione anche tramite mail dal pulsante Richiedi Assistenza oppure scrivete a <a href="mailto:corsi@tutor81.it" style="color: #0ea5e9;">corsi@tutor81.it</a>
                  </p>
                </div>
                
                <!-- Footer -->
                <div style="background-color: #1a365d; padding: 20px; text-align: center;">
                  <p style="color: #EAB308; font-size: 12px; font-weight: bold; margin: 0;">
                    TUTOR81 - corsi@tutor81.it
                  </p>
                </div>
                
                <img src="${trackingPixelUrl}" width="1" height="1" style="display:none;" alt="" />
              </div>
            `,
          });

          // Update email sent timestamp
          await db.update(schema.enrollments)
            .set({ emailSentAt: now })
            .where(eq(schema.enrollments.id, enrollment.id));

          console.log(`Email sent to ${corsista.email} for course ${courseId}`);
        } catch (emailError) {
          console.error(`Failed to send email to ${corsista.email}:`, emailError);
        }

        createdCount++;
        console.log(`Enrollment created for ${corsista.email} - course ${courseId}`);
      }

      res.json({
        success: true,
        created: createdCount,
        message: `${createdCount} iscrizioni create con successo`
      });
    } catch (error) {
      console.error("Activate enrollment error:", error);
      res.status(500).json({ message: "Errore nella creazione delle iscrizioni" });
    }
  });

  // Send activation emails to selected enrollments
  app.post("/api/enrollments/send-emails", isAuthenticated, async (req, res) => {
    try {
      const { enrollmentIds } = req.body as { enrollmentIds: number[] };
      
      if (!enrollmentIds || !Array.isArray(enrollmentIds) || enrollmentIds.length === 0) {
        return res.status(400).json({ message: "Nessun iscritto selezionato" });
      }

      const now = new Date();
      let sentCount = 0;

      for (const enrollmentId of enrollmentIds) {
        // Get enrollment details
        const [enrollment] = await db.select()
          .from(schema.enrollments)
          .where(eq(schema.enrollments.id, enrollmentId));

        if (!enrollment) continue;

        // Update email_sent_at timestamp
        await db.update(schema.enrollments)
          .set({ 
            emailSentAt: now,
            emailTrackingId: `track_${enrollmentId}_${Date.now()}`
          })
          .where(eq(schema.enrollments.id, enrollmentId));

        sentCount++;
        
        // TODO: Actual email sending will be implemented when email service is configured
        console.log(`Email marked as sent for enrollment ${enrollmentId}`);
      }

      res.json({ 
        success: true, 
        message: `${sentCount} email segnate come inviate`,
        sentCount 
      });
    } catch (error) {
      console.error("Send emails error:", error);
      res.status(500).json({ message: "Errore nell'invio delle email" });
    }
  });

  app.get("/api/companies-list", isAuthenticated, async (req, res) => {
    try {
      // Filter companies under Tutor81 (parent_company_id = 2)
      const companies = await db.select({ 
        id: schema.companies.id, 
        businessName: schema.companies.businessName 
      })
        .from(schema.companies)
        .where(and(
          eq(schema.companies.isTutor, false),
          eq(schema.companies.parentCompanyId, 2)
        ))
        .orderBy(schema.companies.businessName);
      res.json(companies);
    } catch (error) {
      console.error("Companies list error:", error);
      res.json([]);
    }
  });

  app.get("/api/companies/tutors", isAuthenticated, async (req, res) => {
    try {
      const tutors = await db.select()
        .from(schema.companies)
        .where(eq(schema.companies.isTutor, true))
        .orderBy(schema.companies.businessName);
      res.json(tutors);
    } catch (error) {
      console.error("Tutors list error:", error);
      res.json([]);
    }
  });

  app.patch("/api/learning-projects/:id/reserve", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { reservedTo } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ reservedTo: reservedTo })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Reserve project error:", error);
      res.status(500).json({ error: "Failed to reserve project" });
    }
  });

  app.patch("/api/learning-projects/:id/category", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { category } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ category: category })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update category error:", error);
      res.status(500).json({ error: "Failed to update category" });
    }
  });

  app.patch("/api/learning-projects/:id/subcategory", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { subcategory } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ subcategory: subcategory })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update subcategory error:", error);
      res.status(500).json({ error: "Failed to update subcategory" });
    }
  });

  app.patch("/api/learning-projects/:id/sector", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { sector } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ sector: sector })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update sector error:", error);
      res.status(500).json({ error: "Failed to update sector" });
    }
  });

  app.patch("/api/learning-projects/:id/course-type", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { courseType } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ courseType: courseType })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update courseType error:", error);
      res.status(500).json({ error: "Failed to update courseType" });
    }
  });

  app.patch("/api/learning-projects/:id/modality", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { modality } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ modality: modality })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update modality error:", error);
      res.status(500).json({ error: "Failed to update modality" });
    }
  });

  app.patch("/api/learning-projects/:id/risk-level", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { riskLevel } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ riskLevel: riskLevel })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update risk level error:", error);
      res.status(500).json({ error: "Failed to update risk level" });
    }
  });

  app.patch("/api/learning-projects/:id/validity", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { validity } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ courseValidity: validity })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update validity error:", error);
      res.status(500).json({ error: "Failed to update validity" });
    }
  });

  app.patch("/api/learning-projects/:id/integration", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { integration } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ externalIntegration: integration })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update integration error:", error);
      res.status(500).json({ error: "Failed to update integration" });
    }
  });

  app.patch("/api/learning-projects/:id/destination", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { destination } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      await db.update(schema.learningProjects)
        .set({ destination: destination })
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update destination error:", error);
      res.status(500).json({ error: "Failed to update destination" });
    }
  });

  // Update learning project - generic PATCH for multiple fields
  app.patch("/api/learning-projects/:id", isAuthenticated, async (req, res) => {
    try {
      const projectId = parseInt(req.params.id as string);
      const { hours, totalElearning, maxExecutionTime, percentageToPass, externalIntegration } = req.body;
      
      if (isNaN(projectId)) {
        return res.status(400).json({ error: "ID progetto non valido" });
      }

      const updateData: Record<string, unknown> = {};
      if (hours !== undefined) updateData.hours = hours;
      if (totalElearning !== undefined) updateData.totalElearning = totalElearning;
      if (maxExecutionTime !== undefined) updateData.maxExecutionTime = maxExecutionTime;
      if (percentageToPass !== undefined) updateData.percentageToPass = percentageToPass;
      if (externalIntegration !== undefined) updateData.externalIntegration = externalIntegration;

      if (Object.keys(updateData).length === 0) {
        return res.status(400).json({ error: "Nessun campo da aggiornare" });
      }

      await db.update(schema.learningProjects)
        .set(updateData)
        .where(eq(schema.learningProjects.id, projectId));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Update learning project error:", error);
      res.status(500).json({ error: "Failed to update learning project" });
    }
  });

  // Attestati endpoints
  app.get("/api/attestati", isAuthenticated, async (req, res) => {
    try {
      // Get enrollments that have a legacy_id (meaning they have a certificate file)
      const attestati = await db.execute(sql`
        SELECT 
          e.id,
          e.legacy_id,
          e.legacy_user_id,
          e.start_date,
          e.end_date,
          e.accreditation_code,
          e.progress,
          u.first_name as user_first_name,
          u.last_name as user_last_name,
          u.email as user_email,
          u.fiscal_code as user_fiscal_code,
          lp.title as course_title,
          lp.hours as course_hours,
          c.business_name as company_name,
          tutor.business_name as tutor_name
        FROM enrollments e
        LEFT JOIN users u ON e.legacy_user_id::text = u.id
        LEFT JOIN learning_projects lp ON e.learning_project_id = lp.id
        LEFT JOIN companies c ON e.company_id = c.id
        LEFT JOIN tutors_purchases tp ON e.purchase_id = tp.id
        LEFT JOIN companies tutor ON tp.tutor_id = tutor.id
        WHERE e.legacy_id IS NOT NULL
        ORDER BY e.end_date DESC NULLS LAST
        LIMIT 1000
      `);
      
      res.json(attestati.rows);
    } catch (error) {
      console.error("Attestati error:", error);
      res.status(500).json({ error: "Failed to fetch attestati" });
    }
  });

  app.get("/api/attestato/:legacyId/download", isAuthenticated, async (req, res) => {
    const { legacyId } = req.params;
    
    if (!legacyId || isNaN(Number(legacyId))) {
      return res.status(400).json({ error: "Invalid legacy ID" });
    }
    
    const client = new ftp.Client();
    
    try {
      await client.access({
        host: process.env.FTP_HOST || "135.125.205.19",
        user: process.env.FTP_USERNAME!,
        password: process.env.FTP_PASSWORD!,
        secure: false,
      });
      
      const remotePath = `${process.env.FTP_ATTESTATI_PATH || "/media/media/attestati"}/attestato_licenza_${legacyId}.pdf`;
      
      // Create a temporary buffer to store the file
      const chunks: Buffer[] = [];
      const writable = new Writable({
        write(chunk: Buffer, encoding: string, callback: () => void) {
          chunks.push(chunk);
          callback();
        }
      });
      
      await client.downloadTo(writable, remotePath);
      
      const fileBuffer = Buffer.concat(chunks);
      
      res.setHeader('Content-Type', 'application/pdf');
      res.setHeader('Content-Disposition', `attachment; filename="attestato_${legacyId}.pdf"`);
      res.send(fileBuffer);
      
    } catch (error: any) {
      console.error("FTP download error:", error.message);
      res.status(404).json({ error: "Attestato non trovato" });
    } finally {
      client.close();
    }
  });

  app.get("/api/attestato/:legacyId/check", isAuthenticated, async (req, res) => {
    const { legacyId } = req.params;
    
    if (!legacyId || isNaN(Number(legacyId))) {
      return res.status(400).json({ exists: false });
    }
    
    const client = new ftp.Client();
    
    try {
      await client.access({
        host: process.env.FTP_HOST || "135.125.205.19",
        user: process.env.FTP_USERNAME!,
        password: process.env.FTP_PASSWORD!,
        secure: false,
      });
      
      const remotePath = `${process.env.FTP_ATTESTATI_PATH || "/media/media/attestati"}/attestato_licenza_${legacyId}.pdf`;
      const size = await client.size(remotePath);
      
      res.json({ exists: true, size });
    } catch (error) {
      res.json({ exists: false });
    } finally {
      client.close();
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

  // Learning Objects API
  app.get("/api/learning-objects", isAuthenticated, async (req, res) => {
    try {
      const objects = await db.select()
        .from(schema.learningObjects)
        .orderBy(desc(schema.learningObjects.id));
      
      res.json(objects);
    } catch (error) {
      console.error("Learning objects error:", error);
      res.status(500).json({ error: "Failed to fetch learning objects" });
    }
  });

  // Get learning object details with questions
  app.get("/api/learning-objects/:id/details", isAuthenticated, async (req, res) => {
    try {
      const { id } = req.params;
      
      // Get the learning object
      const [lo] = await db.select()
        .from(schema.learningObjects)
        .where(eq(schema.learningObjects.id, parseInt(id)));
      
      if (!lo) {
        return res.status(404).json({ error: "Learning object not found" });
      }
      
      // Get interruption points for this learning object
      const interruptionPoints = await db.execute(sql`
        SELECT 
          ip.id, ip.legacy_id, ip.time,
          json_agg(json_build_object(
            'id', qs.id,
            'text', qs.text,
            'answers', (
              SELECT json_agg(json_build_object('id', qa.id, 'text', qa.text, 'isCorrect', qa.is_correct))
              FROM question_answers qa WHERE qa.question_sentence_id = qs.id
            )
          )) as questions
        FROM video_test_interruption_points ip
        LEFT JOIN interruption_questions iq ON iq.interruption_point_id = ip.id
        LEFT JOIN question_sentences qs ON qs.id = iq.question_sentence_id
        WHERE ip.learning_object_id = ${parseInt(id)}
        GROUP BY ip.id, ip.legacy_id, ip.time
        ORDER BY ip.time
      `);
      
      res.json({
        learningObject: lo,
        interruptionPoints: interruptionPoints.rows
      });
    } catch (error) {
      console.error("Learning object details error:", error);
      res.status(500).json({ error: "Failed to fetch learning object details" });
    }
  });

  app.patch("/api/learning-objects/:id/suspend", isAuthenticated, async (req, res) => {
    try {
      const { id } = req.params;
      const { suspended } = req.body;
      
      await db.update(schema.learningObjects)
        .set({ suspended: suspended })
        .where(eq(schema.learningObjects.id, parseInt(id)));
      
      res.json({ success: true });
    } catch (error) {
      console.error("Suspend learning object error:", error);
      res.status(500).json({ error: "Failed to update learning object" });
    }
  });

  // Email tracking pixel - traccia apertura email
  app.get("/api/email-track/:trackingId", async (req, res) => {
    try {
      const { trackingId } = req.params;
      
      // Aggiorna il record con la data di apertura
      await db.update(schema.enrollments)
        .set({ emailOpenedAt: new Date() })
        .where(
          and(
            eq(schema.enrollments.emailTrackingId, trackingId),
            sql`${schema.enrollments.emailOpenedAt} IS NULL`
          )
        );
      
      // Restituisci un pixel GIF trasparente 1x1
      const pixel = Buffer.from(
        'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
        'base64'
      );
      
      res.set({
        'Content-Type': 'image/gif',
        'Content-Length': pixel.length,
        'Cache-Control': 'no-cache, no-store, must-revalidate',
        'Pragma': 'no-cache',
        'Expires': '0'
      });
      
      res.send(pixel);
    } catch (error) {
      console.error("Email tracking error:", error);
      // Restituisci comunque il pixel per non rompere l'email
      const pixel = Buffer.from(
        'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
        'base64'
      );
      res.set('Content-Type', 'image/gif');
      res.send(pixel);
    }
  });

  return httpServer;
}
