const { Client } = require('pg');
const fs = require('fs');

function parseCSVLine(line) {
  const result = [];
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
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  console.log("Importing learning_project_users as enrollments...");
  
  const csvPath = "attached_assets/learning_project_users_1769816546267.csv";
  const content = fs.readFileSync(csvPath, "utf-8");
  const lines = content.split('\n');
  
  console.log(`Found ${lines.length - 1} lines`);
  
  // Header: id, user_id, id_company, learning_project_id, learning_project_pwd, creation_date, 
  //         company_id, tutor_purchase_id, assigned, starting_from, finish_within, days_to_alert, 
  //         accreditation_code, email
  
  // Clear existing enrollments
  await client.query('TRUNCATE TABLE enrollments CASCADE');
  console.log("Cleared existing enrollments");
  
  // Get existing students and courses for validation
  const studentsRes = await client.query('SELECT id FROM students');
  const studentIds = new Set(studentsRes.rows.map(r => r.id));
  console.log(`Found ${studentIds.size} students in database`);
  
  const coursesRes = await client.query('SELECT id FROM courses');
  const courseIds = new Set(coursesRes.rows.map(r => r.id));
  console.log(`Found ${courseIds.size} courses in database`);
  
  let imported = 0;
  let skippedNoStudent = 0;
  let skippedNoCourse = 0;
  let errors = 0;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const parts = parseCSVLine(line);
    if (parts.length < 11) continue;
    
    const id = parseInt(parts[0]) || null;
    const studentId = parseInt(parts[1]) || null;  // user_id -> student_id
    const companyId = parseInt(parts[2]) || null;  // id_company (client company)
    const courseId = parseInt(parts[3]) || null;   // learning_project_id -> course_id
    const licenseCode = parts[4] || '';            // learning_project_pwd
    const creationDate = parts[5] || null;
    const adminId = parseInt(parts[6]) || null;    // company_id (admin)
    const purchaseId = parseInt(parts[7]) || null;
    const startingFrom = parts[9] || null;
    const finishWithin = parts[10] || null;
    const daysToAlert = parseInt(parts[11]) || 15;
    
    if (!id || !courseId) {
      continue;
    }
    
    // Skip if student not in database
    if (!studentId || !studentIds.has(studentId)) {
      skippedNoStudent++;
      continue;
    }
    
    // Skip if course not in database
    if (!courseIds.has(courseId)) {
      skippedNoCourse++;
      continue;
    }
    
    try {
      await client.query(`
        INSERT INTO enrollments (id, student_id, course_id, license_code, start_date, end_date, days_to_alert, progress, status, created_at, legacy_id)
        VALUES ($1, $2, $3, $4, $5, $6, $7, 0, 'active', $8, $1)
        ON CONFLICT (id) DO NOTHING
      `, [
        id, 
        studentId, 
        courseId, 
        licenseCode,
        startingFrom ? new Date(startingFrom) : null,
        finishWithin ? new Date(finishWithin) : null,
        daysToAlert,
        creationDate ? new Date(creationDate) : new Date()
      ]);
      imported++;
      
      if (imported % 5000 === 0) {
        console.log(`Imported ${imported}...`);
      }
    } catch (err) {
      errors++;
      if (errors < 10) {
        console.error(`Error line ${i}:`, err.message);
      }
    }
  }
  
  // Reset sequence
  await client.query(`SELECT setval('enrollments_id_seq', (SELECT COALESCE(MAX(id), 1) FROM enrollments))`);
  
  console.log(`\nDone!`);
  console.log(`Imported: ${imported}`);
  console.log(`Skipped (no student): ${skippedNoStudent}`);
  console.log(`Skipped (no course): ${skippedNoCourse}`);
  console.log(`Errors: ${errors}`);
  
  await client.end();
}

importEnrollments().catch(console.error);
