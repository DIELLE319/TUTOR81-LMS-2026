import { db } from "../server/db";
import * as fs from "fs";
import { sql } from "drizzle-orm";

function parseCSVWithMultiline(content: string): string[][] {
  const rows: string[][] = [];
  let currentRow: string[] = [];
  let currentField = '';
  let inQuotes = false;
  
  for (let i = 0; i < content.length; i++) {
    const char = content[i];
    const nextChar = content[i + 1];
    
    if (char === '"') {
      if (inQuotes && nextChar === '"') {
        currentField += '"';
        i++;
      } else {
        inQuotes = !inQuotes;
      }
    } else if (char === ',' && !inQuotes) {
      currentRow.push(currentField);
      currentField = '';
    } else if (char === '\n' && !inQuotes) {
      currentRow.push(currentField);
      if (currentRow.length > 1 || currentRow[0] !== '') {
        rows.push(currentRow);
      }
      currentRow = [];
      currentField = '';
    } else if (char === '\r' && !inQuotes) {
      // Skip
    } else {
      currentField += char;
    }
  }
  
  if (currentField || currentRow.length > 0) {
    currentRow.push(currentField);
    rows.push(currentRow);
  }
  
  return rows;
}

async function importLearningProjects() {
  console.log("Importing learning_projects...");
  
  const csvPath = "attached_assets/learning_project_1769815773928.csv";
  const content = fs.readFileSync(csvPath, "utf-8");
  const rows = parseCSVWithMultiline(content);
  
  console.log(`Parsed ${rows.length} rows`);
  
  const header = rows[0];
  console.log("Columns:", header.join(", "));
  
  // id, title, description, owner_user_id, creation_date, ...
  let imported = 0;
  let errors = 0;
  
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    if (row.length < 3) continue;
    
    const id = parseInt(row[0]) || null;
    const title = row[1]?.trim() || null;
    const description = row[2]?.substring(0, 5000) || null;
    
    if (!id || !title) {
      continue;
    }
    
    try {
      await db.execute(sql`
        INSERT INTO learning_projects (id, title, description, is_published, created_at)
        VALUES (${id}, ${title}, ${description}, true, NOW())
        ON CONFLICT (id) DO NOTHING
      `);
      imported++;
      
      if (imported % 100 === 0) {
        console.log(`Imported ${imported}...`);
      }
    } catch (err) {
      errors++;
      if (errors < 5) {
        console.error(`Error row ${i}:`, err);
      }
    }
  }
  
  await db.execute(sql`SELECT setval('learning_projects_id_seq', (SELECT COALESCE(MAX(id), 1) FROM learning_projects))`);
  
  console.log(`\nDone! Imported: ${imported}, Errors: ${errors}`);
  
  const [count] = await db.execute(sql`SELECT COUNT(*) as cnt FROM learning_projects`);
  console.log("Total in DB:", count);
  
  process.exit(0);
}

importLearningProjects().catch(console.error);
