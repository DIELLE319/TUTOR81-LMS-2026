const { Pool } = require('pg');
const fs = require('fs');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});

async function importPrices() {
  const data = fs.readFileSync('/tmp/course_prices.tsv', 'utf8');
  const lines = data.trim().split('\n').slice(1);
  
  let updated = 0;
  let notFound = 0;
  
  for (const line of lines) {
    const [lpId, listPrice] = line.split('\t');
    const id = parseInt(lpId);
    const price = parseFloat(listPrice);
    
    if (isNaN(id) || isNaN(price)) continue;
    
    const result = await pool.query(
      `UPDATE courses SET list_price = $1 WHERE id = $2`,
      [price, id]
    );
    
    if (result.rowCount > 0) {
      updated++;
    } else {
      notFound++;
    }
  }
  
  console.log(`Updated ${updated} courses with prices`);
  console.log(`${notFound} courses not found in local DB`);
  
  const stats = await pool.query(`
    SELECT 
      COUNT(*) as total,
      SUM(CASE WHEN list_price > 0 THEN 1 ELSE 0 END) as with_price
    FROM courses
  `);
  console.log('Final stats:', stats.rows[0]);
  
  await pool.end();
}

importPrices().catch(console.error);
