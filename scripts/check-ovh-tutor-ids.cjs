const mysql = require('mysql2/promise');

async function main() {
  const connection = await mysql.createConnection({
    host: '135.125.205.19',
    port: 3306,
    user: 'pro_tutor81',
    password: 'hpm0?7C3',
    database: 'pro_tutor81'
  });

  const [rows] = await connection.execute(`
    SELECT DISTINCT tutor_id, COUNT(*) as cnt
    FROM tutors_purchases 
    GROUP BY tutor_id
    ORDER BY cnt DESC
    LIMIT 20
  `);

  console.log('Tutor IDs nelle vendite OVH:');
  rows.forEach(r => console.log(`Tutor ${r.tutor_id}: ${r.cnt} vendite`));

  await connection.end();
}

main().catch(console.error);
