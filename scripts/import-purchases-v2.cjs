const mysql = require('mysql2/promise');
const { Pool } = require('pg');

async function main() {
  const ovh = await mysql.createConnection({
    host: '135.125.205.19',
    port: 3306,
    user: 'pro_tutor81',
    password: 'hpm0?7C3',
    database: 'pro_tutor81'
  });

  const pg = new Pool({ connectionString: process.env.DATABASE_URL });

  // Get admin mappings: admin_id -> tutor_id
  const admins = await pg.query('SELECT id, tutor_id FROM tutor_admins');
  const adminToTutor = {};
  admins.rows.forEach(a => { adminToTutor[a.id] = a.tutor_id; });
  
  console.log('Admin mappings:', Object.keys(adminToTutor).length);
  
  // Get purchases from OVH where tutor_id (which is actually admin_id) matches our admins
  const adminIds = Object.keys(adminToTutor).join(',');
  const [purchases] = await ovh.execute(`
    SELECT * FROM tutors_purchases 
    WHERE tutor_id IN (${adminIds})
    ORDER BY id
  `);

  console.log(`Trovate ${purchases.length} vendite da importare`);

  // Clear existing
  await pg.query('DELETE FROM tutors_purchases');

  let imported = 0;
  for (const p of purchases) {
    const realTutorId = adminToTutor[p.tutor_id];
    if (!realTutorId) continue;
    
    try {
      await pg.query(`
        INSERT INTO tutors_purchases (id, tutor_id, customer_company_id, user_company_ref, learning_project_id, qta, price, creation_date, code, executed, nota, invoiced, invoice_date, ext_po_number, cost_centre_id, pack_purchase_id)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)
      `, [
        p.id,
        realTutorId, // Mapped tutor_id
        p.customer_company_id,
        p.user_company_ref,
        p.learning_project_id,
        p.qta,
        p.price,
        p.creation_date,
        p.code,
        p.executed ? true : false,
        p.nota,
        p.invoiced ? true : false,
        p.invoice_date,
        p.ext_po_number,
        p.cost_centre_id,
        p.pack_purchase_id
      ]);
      imported++;
    } catch (err) {
      // Skip duplicates silently
    }
  }

  console.log(`Importate ${imported} vendite`);

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
