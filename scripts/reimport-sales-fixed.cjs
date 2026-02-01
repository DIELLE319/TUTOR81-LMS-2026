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

  // Rimuovo FK per importare
  await pg.query('ALTER TABLE tutors_purchases DROP CONSTRAINT IF EXISTS tutors_purchases_tutor_id_fkey');
  await pg.query('DELETE FROM tutors_purchases');

  // Admin IDs che abbiamo mappato (con il loro tutor_id)
  const admins = await pg.query('SELECT id, tutor_id FROM tutor_admins');
  const adminIds = admins.rows.map(a => a.id);
  console.log('Admin IDs:', adminIds.join(', '));

  // Query OVH: tutor_id nelle purchases è l'admin
  const [purchases] = await ovh.execute(`
    SELECT * FROM tutors_purchases 
    WHERE tutor_id IN (${adminIds.join(',')})
    ORDER BY id
  `);

  console.log(`Trovate ${purchases.length} vendite`);

  let imported = 0;
  for (const p of purchases) {
    try {
      await pg.query(`
        INSERT INTO tutors_purchases (id, tutor_id, customer_company_id, user_company_ref, learning_project_id, qta, price, creation_date, code, executed, nota, invoiced, invoice_date, ext_po_number, cost_centre_id, pack_purchase_id)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)
      `, [
        p.id,
        p.tutor_id,           // Admin ID (chi fa l'acquisto)
        p.customer_company_id,
        p.user_company_ref,   // Tutor di riferimento
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
      // Skip errors
    }
  }

  console.log(`Importate ${imported} vendite`);

  // Verifica per admin
  const check = await pg.query(`
    SELECT ta.name as admin, ta.tutor_id, COUNT(*) as vendite 
    FROM tutors_purchases tp 
    JOIN tutor_admins ta ON ta.id = tp.tutor_id
    GROUP BY ta.id, ta.name, ta.tutor_id
    ORDER BY vendite DESC
  `);
  console.log('\nVendite per Admin:');
  check.rows.forEach(r => console.log(`${r.admin} (tutor ${r.tutor_id}): ${r.vendite} vendite`));

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
