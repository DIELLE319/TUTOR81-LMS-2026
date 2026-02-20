import type { Express } from "express";
import { registerTutorsRoutes } from "./tutors";
import { registerCompaniesRoutes } from "./companies";
import { registerStudentsRoutes } from "./students";
import { registerCoursesRoutes } from "./courses";
import { registerEnrollmentsRoutes } from "./enrollments";
import { registerCertificatesRoutes } from "./certificates";
import { registerInvoicesRoutes } from "./invoices";
import { registerPlayerRoutes } from "./player";

export function registerAllRoutes(app: Express) {
  registerTutorsRoutes(app);
  registerCompaniesRoutes(app);
  registerStudentsRoutes(app);
  registerCoursesRoutes(app);
  registerEnrollmentsRoutes(app);
  registerCertificatesRoutes(app);
  registerInvoicesRoutes(app);
  registerPlayerRoutes(app);
}
