import type { Request } from "express";
import { db } from "../db";
import { sql } from "drizzle-orm";
import memoizee from "memoizee";

function extractRows(result: any): any[] {
  if (Array.isArray(result)) return result;
  if (result?.rows && Array.isArray(result.rows)) return result.rows;
  return [];
}

export function getAuthenticatedUserTutorId(req: Request): { userId: string; tutorId: number | null } {
  const user = (req as any).user;
  const userId = user?.claims?.sub;
  return { userId, tutorId: null };
}

export async function getAuthenticatedDbUser(req: Request) {
  const user = (req as any).user;
  const userId = user?.claims?.sub;
  if (!userId) return null;

  const result = await db.execute(sql`
    SELECT u.*, au.tutor_id, au.company_id as admin_company_id
    FROM users u
    LEFT JOIN admin_users au ON au.replit_user_id = u.id AND au.is_active = true
    WHERE u.id = ${userId}
    LIMIT 1
  `);
  const rows = extractRows(result);
  return rows[0] || null;
}

export const getPlatformTutorIdsCached = memoizee(
  async (): Promise<number[]> => {
    const result = await db.execute(sql`SELECT id FROM tutors WHERE is_active = true`);
    return extractRows(result).map((r: any) => r.id as number);
  },
  { maxAge: 5 * 60 * 1000, promise: true }
);

export { extractRows };
