import { db } from "../server/db";
import { learningProjects } from "../shared/schema";
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
      // Skip carriage return
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

function cleanHtml(text: string): string {
  if (!text) return '';
  return text
    .replace(/<[^>]*>/g, ' ')
    .replace(/&nbsp;/g, ' ')
    .replace(/&egrave;/g, 'è')
    .replace(/&agrave;/g, 'à')
    .replace(/&ograve;/g, 'ò')
    .replace(/&ugrave;/g, 'ù')
    .replace(/&igrave;/g, 'ì')
    .replace(/&#39;/g, "'")
    .replace(/&quot;/g, '"')
    .replace(/&rsquo;/g, "'")
    .replace(/&lsquo;/g, "'")
    .replace(/&rdquo;/g, '"')
    .replace(/&ldquo;/g, '"')
    .replace(/&amp;/g, '&')
    .replace(/\s+/g, ' ')
    .trim();
}

async function importCourses() {
  console.log("Starting courses import with multiline support...");

  const coursesRaw = fs.readFileSync("attached_assets/courses_1769813362686.csv", "utf-8");
  const rows = parseCSVWithMultiline(coursesRaw);
  
  console.log(`Parsed ${rows.length} rows`);
  
  const header = rows[0];
  console.log("Columns:", header.slice(0, 5).join(", "));
  
  const idIdx = 0;
  const titleIdx = 1;
  const maxTimeIdx = 2;
  const descIdx = 3;
  
  const existingIds = new Set<number>();
  const existing = await db.select({ id: learningProjects.id }).from(learningProjects);
  existing.forEach(e => existingIds.add(e.id));
  console.log(`Existing courses: ${existingIds.size}`);
  
  let imported = 0;
  let skipped = 0;
  let errors = 0;
  
  for (let i = 1; i < rows.length; i++) {
    const row = rows[i];
    if (row.length < 4) continue;
    
    const id = parseInt(row[idIdx]) || null;
    const title = row[titleIdx]?.trim() || null;
    const description = cleanHtml(row[descIdx] || '').substring(0, 5000) || null;
    const maxTime = parseInt(row[maxTimeIdx]) || 0;
    const hours = Math.ceil(maxTime / 60);
    
    if (!id || !title || title === 'NULL') {
      skipped++;
      continue;
    }
    
    if (existingIds.has(id)) {
      skipped++;
      continue;
    }
    
    try {
      await db.execute(sql`
        INSERT INTO learning_projects (id, title, description, hours, is_published, created_at)
        VALUES (${id}, ${title}, ${description}, ${hours}, true, NOW())
        ON CONFLICT (id) DO NOTHING
      `);
      imported++;
      existingIds.add(id);
      
      if (imported % 500 === 0) {
        console.log(`Imported ${imported} courses...`);
      }
    } catch (err) {
      errors++;
      if (errors < 3) {
        console.error(`Error on row ${i} (id=${id}):`, err);
      }
    }
  }
  
  await db.execute(sql`SELECT setval('learning_projects_id_seq', (SELECT COALESCE(MAX(id), 1) FROM learning_projects))`);
  
  console.log(`\nImport complete!`);
  console.log(`Imported: ${imported}`);
  console.log(`Skipped: ${skipped}`);
  console.log(`Errors: ${errors}`);
  
  const [count] = await db.select({ count: sql<number>`count(*)` }).from(learningProjects);
  console.log(`Total courses in DB: ${count?.count}`);
  
  process.exit(0);
}

importCourses().catch(console.error);
