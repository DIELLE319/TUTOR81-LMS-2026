import { drizzle } from "drizzle-orm/node-postgres";
import pg from "pg";
import * as schema from "@shared/schema";

const { Pool } = pg;

export const hasDatabase = Boolean(process.env.DATABASE_URL);

if (!hasDatabase) {
  console.warn(
    "[db] DATABASE_URL is not set. Database-backed routes/auth will be disabled.",
  );
}

export const pool = hasDatabase
  ? new Pool({ connectionString: process.env.DATABASE_URL })
  : (null as unknown as pg.Pool);

export const db = hasDatabase
  ? drizzle(pool, { schema })
  : (null as unknown as ReturnType<typeof drizzle>);
