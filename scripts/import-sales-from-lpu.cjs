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

  // Usa la STESSA query che abbiamo fatto prima
  const [rows] = await ovh.execute(`
    SELECT 
      tutor.id as id_ente_formativo,
      tutor.business_name as ente_formativo,
      admin_user.id as id_admin,
      CONCAT(admin_user.name, ' ', admin_user.surname) as admin,
      client.id as id_cliente,
      client.business_name as cliente,
      tp.id as id_acquisto,
      tp.learning_project_id as id_corso,
      tp.qta as quantita,
      tp.price as prezzo,
      tp.code as codice_acquisto,
      tp.creation_date as data_acquisto
    FROM learning_project_users lpu
    JOIN users admin_user ON admin_user.id = lpu.company_id
    JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
    JOIN companies client ON client.id = lpu.id_company
    LEFT JOIN tutors_purchases tp ON tp.user_company_ref = admin_user.id AND tp.customer_company_id = client.id
    WHERE tutor.business_name NOT LIKE '%MIROGLIO%'
      AND tutor.business_name NOT LIKE '%SINTEX%'
      AND tutor.business_name NOT LIKE '%ADECCO%'
      AND tp.id IS NOT NULL
    GROUP BY tutor.id, tutor.business_name, admin_user.id, admin_user.name, admin_user.surname, client.id, client.business_name, tp.id, tp.learning_project_id, tp.qta, tp.price, tp.code, tp.creation_date
    ORDER BY tutor.business_name
    LIMIT 20
  `);

  console.log('Vendite dalla query originale:');
  console.log('='.repeat(100));
  rows.forEach(r => {
    console.log(`Tutor ${r.id_ente_formativo} (${r.ente_formativo}) - Admin ${r.id_admin} (${r.admin}) - Cliente ${r.id_cliente} (${r.cliente}) - Acquisto ${r.id_acquisto}`);
  });
  console.log(`\nTotale: ${rows.length} righe`);

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
