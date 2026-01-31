import { db } from "../server/db";
import { enrollments } from "../shared/schema";
import * as fs from "fs";
import * as path from "path";

interface LearningProjectUserRow {
  id: string;
  user_id: string;
  id_company: string;
  learning_project_id: string;
  learning_project_pwd: string;
  creation_date: string;
  company_id: string;
  tutor_purchase_id: string;
  assigned: string;
  starting_from: string;
  finish_within: string;
  days_to_alert: string;
  accreditation_code: string;
  email: string;
}

function parseCSV(content: string): LearningProjectUserRow[] {
  const lines = content.split("\n");
  const headers = lines[0].split(",").map(h => h.replace(/"/g, "").trim());
  const rows: LearningProjectUserRow[] = [];
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const values: string[] = [];
    let current = "";
    let inQuotes = false;
    
    for (const char of line) {
      if (char === '"') {
        inQuotes = !inQuotes;
      } else if (char === "," && !inQuotes) {
        values.push(current.trim());
        current = "";
      } else {
        current += char;
      }
    }
    values.push(current.trim());
    
    const row: any = {};
    headers.forEach((header, idx) => {
      row[header] = values[idx] || "";
    });
    rows.push(row);
  }
  
  return rows;
}

function parseDate(dateStr: string): Date | null {
  if (!dateStr || dateStr === "0000-00-00" || dateStr === "") return null;
  const date = new Date(dateStr);
  return isNaN(date.getTime()) ? null : date;
}

async function importLearningProjectUsers() {
  console.log("Starting import of learning_project_users...");
  
  const csvPath = path.join(process.cwd(), "attached_assets", "learning_project_users_1769816546267.csv");
  const content = fs.readFileSync(csvPath, "utf-8");
  const rows = parseCSV(content);
  
  console.log(`Found ${rows.length} records to import`);
  
  let imported = 0;
  let errors = 0;
  const batchSize = 500;
  
  for (let i = 0; i < rows.length; i += batchSize) {
    const batch = rows.slice(i, i + batchSize);
    const insertData = batch.map(row => ({
      legacyId: parseInt(row.id) || null,
      legacyUserId: parseInt(row.user_id) || null,
      companyId: parseInt(row.company_id) || parseInt(row.id_company) || null,
      learningProjectId: parseInt(row.learning_project_id) || null,
      purchaseId: parseInt(row.tutor_purchase_id) || null,
      startDate: parseDate(row.starting_from),
      endDate: parseDate(row.finish_within),
      daysToAlert: parseInt(row.days_to_alert) || 15,
      accreditationCode: row.accreditation_code || null,
      status: "active",
      progress: 0,
      createdAt: parseDate(row.creation_date) || new Date(),
    })).filter(r => r.legacyUserId && r.learningProjectId);
    
    try {
      if (insertData.length > 0) {
        await db.insert(enrollments).values(insertData);
        imported += insertData.length;
      }
    } catch (error) {
      console.error(`Error inserting batch at ${i}:`, error);
      errors += batch.length;
    }
    
    if ((i + batchSize) % 5000 === 0 || i + batchSize >= rows.length) {
      console.log(`Progress: ${Math.min(i + batchSize, rows.length)}/${rows.length} processed`);
    }
  }
  
  console.log(`\nImport complete!`);
  console.log(`Imported: ${imported}`);
  console.log(`Errors: ${errors}`);
}

importLearningProjectUsers()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error("Import failed:", err);
    process.exit(1);
  });
