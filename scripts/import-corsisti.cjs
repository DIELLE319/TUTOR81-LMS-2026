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

  // Get company IDs that exist in Replit
  const companiesRes = await pg.query('SELECT id FROM companies');
  const validCompanyIds = new Set(companiesRes.rows.map(r => r.id));
  console.log(`${validCompanyIds.size} companies valide`);

  // Get corsisti from OVH with their company (client)
  const [corsisti] = await ovh.execute(`
    SELECT DISTINCT
      corsista.id,
      corsista.email,
      corsista.name as first_name,
      corsista.surname as last_name,
      corsista.fiscal_code,
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

  // Clear and import
  await pg.query('DELETE FROM students');

  let imported = 0;
  let skipped = 0;
  const seenIds = new Set();

  for (const c of corsisti) {
    if (seenIds.has(c.id)) continue;
    seenIds.add(c.id);
    
    if (!validCompanyIds.has(c.company_id)) {
      skipped++;
      continue;
    }

    try {
      await pg.query(`
        INSERT INTO students (id, company_id, email, first_name, last_name, fiscal_code, phone, is_active)
        VALUES ($1, $2, $3, $4, $5, $6, $7, true)
      `, [c.id, c.company_id, c.email || '', c.first_name, c.last_name, c.fiscal_code, c.phone]);
      imported++;
    } catch (err) {
      // Skip
    }
  }

  console.log(`Importati ${imported} corsisti (skipped ${skipped} per company mancante)`);

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
