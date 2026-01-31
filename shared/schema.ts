import { pgTable, text, serial, integer, boolean, timestamp, jsonb, primaryKey, varchar, decimal } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";
import { relations, sql } from "drizzle-orm";

export * from "./models/auth";
import { users } from "./models/auth";

export const companies = pgTable("companies", {
  id: serial("id").primaryKey(),
  businessName: text("business_name").notNull(),
  address: text("address"),
  city: text("city"),
  cap: text("cap"),
  province: text("province"),
  vatNumber: text("vat_number"),
  fiscalCode: text("fiscal_code"),
  phone: text("phone"),
  email: text("email"),
  pec: text("pec"),
  website: text("website"),
  regionalAuthorization: text("regional_authorization"),
  licenseType: text("license_type"),
  isTutor: boolean("is_tutor").default(false),
  ownerUserId: varchar("owner_user_id").references(() => users.id),
  parentCompanyId: integer("parent_company_id"),
  contactPerson: text("contact_person"),
  notes: text("notes"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const learningProjects = pgTable("learning_projects", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  description: text("description"),
  category: text("category"),
  riskLevel: text("risk_level"),
  sector: text("sector"),
  language: text("language").default("IT"),
  hours: integer("hours").default(0),
  modality: text("modality"),
  listPrice: decimal("list_price", { precision: 10, scale: 2 }).default("0"),
  tutorCost: decimal("tutor_cost", { precision: 10, scale: 2 }).default("0"),
  isPublished: boolean("is_published").default(true),
  isPublishedInEcommerce: integer("is_published_in_ecommerce").default(0), // 0=non pubblicato, 1=attivo, 2=sospeso
  reservedTo: integer("reserved_to").references(() => companies.id),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
  thumbnailUrl: text("thumbnail_url"),
  sortOrder: integer("sort_order").default(0),
  subcategory: text("subcategory"),
  courseType: text("course_type"),
  destinatario: text("destinatario"),
  destination: text("destination"),
  courseValidity: text("course_validity"),
  externalIntegration: text("external_integration"),
  lawReference: text("law_reference"),
  totalElearning: integer("total_elearning").default(0),
  maxExecutionTime: integer("max_execution_time").default(90),
  percentageToPass: integer("percentage_to_pass").default(80),
  producers: text("producers"),
  professors: text("professors"),
  didactics: text("didactics"),
  objectives: text("objectives"),
  targetAudience: text("target_audience"),
  prerequisites: text("prerequisites"),
  courseProgram: text("course_program"),
  ownerUserId: integer("owner_user_id"),
});

export const tutorsPurchases = pgTable("tutors_purchases", {
  id: serial("id").primaryKey(),
  tutorId: integer("tutor_id").references(() => companies.id),
  customerCompanyId: integer("customer_company_id").references(() => companies.id),
  userCompanyRef: varchar("user_company_ref").references(() => users.id),
  learningProjectId: integer("learning_project_id").references(() => learningProjects.id),
  qta: integer("qta").default(1),
  price: decimal("price", { precision: 10, scale: 2 }).default("0"),
  startDate: timestamp("start_date"),
  endDate: timestamp("end_date"),
  notifyDays: integer("notify_days").default(15),
  status: text("status").default("active"),
  createdAt: timestamp("creation_date").defaultNow(),
});

export const companyUsers = pgTable("company_users", {
  id: serial("id").primaryKey(),
  userId: varchar("user_id").notNull().references(() => users.id),
  companyId: integer("company_id").notNull().references(() => companies.id),
  role: integer("role").default(0),
  createdAt: timestamp("created_at").defaultNow(),
});

export const certificates = pgTable("certificates", {
  id: serial("id").primaryKey(),
  purchaseId: integer("purchase_id").references(() => tutorsPurchases.id),
  userId: varchar("user_id").references(() => users.id),
  courseTitle: text("course_title"),
  completedAt: timestamp("completed_at"),
  certificateUrl: text("certificate_url"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const enrollments = pgTable("enrollments", {
  id: serial("id").primaryKey(),
  legacyId: integer("legacy_id"),
  purchaseId: integer("purchase_id").references(() => tutorsPurchases.id),
  userId: varchar("user_id").references(() => users.id),
  legacyUserId: integer("legacy_user_id"),
  companyId: integer("company_id").references(() => companies.id),
  learningProjectId: integer("learning_project_id").references(() => learningProjects.id),
  startDate: timestamp("start_date"),
  endDate: timestamp("end_date"),
  lastAccessAt: timestamp("last_access_at"),
  progress: integer("progress").default(0),
  status: text("status").default("active"),
  accreditationCode: text("accreditation_code"),
  daysToAlert: integer("days_to_alert").default(15),
  createdAt: timestamp("created_at").defaultNow(),
});

export const enrollmentsRelations = relations(enrollments, ({ one }) => ({
  purchase: one(tutorsPurchases, {
    fields: [enrollments.purchaseId],
    references: [tutorsPurchases.id],
  }),
  user: one(users, {
    fields: [enrollments.userId],
    references: [users.id],
  }),
  company: one(companies, {
    fields: [enrollments.companyId],
    references: [companies.id],
  }),
  learningProject: one(learningProjects, {
    fields: [enrollments.learningProjectId],
    references: [learningProjects.id],
  }),
}));

export const companiesRelations = relations(companies, ({ one, many }) => ({
  owner: one(users, {
    fields: [companies.ownerUserId],
    references: [users.id],
  }),
  parentCompany: one(companies, {
    fields: [companies.parentCompanyId],
    references: [companies.id],
  }),
  companyUsers: many(companyUsers),
  tutorPurchases: many(tutorsPurchases, { relationName: "tutorPurchases" }),
  clientPurchases: many(tutorsPurchases, { relationName: "clientPurchases" }),
}));

export const learningProjectsRelations = relations(learningProjects, ({ many }) => ({
  purchases: many(tutorsPurchases),
}));

export const tutorsPurchasesRelations = relations(tutorsPurchases, ({ one }) => ({
  tutor: one(companies, {
    fields: [tutorsPurchases.tutorId],
    references: [companies.id],
    relationName: "tutorPurchases",
  }),
  customer: one(companies, {
    fields: [tutorsPurchases.customerCompanyId],
    references: [companies.id],
    relationName: "clientPurchases",
  }),
  user: one(users, {
    fields: [tutorsPurchases.userCompanyRef],
    references: [users.id],
  }),
  learningProject: one(learningProjects, {
    fields: [tutorsPurchases.learningProjectId],
    references: [learningProjects.id],
  }),
}));

export const companyUsersRelations = relations(companyUsers, ({ one }) => ({
  user: one(users, {
    fields: [companyUsers.userId],
    references: [users.id],
  }),
  company: one(companies, {
    fields: [companyUsers.companyId],
    references: [companies.id],
  }),
}));

// Content Management Tables
export const modules = pgTable("modules", {
  id: serial("id").primaryKey(),
  learningProjectId: integer("learning_project_id").notNull().references(() => learningProjects.id),
  title: text("title").notNull(),
  description: text("description"),
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow(),
});

export const lessons = pgTable("lessons", {
  id: serial("id").primaryKey(),
  moduleId: integer("module_id").notNull().references(() => modules.id),
  title: text("title").notNull(),
  description: text("description"),
  contentType: text("content_type").default("video"), // video, audio, document, scorm
  contentUrl: text("content_url"),
  duration: integer("duration").default(0), // in minutes
  sortOrder: integer("sort_order").default(0),
  createdAt: timestamp("created_at").defaultNow(),
});

export const learningObjects = pgTable("learning_objects", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  objectType: text("object_type").notNull(), // video, audio, document, scorm, image
  fileUrl: text("file_url"),
  mimeType: text("mime_type"),
  fileSize: integer("file_size"),
  duration: integer("duration"), // for video/audio
  metadata: jsonb("metadata"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const tests = pgTable("tests", {
  id: serial("id").primaryKey(),
  learningProjectId: integer("learning_project_id").references(() => learningProjects.id),
  moduleId: integer("module_id").references(() => modules.id),
  title: text("title").notNull(),
  description: text("description"),
  passingScore: integer("passing_score").default(60),
  timeLimit: integer("time_limit"), // in minutes
  maxAttempts: integer("max_attempts").default(3),
  shuffleQuestions: boolean("shuffle_questions").default(true),
  isPublished: boolean("is_published").default(false),
  createdAt: timestamp("created_at").defaultNow(),
});

export const questions = pgTable("questions", {
  id: serial("id").primaryKey(),
  testId: integer("test_id").notNull().references(() => tests.id),
  questionText: text("question_text").notNull(),
  questionType: text("question_type").default("single"), // single, multiple, true_false
  points: integer("points").default(1),
  sortOrder: integer("sort_order").default(0),
  explanation: text("explanation"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const answers = pgTable("answers", {
  id: serial("id").primaryKey(),
  questionId: integer("question_id").notNull().references(() => questions.id),
  answerText: text("answer_text").notNull(),
  isCorrect: boolean("is_correct").default(false),
  sortOrder: integer("sort_order").default(0),
});

// Relations for Content Management
export const modulesRelations = relations(modules, ({ one, many }) => ({
  learningProject: one(learningProjects, {
    fields: [modules.learningProjectId],
    references: [learningProjects.id],
  }),
  lessons: many(lessons),
  tests: many(tests),
}));

export const lessonsRelations = relations(lessons, ({ one }) => ({
  module: one(modules, {
    fields: [lessons.moduleId],
    references: [modules.id],
  }),
}));

export const testsRelations = relations(tests, ({ one, many }) => ({
  learningProject: one(learningProjects, {
    fields: [tests.learningProjectId],
    references: [learningProjects.id],
  }),
  module: one(modules, {
    fields: [tests.moduleId],
    references: [modules.id],
  }),
  questions: many(questions),
}));

export const questionsRelations = relations(questions, ({ one, many }) => ({
  test: one(tests, {
    fields: [questions.testId],
    references: [tests.id],
  }),
  answers: many(answers),
}));

export const answersRelations = relations(answers, ({ one }) => ({
  question: one(questions, {
    fields: [answers.questionId],
    references: [questions.id],
  }),
}));

export const insertCompanySchema = createInsertSchema(companies).omit({ id: true, createdAt: true });
export const insertLearningProjectSchema = createInsertSchema(learningProjects).omit({ id: true, createdAt: true });
export const insertTutorsPurchaseSchema = createInsertSchema(tutorsPurchases).omit({ id: true, createdAt: true });
export const insertCompanyUserSchema = createInsertSchema(companyUsers).omit({ id: true, createdAt: true });
export const insertCertificateSchema = createInsertSchema(certificates).omit({ id: true, createdAt: true });
export const insertModuleSchema = createInsertSchema(modules).omit({ id: true, createdAt: true });
export const insertLessonSchema = createInsertSchema(lessons).omit({ id: true, createdAt: true });
export const insertLearningObjectSchema = createInsertSchema(learningObjects).omit({ id: true, createdAt: true });
export const insertTestSchema = createInsertSchema(tests).omit({ id: true, createdAt: true });
export const insertQuestionSchema = createInsertSchema(questions).omit({ id: true, createdAt: true });
export const insertAnswerSchema = createInsertSchema(answers).omit({ id: true });
export const insertEnrollmentSchema = createInsertSchema(enrollments).omit({ id: true, createdAt: true });

export type Enrollment = typeof enrollments.$inferSelect;
export type InsertEnrollment = z.infer<typeof insertEnrollmentSchema>;

export type Company = typeof companies.$inferSelect;
export type InsertCompany = z.infer<typeof insertCompanySchema>;

export type LearningProject = typeof learningProjects.$inferSelect;
export type InsertLearningProject = z.infer<typeof insertLearningProjectSchema>;

export type TutorsPurchase = typeof tutorsPurchases.$inferSelect;
export type InsertTutorsPurchase = z.infer<typeof insertTutorsPurchaseSchema>;

export type CompanyUser = typeof companyUsers.$inferSelect;
export type InsertCompanyUser = z.infer<typeof insertCompanyUserSchema>;

export type Certificate = typeof certificates.$inferSelect;
export type InsertCertificate = z.infer<typeof insertCertificateSchema>;

export type Module = typeof modules.$inferSelect;
export type InsertModule = z.infer<typeof insertModuleSchema>;

export type Lesson = typeof lessons.$inferSelect;
export type InsertLesson = z.infer<typeof insertLessonSchema>;

export type LearningObject = typeof learningObjects.$inferSelect;
export type InsertLearningObject = z.infer<typeof insertLearningObjectSchema>;

export type Test = typeof tests.$inferSelect;
export type InsertTest = z.infer<typeof insertTestSchema>;

export type Question = typeof questions.$inferSelect;
export type InsertQuestion = z.infer<typeof insertQuestionSchema>;

export type Answer = typeof answers.$inferSelect;
export type InsertAnswer = z.infer<typeof insertAnswerSchema>;

// Invoices archive
export const invoices = pgTable("invoices", {
  id: serial("id").primaryKey(),
  tutorId: integer("tutor_id").references(() => companies.id),
  tutorName: text("tutor_name").notNull(),
  month: integer("month").notNull(),
  year: integer("year").notNull(),
  orderIds: text("order_ids").notNull(),
  totalAmount: decimal("total_amount", { precision: 10, scale: 2 }).notNull(),
  invoiceNumber: text("invoice_number"),
  createdAt: timestamp("created_at").defaultNow(),
});

export const insertInvoiceSchema = createInsertSchema(invoices).omit({ id: true, createdAt: true });
export type Invoice = typeof invoices.$inferSelect;
export type InsertInvoice = z.infer<typeof insertInvoiceSchema>;
