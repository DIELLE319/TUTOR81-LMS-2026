import type { Express } from "express";
import { authStorage } from "./storage";
import { isAuthenticated } from "./sessionAuth";
import { db } from "../db";
import { sql } from "drizzle-orm";

export function registerAuthRoutes(app: Express): void {
  app.get("/api/auth/user", isAuthenticated, async (req: any, res) => {
    try {
      const userId = req.user.claims.sub;
      const user = await authStorage.getUser(userId);
      if (!user) return res.status(404).json({ error: "User not found" });

      let normalizedRole = user.role ?? 0;
      if (normalizedRole >= 100 && normalizedRole < 1000) normalizedRole = Math.floor(normalizedRole / 100);

      let tutorId: number | null = null;
      let tutorName: string | null = null;

      if (normalizedRole >= 1) {
        const result = await db.execute(sql`
          SELECT au.tutor_id, t.business_name
          FROM admin_users au
          LEFT JOIN tutors t ON t.id = au.tutor_id
          WHERE au.replit_user_id = ${userId} AND au.is_active = true
          LIMIT 1
        `);
        if (result.rows.length > 0) {
          tutorId = result.rows[0].tutor_id as number;
          tutorName = result.rows[0].business_name as string;
        }
      }

      const { passwordHash, ...safeUser } = user as any;
      res.json({ ...safeUser, role: normalizedRole, tutorId, tutorName });
    } catch (error) {
      console.error("Error fetching user:", error);
      res.status(500).json({ error: "Internal server error" });
    }
  });
}
