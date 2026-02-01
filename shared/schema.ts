import { pgTable, text, serial, integer, boolean, timestamp, jsonb, primaryKey, varchar, decimal } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";
import { relations, sql } from "drizzle-orm";

export * from "./models/auth";
import { users } from "./models/auth";

// ============================================================
// TABELLA 1: ENTI FORMATIVI (TUTOR)
// Chi eroga i corsi e vende alle aziende clienti
// ============================================================
export const tutors = pgTable("tutors", {
  id: serial("id").primaryKey(),
  businessName: text("business_name").notNull(),
  vatNumber: text("vat_number"),
  fiscalCode: text("fiscal_code"),
  address: text("address"),
  city: text("city"),
  cap: text("cap"),
  province: text("province"),
  phone: text("phone"),
  email: text("email"),
  pec: text("pec"),
  website: text("website"),
  regionalAuthorization: text("regional_authorization"),
  contactPerson: text("contact_person"),
  notes: text("notes"),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLA 2: AZIENDE CLIENTI
// Aziende che comprano corsi per i loro dipendenti
// ============================================================
export const companies = pgTable("companies", {
  id: serial("id").primaryKey(),
  tutorId: integer("tutor_id").references(() => tutors.id).notNull(), // CHI HA VENDUTO
  businessName: text("business_name").notNull(),
  vatNumber: text("vat_number"),
  fiscalCode: text("fiscal_code"),
  address: text("address"),
  city: text("city"),
  cap: text("cap"),
  province: text("province"),
  phone: text("phone"),
  email: text("email"),
  pec: text("pec"),
  contactPerson: text("contact_person"),
  notes: text("notes"),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLA 3: UTENTI (Studenti/Dipendenti)
// ============================================================
export const students = pgTable("students", {
  id: serial("id").primaryKey(),
  companyId: integer("company_id").references(() => companies.id).notNull(), // DOVE LAVORA
  email: text("email").notNull(),
  firstName: text("first_name"),
  lastName: text("last_name"),
  fiscalCode: text("fiscal_code"),
  phone: text("phone"),
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLA 4: CORSI (Learning Projects)
// ============================================================
export const courses = pgTable("courses", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  description: text("description"),
  category: text("category"),
  subcategory: text("subcategory"),
  riskLevel: text("risk_level"),
  sector: text("sector"),
  language: text("language").default("IT"),
  hours: integer("hours").default(0),
  modality: text("modality"),
  listPrice: decimal("list_price", { precision: 10, scale: 2 }).default("0"),
  tutorCost: decimal("tutor_cost", { precision: 10, scale: 2 }).default("0"),
  courseValidity: text("course_validity"),
  lawReference: text("law_reference"),
  maxExecutionTime: integer("max_execution_time").default(90),
  percentageToPass: integer("percentage_to_pass").default(80),
  objectives: text("objectives"),
  targetAudience: text("target_audience"),
  prerequisites: text("prerequisites"),
  courseProgram: text("course_program"),
  thumbnailUrl: text("thumbnail_url"),
  isPublished: boolean("is_published").default(true),
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// ============================================================
// TABELLA 5: MODULI (dentro i corsi)
// ============================================================
export const modules = pgTable("modules", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  description: text("description"),
  duration: integer("duration").default(0),
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLA 6: LEZIONI (dentro i moduli)
// ============================================================
export const lessons = pgTable("lessons", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  description: text("description"),
  duration: integer("duration").default(0),
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLA 7: LEARNING OBJECTS (video, slide, documenti)
// ============================================================
export const learningObjects = pgTable("learning_objects", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  objectType: text("object_type").default("video"), // 'video', 'slide', 'document'
  jwplayerCode: text("jwplayer_code"),
  videoFilename: text("video_filename"),
  slideFilename: text("slide_filename"),
  documentFilename: text("document_filename"),
  duration: integer("duration").default(0), // in minuti
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLE DI RELAZIONE (Many-to-Many)
// ============================================================
export const courseModules = pgTable("course_modules", {
  id: serial("id").primaryKey(),
  courseId: integer("course_id").references(() => courses.id).notNull(),
  moduleId: integer("module_id").references(() => modules.id).notNull(),
  position: integer("position").default(0),
});

export const moduleLessons = pgTable("module_lessons", {
  id: serial("id").primaryKey(),
  moduleId: integer("module_id").references(() => modules.id).notNull(),
  lessonId: integer("lesson_id").references(() => lessons.id).notNull(),
  position: integer("position").default(0),
});

export const lessonLearningObjects = pgTable("lesson_learning_objects", {
  id: serial("id").primaryKey(),
  lessonId: integer("lesson_id").references(() => lessons.id).notNull(),
  learningObjectId: integer("learning_object_id").references(() => learningObjects.id).notNull(),
  position: integer("position").default(0),
});

// ============================================================
// TABELLA 8: ISCRIZIONI (Enrollments)
// Uno studente iscritto a un corso
// ============================================================
export const enrollments = pgTable("enrollments", {
  id: serial("id").primaryKey(),
  studentId: integer("student_id").references(() => students.id).notNull(),
  courseId: integer("course_id").references(() => courses.id).notNull(),
  licenseCode: text("license_code").notNull(), // Codice univoco per accesso player
  startDate: timestamp("start_date"),
  endDate: timestamp("end_date"),
  daysToAlert: integer("days_to_alert").default(15),
  progress: integer("progress").default(0), // 0-100%
  status: text("status").default("active"), // 'active', 'completed', 'expired'
  completedAt: timestamp("completed_at"),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// TABELLA 9: PROGRESSO DETTAGLIATO
// Traccia quale learning object è stato completato
// ============================================================
export const enrollmentProgress = pgTable("enrollment_progress", {
  id: serial("id").primaryKey(),
  enrollmentId: integer("enrollment_id").references(() => enrollments.id).notNull(),
  learningObjectId: integer("learning_object_id").references(() => learningObjects.id).notNull(),
  watchedSeconds: integer("watched_seconds").default(0),
  completed: boolean("completed").default(false),
  completedAt: timestamp("completed_at"),
});

// ============================================================
// TABELLA 10: ATTESTATI (Certificati)
// ============================================================
export const certificates = pgTable("certificates", {
  id: serial("id").primaryKey(),
  enrollmentId: integer("enrollment_id").references(() => enrollments.id).notNull(),
  certificateNumber: text("certificate_number"),
  issuedAt: timestamp("issued_at").defaultNow(),
  pdfUrl: text("pdf_url"),
});

// ============================================================
// TABELLA 11: QUIZ/TEST (opzionale, per interruzioni video)
// ============================================================
export const quizQuestions = pgTable("quiz_questions", {
  id: serial("id").primaryKey(),
  learningObjectId: integer("learning_object_id").references(() => learningObjects.id).notNull(),
  timeSeconds: integer("time_seconds").notNull(), // A che secondo del video appare
  questionText: text("question_text").notNull(),
  sortOrder: integer("sort_order").default(0),
});

export const quizAnswers = pgTable("quiz_answers", {
  id: serial("id").primaryKey(),
  questionId: integer("question_id").references(() => quizQuestions.id).notNull(),
  answerText: text("answer_text").notNull(),
  isCorrect: boolean("is_correct").default(false),
  sortOrder: integer("sort_order").default(0),
});

// ============================================================
// TABELLA 12: ADMIN USERS (per accesso al sistema)
// ============================================================
export const adminUsers = pgTable("admin_users", {
  id: serial("id").primaryKey(),
  replitUserId: varchar("replit_user_id").references(() => users.id),
  email: text("email").notNull(),
  role: text("role").default("tutor_admin"), // 'super_admin', 'tutor_admin', 'company_admin'
  tutorId: integer("tutor_id").references(() => tutors.id), // NULL se super_admin
  companyId: integer("company_id").references(() => companies.id), // NULL se tutor_admin o super_admin
  isActive: boolean("is_active").default(true),
  createdAt: timestamp("created_at").defaultNow(),
});

// ============================================================
// RELATIONS
// ============================================================
export const tutorsRelations = relations(tutors, ({ many }) => ({
  companies: many(companies),
  adminUsers: many(adminUsers),
}));

export const companiesRelations = relations(companies, ({ one, many }) => ({
  tutor: one(tutors, {
    fields: [companies.tutorId],
    references: [tutors.id],
  }),
  students: many(students),
  adminUsers: many(adminUsers),
}));

export const studentsRelations = relations(students, ({ one, many }) => ({
  company: one(companies, {
    fields: [students.companyId],
    references: [companies.id],
  }),
  enrollments: many(enrollments),
}));

export const coursesRelations = relations(courses, ({ many }) => ({
  courseModules: many(courseModules),
  enrollments: many(enrollments),
}));

export const modulesRelations = relations(modules, ({ many }) => ({
  courseModules: many(courseModules),
  moduleLessons: many(moduleLessons),
}));

export const lessonsRelations = relations(lessons, ({ many }) => ({
  moduleLessons: many(moduleLessons),
  lessonLearningObjects: many(lessonLearningObjects),
}));

export const learningObjectsRelations = relations(learningObjects, ({ many }) => ({
  lessonLearningObjects: many(lessonLearningObjects),
  quizQuestions: many(quizQuestions),
  enrollmentProgress: many(enrollmentProgress),
}));

export const courseModulesRelations = relations(courseModules, ({ one }) => ({
  course: one(courses, {
    fields: [courseModules.courseId],
    references: [courses.id],
  }),
  module: one(modules, {
    fields: [courseModules.moduleId],
    references: [modules.id],
  }),
}));

export const moduleLessonsRelations = relations(moduleLessons, ({ one }) => ({
  module: one(modules, {
    fields: [moduleLessons.moduleId],
    references: [modules.id],
  }),
  lesson: one(lessons, {
    fields: [moduleLessons.lessonId],
    references: [lessons.id],
  }),
}));

export const lessonLearningObjectsRelations = relations(lessonLearningObjects, ({ one }) => ({
  lesson: one(lessons, {
    fields: [lessonLearningObjects.lessonId],
    references: [lessons.id],
  }),
  learningObject: one(learningObjects, {
    fields: [lessonLearningObjects.learningObjectId],
    references: [learningObjects.id],
  }),
}));

export const enrollmentsRelations = relations(enrollments, ({ one, many }) => ({
  student: one(students, {
    fields: [enrollments.studentId],
    references: [students.id],
  }),
  course: one(courses, {
    fields: [enrollments.courseId],
    references: [courses.id],
  }),
  progress: many(enrollmentProgress),
  certificate: one(certificates),
}));

export const enrollmentProgressRelations = relations(enrollmentProgress, ({ one }) => ({
  enrollment: one(enrollments, {
    fields: [enrollmentProgress.enrollmentId],
    references: [enrollments.id],
  }),
  learningObject: one(learningObjects, {
    fields: [enrollmentProgress.learningObjectId],
    references: [learningObjects.id],
  }),
}));

export const certificatesRelations = relations(certificates, ({ one }) => ({
  enrollment: one(enrollments, {
    fields: [certificates.enrollmentId],
    references: [enrollments.id],
  }),
}));

export const quizQuestionsRelations = relations(quizQuestions, ({ one, many }) => ({
  learningObject: one(learningObjects, {
    fields: [quizQuestions.learningObjectId],
    references: [learningObjects.id],
  }),
  answers: many(quizAnswers),
}));

export const quizAnswersRelations = relations(quizAnswers, ({ one }) => ({
  question: one(quizQuestions, {
    fields: [quizAnswers.questionId],
    references: [quizQuestions.id],
  }),
}));

export const adminUsersRelations = relations(adminUsers, ({ one }) => ({
  replitUser: one(users, {
    fields: [adminUsers.replitUserId],
    references: [users.id],
  }),
  tutor: one(tutors, {
    fields: [adminUsers.tutorId],
    references: [tutors.id],
  }),
  company: one(companies, {
    fields: [adminUsers.companyId],
    references: [companies.id],
  }),
}));

// ============================================================
// INSERT SCHEMAS & TYPES
// ============================================================
export const insertTutorSchema = createInsertSchema(tutors).omit({ id: true, createdAt: true });
export const insertCompanySchema = createInsertSchema(companies).omit({ id: true, createdAt: true });
export const insertStudentSchema = createInsertSchema(students).omit({ id: true, createdAt: true });
export const insertCourseSchema = createInsertSchema(courses).omit({ id: true, createdAt: true, updatedAt: true });
export const insertModuleSchema = createInsertSchema(modules).omit({ id: true, createdAt: true });
export const insertLessonSchema = createInsertSchema(lessons).omit({ id: true, createdAt: true });
export const insertLearningObjectSchema = createInsertSchema(learningObjects).omit({ id: true, createdAt: true });
export const insertEnrollmentSchema = createInsertSchema(enrollments).omit({ id: true, createdAt: true });
export const insertCertificateSchema = createInsertSchema(certificates).omit({ id: true, issuedAt: true });
export const insertAdminUserSchema = createInsertSchema(adminUsers).omit({ id: true, createdAt: true });

export type Tutor = typeof tutors.$inferSelect;
export type InsertTutor = z.infer<typeof insertTutorSchema>;

export type Company = typeof companies.$inferSelect;
export type InsertCompany = z.infer<typeof insertCompanySchema>;

export type Student = typeof students.$inferSelect;
export type InsertStudent = z.infer<typeof insertStudentSchema>;

export type Course = typeof courses.$inferSelect;
export type InsertCourse = z.infer<typeof insertCourseSchema>;

export type Module = typeof modules.$inferSelect;
export type InsertModule = z.infer<typeof insertModuleSchema>;

export type Lesson = typeof lessons.$inferSelect;
export type InsertLesson = z.infer<typeof insertLessonSchema>;

export type LearningObject = typeof learningObjects.$inferSelect;
export type InsertLearningObject = z.infer<typeof insertLearningObjectSchema>;

export type Enrollment = typeof enrollments.$inferSelect;
export type InsertEnrollment = z.infer<typeof insertEnrollmentSchema>;

export type Certificate = typeof certificates.$inferSelect;
export type InsertCertificate = z.infer<typeof insertCertificateSchema>;

export type AdminUser = typeof adminUsers.$inferSelect;
export type InsertAdminUser = z.infer<typeof insertAdminUserSchema>;
