const mysql = require('mysql2/promise');
const { getOvhDbConfig } = require('./ovh-db-config.cjs');

async function main() {
  const connection = await mysql.createConnection(getOvhDbConfig());

  // Lista admin IDs dal database Replit
  const adminIds = [10983, 10829, 40075, 41611, 16939, 11092, 24600, 11091, 24602, 21661, 
                    20529, 17014, 38162, 29686, 21933, 25317, 24651, 25494, 26247, 26627, 
                    30015, 40953, 36778, 36913, 27952, 39664, 41392];

  // Query vendite per admin
  const [rows] = await connection.execute(`
    SELECT 
      tp.id,
      tp.user_company_ref as admin_id,
      CONCAT(u.name, ' ', u.surname) as admin_name,
      tp.customer_company_id as client_id,
      c.business_name as client_name,
      tp.learning_project_id,
      tp.qta,
      tp.price,
      tp.code,
      tp.creation_date
    FROM tutors_purchases tp
    JOIN users u ON u.id = tp.user_company_ref
    JOIN companies c ON c.id = tp.customer_company_id
    WHERE tp.user_company_ref IN (${adminIds.join(',')})
    ORDER BY tp.user_company_ref, tp.creation_date DESC
    LIMIT 50
  `);

  console.log('VENDITE PER ADMIN:');
  console.log('='.repeat(100));
  rows.forEach(r => {
    console.log(`Admin ${r.admin_id} (${r.admin_name}) -> Cliente ${r.client_id} (${r.client_name}) - Corso ${r.learning_project_id}, Qta: ${r.qta}, Prezzo: ${r.price}`);
  });
  console.log(`\nTotale: ${rows.length} vendite`);

  await connection.end();
}

main().catch(console.error);
