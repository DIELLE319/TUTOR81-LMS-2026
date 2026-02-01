const fs = require('fs');
const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL
});

async function importLicenseCodes() {
  const csv = fs.readFileSync('attached_assets/learning_project_users_1769816546267.csv', 'utf8');
  const lines = csv.split('\n').slice(1).filter(l => l.trim());
  
  console.log(`Importing ${lines.length} license codes...`);
  
  let updated = 0;
  
  for (const line of lines) {
    // Parse CSV line with quotes
    const matches = line.match(/"([^"]*)"/g);
    if (!matches || matches.length < 5) continue;
    
    const legacyId = parseInt(matches[0].replace(/"/g, ''));
    const licenseCode = matches[4].replace(/"/g, '');
    
    if (!legacyId || !licenseCode) continue;
    
    const result = await pool.query(
      'UPDATE enrollments SET license_code = $1 WHERE legacy_id = $2',
      [licenseCode, legacyId]
    );
    
    if (result.rowCount > 0) {
      updated++;
    }
  }
  
  console.log(`Updated ${updated} enrollments with license codes`);
  await pool.end();
}

importLicenseCodes().catch(console.error);
