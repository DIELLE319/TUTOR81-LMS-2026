import { db } from "../server/db";
import { companies } from "../shared/schema";
import { eq } from "drizzle-orm";
import * as fs from "fs";

async function fixMissingUsers() {
  const missingUserIds = ['10832', '10852', '10855', '10880', '11036', '15391', '15478', '18230', '18268', '20529', '24457', '25356', '29686', '30015', '31164', '39664', '40074'];
  
  console.log(`Fixing ${missingUserIds.length} missing users...`);

  // Get enti formativi IDs
  const entiFormativi = await db.select({ id: companies.id }).from(companies).where(eq(companies.isTutor, true));
  const entiIds = new Set(entiFormativi.map(e => e.id));

  // Read users CSV
  const usersRaw = fs.readFileSync("attached_assets/users_1769813021731.csv", "utf-8");
  const lines = usersRaw.split("\n").slice(1);

  for (const line of lines) {
    if (!line.trim()) continue;
    
    const parts = line.split(";");
    if (parts.length < 24) continue;

    const userId = parts[0];
    
    if (missingUserIds.includes(userId)) {
      const firstName = parts[1];
      const lastName = parts[2];
      const companyId = parseInt(parts[16]);
      const taxCode = parts[18];
      const phone = parts[21];

      // Insert user without email (to avoid duplicate constraint)
      try {
        await db.execute(`
          INSERT INTO users (id, first_name, last_name, role, fiscal_code, phone)
          VALUES ('${userId}', '${firstName.replace(/'/g, "''")}', '${lastName.replace(/'/g, "''")}', 1, '${taxCode || ''}', '${phone || ''}')
          ON CONFLICT (id) DO NOTHING
        `);
        console.log(`Inserted user ${userId}: ${firstName} ${lastName}`);

        // Add association if company is ente formativo
        if (entiIds.has(companyId)) {
          await db.execute(`
            INSERT INTO company_users (user_id, company_id, role) 
            VALUES ('${userId}', ${companyId}, 1)
            ON CONFLICT DO NOTHING
          `);
          console.log(`  -> Associated with company ${companyId}`);
        }
      } catch (err: any) {
        console.error(`Error for ${userId}: ${err.message}`);
      }
    }
  }

  // Final count
  const result = await db.execute(`
    SELECT c.business_name, COUNT(cu.id) as num_tutors
    FROM companies c
    JOIN company_users cu ON cu.company_id = c.id
    WHERE c.is_tutor = true
    GROUP BY c.id, c.business_name
    ORDER BY num_tutors DESC
    LIMIT 20
  `);
  console.log("Enti formativi con tutor:", result.rows);

  process.exit(0);
}

fixMissingUsers().catch(console.error);
