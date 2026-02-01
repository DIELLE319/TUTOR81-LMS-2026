const { Client } = require('pg');
const fs = require('fs');

async function updateEnrollments() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  console.log("Updating enrollments with full learning_events data...");
  
  const content = fs.readFileSync('ovh_data/learning_events_full.tsv', 'utf-8');
  const lines = content.split('\n');
  
  console.log(`Found ${lines.length - 1} records`);
  
  let updated = 0;
  let notFound = 0;
  let errors = 0;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const parts = line.split('\t');
    const enrollmentId = parseInt(parts[0]);
    const firstStart = parts[1] && parts[1] !== 'NULL' ? parts[1] : null;
    const lastAccess = parts[2] && parts[2] !== 'NULL' ? parts[2] : null;
    const completedAt = parts[3] && parts[3] !== 'NULL' ? parts[3] : null;
    const progress = parseInt(parts[4]) || 0;
    
    if (!enrollmentId) continue;
    
    try {
      const status = progress >= 100 ? 'completed' : 'active';
      
      const result = await client.query(`
        UPDATE enrollments 
        SET progress = $1, 
            status = $2,
            completed_at = $3,
            last_access_at = $4,
            start_date = COALESCE(start_date, $5)
        WHERE id = $6
      `, [
        progress, 
        status, 
        completedAt ? new Date(completedAt) : null, 
        lastAccess ? new Date(lastAccess) : null,
        firstStart ? new Date(firstStart) : null,
        enrollmentId
      ]);
      
      if (result.rowCount > 0) {
        updated++;
      } else {
        notFound++;
      }
      
      if (updated % 5000 === 0 && updated > 0) {
        console.log(`Updated ${updated}...`);
      }
    } catch (err) {
      errors++;
      if (errors < 5) {
        console.error(`Error ${enrollmentId}:`, err.message);
      }
    }
  }
  
  console.log(`\nDone!`);
  console.log(`Updated: ${updated}`);
  console.log(`Not found: ${notFound}`);
  console.log(`Errors: ${errors}`);
  
  const summary = await client.query(`
    SELECT status, COUNT(*) as count 
    FROM enrollments 
    GROUP BY status
  `);
  console.log('\nEnrollments by status:');
  summary.rows.forEach(r => console.log(`  ${r.status}: ${r.count}`));
  
  const withAccess = await client.query(`
    SELECT COUNT(*) as count FROM enrollments WHERE last_access_at IS NOT NULL
  `);
  console.log(`\nEnrollments with last_access_at: ${withAccess.rows[0].count}`);
  
  await client.end();
}

updateEnrollments().catch(console.error);
