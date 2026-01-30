import { db } from "../server/db";
import { companies } from "../shared/schema";
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

async function importClients() {
  console.log("Starting client companies import...");

  const companiesRaw = fs.readFileSync("attached_assets/companies_1769813122828.csv", "utf-8");
  const lines = companiesRaw.split("\n");
  
  const clients: any[] = [];
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i];
    if (!line.trim()) continue;
    
    const parts = parseCSVLine(line);
    if (parts.length < 20) continue;
    
    const id = parts[0];
    const businessName = parts[2]?.replace(/&#39;/g, "'");
    const vat = parts[3];
    const city = parts[4];
    const address = parts[5];
    const isTutor = parts[12];
    const telephone = parts[15];
    const email = parts[16];
    const deleted = parts[18];
    
    // Import only client companies (is_tutor = 0) that are not deleted
    if (isTutor === "0" && businessName && id && deleted !== "1") {
      clients.push({
        id: parseInt(id),
        businessName: businessName,
        vatNumber: vat || null,
        city: city || null,
        address: address || null,
        isTutor: false,
        phone: telephone || null,
        email: email || null,
      });
    }
  }

  console.log(`Found ${clients.length} client companies`);
  if (clients.length > 0) {
    console.log("Sample:", clients[0]);
  }

  // Insert in batches
  console.log("Inserting clients...");
  let inserted = 0;
  for (let i = 0; i < clients.length; i += 50) {
    const batch = clients.slice(i, i + 50);
    try {
      await db.insert(companies).values(batch).onConflictDoNothing();
      inserted += batch.length;
      if (i % 500 === 0) {
        console.log(`Progress: ${i}/${clients.length}`);
      }
    } catch (err: any) {
      console.error(`Error batch ${i}: ${err.message}`);
    }
  }
  
  console.log(`Client companies inserted: ${inserted}`);
  console.log("Import completed!");
  process.exit(0);
}

importClients().catch(console.error);
