import { db } from "../server/db";
import { companies } from "../shared/schema";
import { eq } from "drizzle-orm";
import * as fs from "fs";

async function createAssociations() {
  console.log("Creating company-user associations...");

  // Get all enti formativi IDs
  const entiFormativi = await db.select({ id: companies.id }).from(companies).where(eq(companies.isTutor, true));
  const entiIds = new Set(entiFormativi.map(e => e.id));
  console.log(`Found ${entiIds.size} enti formativi`);

  // Read users CSV
  const usersRaw = fs.readFileSync("attached_assets/users_1769813021731.csv", "utf-8");
  const lines = usersRaw.split("\n").slice(1);

  let inserted = 0;
  let errors = 0;

  for (const line of lines) {
    if (!line.trim()) continue;
    
    const parts = line.split(";");
    if (parts.length < 24) continue;

    const userId = parts[0];
    const companyId = parseInt(parts[16]);
    const role = parseInt(parts[23]);
    const deleted = parts[13];

    // Only process role=1 users (tutors) linked to enti formativi
    if (role === 1 && deleted === "0" && !isNaN(companyId) && entiIds.has(companyId)) {
      try {
        await db.execute(`
          INSERT INTO company_users (user_id, company_id, role) 
          VALUES ('${userId}', ${companyId}, 1)
          ON CONFLICT DO NOTHING
        `);
        inserted++;
      } catch (err: any) {
        errors++;
        console.error(`Error for user ${userId}: ${err.message}`);
      }
    }
  }

  console.log(`Inserted: ${inserted}, Errors: ${errors}`);

  // Verify
  const result = await db.execute(`
    SELECT c.business_name, COUNT(cu.id) as num_tutors
    FROM companies c
    JOIN company_users cu ON cu.company_id = c.id
    WHERE c.is_tutor = true
    GROUP BY c.id, c.business_name
    ORDER BY num_tutors DESC
    LIMIT 15
  `);
  console.log("Enti formativi con tutor:", result.rows);

  process.exit(0);
}

createAssociations().catch(console.error);
