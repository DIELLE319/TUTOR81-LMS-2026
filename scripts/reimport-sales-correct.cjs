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

  // Get tutor IDs from Replit (quelli che abbiamo allineato)
  const tutorRes = await pg.query('SELECT id FROM tutors');
  const validTutorIds = tutorRes.rows.map(r => r.id);
  console.log('Tutor IDs validi:', validTutorIds.join(', '));

  // Query CORRETTA: usa la struttura learning_project_users per trovare le vendite
  const [rows] = await ovh.execute(`
    SELECT DISTINCT
      tutor.id as tutor_id,
      admin_user.id as admin_id,
      client.id as client_id,
      tp.*
    FROM learning_project_users lpu
    JOIN users admin_user ON admin_user.id = lpu.company_id
    JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
    JOIN companies client ON client.id = lpu.id_company
    JOIN tutors_purchases tp ON tp.user_company_ref = admin_user.id AND tp.customer_company_id = client.id
    WHERE tutor.id IN (${validTutorIds.join(',')})
      AND tutor.business_name NOT LIKE '%MIROGLIO%'
      AND tutor.business_name NOT LIKE '%SINTEX%'
      AND tutor.business_name NOT LIKE '%ADECCO%'
    ORDER BY tp.id
  `);

  console.log(`Trovate ${rows.length} vendite dalla query corretta`);

  // Clear existing
  await pg.query('DELETE FROM tutors_purchases');

  let imported = 0;
  const seenIds = new Set();
  
  for (const r of rows) {
    if (seenIds.has(r.id)) continue;
    seenIds.add(r.id);
    
    try {
      await pg.query(`
        INSERT INTO tutors_purchases (id, tutor_id, customer_company_id, user_company_ref, learning_project_id, qta, price, creation_date, code, executed, nota, invoiced, invoice_date, ext_po_number, cost_centre_id, pack_purchase_id)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12, $13, $14, $15, $16)
      `, [
        r.id,
        r.tutor_id,          // Tutor ID corretto dalla query
        r.customer_company_id,
        r.user_company_ref,   // Admin ID
        r.learning_project_id,
        r.qta,
        r.price,
        r.creation_date,
        r.code,
        r.executed ? true : false,
        r.nota,
        r.invoiced ? true : false,
        r.invoice_date,
        r.ext_po_number,
        r.cost_centre_id,
        r.pack_purchase_id
      ]);
      imported++;
    } catch (err) {
      console.error(`Errore ${r.id}:`, err.message);
    }
  }

  console.log(`Importate ${imported} vendite uniche`);

  // Verifica
  const check = await pg.query(`
    SELECT t.business_name, COUNT(*) as cnt 
    FROM tutors_purchases tp 
    JOIN tutors t ON t.id = tp.tutor_id 
    GROUP BY t.id, t.business_name 
    ORDER BY cnt DESC
  `);
  console.log('\nVendite per tutor:');
  check.rows.forEach(r => console.log(`${r.business_name}: ${r.cnt}`));

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
