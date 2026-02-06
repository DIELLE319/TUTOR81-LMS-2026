const mysql = require('mysql2/promise');
const { getOvhDbConfig } = require('./ovh-db-config.cjs');

async function main() {
  const ovh = await mysql.createConnection(getOvhDbConfig());

  // Dalla query originale: tutor → admin → cliente → corsista
  const [rows] = await ovh.execute(`
    SELECT 
      tutor.id as tutor_id,
      tutor.business_name as tutor_name,
      admin_user.id as admin_id,
      CONCAT(admin_user.name, ' ', admin_user.surname) as admin_name,
      client.id as client_id,
      client.business_name as client_name,
      corsista.id as corsista_id,
      CONCAT(corsista.name, ' ', corsista.surname) as corsista_name,
      corsista.email as corsista_email
    FROM learning_project_users lpu
    JOIN users admin_user ON admin_user.id = lpu.company_id
    JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
    JOIN companies client ON client.id = lpu.id_company
    JOIN users corsista ON corsista.id = lpu.user_id
    WHERE tutor.id IN (1498, 588, 1333, 2843, 2571, 2, 2600, 1335, 2569, 1033, 2978, 1469, 443, 1311)
      AND tutor.business_name NOT LIKE '%MIROGLIO%'
      AND tutor.business_name NOT LIKE '%SINTEX%'
      AND tutor.business_name NOT LIKE '%ADECCO%'
    GROUP BY tutor.id, tutor.business_name, admin_user.id, admin_user.name, admin_user.surname, 
             client.id, client.business_name, corsista.id, corsista.name, corsista.surname, corsista.email
    ORDER BY tutor.business_name, client.business_name
    LIMIT 20
  `);

  console.log('Gerarchia completa:');
  rows.forEach(r => {
    console.log(`Tutor: ${r.tutor_name} | Admin: ${r.admin_name} | Cliente: ${r.client_name} | Corsista: ${r.corsista_name}`);
  });

  // Conta clienti e corsisti unici
  const [counts] = await ovh.execute(`
    SELECT 
      COUNT(DISTINCT client.id) as clienti,
      COUNT(DISTINCT corsista.id) as corsisti
    FROM learning_project_users lpu
    JOIN users admin_user ON admin_user.id = lpu.company_id
    JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
    JOIN companies client ON client.id = lpu.id_company
    JOIN users corsista ON corsista.id = lpu.user_id
    WHERE tutor.id IN (1498, 588, 1333, 2843, 2571, 2, 2600, 1335, 2569, 1033, 2978, 1469, 443, 1311)
      AND tutor.business_name NOT LIKE '%MIROGLIO%'
      AND tutor.business_name NOT LIKE '%SINTEX%'
      AND tutor.business_name NOT LIKE '%ADECCO%'
  `);
  console.log(`\nTotali: ${counts[0].clienti} clienti, ${counts[0].corsisti} corsisti`);

  await ovh.end();
}

main().catch(console.error);



