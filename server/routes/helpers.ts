import type { Request } from "express";
import { db } from "../db";
import { authStorage } from "../auth";
import * as schema from "@shared/schema";
import { sql } from "drizzle-orm";

// Helper per ottenere tutorId dall'utente autenticato
export async function getAuthenticatedUserTutorId(req: Request): Promise<{ role: number | null; tutorId: number | null }> {
  try {
    const user = (req as any).user;
    if (!user?.claims?.sub) {
      return { role: null, tutorId: null };
    }

    const dbUser = await authStorage.getUser(user.claims.sub);
    if (!dbUser) {
      return { role: null, tutorId: null };
    }

    if (dbUser.role === 1 && dbUser.idcompany) {
      const result = await db.execute(sql`
        SELECT ta.tutor_id
        FROM tutor_admins ta
        WHERE ta.id = ${dbUser.idcompany}
      `);
      if (result.rows.length > 0) {
        return { role: 1, tutorId: (result.rows[0] as any).tutor_id };
      }
    }

    return { role: dbUser.role, tutorId: null };
  } catch (e) {
    console.error("Error getting user tutor info:", e);
    return { role: null, tutorId: null };
  }
}

// Helper per ottenere il DB user completo
export async function getAuthenticatedDbUser(req: Request) {
  const user = (req as any).user;
  if (!user?.claims?.sub) return null;
  return await authStorage.getUser(user.claims.sub);
}

// Cache per i tutor IDs della piattaforma (tutor81)
type PlatformTutorIdsCache = { fetchedAt: number; ids: Set<number> };
let platformTutorIdsCache: PlatformTutorIdsCache | null = null;

export async function getPlatformTutorIdsCached(): Promise<Set<number>> {
  const ttlMs = 60_000;
  const now = Date.now();
  if (platformTutorIdsCache && now - platformTutorIdsCache.fetchedAt < ttlMs) {
    return platformTutorIdsCache.ids;
  }
  const rows = await db
    .select({ id: schema.tutors.id })
    .from(schema.tutors)
    .where(sql`
      coalesce(${schema.tutors.isActive}, true) = true
      and (
        ${schema.tutors.businessName} ilike '%tutor81%'
        or coalesce(${schema.tutors.email}, '') ilike '%tutor81%'
      )
    `);
  const ids = new Set(rows.map(r => r.id));
  platformTutorIdsCache = { fetchedAt: now, ids };
  return ids;
}
