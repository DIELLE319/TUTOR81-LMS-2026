import type { Express } from "express";
import { authStorage } from "./storage";
import { isAuthenticated } from "./sessionAuth";
import { db } from "../db";
import { sql } from "drizzle-orm";

// Register auth-specific routes
export function registerAuthRoutes(app: Express): void {
  // Get current authenticated user with tutorId if admin
  app.get("/api/auth/user", isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const user = await authStorage.getUser(userId);
      
      if (!user) {
        return res.json(null);
      }
      
      // Se l'utente è venditore (role=1), cerca il suo tutorId dalla tabella tutor_admins
      let tutorId: number | null = null;
      let tutorName: string | null = null;
      
      if (user.role === 1 && user.idcompany) {
        // idcompany contiene l'id del tutor_admin, cerchiamo il tutor collegato
        try {
          const result = await db.execute(sql.raw(`
            SELECT ta.tutor_id, t.business_name as tutor_name
            FROM tutor_admins ta
            JOIN tutors t ON t.id = ta.tutor_id
            WHERE ta.id = ${user.idcompany}
          `));
          if (result.rows.length > 0) {
            tutorId = (result.rows[0] as any).tutor_id;
            tutorName = (result.rows[0] as any).tutor_name;
          }
        } catch (e) {
          console.error("Error fetching tutor info:", e);
        }
      }
      
      const allowedRoles = new Set([0, 1, 2, 1000]);
      const normalizedRole = allowedRoles.has(user.role ?? 0) ? user.role : 0;
      if (normalizedRole !== user.role) {
        console.warn(`[auth] Normalized legacy role for user ${user.id}: ${user.role} -> ${normalizedRole}`);
      }

      const { passwordHash, ...safeUser } = user as any;
      res.json({ ...safeUser, role: normalizedRole, tutorId, tutorName });
    } catch (error) {
      console.error("Error fetching user:", error);
      res.status(500).json({ message: "Failed to fetch user" });
    }
  });
}
