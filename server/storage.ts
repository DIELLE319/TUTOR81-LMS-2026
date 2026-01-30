import { 
  courses, modules, lessons, enrollments, progress, users,
  type InsertCourse, type InsertModule, type InsertLesson,
  type Course, type Module, type Lesson, type Enrollment, type Progress,
  type User
} from "@shared/schema";
import { db } from "./db";
import { eq, and } from "drizzle-orm";

export interface IStorage {
  // Courses
  getCourses(publishedOnly?: boolean): Promise<Course[]>;
  getCourse(id: number): Promise<Course | undefined>;
  createCourse(course: InsertCourse): Promise<Course>;
  updateCourse(id: number, course: Partial<InsertCourse>): Promise<Course>;
  deleteCourse(id: number): Promise<void>;

  // Modules
  getModules(courseId: number): Promise<Module[]>;
  createModule(module: InsertModule): Promise<Module>;
  updateModule(id: number, module: Partial<InsertModule>): Promise<Module>;
  deleteModule(id: number): Promise<void>;

  // Lessons
  getLessons(moduleId: number): Promise<Lesson[]>;
  getLesson(id: number): Promise<Lesson | undefined>;
  createLesson(lesson: InsertLesson): Promise<Lesson>;
  updateLesson(id: number, lesson: Partial<InsertLesson>): Promise<Lesson>;
  deleteLesson(id: number): Promise<void>;

  // Enrollments
  getEnrollments(userId: string): Promise<(Enrollment & { course: Course })[]>;
  createEnrollment(userId: string, courseId: number): Promise<Enrollment>;
  getEnrollment(userId: string, courseId: number): Promise<Enrollment | undefined>;

  // Progress
  updateProgress(enrollmentId: number, lessonId: number, completed: boolean): Promise<Progress>;
  getProgress(enrollmentId: number): Promise<Progress[]>;
  
  // Auth User helper
  getUser(id: string): Promise<User | undefined>;
}

export class DatabaseStorage implements IStorage {
  async getCourses(publishedOnly = false): Promise<Course[]> {
    if (publishedOnly) {
      return await db.select().from(courses).where(eq(courses.isPublished, true));
    }
    return await db.select().from(courses);
  }

  async getCourse(id: number): Promise<Course | undefined> {
    const [course] = await db.select().from(courses).where(eq(courses.id, id));
    return course;
  }

  async createCourse(insertCourse: InsertCourse): Promise<Course> {
    const [course] = await db.insert(courses).values(insertCourse).returning();
    return course;
  }

  async updateCourse(id: number, update: Partial<InsertCourse>): Promise<Course> {
    const [updated] = await db.update(courses).set(update).where(eq(courses.id, id)).returning();
    return updated;
  }

  async deleteCourse(id: number): Promise<void> {
    await db.delete(courses).where(eq(courses.id, id));
  }

  async getModules(courseId: number): Promise<Module[]> {
    return await db.select().from(modules).where(eq(modules.courseId, courseId)).orderBy(modules.order);
  }

  async createModule(insertModule: InsertModule): Promise<Module> {
    const [module] = await db.insert(modules).values(insertModule).returning();
    return module;
  }

  async updateModule(id: number, update: Partial<InsertModule>): Promise<Module> {
    const [updated] = await db.update(modules).set(update).where(eq(modules.id, id)).returning();
    return updated;
  }

  async deleteModule(id: number): Promise<void> {
    await db.delete(modules).where(eq(modules.id, id));
  }

  async getLessons(moduleId: number): Promise<Lesson[]> {
    return await db.select().from(lessons).where(eq(lessons.moduleId, moduleId)).orderBy(lessons.order);
  }

  async getLesson(id: number): Promise<Lesson | undefined> {
    const [lesson] = await db.select().from(lessons).where(eq(lessons.id, id));
    return lesson;
  }

  async createLesson(insertLesson: InsertLesson): Promise<Lesson> {
    const [lesson] = await db.insert(lessons).values(insertLesson).returning();
    return lesson;
  }

  async updateLesson(id: number, update: Partial<InsertLesson>): Promise<Lesson> {
    const [updated] = await db.update(lessons).set(update).where(eq(lessons.id, id)).returning();
    return updated;
  }

  async deleteLesson(id: number): Promise<void> {
    await db.delete(lessons).where(eq(lessons.id, id));
  }

  async getEnrollments(userId: string): Promise<(Enrollment & { course: Course })[]> {
    const results = await db.select({
      enrollment: enrollments,
      course: courses
    })
    .from(enrollments)
    .innerJoin(courses, eq(enrollments.courseId, courses.id))
    .where(eq(enrollments.userId, userId));

    return results.map(r => ({ ...r.enrollment, course: r.course }));
  }

  async createEnrollment(userId: string, courseId: number): Promise<Enrollment> {
    const [enrollment] = await db.insert(enrollments).values({ userId, courseId }).returning();
    return enrollment;
  }

  async getEnrollment(userId: string, courseId: number): Promise<Enrollment | undefined> {
    const [enrollment] = await db.select().from(enrollments)
      .where(and(eq(enrollments.userId, userId), eq(enrollments.courseId, courseId)));
    return enrollment;
  }

  async updateProgress(enrollmentId: number, lessonId: number, completed: boolean): Promise<Progress> {
    // Check if progress entry exists
    const [existing] = await db.select().from(progress)
      .where(and(eq(progress.enrollmentId, enrollmentId), eq(progress.lessonId, lessonId)));

    if (existing) {
      const [updated] = await db.update(progress)
        .set({ completed, completedAt: completed ? new Date() : null })
        .where(eq(progress.id, existing.id))
        .returning();
      return updated;
    } else {
      const [newProgress] = await db.insert(progress)
        .values({ enrollmentId, lessonId, completed, completedAt: completed ? new Date() : null })
        .returning();
      return newProgress;
    }
  }

  async getProgress(enrollmentId: number): Promise<Progress[]> {
    return await db.select().from(progress).where(eq(progress.enrollmentId, enrollmentId));
  }

  async getUser(id: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user;
  }
}

export const storage = new DatabaseStorage();
