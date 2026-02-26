import pg from "pg";

let cmsPool: pg.Pool | null = null;

export function getCmsPool(): pg.Pool {
  if (!cmsPool) {
    cmsPool = new pg.Pool({
      host: process.env.CMS_DB_HOST || "107.191.63.149",
      port: 5432,
      user: "tutor81",
      password: "tutor81pass",
      database: "tutor81",
      max: 5,
    });
  }
  return cmsPool;
}
