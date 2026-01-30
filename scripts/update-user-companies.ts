import { db } from "../server/db";
import { users, companies } from "../shared/schema";
import { eq } from "drizzle-orm";
import * as fs from "fs";

async function updateUserCompanies() {
  console.log("Updating user-company associations...");

  // Get all enti formativi IDs
  const entiFormativi = await db.select({ id: companies.id }).from(companies).where(eq(companies.isTutor, true));
  const entiIds = new Set(entiFormativi.map(e => e.id));
  console.log(`Found ${entiIds.size} enti formativi`);

  // Read users CSV
  const usersRaw = fs.readFileSync("attached_assets/users_1769813021731.csv", "utf-8");
  const lines = usersRaw.split("\n").slice(1);

  let updated = 0;
  let notFound = 0;

  for (const line of lines) {
    if (!line.trim()) continue;
    
    const parts = line.split(";");
    if (parts.length < 24) continue;

    const userId = parts[0];
    const companyId = parseInt(parts[16]); // company_id column
    const role = parseInt(parts[23]);
    const deleted = parts[13];

    // Only update role=1 users (tutors) with valid company associations
    if (role === 1 && deleted === "0" && !isNaN(companyId)) {
      // Check if this company is an ente formativo
      if (entiIds.has(companyId)) {
        try {
          await db.update(users)
            .set({ idcompany: companyId })
            .where(eq(users.id, userId));
          updated++;
        } catch (err: any) {
          // User might not exist
          notFound++;
        }
      }
    }
  }

  console.log(`Updated: ${updated} users`);
  console.log(`Not found: ${notFound} users`);
  
  // Verify the results
  const result = await db.execute(`
    SELECT u.id, u.first_name, u.last_name, u.idcompany, c.business_name 
    FROM users u 
    LEFT JOIN companies c ON u.idcompany = c.id 
    WHERE u.role = 1 AND u.idcompany IS NOT NULL 
    LIMIT 10
  `);
  console.log("Sample associations:", result.rows);
  
  process.exit(0);
}

updateUserCompanies().catch(console.error);
