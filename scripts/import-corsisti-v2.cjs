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

  // Get corsisti (role=0) with company_id and creator_id (admin)
  const [corsisti] = await ovh.execute(`
    SELECT DISTINCT
      u.id,
      u.email,
      u.name as first_name,
      u.surname as last_name,
      u.tax_code as fiscal_code,
      u.phone,
      u.company_id,
      u.creator_id,
      u.code
    FROM users u
    WHERE u.role = 0
      AND u.company_id IN (SELECT id FROM companies WHERE is_tutor = 0)
    LIMIT 20000
  `);

  console.log(`Trovati ${corsisti.length} corsisti`);

  // Clear and import
  await pg.query('DELETE FROM students');

  let imported = 0;
  let skipped = 0;

  for (const c of corsisti) {
    if (!validCompanyIds.has(Number(c.company_id))) {
      skipped++;
      continue;
    }

    try {
      await pg.query(`
        INSERT INTO students (id, company_id, email, first_name, last_name, fiscal_code, phone, is_active)
        VALUES ($1, $2, $3, $4, $5, $6, $7, true)
        ON CONFLICT (id) DO NOTHING
      `, [c.id, c.company_id, c.email || '', c.first_name, c.last_name, c.fiscal_code, c.phone]);
      imported++;
    } catch (err) {
      // Skip
    }
  }

  console.log(`Importati ${imported} corsisti (skipped ${skipped} per company mancante)`);

  // Sample
  const sample = await pg.query('SELECT s.id, s.first_name, s.last_name, c.business_name FROM students s JOIN companies c ON c.id = s.company_id LIMIT 5');
  console.log('\nEsempio:');
  sample.rows.forEach(r => console.log(`${r.first_name} ${r.last_name} - ${r.business_name}`));

  await ovh.end();
  await pg.end();
}

main().catch(console.error);
