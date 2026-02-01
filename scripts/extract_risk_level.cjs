const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});

async function extractRiskLevel() {
  const { rows: courses } = await pool.query('SELECT id, title FROM courses');
  
  let updated = 0;
  
  for (const course of courses) {
    const title = course.title.toUpperCase();
    let riskLevel = null;
    
    if (title.includes('RISCHIO ALTO') || title.includes('RISCHIO ALTO')) {
      riskLevel = 'alto';
    } else if (title.includes('RISCHIO MEDIO')) {
      riskLevel = 'medio';
    } else if (title.includes('RISCHIO BASSO')) {
      riskLevel = 'basso';
    }
    
    if (riskLevel) {
      await pool.query('UPDATE courses SET risk_level = $1 WHERE id = $2', [riskLevel, course.id]);
      console.log(`ID ${course.id}: ${riskLevel} - ${course.title.substring(0, 60)}`);
      updated++;
    }
  }
  
  console.log(`\n=== Aggiornati ${updated} corsi con risk_level ===`);
  await pool.end();
}

extractRiskLevel().catch(console.error);
