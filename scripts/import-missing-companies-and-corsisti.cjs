const mysql = require('mysql2/promise');
const { Pool } = require('pg');

const { getOvhDbConfig } = require('./ovh-db-config.cjs');

async function main() {
  const ovh = await mysql.createConnection(getOvhDbConfig());

  const pg = new Pool({ connectionString: process.env.DATABASE_URL });

  // Get existing company IDs
  const existingRes = await pg.query('SELECT id FROM companies');
  const existingIds = new Set(existingRes.rows.map(r => r.id));
  console.log(`${existingIds.size} companies esistenti`);

  // Get all client companies from OVH for our tutors
  const [clients] = await ovh.execute(`
    SELECT DISTINCT
      client.id,
      client.business_name,
      client.address,
      client.email,
      client.phone,
      client.cap,
      client.city,
      tutor.id as tutor_id
    FROM learning_project_users lpu
    JOIN users admin_user ON admin_user.id = lpu.company_id
    JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
    JOIN companies client ON client.id = lpu.id_company
    WHERE tutor.id IN (1498, 588, 1333, 2843, 2571, 2, 2600, 1335, 2569, 1033, 2978, 1469, 443, 1311)
      AND tutor.business_name NOT LIKE '%MIROGLIO%'
      AND tutor.business_name NOT LIKE '%SINTEX%'
      AND tutor.business_name NOT LIKE '%ADECCO%'
  `);

  console.log(`Trovate ${clients.length} companies cliente in OVH`);

  // Import missing companies
  let newCompanies = 0;
  for (const c of clients) {
    if (existingIds.has(c.id)) continue;

    try {
      await pg.query(`
        INSERT INTO companies (id, tutor_id, business_name, address, email, phone, cap, city, is_active)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, true)
      `, [c.id, c.tutor_id, c.business_name, c.address, c.email, c.phone, c.cap, c.city]);
      newCompanies++;
      existingIds.add(c.id);
    } catch (err) {
      // Skip
    }
  }
  console.log(`Importate ${newCompanies} nuove companies`);

  // Now import ALL corsisti for these companies
  const [corsisti] = await ovh.execute(`
    SELECT DISTINCT
      corsista.id,
      corsista.email,
      corsista.name as first_name,
      corsista.surname as last_name,
      corsista.tax_code,
      corsista.phone,
      lpu.id_company as company_id
    FROM learning_project_users lpu
    JOIN users admin_user ON admin_user.id = lpu.company_id
    JOIN companies tutor ON tutor.id = admin_user.company_id AND tutor.is_tutor = 1
    JOIN users corsista ON corsista.id = lpu.user_id
    WHERE tutor.id IN (1498, 588, 1333, 2843, 2571, 2, 2600, 1335, 2569, 1033, 2978, 1469, 443, 1311)
      AND tutor.business_name NOT LIKE '%MIROGLIO%'
      AND tutor.business_name NOT LIKE '%SINTEX%'
      AND tutor.business_name NOT LIKE '%ADECCO%'
  `);

  console.log(`Trovati ${corsisti.length} corsisti`);

  await pg.query('DELETE FROM students');
  
  let imported = 0;
  const seenIds = new Set();
  for (const c of corsisti) {
    if (seenIds.has(c.id)) continue;
    seenIds.add(c.id);
    
    if (!existingIds.has(c.company_id)) continue;

    try {
      await pg.query(`
        INSERT INTO students (id, company_id, email, first_name, last_name, fiscal_code, phone, is_active)
        VALUES ($1, $2, $3, $4, $5, $6, $7, true)
      `, [c.id, c.company_id, c.email || '', c.first_name, c.last_name, c.tax_code, c.phone]);
      imported++;
    } catch (err) {
      // Skip
    }
  }

  console.log(`Importati ${imported} corsisti`);

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
