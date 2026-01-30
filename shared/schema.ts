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
  createdAt: timestamp("created_at").defaultNow(),
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

export const insertCompanySchema = createInsertSchema(companies).omit({ id: true, createdAt: true });
export const insertLearningProjectSchema = createInsertSchema(learningProjects).omit({ id: true, createdAt: true });
export const insertTutorsPurchaseSchema = createInsertSchema(tutorsPurchases).omit({ id: true, createdAt: true });
export const insertCompanyUserSchema = createInsertSchema(companyUsers).omit({ id: true, createdAt: true });
export const insertCertificateSchema = createInsertSchema(certificates).omit({ id: true, createdAt: true });

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
