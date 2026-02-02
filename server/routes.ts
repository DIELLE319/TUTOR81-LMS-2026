import type { Express, Request } from "express";
import { createServer, type Server } from "http";
import { setupAuth, registerAuthRoutes, isAuthenticated, authStorage } from "./replit_integrations/auth";
import { db } from "./db";
import * as schema from "@shared/schema";
import { eq, and, desc, sql, ilike, or, isNotNull } from "drizzle-orm";
import { z } from "zod";
import mysql from "mysql2/promise";
import crypto from "crypto";

// Helper per ottenere tutorId dall'utente autenticato (sicurezza lato server)
async function getAuthenticatedUserTutorId(req: Request): Promise<{ role: number | null; tutorId: number | null }> {
  try {
    const user = req.user as any;
    if (!user?.claims?.sub) {
      return { role: null, tutorId: null };
    }
    
    const dbUser = await authStorage.getUser(user.claims.sub);
    if (!dbUser) {
      return { role: null, tutorId: null };
    }
    
    // Se l'utente è admin tutor (role=1), cerca il suo tutorId
    if (dbUser.role === 1 && dbUser.idcompany) {
      const result = await db.execute(sql`
        SELECT ta.tutor_id
        FROM tutor_admins ta
        WHERE ta.id = ${dbUser.idcompany}
      `);
      if (result.rows.length > 0) {
        return { role: 1, tutorId: (result.rows[0] as any).tutor_id };
      }
    }
    
    return { role: dbUser.role, tutorId: null };
  } catch (e) {
    console.error("Error getting user tutor info:", e);
    return { role: null, tutorId: null };
  }
}

// Connessione al database OVH
const ovhDbConfig = {
  host: "135.125.205.19",
  port: 3306,
  user: "pro_tutor81",
  password: "hpm0?7C3",
  database: "pro_tutor81",
  connectTimeout: 10000,
};

async function getOvhConnection() {
  return mysql.createConnection(ovhDbConfig);
}

