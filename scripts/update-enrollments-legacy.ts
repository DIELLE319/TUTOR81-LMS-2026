import { db } from "../server/db";
import { enrollments } from "../shared/schema";
import { eq, and, isNull } from "drizzle-orm";
import * as fs from "fs";
import * as path from "path";

interface LearningProjectUserRow {
  id: string;
  user_id: string;
  learning_project_id: string;
  accreditation_code: string;
  days_to_alert: string;
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

async function updateEnrollments() {
  console.log("Loading CSV data...");
  
  const csvPath = path.join(process.cwd(), "attached_assets", "learning_project_users_1769816546267.csv");
  const content = fs.readFileSync(csvPath, "utf-8");
  const rows = parseCSV(content);
  
  console.log(`Found ${rows.length} records in CSV`);
  
  // Create lookup map: learning_project_id + user_id -> row data
  const lookup = new Map<string, LearningProjectUserRow>();
  for (const row of rows) {
    const key = `${row.learning_project_id}-${row.user_id}`;
    lookup.set(key, row);
  }
  
  console.log(`Created lookup with ${lookup.size} unique combinations`);
  
  // Get all enrollments without legacy_id
  const existingEnrollments = await db.select()
    .from(enrollments)
    .where(isNull(enrollments.legacyId));
  
  console.log(`Found ${existingEnrollments.length} enrollments to update`);
  
  let updated = 0;
  let notFound = 0;
  
  for (const enrollment of existingEnrollments) {
    const key = `${enrollment.learningProjectId}-${enrollment.userId}`;
    const csvRow = lookup.get(key);
    
    if (csvRow) {
      await db.update(enrollments)
        .set({
          legacyId: parseInt(csvRow.id) || null,
          legacyUserId: parseInt(csvRow.user_id) || null,
          accreditationCode: csvRow.accreditation_code || null,
          daysToAlert: parseInt(csvRow.days_to_alert) || 15,
        })
        .where(eq(enrollments.id, enrollment.id));
      updated++;
    } else {
      notFound++;
    }
    
    if ((updated + notFound) % 5000 === 0) {
      console.log(`Progress: ${updated + notFound}/${existingEnrollments.length}`);
    }
  }
  
  console.log(`\nUpdate complete!`);
  console.log(`Updated: ${updated}`);
  console.log(`Not found in CSV: ${notFound}`);
}

updateEnrollments()
  .then(() => process.exit(0))
  .catch((err) => {
    console.error("Update failed:", err);
    process.exit(1);
  });
