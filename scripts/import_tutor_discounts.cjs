const { Pool } = require('pg');
const fs = require('fs');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});

async function importDiscounts() {
  const data = fs.readFileSync('/tmp/tutor_discounts.tsv', 'utf8');
  const lines = data.trim().split('\n').slice(1);
  
  let updated = 0;
  
  for (const line of lines) {
    const [id, discount] = line.split('\t');
    const tutorId = parseInt(id);
    const discountPct = parseInt(discount) || 60;
    
    if (isNaN(tutorId)) continue;
    
    const result = await pool.query(
      `UPDATE tutors SET discount_percentage = $1 WHERE id = $2`,
      [discountPct, tutorId]
    );
    
    if (result.rowCount > 0) {
      updated++;
    }
  }
  
  console.log(`Updated ${updated} tutors with discounts`);
  
  const stats = await pool.query(`
    SELECT discount_percentage, COUNT(*) as count
    FROM tutors
    GROUP BY discount_percentage
    ORDER BY count DESC
  `);
  console.log('Discount distribution:', stats.rows);
  
  await pool.end();
}

importDiscounts().catch(console.error);
