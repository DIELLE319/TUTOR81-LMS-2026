import { db } from "../server/db";
import { learningProjects } from "../shared/schema";
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

function cleanHtml(text: string): string {
  if (!text) return '';
  return text
    .replace(/<[^>]*>/g, '')
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
    .trim();
}

async function importCourses() {
  console.log("Starting courses import...");

  const coursesRaw = fs.readFileSync("attached_assets/courses_1769813362686.csv", "utf-8");
  const lines = coursesRaw.split("\n");
  const header = parseCSVLine(lines[0]);
  console.log("Columns:", header.slice(0, 10));

  const courses: any[] = [];
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i];
    if (!line.trim()) continue;
    
    const parts = parseCSVLine(line);
    if (parts.length < 10) continue;
    
    const id = parts[0];
    const title = parts[1];
    const maxExecutionTime = parts[2];
    const description = cleanHtml(parts[3]);
    const lawReference = parts[4];
    
    // Skip if no title or closed
    if (!title || title === 'NULL') continue;
    
    // Parse hours from max_execution_time (in minutes)
    let hours = 0;
    const minutes = parseInt(maxExecutionTime);
    if (!isNaN(minutes) && minutes > 0) {
      hours = Math.ceil(minutes / 60);
    }
    
    courses.push({
      id: parseInt(id),
      title: title,
      description: description || null,
      category: lawReference || null,
      hours: hours,
      isPublished: true,
    });
  }

  console.log(`Found ${courses.length} courses`);
  if (courses.length > 0) {
    console.log("Sample:", courses[0]);
  }

  // Insert in batches
  console.log("Inserting courses...");
  let inserted = 0;
  for (let i = 0; i < courses.length; i += 50) {
    const batch = courses.slice(i, i + 50);
    try {
      await db.insert(learningProjects).values(batch).onConflictDoNothing();
      inserted += batch.length;
      if (i % 500 === 0) {
        console.log(`Progress: ${i}/${courses.length}`);
      }
    } catch (err: any) {
      console.error(`Error batch ${i}: ${err.message}`);
    }
  }
  
  console.log(`Courses inserted: ${inserted}`);
  console.log("Import completed!");
  process.exit(0);
}

importCourses().catch(console.error);
