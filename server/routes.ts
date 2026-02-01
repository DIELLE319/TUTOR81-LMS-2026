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
      // Get all companies with their tutor info
      const companies = await db.select({
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
        .leftJoin(schema.tutors, eq(schema.companies.tutorId, schema.tutors.id))
        .orderBy(schema.tutors.businessName, schema.companies.businessName);

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
      const tutorId = req.query.tutorId ? parseInt(req.query.tutorId as string) : null;
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
  // STUDENTS (Studenti/Dipendenti)
  // ============================================================
  app.get("/api/students", isAuthenticated, async (req, res) => {
    try {
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;

      const students = await db.select({
        id: schema.students.id,
        companyId: schema.students.companyId,
        email: schema.students.email,
        firstName: schema.students.firstName,
        lastName: schema.students.lastName,
        fiscalCode: schema.students.fiscalCode,
        isActive: schema.students.isActive,
        companyName: schema.companies.businessName,
      })
        .from(schema.students)
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .orderBy(schema.students.lastName);

      let filtered = students;
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
  // COURSES (Corsi)
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
      const tutorId = req.query.tutorId ? parseInt(req.query.tutorId as string) : null;
      const companyId = req.query.companyId ? parseInt(req.query.companyId as string) : null;

      const enrollments = await db.select({
        id: schema.enrollments.id,
        studentId: schema.enrollments.studentId,
        courseId: schema.enrollments.courseId,
        licenseCode: schema.enrollments.licenseCode,
        startDate: schema.enrollments.startDate,
        endDate: schema.enrollments.endDate,
        progress: schema.enrollments.progress,
        status: schema.enrollments.status,
        createdAt: schema.enrollments.createdAt,
        studentEmail: schema.students.email,
        studentFirstName: schema.students.firstName,
        studentLastName: schema.students.lastName,
        companyId: schema.students.companyId,
        companyName: schema.companies.businessName,
        tutorId: schema.companies.tutorId,
        courseTitle: schema.courses.title,
      })
        .from(schema.enrollments)
        .leftJoin(schema.students, eq(schema.enrollments.studentId, schema.students.id))
        .leftJoin(schema.companies, eq(schema.students.companyId, schema.companies.id))
        .leftJoin(schema.courses, eq(schema.enrollments.courseId, schema.courses.id))
        .orderBy(desc(schema.enrollments.createdAt));

      let filtered = enrollments;
      if (tutorId) {
        filtered = filtered.filter(e => e.tutorId === tutorId);
      }
      if (companyId) {
        filtered = filtered.filter(e => e.companyId === companyId);
      }

      res.json(filtered);
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

  return httpServer;
}