// Sincronizza iscrizione con OVH
async function syncEnrollmentToOvh(data: {
  firstName: string;
  lastName: string;
  fiscalCode: string;
  email: string;
  companyId: number;
  courseId: number;
  licenseCode: string;
  startDate: Date;
  endDate: Date | null;
  adminId: number;
}) {
  let conn;
  try {
    conn = await getOvhConnection();
    
    // Username formato nome.cognome (minuscolo come OVH)
    const username = `${data.firstName}.${data.lastName}`.toLowerCase().replace(/\s+/g, '');
    // Password = SHA1 del codice fiscale (come fa OVH)
    const passwordPlain = data.fiscalCode.toUpperCase();
    const password = crypto.createHash('sha1').update(passwordPlain).digest('hex');
    
    // Verifica se l'utente esiste già su OVH (per codice fiscale)
    const [existingUsers] = await conn.execute(
      'SELECT id FROM users WHERE tax_code = ? LIMIT 1',
      [data.fiscalCode.toUpperCase()]
    ) as any[];
    
    let userId: number;
    
    if (existingUsers.length > 0) {
      userId = existingUsers[0].id;
      console.log(`[OVH Sync] Utente esistente trovato: ${userId}`);
    } else {
      // Crea nuovo utente su OVH (nomi colonne OVH: name, surname, creation_date, suspended, deleted)
      const [result] = await conn.execute(
        `INSERT INTO users (company_id, role, name, surname, username, password, email, tax_code, suspended, deleted, creation_date) 
         VALUES (?, 0, ?, ?, ?, ?, ?, ?, 0, 0, NOW())`,
        [data.companyId, data.firstName, data.lastName, username, password, data.email, data.fiscalCode.toUpperCase()]
      ) as any[];
      userId = result.insertId;
      console.log(`[OVH Sync] Nuovo utente creato: ${userId}`);
    }
    
    // Crea iscrizione in learning_project_users (nomi colonne OVH: starting_from, finish_within, learning_project_pwd)
    const startingFrom = data.startDate.toISOString().split('T')[0]; // formato YYYY-MM-DD
    const finishWithin = data.endDate ? data.endDate.toISOString().split('T')[0] : null;
    
    // Crea record vendita in tutors_purchases
    const [purchaseResult] = await conn.execute(
      `INSERT INTO tutors_purchases (tutor_id, customer_company_id, user_company_ref, learning_project_id, qta, price, creation_date, executed)
       VALUES (?, ?, ?, ?, 1, 0, NOW(), 1)`,
      [data.adminId, data.companyId, data.adminId, data.courseId]
    ) as any[];
    const purchaseId = purchaseResult.insertId;
    console.log(`[OVH Sync] Vendita creata: ${purchaseId}`);
    
    // Crea iscrizione in learning_project_users con riferimento alla vendita
    const [lpuResult] = await conn.execute(
      `INSERT INTO learning_project_users (user_id, learning_project_id, learning_project_pwd, company_id, starting_from, finish_within, days_to_alert, id_company, email, assigned, tutor_purchase_id)
       VALUES (?, ?, ?, ?, ?, ?, 30, ?, ?, 1, ?)`,
      [userId, data.courseId, data.licenseCode, data.adminId, startingFrom, finishWithin, data.companyId, data.email, purchaseId]
    ) as any[];
    
    console.log(`[OVH Sync] Iscrizione creata: ${lpuResult.insertId} per utente ${userId} corso ${data.courseId}`);
    
    await conn.end();
    return { success: true, userId, username };
  } catch (error) {
    console.error("[OVH Sync] Errore:", error);
    if (conn) await conn.end();
    return { success: false, error: String(error) };
  }
}

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {
  await setupAuth(app);
  registerAuthRoutes(app);

  // ============================================================
  // TEST OVH CONNECTION (temporaneo per debug)
  // ============================================================
  app.get("/api/test-ovh", async (req, res) => {
    let conn;
    try {
      conn = await getOvhConnection();
      
      // Test connessione
      const [rows] = await conn.execute('SELECT COUNT(*) as count FROM users') as any[];
      const usersCount = rows[0].count;
      
      const [lpuRows] = await conn.execute('SELECT COUNT(*) as count FROM learning_project_users') as any[];
      const lpuCount = lpuRows[0].count;
      
      const [tpRows] = await conn.execute('SELECT COUNT(*) as count FROM tutors_purchases') as any[];
      const tpCount = tpRows[0].count;
      
      await conn.end();
      
      res.json({
        success: true,
        message: "Connessione OVH OK",
        stats: {
          users: usersCount,
          learning_project_users: lpuCount,
          tutors_purchases: tpCount
        }
      });
    } catch (error) {
      console.error("OVH connection test error:", error);
      if (conn) await conn.end();
      res.status(500).json({ success: false, error: String(error) });
    }
  });

  // Endpoint per vedere le ultime iscrizioni su OVH
  app.get("/api/test-ovh-recent", async (req, res) => {
    let conn;
    try {
      conn = await getOvhConnection();
      
      // Ultime 10 iscrizioni create su OVH
      const [lpuRows] = await conn.execute(`
        SELECT 
          lpu.id,
          lpu.user_id,
          lpu.learning_project_id,
          lpu.learning_project_pwd as license_code,
          lpu.starting_from,
          lpu.finish_within,
          lpu.creation_date,
          lpu.email,
          lpu.id_company,
          u.username,
          u.name as user_name,
          u.surname as user_surname,
          u.tax_code,
          lp.title as course_title
        FROM learning_project_users lpu
        LEFT JOIN users u ON lpu.user_id = u.id
        LEFT JOIN learning_project lp ON lpu.learning_project_id = lp.id
        ORDER BY lpu.id DESC
        LIMIT 10
      `) as any[];
      
      await conn.end();
      
      res.json({
        success: true,
        recentEnrollments: lpuRows
      });
    } catch (error) {
      console.error("OVH recent enrollments error:", error);
      if (conn) await conn.end();
      res.status(500).json({ success: false, error: String(error) });
    }
  });

  // Verifica iscrizione specifica per debug
  app.get("/api/test-ovh-check/:licenseCode", async (req, res) => {
    let conn;
    try {
      const licenseCode = req.params.licenseCode;
      conn = await getOvhConnection();
      
      // Cerca l'iscrizione come fa il player
      const [lpuRows] = await conn.execute(`
        SELECT 
          lpu.*,
          u.id as user_db_id,
          u.username,
          u.password,
          u.name as user_name,
          u.surname as user_surname,
          u.email as user_email,
          u.tax_code,
          u.suspended,
          u.deleted,
          lp.id as lp_id,
          lp.title as course_title
        FROM learning_project_users lpu
        LEFT JOIN users u ON lpu.user_id = u.id
        LEFT JOIN learning_project lp ON lpu.learning_project_id = lp.id
        WHERE lpu.learning_project_pwd = ?
      `, [licenseCode]) as any[];
      
      await conn.end();
      
      if (lpuRows.length === 0) {
        return res.json({ success: false, error: "Iscrizione non trovata con questo codice licenza" });
      }
      
      const enrollment = lpuRows[0];
      
      res.json({
        success: true,
        enrollment: {
          id: enrollment.id,
          licenseCode: enrollment.learning_project_pwd,
          userId: enrollment.user_id,
          learningProjectId: enrollment.learning_project_id,
          startingFrom: enrollment.starting_from,
          finishWithin: enrollment.finish_within,
          courseTitle: enrollment.course_title,
          user: {
            id: enrollment.user_db_id,
            username: enrollment.username,
            hasPassword: !!enrollment.password,
            passwordLength: enrollment.password?.length,
            name: enrollment.user_name,
            surname: enrollment.user_surname,
            email: enrollment.user_email,
            taxCode: enrollment.tax_code,
            suspended: enrollment.suspended,
            deleted: enrollment.deleted
          }
        }
      });
    } catch (error) {
      console.error("OVH check enrollment error:", error);
      if (conn) await conn.end();
      res.status(500).json({ success: false, error: String(error) });
    }
  });

  // ============================================================
  // STATS
  // ============================================================
  app.get("/api/stats", isAuthenticated, async (req, res) => {
    try {
      const [tutorsResult] = await db.select({ count: sql<number>`count(*)` }).from(schema.tutors);
      const [companiesResult] = await db.select({ count: sql<number>`count(*)` }).from(schema.companies);
      const [studentsResult] = await db.select({ count: sql<number>`count(*)` }).from(schema.students);
      const [enrollmentsResult] = await db.select({ count: sql<number>`count(*)` }).from(schema.enrollments);
      const [coursesResult] = await db.select({ count: sql<number>`count(*)` }).from(schema.courses);

      res.json({
        tutors: Number(tutorsResult?.count ?? 0),
        companies: Number(companiesResult?.count ?? 0),
        students: Number(studentsResult?.count ?? 0),
        enrollments: Number(enrollmentsResult?.count ?? 0),
        courses: Number(coursesResult?.count ?? 0),
      });
    } catch (error) {
      console.error("Stats error:", error);
      res.json({ tutors: 0, companies: 0, students: 0, enrollments: 0, courses: 0 });
    }
  });

  // ============================================================
  // TUTORS (Enti Formativi)
  // ============================================================
  app.get("/api/tutors", isAuthenticated, async (req, res) => {
    try {
      const tutors = await db.select().from(schema.tutors).orderBy(schema.tutors.businessName);
      res.json(tutors);
    } catch (error) {
      console.error("Tutors error:", error);
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  app.get("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid tutor ID" });

      const [tutor] = await db.select().from(schema.tutors).where(eq(schema.tutors.id, id));
      if (!tutor) return res.status(404).json({ error: "Tutor not found" });

      res.json(tutor);
    } catch (error) {
      console.error("Tutor error:", error);
      res.status(500).json({ error: "Failed to fetch tutor" });
    }
  });

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

  app.put("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
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

  app.delete("/api/tutors/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid tutor ID" });

      // Check if tutor has companies
      const [hasCompanies] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.companies)
        .where(eq(schema.companies.tutorId, id));

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

  // ============================================================
  // TUTOR ADMINS (Amministratori Enti Formativi)
  // ============================================================
  app.get("/api/tutors/:tutorId/admins", isAuthenticated, async (req, res) => {
    try {
      const tutorId = parseInt(req.params.tutorId as string);
      if (isNaN(tutorId)) return res.status(400).json({ error: "Invalid tutor ID" });

      const admins = await db.select()
        .from(schema.tutorAdmins)
        .where(eq(schema.tutorAdmins.tutorId, tutorId))
        .orderBy(schema.tutorAdmins.name);

      res.json(admins);
    } catch (error) {
      console.error("Tutor admins error:", error);
      res.status(500).json({ error: "Failed to fetch tutor admins" });
    }
  });

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

  // ============================================================
  // CLIENTS (Aziende Clienti raggruppate per Tutor)
  // ============================================================
  app.get("/api/clients", isAuthenticated, async (req, res) => {
    try {
      // SICUREZZA: per admin tutor (role=1), forza il filtro tutorId dal server
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      
      // Se role=1 ma tutorId non è stato risolto, nega accesso
      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }
      
      const tutorIdFilter = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      
      // Get all companies with their tutor info
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
      
      // Se c'è un filtro tutorId, filtra per quel tutor
      const companies = tutorIdFilter 
        ? await query.where(eq(schema.companies.tutorId, tutorIdFilter)).orderBy(schema.companies.businessName)
        : await query.orderBy(schema.tutors.businessName, schema.companies.businessName);

      // Group by tutor
      const tutorGroups: { tutorId: number | null; tutorName: string; clients: any[] }[] = [];
      const tutorMap = new Map<number | null, { tutorId: number | null; tutorName: string; clients: any[] }>();

      for (const c of companies) {
        const key = c.tutorId;
        if (!tutorMap.has(key)) {
          tutorMap.set(key, {
            tutorId: c.tutorId,
            tutorName: c.tutorName || 'Senza Ente',
            clients: []
          });
        }
        tutorMap.get(key)!.clients.push({
          id: c.id,
          businessName: c.businessName,
          city: c.city,
          email: c.email,
          phone: c.phone,
          address: c.address,
          vatNumber: c.vatNumber
        });
      }

      tutorMap.forEach(group => tutorGroups.push(group));
      res.json(tutorGroups);
    } catch (error) {
      console.error("Clients error:", error);
      res.status(500).json({ error: "Failed to fetch clients" });
    }
  });

  // ============================================================
  // COMPANIES (Aziende Clienti)
  // ============================================================
  app.get("/api/companies", isAuthenticated, async (req, res) => {
    try {
      // SICUREZZA: per admin tutor (role=1), forza il filtro tutorId dal server
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      
      // Se role=1 ma tutorId non è stato risolto, nega accesso
      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }
      
      const tutorId = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      const search = (req.query.search as string) || "";

      let query = db.select({
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

      const companies = await query;

      // Filter in memory for simplicity
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

  // Endpoint per ottenere solo i tutors (enti formativi)
  app.get("/api/companies/tutors", isAuthenticated, async (req, res) => {
    try {
      const tutors = await db.select()
        .from(schema.tutors)
        .orderBy(schema.tutors.businessName);
      res.json(tutors);
    } catch (error) {
      console.error("Tutors error:", error);
      res.status(500).json({ error: "Failed to fetch tutors" });
    }
  });

  app.get("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid company ID" });

      const [company] = await db.select().from(schema.companies).where(eq(schema.companies.id, id));
      if (!company) return res.status(404).json({ error: "Company not found" });

      res.json(company);
    } catch (error) {
      console.error("Company error:", error);
      res.status(500).json({ error: "Failed to fetch company" });
    }
  });

  app.get("/api/companies/:id/users", isAuthenticated, async (req, res) => {
    try {
      const companyId = parseInt(req.params.id);
      if (isNaN(companyId)) return res.status(400).json({ error: "Invalid company ID" });

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

  app.put("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
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

  app.delete("/api/companies/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid company ID" });

      // Check if company has students
      const [hasStudents] = await db.select({ count: sql<number>`count(*)` })
        .from(schema.students)
        .where(eq(schema.students.companyId, id));

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

  // ============================================================
  // VERIFICA CODICE FISCALE IN TEMPO REALE
  // ============================================================
  app.get("/api/check-fiscal-code", isAuthenticated, async (req, res) => {
    try {
      const fiscalCode = (req.query.fiscalCode as string || '').toUpperCase().trim();
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;
      
      if (!fiscalCode) {
        return res.json({ exists: false });
      }
      
      // Controlla su Replit
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
            : `Corsista già esistente in altra azienda: ${student.companyName}`
        });
      }
      
      // Controlla anche su OVH
      let ovhExists = false;
      let ovhUser = null;
      try {
        const conn = await getOvhConnection();
        const [ovhUsers] = await conn.execute(
          'SELECT id, name, surname FROM users WHERE tax_code = ? LIMIT 1',
          [fiscalCode]
        ) as any[];
        await conn.end();
        
        if (ovhUsers.length > 0) {
          ovhExists = true;
          ovhUser = ovhUsers[0];
        }
      } catch (ovhError) {
        console.error("[Check CF] OVH error:", ovhError);
      }
      
      if (ovhExists && ovhUser) {
        return res.json({
          exists: true,
          sameCompany: true, // Assumiamo che verrà usato lo stesso utente OVH
          student: {
            id: ovhUser.id,
            firstName: ovhUser.name,
            lastName: ovhUser.surname,
            companyName: 'OVH',
          },
          message: `Corsista già esistente su OVH: ${ovhUser.name} ${ovhUser.surname} - verrà usato utente esistente`,
          source: 'ovh'
        });
      }
      
      res.json({ exists: false });
    } catch (error) {
      console.error("Check fiscal code error:", error);
      res.json({ exists: false });
    }
  });

  // ============================================================
  // STUDENTS (Studenti/Dipendenti)
  // ============================================================
  app.get("/api/students", isAuthenticated, async (req, res) => {
    try {
      // SICUREZZA: per admin tutor (role=1), forza il filtro tutorId dal server
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      
      // Se role=1 ma tutorId non è stato risolto, nega accesso
      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }
      
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;
      const tutorIdFilter = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      const limit = req.query.limit ? parseInt(req.query.limit as string) : 500;

      const students = await db.select({
        id: schema.students.id,
        companyId: schema.students.companyId,
        email: schema.students.email,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        fiscalCode: schema.students.fiscalCode,
        phone: schema.students.phone,
        isActive: schema.students.isActive,
        companyName: schema.companies.businessName,
        tutorId: schema.companies.tutorId,
        tutorName: schema.tutors.businessName,
      })
        .from(schema.students)
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.tutors, eq(schema.companies.tutorId, schema.tutors.id))
        .orderBy(schema.students.lastName)
        .limit(limit);

      let filtered = students;
      if (tutorIdFilter) {
        filtered = filtered.filter(s => s.tutorId === tutorIdFilter);
      }
      if (companyId) {
        filtered = filtered.filter(s => s.companyId === companyId);
      }

      res.json(filtered);
    } catch (error) {
      console.error("Students error:", error);
      res.status(500).json({ error: "Failed to fetch students" });
    }
  });

  app.post("/api/students", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertStudentSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newStudent] = await db.insert(schema.students).values(result.data).returning();
      res.status(201).json(newStudent);
    } catch (error) {
      console.error("Create student error:", error);
      res.status(500).json({ error: "Failed to create student" });
    }
  });

  app.delete("/api/students/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid student ID" });

      // Delete enrollments first
      await db.delete(schema.enrollments).where(eq(schema.enrollments.studentId, id));
      await db.delete(schema.students).where(eq(schema.students.id, id));
      res.json({ success: true });
    } catch (error) {
      console.error("Delete student error:", error);
      res.status(500).json({ error: "Failed to delete student" });
    }
  });

  // ============================================================
  // COURSES (Corsi) - anche /api/learning-projects per retrocompatibilità
  // ============================================================
  app.get("/api/courses", isAuthenticated, async (req, res) => {
    try {
      const courses = await db.select().from(schema.courses).orderBy(schema.courses.title);
      res.json(courses);
    } catch (error) {
      console.error("Courses error:", error);
      res.status(500).json({ error: "Failed to fetch courses" });
    }
  });

  // Alias per /api/learning-projects (usato da ContentManagement)
  app.get("/api/learning-projects", isAuthenticated, async (req, res) => {
    try {
      const courses = await db.select().from(schema.courses).orderBy(schema.courses.title);
      res.json(courses);
    } catch (error) {
      console.error("Learning projects error:", error);
      res.status(500).json({ error: "Failed to fetch learning projects" });
    }
  });

  app.get("/api/courses/:id", isAuthenticated, async (req, res) => {
    try {
      const id = parseInt(req.params.id);
      if (isNaN(id)) return res.status(400).json({ error: "Invalid course ID" });

      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, id));
      if (!course) return res.status(404).json({ error: "Course not found" });

      res.json(course);
    } catch (error) {
      console.error("Course error:", error);
      res.status(500).json({ error: "Failed to fetch course" });
    }
  });

  app.post("/api/courses", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertCourseSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newCourse] = await db.insert(schema.courses).values(result.data).returning();
      res.status(201).json(newCourse);
    } catch (error) {
      console.error("Create course error:", error);
      res.status(500).json({ error: "Failed to create course" });
    }
  });

  // ============================================================
  // ENROLLMENTS (Iscrizioni)
  // ============================================================
  app.get("/api/enrollments", isAuthenticated, async (req, res) => {
    try {
      // SICUREZZA: per admin tutor (role=1), forza il filtro tutorId dal server
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      
      // Se role=1 ma tutorId non è stato risolto, nega accesso
      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }
      
      const tutorId = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;

      const enrollmentsRaw = await db.select({
        id: schema.enrollments.id,
        studentId: schema.enrollments.studentId,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        createdAt: schema.enrollments.createdAt,
        completedAt: schema.enrollments.completedAt,
        lastAccessAt: schema.enrollments.lastAccessAt,
        enrollmentTutorId: schema.enrollments.tutorId,
        studentEmail: schema.students.email,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
        tutorId: schema.companies.tutorId,
        courseTitle: schema.courses.title,
        tutorName: schema.tutors.businessName,
      })
        .from(schema.enrollments)
        .leftJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .leftJoin(schema.tutors, eq(schema.enrollments.tutorId, schema.tutors.id))
        .where(isNotNull(schema.enrollments.tutorId))
        .orderBy(desc(schema.enrollments.startDate));

      let filtered = enrollmentsRaw;
      if (tutorId) {
        filtered = filtered.filter(e => e.tutorId === tutorId);
      }
      if (companyId) {
        filtered = filtered.filter(e => e.companyId === companyId);
      }

      const enrollments = filtered.map(e => ({
        id: e.id,
        companyName: e.companyName || '',
        userName: `${e.studentLastName || ''} ${e.studentFirstName || ''}`.trim(),
        userEmail: e.studentEmail || '',
        courseName: e.courseTitle || '',
        startDate: e.startDate,
        endDate: e.endDate,
        lastAccessAt: e.lastAccessAt,
        progress: e.progress || 0,
        status: e.status || 'active',
        emailSentAt: null,
        emailOpenedAt: null,
        licenseCode: e.licenseCode,
        tutorId: e.enrollmentTutorId || null,
        tutorName: e.tutorName || '',
      }));

      res.json(enrollments);
    } catch (error) {
      console.error("Enrollments error:", error);
      res.status(500).json({ error: "Failed to fetch enrollments" });
    }
  });

  app.post("/api/enrollments", isAuthenticated, async (req, res) => {
    try {
      const result = schema.insertEnrollmentSchema.safeParse(req.body);
      if (!result.success) {
        return res.status(400).json({ error: result.error.errors[0].message });
      }

      const [newEnrollment] = await db.insert(schema.enrollments).values(result.data).returning();
      res.status(201).json(newEnrollment);
    } catch (error) {
      console.error("Create enrollment error:", error);
      res.status(500).json({ error: "Failed to create enrollment" });
    }
  });

  app.post("/api/enrollments/activate", isAuthenticated, async (req, res) => {
    try {
      const { courseId, companyId, corsisti } = req.body;
      
      if (!courseId || !companyId || !corsisti || !Array.isArray(corsisti) || corsisti.length === 0) {
        return res.status(400).json({ error: "Dati mancanti: courseId, companyId e corsisti sono obbligatori" });
      }

      const company = await db.select().from(schema.companies).where(eq(schema.companies.id, companyId)).limit(1);
      if (!company.length) {
        return res.status(404).json({ error: "Azienda non trovata" });
      }

      const course = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId)).limit(1);
      if (!course.length) {
        return res.status(404).json({ error: "Corso non trovato" });
      }

      const tutorId = company[0].tutorId;
      let created = 0;
      let ovhSynced = 0;
      const results: { studentId: number; licenseCode: string; email: string; firstName: string; lastName: string; fiscalCode: string; username: string; ovhSync: boolean }[] = [];

      // Usa l'ID direttamente (Replit e OVH usano gli stessi ID)
      const ovhCourseId = courseId;
      const ovhCompanyId = companyId;

      for (const corsista of corsisti) {
        const { lastName, firstName, fiscalCode, startDate, endDate, daysToAlert } = corsista;
        
        if (!lastName || !firstName || !fiscalCode) {
          continue;
        }

        const email = corsista.email || `${fiscalCode.toLowerCase()}@corsista.tutor81.com`;
        const username = `${firstName}.${lastName}`.toLowerCase().replace(/\s+/g, '');
        
        let studentId: number;
        const existingStudent = await db.select()
          .from(schema.students)
          .where(and(
            eq(schema.students.fiscalCode, fiscalCode),
            eq(schema.students.companyId, companyId)
          ))
          .limit(1);

        if (existingStudent.length > 0) {
          studentId = existingStudent[0].id;
        } else {
          const [newStudent] = await db.insert(schema.students).values({
            companyId,
            email,
            firstName,
            lastName,
            fiscalCode,
            isActive: true,
          }).returning();
          studentId = newStudent.id;
        }

        const licenseCode = `${courseId}-${studentId}-${Date.now().toString(36).toUpperCase()}`;
        const enrollStartDate = startDate ? new Date(startDate) : new Date();
        const enrollEndDate = endDate ? new Date(endDate) : null;

        const [enrollment] = await db.insert(schema.enrollments).values({
          studentId,
          courseId,
          tutorId,
          licenseCode,
          startDate: enrollStartDate,
          endDate: enrollEndDate,
          daysToAlert: daysToAlert || 15,
          progress: 0,
          status: "active",
        }).returning();

        // Sincronizza con OVH
        let ovhSyncResult = { success: false };
        try {
          ovhSyncResult = await syncEnrollmentToOvh({
            firstName,
            lastName,
            fiscalCode,
            email,
            companyId: ovhCompanyId,
            courseId: ovhCourseId,
            licenseCode,
            startDate: enrollStartDate,
            endDate: enrollEndDate,
            adminId: tutorId || 2,
          });
          if (ovhSyncResult.success) ovhSynced++;
        } catch (err) {
          console.error("[OVH Sync] Errore sincronizzazione:", err);
        }

        const student = await db.select().from(schema.students).where(eq(schema.students.id, studentId)).limit(1);
        results.push({ 
          studentId, 
          licenseCode, 
          email,
          firstName: student[0]?.firstName || firstName,
          lastName: student[0]?.lastName || lastName,
          fiscalCode: student[0]?.fiscalCode || fiscalCode,
          username,
          ovhSync: ovhSyncResult.success,
        });
        created++;
      }

      res.json({ 
        success: true, 
        created,
        ovhSynced,
        message: `${created} iscrizioni create, ${ovhSynced} sincronizzate con OVH`,
        courseTitle: course[0].title,
        enrollments: results 
      });
    } catch (error) {
      console.error("Activate enrollments error:", error);
      res.status(500).json({ error: "Errore durante la creazione delle iscrizioni" });
    }
  });

  // ============================================================
  // MODULES, LESSONS, LEARNING OBJECTS
  // ============================================================
  app.get("/api/modules", isAuthenticated, async (req, res) => {
    try {
      const modules = await db.select().from(schema.modules).orderBy(schema.modules.sortOrder);
      res.json(modules);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch modules" });
    }
  });

  app.get("/api/lessons", isAuthenticated, async (req, res) => {
    try {
      const lessons = await db.select().from(schema.lessons).orderBy(schema.lessons.sortOrder);
      res.json(lessons);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch lessons" });
    }
  });

  app.get("/api/learning-objects", isAuthenticated, async (req, res) => {
    try {
      const objects = await db.select().from(schema.learningObjects).orderBy(schema.learningObjects.sortOrder);
      res.json(objects);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch learning objects" });
    }
  });

  // Course structure (modules > lessons > learning objects)
  app.get("/api/courses/:id/structure", isAuthenticated, async (req, res) => {
    try {
      const courseId = parseInt(req.params.id);
      if (isNaN(courseId)) return res.status(400).json({ error: "Invalid course ID" });

      // Get course
      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId));
      if (!course) return res.status(404).json({ error: "Course not found" });

      // Get modules for this course
      const courseModules = await db.select({
        id: schema.modules.id,
        title: schema.modules.title,
        description: schema.modules.description,
        duration: schema.modules.duration,
        position: schema.courseModules.position,
      })
        .from(schema.courseModules)
        .innerJoin(schema.modules, eq(schema.courseModules.moduleId, schema.modules.id))
        .where(eq(schema.courseModules.courseId, courseId))
        .orderBy(schema.courseModules.position);

      // Get lessons for each module
      const modulesWithLessons = await Promise.all(courseModules.map(async (module) => {
        const moduleLessons = await db.select({
          id: schema.lessons.id,
          title: schema.lessons.title,
          description: schema.lessons.description,
          duration: schema.lessons.duration,
          position: schema.moduleLessons.position,
        })
          .from(schema.moduleLessons)
          .innerJoin(schema.lessons, eq(schema.moduleLessons.lessonId, schema.lessons.id))
          .where(eq(schema.moduleLessons.moduleId, module.id))
          .orderBy(schema.moduleLessons.position);

        // Get learning objects for each lesson
        const lessonsWithObjects = await Promise.all(moduleLessons.map(async (lesson) => {
          const lessonObjects = await db.select({
            id: schema.learningObjects.id,
            title: schema.learningObjects.title,
            objectType: schema.learningObjects.objectType,
            duration: schema.learningObjects.duration,
            jwplayerCode: schema.learningObjects.jwplayerCode,
            position: schema.lessonLearningObjects.position,
          })
            .from(schema.lessonLearningObjects)
            .innerJoin(schema.learningObjects, eq(schema.lessonLearningObjects.learningObjectId, schema.learningObjects.id))
            .where(eq(schema.lessonLearningObjects.lessonId, lesson.id))
            .orderBy(schema.lessonLearningObjects.position);

          return { ...lesson, learningObjects: lessonObjects };
        }));

        return { ...module, lessons: lessonsWithObjects };
      }));

      res.json({
        ...course,
        modules: modulesWithLessons,
      });
    } catch (error) {
      console.error("Course structure error:", error);
      res.status(500).json({ error: "Failed to fetch course structure" });
    }
  });

  // ============================================================
  // CERTIFICATES
  // ============================================================
  app.get("/api/certificates", isAuthenticated, async (req, res) => {
    try {
      const certificates = await db.select({
        id: schema.certificates.id,
        enrollmentId: schema.certificates.enrollmentId,
        certificateNumber: schema.certificates.certificateNumber,
        issuedAt: schema.certificates.issuedAt,
        pdfUrl: schema.certificates.pdfUrl,
      })
        .from(schema.certificates)
        .orderBy(desc(schema.certificates.issuedAt));

      res.json(certificates);
    } catch (error) {
      res.status(500).json({ error: "Failed to fetch certificates" });
    }
  });

  // ============================================================
  // ATTESTATI FTP (Legacy Certificates from Vultr)
  // ============================================================
  app.get("/api/attestati", isAuthenticated, async (req, res) => {
    try {
      // SICUREZZA: per admin tutor (role=1), forza il filtro tutorId dal server
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      
      // Se role=1 ma tutorId non è stato risolto, nega accesso
      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }
      
      const tutorIdFilter = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      
      // Query attestati from legacy tables with all joins
      let query = sql`
        SELECT 
          le.id,
          le.legacy_id,
          le.legacy_user_id,
          le.license_code,
          le.start_date,
          le.end_date,
          le.accreditation_code,
          lu.first_name as user_first_name,
          lu.last_name as user_last_name,
          lu.email as user_email,
          lu.fiscal_code as user_fiscal_code,
          c.title as course_title,
          c.hours as course_hours,
          t.business_name as tutor_name,
          t.id as tutor_id
        FROM legacy_enrollments le
        LEFT JOIN legacy_users lu ON le.legacy_user_id = lu.legacy_id
        LEFT JOIN courses c ON le.legacy_course_id = c.id
        LEFT JOIN tutors t ON lu.creator_id = t.id
      `;
      
      if (tutorIdFilter) {
        query = sql`${query} WHERE t.id = ${tutorIdFilter}`;
      }
      
      query = sql`${query} ORDER BY le.end_date DESC NULLS LAST LIMIT 1000`;
      
      const attestati = await db.execute(query);
      
      res.json(attestati.rows);
    } catch (error) {
      console.error("Attestati error:", error);
      res.status(500).json({ error: "Failed to fetch attestati" });
    }
  });

  // Download specific attestato
  app.get("/api/attestato/:legacyId/download", isAuthenticated, async (req, res) => {
    const ftp = require("basic-ftp");
    const { Writable } = require("stream");
    const client = new ftp.Client();
    const legacyId = req.params.legacyId;
    
    try {
      await client.access({
        host: "95.179.207.157",
        user: process.env.FTP_USERNAME,
        password: process.env.FTP_PASSWORD,
        secure: false,
      });
      
      const filename = `attestato_licenza_${legacyId}.pdf`;
      const remotePath = `/media/media/attestati/${filename}`;
      
      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `attachment; filename="${filename}"`);
      
      await client.downloadTo(res, remotePath);
    } catch (error) {
      console.error("FTP download error:", error);
      res.status(500).json({ error: "Failed to download attestato" });
    } finally {
      client.close();
    }
  });

  // ============================================================
  // VENDITE (Sales/Purchases) 
  // ============================================================
  app.get("/api/sales", isAuthenticated, async (req, res) => {
    try {
      // SICUREZZA: per admin tutor (role=1), forza il filtro tutorId dal server
      const { role, tutorId: authTutorId } = await getAuthenticatedUserTutorId(req);
      
      // Se role=1 ma tutorId non è stato risolto, nega accesso
      if (role === 1 && !authTutorId) {
        return res.status(403).json({ error: "Admin tutor non associato a un ente formativo" });
      }
      
      const tutorId = role === 1 ? authTutorId : (req.query.tutorId ? parseInt(req.query.tutorId as string) : null);
      
      let query = `
        SELECT 
          tp.id,
          ta.id as "adminId",
          ta.name as "adminName",
          c.business_name as client,
          c.id as "clientId",
          t.id as "tutorId",
          t.business_name as "tutorName",
          tp.creation_date as date,
          tp.learning_project_id as "courseId",
          co.title as "courseName",
          tp.qta as qty,
          tp.price as "unitPrice",
          (tp.qta * tp.price) as "totalCost"
        FROM tutors_purchases tp
        JOIN tutor_admins ta ON ta.id = tp.tutor_id
        JOIN tutors t ON t.id = ta.tutor_id
        JOIN companies c ON c.id = tp.customer_company_id
        LEFT JOIN courses co ON co.id = tp.learning_project_id
      `;
      
      if (tutorId) {
        query += ` WHERE t.id = ${tutorId} ORDER BY tp.creation_date DESC`;
      } else {
        query += ` ORDER BY tp.creation_date DESC LIMIT 500`;
      }
      
      const salesResult = await db.execute(sql.raw(query));
      const salesRows = salesResult.rows as any[];
      
      // Fetch activated students for each sale (by company + course)
      const salesWithStudents = await Promise.all(salesRows.map(async (sale) => {
        if (!sale.courseId || !sale.clientId) return { ...sale, activatedStudents: '' };
        
        try {
          // Limit students shown to the quantity sold in this order (max 5 for display)
          const qty = parseInt(sale.qty) || 0;
          const displayLimit = Math.min(qty, 5);
          if (displayLimit <= 0) return { ...sale, activatedStudents: '' };
          
          const studentsQuery = `
            SELECT s.first_name, s.last_name
            FROM enrollments e
            JOIN students s ON s.id = e.student_id
            WHERE e.course_id = ${sale.courseId}
              AND s.company_id = ${sale.clientId}
            ORDER BY e.id DESC
            LIMIT ${displayLimit}
          `;
          const studentsResult = await db.execute(sql.raw(studentsQuery));
          const students = studentsResult.rows as any[];
          const studentNames = students.map(s => `${s.first_name} ${s.last_name}`).join(', ');
          // Show ... only if qty is greater than what we displayed
          const suffix = qty > displayLimit ? '...' : '';
          return { ...sale, activatedStudents: studentNames + suffix };
        } catch {
          return { ...sale, activatedStudents: '' };
        }
      }));
      
      res.json(salesWithStudents);
    } catch (error) {
      console.error("Sales error:", error);
      res.status(500).json({ error: "Failed to fetch sales" });
    }
  });

  // ============================================================
  // PLAYER API - Public endpoints for course player
  // ============================================================
  
  // Get course structure for player (no auth required - uses license code)
  app.get("/api/player/course/:id/structure", async (req, res) => {
    try {
      const courseId = parseInt(req.params.id);
      if (isNaN(courseId)) return res.status(400).json({ error: "Invalid course ID" });

      // Get course
      const [course] = await db.select().from(schema.courses).where(eq(schema.courses.id, courseId));
      if (!course) return res.status(404).json({ error: "Course not found" });

      // Get modules for this course
      const courseModules = await db.select({
        id: schema.modules.id,
        title: schema.modules.title,
        description: schema.modules.description,
        duration: schema.modules.duration,
        position: schema.courseModules.position,
      })
        .from(schema.courseModules)
        .innerJoin(schema.modules, eq(schema.courseModules.moduleId, schema.modules.id))
        .where(eq(schema.courseModules.courseId, courseId))
        .orderBy(schema.courseModules.position);

      // Get lessons for each module
      const modulesWithLessons = await Promise.all(courseModules.map(async (module) => {
        const moduleLessons = await db.select({
          id: schema.lessons.id,
          title: schema.lessons.title,
          description: schema.lessons.description,
          duration: schema.lessons.duration,
          position: schema.moduleLessons.position,
        })
          .from(schema.moduleLessons)
          .innerJoin(schema.lessons, eq(schema.moduleLessons.lessonId, schema.lessons.id))
          .where(eq(schema.moduleLessons.moduleId, module.id))
          .orderBy(schema.moduleLessons.position);

        // Get learning objects for each lesson
        const lessonsWithObjects = await Promise.all(moduleLessons.map(async (lesson) => {
          const lessonObjects = await db.select({
            id: schema.learningObjects.id,
            title: schema.learningObjects.title,
            objectType: schema.learningObjects.objectType,
            duration: schema.learningObjects.duration,
            jwplayerCode: schema.learningObjects.jwplayerCode,
            position: schema.lessonLearningObjects.position,
          })
            .from(schema.lessonLearningObjects)
            .innerJoin(schema.learningObjects, eq(schema.lessonLearningObjects.learningObjectId, schema.learningObjects.id))
            .where(eq(schema.lessonLearningObjects.lessonId, lesson.id))
            .orderBy(schema.lessonLearningObjects.position);

          return { ...lesson, learningObjects: lessonObjects };
        }));

        return { ...module, lessons: lessonsWithObjects };
      }));

      res.json({
        ...course,
        modules: modulesWithLessons,
      });
    } catch (error) {
      console.error("Player course structure error:", error);
      res.status(500).json({ error: "Failed to fetch course structure" });
    }
  });

  // Get quiz questions for a learning object (interruption points)
  app.get("/api/learning-objects/:id/interruptions", async (req, res) => {
    try {
      const loId = parseInt(req.params.id);
      if (isNaN(loId)) return res.status(400).json({ error: "Invalid learning object ID" });

      // Get quiz questions for this learning object
      const questions = await db.select()
        .from(schema.quizQuestions)
        .where(eq(schema.quizQuestions.learningObjectId, loId))
        .orderBy(schema.quizQuestions.sortOrder);

      // Get answers for each question and format as interruption points
      const interruptionPoints: { triggerTime: number; questions: any[] }[] = [];
      
      for (const question of questions) {
        const answers = await db.select()
          .from(schema.quizAnswers)
          .where(eq(schema.quizAnswers.questionId, question.id))
          .orderBy(schema.quizAnswers.sortOrder);

        // Check if we already have an interruption at this time
        let point = interruptionPoints.find(p => p.triggerTime === question.timeSeconds);
        if (!point) {
          point = { triggerTime: question.timeSeconds, questions: [] };
          interruptionPoints.push(point);
        }

        point.questions.push({
          id: question.id,
          text: question.questionText,
          answers: answers.map(a => ({
            id: a.id,
            text: a.answerText,
            isCorrect: a.isCorrect,
          })),
        });
      }

      // Sort by trigger time
      interruptionPoints.sort((a, b) => a.triggerTime - b.triggerTime);

      res.json(interruptionPoints);
    } catch (error) {
      console.error("Interruptions error:", error);
      res.status(500).json({ error: "Failed to fetch interruptions" });
    }
  });

  // Validate license code and get enrollment info
  app.post("/api/player/validate-license", async (req, res) => {
    try {
      const { licenseCode } = req.body;
      if (!licenseCode) return res.status(400).json({ error: "License code required" });

      const enrollment = await db.select({
        id: schema.enrollments.id,
        courseId: schema.enrollments.courseId,
        studentId: schema.enrollments.studentId,
        licenseCode: schema.enrollments.licenseCode,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        studentName: schema.students.firstName,
        studentSurname: schema.students.lastName,
        studentEmail: schema.students.email,
        courseTitle: schema.courses.title,
      })
        .from(schema.enrollments)
        .innerJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.licenseCode, licenseCode))
        .limit(1);

      if (enrollment.length === 0) {
        return res.status(404).json({ error: "Invalid license code" });
      }

      res.json({
        valid: true,
        enrollment: enrollment[0],
      });
    } catch (error) {
      console.error("License validation error:", error);
      res.status(500).json({ error: "Failed to validate license" });
    }
  });

  // Player login - authenticate with username (nome.cognome) and fiscalCode
  app.post("/api/player/login", async (req, res) => {
    try {
      const { username, fiscalCode } = req.body;
      if (!username || !fiscalCode) {
        return res.status(400).json({ error: "Username e codice fiscale richiesti" });
      }

      // Parse username (nome.cognome)
      const parts = username.toLowerCase().split(".");
      if (parts.length < 2) {
        return res.status(400).json({ error: "Username deve essere nel formato nome.cognome" });
      }
      const firstName = parts[0];
      const lastName = parts.slice(1).join(".");

      // Find student by name and fiscal code
      const studentResults = await db.select()
        .from(schema.students)
        .where(
          and(
            sql`LOWER(${schema.students.firstName}) = ${firstName}`,
            sql`LOWER(${schema.students.lastName}) = ${lastName}`,
            sql`${schema.students.fiscalCode} = ${fiscalCode}`
          )
        )
        .limit(1);

      if (studentResults.length === 0) {
        return res.status(401).json({ 
          success: false, 
          error: "Credenziali non valide. Verifica username e codice fiscale." 
        });
      }

      const student = studentResults[0];

      // Find active enrollment for this student
      const enrollmentResults = await db.select({
        id: schema.enrollments.id,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        courseTitle: schema.courses.title,
      })
        .from(schema.enrollments)
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(
          and(
            eq(schema.enrollments.studentId, student.id),
            eq(schema.enrollments.status, "active")
          )
        )
        .limit(1);

      if (enrollmentResults.length === 0) {
        return res.status(404).json({ 
          success: false, 
          error: "Nessun corso attivo trovato per questo utente." 
        });
      }

      const enrollment = enrollmentResults[0];

      // Get company info for the student
      const companyResults = await db.select()
        .from(schema.companies)
        .where(eq(schema.companies.id, student.companyId))
        .limit(1);

      const company = companyResults[0];

      // Update last access
      await db.update(schema.enrollments)
        .set({ lastAccessAt: new Date() })
        .where(eq(schema.enrollments.id, enrollment.id));

      res.json({
        success: true,
        user: {
          id: student.id,
          firstName: student.firstName,
          lastName: student.lastName,
          fiscalCode: student.fiscalCode,
          company: company?.businessName || "",
        },
        enrollment: {
          id: enrollment.id,
          learningProjectId: enrollment.courseId,
          courseName: enrollment.courseTitle,
          licenseCode: enrollment.licenseCode,
          startDate: enrollment.startDate,
          endDate: enrollment.endDate,
          progress: enrollment.progress || 0,
          status: enrollment.status,
        },
      });
    } catch (error) {
      console.error("Player login error:", error);
      res.status(500).json({ error: "Errore durante l'accesso" });
    }
  });

  // Save player progress
  app.post("/api/player/save-progress", async (req, res) => {
    try {
      const { enrollmentId, progress, currentLo, currentLesson } = req.body;
      if (!enrollmentId) return res.status(400).json({ error: "Enrollment ID required" });

      const status = progress >= 100 ? "completed" : "active";
      const completedAt = progress >= 100 ? new Date() : null;

      await db.update(schema.enrollments)
        .set({ 
          progress, 
          status,
          completedAt,
          lastAccessAt: new Date(),
        })
        .where(eq(schema.enrollments.id, enrollmentId));

      res.json({ success: true });
    } catch (error) {
      console.error("Save progress error:", error);
      res.status(500).json({ error: "Failed to save progress" });
    }
  });

  // ============================================================
  // ATTESTATI (Certificati) - Generate PDF
  // ============================================================
  
  // Generate certificate PDF for a completed enrollment
  app.get("/api/attestato/:enrollmentId/generate", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId);
      if (isNaN(enrollmentId)) return res.status(400).json({ error: "Invalid enrollment ID" });

      // Get enrollment with all related data
      const enrollmentData = await db.select({
        id: schema.enrollments.id,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        completedAt: schema.enrollments.completedAt,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        studentId: schema.students.id,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        studentFiscalCode: schema.students.fiscalCode,
        companyId: schema.companies.id,
        companyName: schema.companies.businessName,
        courseId: schema.courses.id,
        courseTitle: schema.courses.title,
        courseHours: schema.courses.hours,
        courseLawReference: schema.courses.lawReference,
        courseValidity: schema.courses.courseValidity,
        courseTargetAudience: schema.courses.targetAudience,
      })
        .from(schema.enrollments)
        .innerJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .innerJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .innerJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .where(eq(schema.enrollments.id, enrollmentId))
        .limit(1);

      if (enrollmentData.length === 0) {
        return res.status(404).json({ error: "Enrollment not found" });
      }

      const enrollment = enrollmentData[0];

      // Check if course is completed
      if (enrollment.status !== "completed" && enrollment.progress < 100) {
        return res.status(400).json({ error: "Il corso non è ancora completato" });
      }

      // Get tutor info from company
      const tutorData = await db.select()
        .from(schema.tutors)
        .where(eq(schema.tutors.id, enrollment.companyId))
        .limit(1);

      const tutor = tutorData[0] || { businessName: "Tutor81", regionalAuthorization: "" };

      // Get course modules
      const courseModules = await db.select({
        id: schema.modules.id,
        title: schema.modules.title,
        duration: schema.modules.duration,
      })
        .from(schema.courseModules)
        .innerJoin(schema.modules, eq(schema.courseModules.moduleId, schema.modules.id))
        .where(eq(schema.courseModules.courseId, enrollment.courseId))
        .orderBy(schema.courseModules.position);

      // Generate certificate number
      const certificateNumber = `T81-${enrollment.id}-${new Date().getFullYear()}`;

      // Check if certificate already exists
      const existingCert = await db.select()
        .from(schema.certificates)
        .where(eq(schema.certificates.enrollmentId, enrollmentId))
        .limit(1);

      let certificateId: number;
      if (existingCert.length > 0) {
        certificateId = existingCert[0].id;
      } else {
        // Create certificate record
        const [newCert] = await db.insert(schema.certificates)
          .values({
            enrollmentId,
            certificateNumber,
            issuedAt: new Date(),
          })
          .returning({ id: schema.certificates.id });
        certificateId = newCert.id;
      }

      // Generate PDF using PDFKit
      const PDFDocument = (await import("pdfkit")).default;
      const doc = new PDFDocument({ 
        size: "A4", 
        margin: 50,
        info: {
          Title: "Attestato di Formazione",
          Author: "Tutor81 LMS",
          Subject: `Attestato corso: ${enrollment.courseTitle}`,
        }
      });

      // Set response headers for PDF
      res.setHeader("Content-Type", "application/pdf");
      res.setHeader("Content-Disposition", `inline; filename="attestato_${enrollment.licenseCode}.pdf"`);
      
      doc.pipe(res);

      // Header
      doc.fontSize(24).fillColor("#1a1a1a").text("tutor", 50, 50, { continued: true });
      doc.fillColor("#eab308").text("81");
      
      doc.moveDown(0.5);
      doc.fontSize(10).fillColor("#666666").text(`TRACCIATO N° ${certificateNumber}`, { align: "center" });
      
      doc.moveDown(1);
      doc.fontSize(16).fillColor("#1a1a1a").text("ATTESTATO DI AVVENUTA FORMAZIONE IN E-LEARNING", { align: "center" });
      
      doc.moveDown(1);
      doc.fontSize(10).fillColor("#333333").text(
        "L'infrastruttura tecnologica TUTOR81 LMS certifica il completamento del corso in e-learning da parte di:",
        { align: "center" }
      );

      // Student info box
      doc.moveDown(1.5);
      const boxY = doc.y;
      doc.rect(50, boxY, 495, 120).stroke("#cccccc");
      
      doc.fontSize(11).fillColor("#1a1a1a");
      const labelX = 60;
      const valueX = 200;
      let currentY = boxY + 15;

      doc.text("Nominativo:", labelX, currentY);
      doc.font("Helvetica-Bold").text(`${enrollment.studentFirstName || ""} ${enrollment.studentLastName || ""}`.toUpperCase(), valueX, currentY);
      doc.font("Helvetica");
      
      currentY += 22;
      doc.text("Codice Fiscale:", labelX, currentY);
      doc.font("Helvetica-Bold").text((enrollment.studentFiscalCode || "").toUpperCase(), valueX, currentY);
      doc.font("Helvetica");

      currentY += 22;
      doc.text("Organizzatore:", labelX, currentY);
      doc.text((enrollment.companyName || "").toUpperCase(), valueX, currentY);

      currentY += 22;
      doc.text("Ente Formatore:", labelX, currentY);
      doc.text((tutor.businessName || "Tutor81").toUpperCase(), valueX, currentY);

      // Course details
      doc.moveDown(4);
      doc.fontSize(14).fillColor("#1a1a1a").text("Scheda Progettuale del Corso", { align: "center" });
      
      doc.moveDown(1);
      doc.fontSize(10).fillColor("#333333");

      const courseInfoY = doc.y;
      doc.text("Titolo del corso:", labelX, courseInfoY);
      doc.font("Helvetica-Bold").text(enrollment.courseTitle || "", valueX, courseInfoY, { width: 340 });
      doc.font("Helvetica");

      doc.moveDown(1.5);
      doc.text("Durata:", labelX, doc.y);
      doc.text(`${enrollment.courseHours || 0} ore`, valueX, doc.y - 12);

      doc.moveDown(0.5);
      doc.text("Riferimento normativo:", labelX, doc.y);
      doc.text(enrollment.courseLawReference || "D.Lgs. 81/2008", valueX, doc.y - 12, { width: 340 });

      doc.moveDown(0.5);
      doc.text("Validità corso:", labelX, doc.y);
      doc.text(enrollment.courseValidity || "5 anni", valueX, doc.y - 12);

      // Modules
      if (courseModules.length > 0) {
        doc.moveDown(1.5);
        doc.fontSize(12).fillColor("#1a1a1a").text("Moduli completati:", labelX);
        doc.moveDown(0.5);
        doc.fontSize(10).fillColor("#333333");
        
        courseModules.forEach((module, idx) => {
          doc.text(`${idx + 1}. ${module.title} (${module.duration || 0} min)`, labelX + 10);
        });
      }

      // Dates
      doc.moveDown(2);
      doc.fontSize(10);
      const startDateStr = enrollment.startDate ? new Date(enrollment.startDate).toLocaleDateString("it-IT") : "-";
      const endDateStr = enrollment.completedAt ? new Date(enrollment.completedAt).toLocaleDateString("it-IT") : "-";
      
      doc.text(`Data inizio: ${startDateStr}`, labelX);
      doc.text(`Data completamento: ${endDateStr}`, labelX);

      // Footer with signature area
      doc.moveDown(3);
      doc.fontSize(10).fillColor("#666666").text(
        `Documento generato automaticamente il ${new Date().toLocaleDateString("it-IT")}`,
        { align: "center" }
      );
      
      doc.moveDown(1);
      doc.text("Il presente attestato certifica l'avvenuta formazione secondo quanto previsto dalla normativa vigente.", { align: "center" });

      // Finalize PDF
      doc.end();

    } catch (error) {
      console.error("Certificate generation error:", error);
      res.status(500).json({ error: "Failed to generate certificate" });
    }
  });

  // Get certificate info for an enrollment
  app.get("/api/attestato/:enrollmentId", async (req, res) => {
    try {
      const enrollmentId = parseInt(req.params.enrollmentId);
      if (isNaN(enrollmentId)) return res.status(400).json({ error: "Invalid enrollment ID" });

      const certData = await db.select()
        .from(schema.certificates)
        .where(eq(schema.certificates.enrollmentId, enrollmentId))
        .limit(1);

      if (certData.length === 0) {
        return res.status(404).json({ error: "Certificate not found", exists: false });
      }

      res.json({ exists: true, certificate: certData[0] });
    } catch (error) {
      console.error("Certificate fetch error:", error);
      res.status(500).json({ error: "Failed to fetch certificate" });
    }
  });

  // ============================================================
  // EXPORT CSV - Generate from OVH database
  // ============================================================
  app.get("/api/export/tutor-gerarchia", async (req, res) => {
    try {
      const mysql = await import("mysql2/promise");
      
      const connection = await mysql.createConnection({
        host: '135.125.205.19',
        port: 3306,
        user: 'pro_tutor81',
        password: 'hpm0?7C3',
        database: 'pro_tutor81'
      });

      const [rows] = await connection.execute(`
        SELECT 
          tutor.id as id_ente_formativo,
          tutor.business_name as ente_formativo,
          admin_user.id as id_admin,
          CONCAT(admin_user.name, ' ', admin_user.surname) as admin,
          client.id as id_cliente,
          client.business_name as cliente,
          corsista.id as id_corsista,
          CONCAT(corsista.name, ' ', corsista.surname) as corsista
        FROM learning_project_users lpu
        JOIN users admin_user ON admin_user.id = lpu.company_id
        JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
        JOIN companies client ON client.id = lpu.id_company
        JOIN users corsista ON corsista.id = lpu.user_id
        WHERE tutor.business_name NOT LIKE '%MIROGLIO%'
          AND tutor.business_name NOT LIKE '%SINTEX%'
          AND tutor.business_name NOT LIKE '%ADECCO%'
        GROUP BY tutor.id, tutor.business_name, admin_user.id, admin_user.name, admin_user.surname, client.id, client.business_name, corsista.id, corsista.name, corsista.surname
        ORDER BY tutor.business_name, client.business_name, corsista.surname
      `);

      await connection.end();

      // Generate CSV for Numbers (Mac)
      const headers = "id_ente_formativo,ente_formativo,id_admin,admin,id_cliente,cliente,id_corsista,corsista";
      const csvRows = (rows as any[]).map(row => 
        `${row.id_ente_formativo},"${(row.ente_formativo || '').replace(/"/g, '""')}",${row.id_admin},"${(row.admin || '').replace(/"/g, '""')}",${row.id_cliente},"${(row.cliente || '').replace(/"/g, '""')}",${row.id_corsista},"${(row.corsista || '').replace(/"/g, '""')}"`
      );
      
      const csv = headers + "\n" + csvRows.join("\n");

      res.setHeader("Content-Type", "text/csv; charset=utf-8");
      res.setHeader("Content-Disposition", 'attachment; filename="tutor_gerarchia.csv"');
      res.setHeader("Content-Length", Buffer.byteLength(csv, 'utf8'));
      res.send(csv);
    } catch (error) {
      console.error("Export error:", error);
      res.status(500).json({ error: "Failed to generate export" });
    }
  });

  return httpServer;
}
