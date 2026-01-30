import { db } from "../server/db";
import { companies, users } from "../shared/schema";
import * as fs from "fs";

function parseCSVLine(line: string): string[] {
  const result: string[] = [];
  let current = '';
  let inQuotes = false;
  
  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    
    if (char === '"') {
      inQuotes = !inQuotes;
    } else if (char === ',' && !inQuotes) {
      result.push(current);
      current = '';
    } else {
      current += char;
    }
  }
  result.push(current);
  
  return result;
}

async function importData() {
  console.log("Starting import...");

  // Read companies CSV
  const companiesRaw = fs.readFileSync("attached_assets/companies_1769813122828.csv", "utf-8");
  const companyLines = companiesRaw.split("\n");
  const companyHeader = parseCSVLine(companyLines[0]);
  console.log("Company columns:", companyHeader.slice(0, 5));

  // Parse companies - only import enti formativi (is_tutor = 1)
  const entiFormativi: any[] = [];
  for (let i = 1; i < companyLines.length; i++) {
    const line = companyLines[i];
    if (!line.trim()) continue;
    
    const parts = parseCSVLine(line);
    
    const id = parts[0];
    const businessName = parts[2]?.replace(/&#39;/g, "'");
    const vat = parts[3];
    const city = parts[4];
    const address = parts[5];
    const isTutor = parts[12];
    const telephone = parts[15];
    const email = parts[16];
    
    if (isTutor === "1" && businessName && id) {
      entiFormativi.push({
        id: parseInt(id),
        businessName: businessName,
        vatNumber: vat || null,
        city: city || null,
        address: address || null,
        isTutor: true,
        phone: telephone || null,
        email: email || null,
      });
    }
  }

  console.log(`Found ${entiFormativi.length} enti formativi`);
  if (entiFormativi.length > 0) {
    console.log("Sample:", entiFormativi[0]);
  }

  // Get company IDs
  const companyIds = new Set(entiFormativi.map(c => c.id));

  // Read users CSV (semicolon separated)
  const usersRaw = fs.readFileSync("attached_assets/users_1769813021731.csv", "utf-8");
  const userLines = usersRaw.split("\n").slice(1);

  // Parse users
  const tutorsAndAdmins: any[] = [];
  for (const line of userLines) {
    if (!line.trim()) continue;
    
    const parts = line.split(";");
    if (parts.length < 24) continue;

    const [id, name, surname, , , , address, cap, code, email, suspended, password, , deleted, , username, companyId, , taxCode, , job, phone, , role] = parts;
    
    const roleNum = parseInt(role);
    const companyIdNum = parseInt(companyId);
    
    if ((roleNum === 1 || roleNum === 2 || roleNum === 1000) && deleted === "0") {
      tutorsAndAdmins.push({
        id: id,
        firstName: name,
        lastName: surname,
        email: email || null,
        role: roleNum,
        idcompany: companyIds.has(companyIdNum) ? companyIdNum : null,
        fiscalCode: taxCode || null,
        phone: phone || null,
      });
    }
  }

  console.log(`Found ${tutorsAndAdmins.length} tutors and admins`);

  // Insert companies in batches
  console.log("Inserting companies...");
  let companiesInserted = 0;
  for (let i = 0; i < entiFormativi.length; i += 50) {
    const batch = entiFormativi.slice(i, i + 50);
    try {
      await db.insert(companies).values(batch).onConflictDoNothing();
      companiesInserted += batch.length;
    } catch (err: any) {
      console.error(`Error batch ${i}: ${err.message}`);
    }
  }
  console.log(`Companies inserted: ${companiesInserted}`);

  // Insert users in batches
  console.log("Inserting users...");
  let usersInserted = 0;
  for (let i = 0; i < tutorsAndAdmins.length; i += 50) {
    const batch = tutorsAndAdmins.slice(i, i + 50);
    try {
      await db.insert(users).values(batch).onConflictDoNothing();
      usersInserted += batch.length;
    } catch (err: any) {
      console.error(`Error batch ${i}: ${err.message}`);
    }
  }
  console.log(`Users inserted: ${usersInserted}`);

  console.log("Import completed!");
  process.exit(0);
}

importData().catch(console.error);
