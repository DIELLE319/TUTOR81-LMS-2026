import { db } from "../server/db";
import { users, companyUsers, companies } from "../shared/schema";
import { eq } from "drizzle-orm";
import * as fs from "fs";

async function importTutorsAndAssociations() {
  console.log("Importing tutors and associations...");

  // Get all enti formativi IDs
  const entiFormativi = await db.select({ id: companies.id }).from(companies).where(eq(companies.isTutor, true));
  const entiIds = new Set(entiFormativi.map(e => e.id));
  console.log(`Found ${entiIds.size} enti formativi`);

  // Read users CSV
  const usersRaw = fs.readFileSync("attached_assets/users_1769813021731.csv", "utf-8");
  const lines = usersRaw.split("\n").slice(1);

  const tutorsToInsert: any[] = [];
  const associations: any[] = [];

  for (const line of lines) {
    if (!line.trim()) continue;
    
    const parts = line.split(";");
    if (parts.length < 24) continue;

    const userId = parts[0];
    const firstName = parts[1];
    const lastName = parts[2];
    const email = parts[9];
    const companyId = parseInt(parts[16]);
    const taxCode = parts[18];
    const phone = parts[21];
    const role = parseInt(parts[23]);
    const deleted = parts[13];

    // Only process role=1 users (tutors) linked to enti formativi
    if (role === 1 && deleted === "0" && !isNaN(companyId) && entiIds.has(companyId)) {
      // Add user to insert list
      tutorsToInsert.push({
        id: userId,
        firstName: firstName,
        lastName: lastName,
        email: email || null,
        role: 1,
        fiscalCode: taxCode || null,
        phone: phone || null,
      });
      
      // Add association
      associations.push({
        userId: userId,
        companyId: companyId,
        role: 1,
      });
    }
  }

  console.log(`Found ${tutorsToInsert.length} tutors to insert`);
  console.log(`Found ${associations.length} associations to create`);

  // Insert users first
  console.log("Inserting tutors...");
  let usersInserted = 0;
  for (let i = 0; i < tutorsToInsert.length; i += 50) {
    const batch = tutorsToInsert.slice(i, i + 50);
    try {
      await db.insert(users).values(batch).onConflictDoNothing();
      usersInserted += batch.length;
    } catch (err: any) {
      console.error(`Error users batch ${i}: ${err.message}`);
    }
  }
  console.log(`Tutors inserted: ${usersInserted}`);

  // Now insert associations
  console.log("Inserting associations...");
  let assocInserted = 0;
  for (let i = 0; i < associations.length; i += 50) {
    const batch = associations.slice(i, i + 50);
    try {
      await db.insert(companyUsers).values(batch).onConflictDoNothing();
      assocInserted += batch.length;
    } catch (err: any) {
      console.error(`Error assoc batch ${i}: ${err.message}`);
    }
  }
  console.log(`Associations inserted: ${assocInserted}`);

  // Verify
  const result = await db.execute(`
    SELECT c.business_name, COUNT(cu.id) as num_tutors
    FROM companies c
    JOIN company_users cu ON cu.company_id = c.id
    WHERE c.is_tutor = true
    GROUP BY c.id, c.business_name
    ORDER BY num_tutors DESC
    LIMIT 10
  `);
  console.log("Enti formativi con tutor:", result.rows);

  process.exit(0);
}

importTutorsAndAssociations().catch(console.error);
