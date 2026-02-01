const fs = require('fs');
const { Client } = require('pg');

async function main() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  // Leggi mapping tutor da OVH
  const data = fs.readFileSync('ovh_data/enrollment_tutors.tsv', 'utf-8');
  const lines = data.trim().split('\n').slice(1); // Skip header
  
  // Leggi mapping tutor_id OVH -> Replit
  const tutorMapping = {};
  const tutorRes = await client.query('SELECT id, business_name FROM tutors');
  for (const t of tutorRes.rows) {
    tutorMapping[t.business_name.trim().toUpperCase()] = t.id;
  }
  console.log('Tutors in Replit:', Object.keys(tutorMapping).length);
  
  let updated = 0;
  let notFound = 0;
  
  for (const line of lines) {
    const [legacyId, tutorOvhId, tutorName] = line.split('\t');
    const tutorKey = tutorName.trim().toUpperCase();
    const tutorId = tutorMapping[tutorKey];
    
    if (!tutorId) {
      console.log('Tutor non trovato:', tutorName);
      notFound++;
      continue;
    }
    
    const result = await client.query(
      'UPDATE enrollments SET tutor_id = $1 WHERE legacy_id = $2',
      [tutorId, parseInt(legacyId)]
    );
    
    if (result.rowCount > 0) updated++;
  }
  
  console.log(`Aggiornati: ${updated}, Non trovati: ${notFound}`);
  await client.end();
}

main().catch(console.error);
