import { pgTable, text, serial, integer, boolean, timestamp, jsonb, primaryKey } from "drizzle-orm/pg-core";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";
import { relations } from "drizzle-orm";

// Import auth tables
export * from "./models/auth";
import { users } from "./models/auth";

// === TABLE DEFINITIONS ===

export const courses = pgTable("courses", {
  id: serial("id").primaryKey(),
  title: text("title").notNull(),
  description: text("description").notNull(),
  imageUrl: text("image_url"), // Optional course image
  price: integer("price").default(0), // Price in cents, 0 = free
  isPublished: boolean("is_published").default(false),
  instructorId: text("instructor_id").references(() => users.id), // FK to auth users
  createdAt: timestamp("created_at").defaultNow(),
});

export const modules = pgTable("modules", {
  id: serial("id").primaryKey(),
  courseId: integer("course_id").notNull().references(() => courses.id, { onDelete: 'cascade' }),
  title: text("title").notNull(),
  order: integer("order").notNull(), // To order modules within a course
});

export const lessons = pgTable("lessons", {
  id: serial("id").primaryKey(),
  moduleId: integer("module_id").notNull().references(() => modules.id, { onDelete: 'cascade' }),
  title: text("title").notNull(),
  content: text("content"), // Text content
  videoUrl: text("video_url"), // Video URL (e.g., YouTube, Vimeo, or direct link)
  order: integer("order").notNull(),
  duration: integer("duration"), // Duration in minutes
});

export const enrollments = pgTable("enrollments", {
  id: serial("id").primaryKey(),
  userId: text("user_id").notNull().references(() => users.id),
  courseId: integer("course_id").notNull().references(() => courses.id),
  enrolledAt: timestamp("enrolled_at").defaultNow(),
  // Could add payment info here later
});

export const progress = pgTable("progress", {
  id: serial("id").primaryKey(),
  enrollmentId: integer("enrollment_id").notNull().references(() => enrollments.id, { onDelete: 'cascade' }),
  lessonId: integer("lesson_id").notNull().references(() => lessons.id, { onDelete: 'cascade' }),
  completed: boolean("completed").default(false),
  completedAt: timestamp("completed_at"),
});

// === RELATIONS ===

export const coursesRelations = relations(courses, ({ one, many }) => ({
  instructor: one(users, {
    fields: [courses.instructorId],
    references: [users.id],
  }),
  modules: many(modules),
  enrollments: many(enrollments),
}));

export const modulesRelations = relations(modules, ({ one, many }) => ({
  course: one(courses, {
    fields: [modules.courseId],
    references: [courses.id],
  }),
  lessons: many(lessons),
}));

export const lessonsRelations = relations(lessons, ({ one, many }) => ({
  module: one(modules, {
    fields: [lessons.moduleId],
    references: [modules.id],
  }),
  progress: many(progress),
}));

export const enrollmentsRelations = relations(enrollments, ({ one, many }) => ({
  user: one(users, {
    fields: [enrollments.userId],
    references: [users.id],
  }),
  course: one(courses, {
    fields: [enrollments.courseId],
    references: [courses.id],
  }),
  progress: many(progress),
}));

export const progressRelations = relations(progress, ({ one }) => ({
  enrollment: one(enrollments, {
    fields: [progress.enrollmentId],
    references: [enrollments.id],
  }),
  lesson: one(lessons, {
    fields: [progress.lessonId],
    references: [lessons.id],
  }),
}));

// === BASE SCHEMAS ===

export const insertCourseSchema = createInsertSchema(courses).omit({ id: true, createdAt: true });
export const insertModuleSchema = createInsertSchema(modules).omit({ id: true });
export const insertLessonSchema = createInsertSchema(lessons).omit({ id: true });
export const insertEnrollmentSchema = createInsertSchema(enrollments).omit({ id: true, enrolledAt: true });
export const insertProgressSchema = createInsertSchema(progress).omit({ id: true });

// === EXPLICIT API CONTRACT TYPES ===

export type Course = typeof courses.$inferSelect;
export type InsertCourse = z.infer<typeof insertCourseSchema>;
export type CreateCourseRequest = InsertCourse;
export type UpdateCourseRequest = Partial<InsertCourse>;

export type Module = typeof modules.$inferSelect;
export type InsertModule = z.infer<typeof insertModuleSchema>;
export type CreateModuleRequest = InsertModule;
export type UpdateModuleRequest = Partial<InsertModule>;

export type Lesson = typeof lessons.$inferSelect;
export type InsertLesson = z.infer<typeof insertLessonSchema>;
export type CreateLessonRequest = InsertLesson;
export type UpdateLessonRequest = Partial<InsertLesson>;

export type Enrollment = typeof enrollments.$inferSelect;
export type CreateEnrollmentRequest = { courseId: number }; // User ID comes from auth

export type Progress = typeof progress.$inferSelect;
export type UpdateProgressRequest = { completed: boolean };

// For responses including relations
export type CourseWithModules = Course & { modules: (Module & { lessons: Lesson[] })[] };
export type CourseWithInstructor = Course & { instructor: typeof users.$inferSelect };
