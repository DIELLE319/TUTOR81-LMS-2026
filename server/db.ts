import { drizzle } from "drizzle-orm/node-postgres";
import pg from "pg";
import * as schema from "@shared/schema";

const hasDatabase = !!process.env.DATABASE_URL;

let db: ReturnType<typeof drizzle<typeof schema>>;

if (hasDatabase) {
  const pool = new pg.Pool({ connectionString: process.env.DATABASE_URL });
  db = drizzle(pool, { schema });
} else {
  db = null as any;
}

export { db, hasDatabase };
