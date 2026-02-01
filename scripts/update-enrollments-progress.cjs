const { Client } = require('pg');
const fs = require('fs');

async function updateProgress() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  console.log("Updating enrollments with progress data...");
  
  const content = fs.readFileSync('ovh_data/learning_events_progress.tsv', 'utf-8');
  const lines = content.split('\n');
  
  console.log(`Found ${lines.length - 1} progress records`);
  
  let updated = 0;
  let notFound = 0;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const parts = line.split('\t');
    const enrollmentId = parseInt(parts[0]);
    const progress = parseInt(parts[1]) || 0;
    const completedAt = parts[2] && parts[2] !== 'NULL' ? parts[2] : null;
    
    if (!enrollmentId) continue;
    
    try {
      const status = progress >= 100 ? 'completed' : 'active';
      
      const result = await client.query(`
        UPDATE enrollments 
        SET progress = $1, 
            status = $2,
            completed_at = $3
        WHERE id = $4
      `, [progress, status, completedAt ? new Date(completedAt) : null, enrollmentId]);
      
      if (result.rowCount > 0) {
        updated++;
      } else {
        notFound++;
      }
      
      if (updated % 5000 === 0 && updated > 0) {
        console.log(`Updated ${updated}...`);
      }
    } catch (err) {
      // Skip errors silently
    }
  }
  
  console.log(`\nDone!`);
  console.log(`Updated: ${updated}`);
  console.log(`Not found: ${notFound}`);
  
  // Summary
  const summary = await client.query(`
    SELECT status, COUNT(*) as count 
    FROM enrollments 
    GROUP BY status
  `);
  console.log('\nEnrollments by status:');
  summary.rows.forEach(r => console.log(`  ${r.status}: ${r.count}`));
  
  await client.end();
}

updateProgress().catch(console.error);
