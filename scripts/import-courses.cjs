const { Client } = require('pg');
const fs = require('fs');

async function importCourses() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  console.log("Importing courses from OVH learning_project...");
  
  const content = fs.readFileSync('ovh_data/learning_project_export.tsv', 'utf-8');
  const lines = content.split('\n');
  
  console.log(`Found ${lines.length - 1} courses`);
  
  let imported = 0;
  let errors = 0;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const parts = line.split('\t');
    const id = parseInt(parts[0]);
    const title = parts[1] || '';
    const description = parts[2] || '';
    const code = parts[3] || '';
    const isPublished = parts[4] === '1';
    const creationDate = parts[5] || null;
    
    if (!id || !title) continue;
    
    try {
      await client.query(`
        INSERT INTO courses (id, title, description, is_published, created_at)
        VALUES ($1, $2, $3, $4, $5)
        ON CONFLICT (id) DO UPDATE SET
          title = EXCLUDED.title,
          description = EXCLUDED.description,
          is_published = EXCLUDED.is_published
      `, [id, title, description, isPublished, creationDate || new Date()]);
      
      imported++;
      if (imported % 50 === 0) {
        console.log(`Imported ${imported}...`);
      }
    } catch (err) {
      errors++;
      if (errors < 5) {
        console.error(`Error course ${id}:`, err.message);
      }
    }
  }
  
  // Reset sequence
  await client.query(`SELECT setval('courses_id_seq', (SELECT COALESCE(MAX(id), 1) FROM courses))`);
  
  console.log(`\nDone!`);
  console.log(`Imported: ${imported}`);
  console.log(`Errors: ${errors}`);
  
  await client.end();
}

importCourses().catch(console.error);
