import { db } from "../server/db";
import * as fs from "fs";
import { sql } from "drizzle-orm";

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

async function importEnrollments() {
  console.log("Importing learning_project_users as enrollments...");
  
  const csvPath = "attached_assets/learning_project_users_1769816546267.csv";
  const content = fs.readFileSync(csvPath, "utf-8");
  const lines = content.split('\n');
  
  console.log(`Found ${lines.length} lines`);
  
  // Header: id, user_id, id_company, learning_project_id, learning_project_pwd, creation_date, 
  //         company_id, tutor_purchase_id, assigned, starting_from, finish_within, days_to_alert, 
  //         accreditation_code, email
  
  // Clear existing enrollments
  await db.execute(sql`TRUNCATE TABLE enrollments`);
  console.log("Cleared existing enrollments");
  
  let imported = 0;
  let skipped = 0;
  let errors = 0;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const parts = parseCSVLine(line);
    if (parts.length < 11) continue;
    
    const id = parseInt(parts[0]) || null;
    const userId = parts[1] || null;
    const idCompany = parseInt(parts[2]) || null;
    const learningProjectId = parseInt(parts[3]) || null;
    const creationDate = parts[5] || null;
    const tutorPurchaseId = parseInt(parts[7]) || null;
    const startingFrom = parts[9] || null;
    const finishWithin = parts[10] || null;
    
    if (!id || !learningProjectId) {
      skipped++;
      continue;
    }
    
    try {
      await db.execute(sql`
        INSERT INTO enrollments (id, user_id, company_id, learning_project_id, purchase_id, start_date, end_date, progress, status, created_at)
        VALUES (
          ${id}, 
          ${userId}, 
          ${idCompany}, 
          ${learningProjectId}, 
          ${tutorPurchaseId && tutorPurchaseId > 0 ? tutorPurchaseId : null},
          ${startingFrom ? new Date(startingFrom) : null},
          ${finishWithin ? new Date(finishWithin) : null},
          0,
          'active',
          ${creationDate ? new Date(creationDate) : new Date()}
        )
        ON CONFLICT (id) DO NOTHING
      `);
      imported++;
      
      if (imported % 5000 === 0) {
        console.log(`Imported ${imported}...`);
      }
    } catch (err: any) {
      errors++;
      if (errors < 5) {
        console.error(`Error line ${i}:`, err.message);
      }
    }
  }
  
  await db.execute(sql`SELECT setval('enrollments_id_seq', (SELECT COALESCE(MAX(id), 1) FROM enrollments))`);
  
  console.log(`\nDone!`);
  console.log(`Imported: ${imported}`);
  console.log(`Skipped: ${skipped}`);
  console.log(`Errors: ${errors}`);
  
  process.exit(0);
}

importEnrollments().catch(console.error);
