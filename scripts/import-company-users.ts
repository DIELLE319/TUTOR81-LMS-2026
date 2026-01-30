import { db } from "../server/db";
import { companyUsers, companies } from "../shared/schema";
import { eq } from "drizzle-orm";
import * as fs from "fs";

async function importCompanyUsers() {
  console.log("Importing company-user associations...");

  // Get all enti formativi IDs
  const entiFormativi = await db.select({ id: companies.id }).from(companies).where(eq(companies.isTutor, true));
  const entiIds = new Set(entiFormativi.map(e => e.id));
  console.log(`Found ${entiIds.size} enti formativi`);

  // Read users CSV
  const usersRaw = fs.readFileSync("attached_assets/users_1769813021731.csv", "utf-8");
  const lines = usersRaw.split("\n").slice(1);

  const associations: any[] = [];

  for (const line of lines) {
    if (!line.trim()) continue;
    
    const parts = line.split(";");
    if (parts.length < 24) continue;

    const userId = parts[0];
    const companyId = parseInt(parts[16]); // company_id column
    const role = parseInt(parts[23]);
    const deleted = parts[13];

    // Only process role=1 users (tutors) with valid company associations to enti formativi
    if (role === 1 && deleted === "0" && !isNaN(companyId) && entiIds.has(companyId)) {
      associations.push({
        userId: userId,
        companyId: companyId,
        role: 1, // tutor role
      });
    }
  }

  console.log(`Found ${associations.length} associations to create`);

  // Insert in batches
  let inserted = 0;
  for (let i = 0; i < associations.length; i += 50) {
    const batch = associations.slice(i, i + 50);
    try {
      await db.insert(companyUsers).values(batch).onConflictDoNothing();
      inserted += batch.length;
    } catch (err: any) {
      console.error(`Error batch ${i}: ${err.message}`);
    }
  }

  console.log(`Associations inserted: ${inserted}`);

  // Verify
  const result = await db.execute(`
    SELECT c.business_name, u.first_name, u.last_name, cu.role
    FROM company_users cu
    JOIN companies c ON cu.company_id = c.id
    JOIN users u ON cu.user_id = u.id
    WHERE c.is_tutor = true
    ORDER BY c.business_name
    LIMIT 15
  `);
  console.log("Sample:", result.rows);

  process.exit(0);
}

importCompanyUsers().catch(console.error);
