const { Client } = require('pg');
const fs = require('fs');

async function updateEnrollments() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  console.log("Reset all to pending, then update with 2025+ active data...");
  
  await client.query(`UPDATE enrollments SET status = 'pending', progress = 0, last_access_at = NULL`);
  
  const content = fs.readFileSync('ovh_data/learning_events_2025.tsv', 'utf-8');
  const lines = content.split('\n');
  
  let updated = 0;
  
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;
    
    const parts = line.split('\t');
    const enrollmentId = parseInt(parts[0]);
    const firstStart = parts[1] && parts[1] !== 'NULL' ? parts[1] : null;
    const lastAccess = parts[2] && parts[2] !== 'NULL' ? parts[2] : null;
    const progress = parseInt(parts[3]) || 0;
    
    if (!enrollmentId) continue;
    
    const result = await client.query(`
      UPDATE enrollments 
      SET progress = $1, status = 'active', last_access_at = $2, start_date = $3
      WHERE id = $4
    `, [progress, lastAccess ? new Date(lastAccess) : null, firstStart ? new Date(firstStart) : null, enrollmentId]);
    
    if (result.rowCount > 0) updated++;
  }
  
  console.log(`Updated to ACTIVE: ${updated}`);
  
  const summary = await client.query(`SELECT status, COUNT(*) as count FROM enrollments GROUP BY status`);
  summary.rows.forEach(r => console.log(`${r.status}: ${r.count}`));
  
  await client.end();
}

updateEnrollments().catch(console.error);
