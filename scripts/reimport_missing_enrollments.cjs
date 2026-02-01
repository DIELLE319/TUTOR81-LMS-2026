const { Pool } = require('pg');
const fs = require('fs');
const path = require('path');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});

async function reimportMissingEnrollments() {
  const client = await pool.connect();
  
  try {
    // Read the CSV file
    const csvPath = path.join(__dirname, '../attached_assets/learning_project_users_1769816546267.csv');
    const csvContent = fs.readFileSync(csvPath, 'utf-8');
    const lines = csvContent.split('\n').filter(l => l.trim());
    
    // Skip header
    const dataLines = lines.slice(1);
    
    console.log(`Total lines in CSV: ${dataLines.length}`);
    
    let imported = 0;
    let skipped = 0;
    let errors = 0;
    
    // Companies that need reimport (all enrollments were deleted as "too old")
    const companiesNeedReimport = [1053, 2293]; // AZOVE, Camera di Commercio TV
    
    for (const line of dataLines) {
      // Parse CSV line (handle quoted values)
      const matches = line.match(/("([^"]*)"|[^,]*)(,|$)/g);
      if (!matches) continue;
      
      const values = matches.map(m => {
        let v = m.replace(/,$/,'').trim();
        if (v.startsWith('"') && v.endsWith('"')) {
          v = v.slice(1, -1);
        }
        return v;
      });
      
      const [id, legacyId, companyId, learningProjectId, licenseCode, createdAt, tutorId, enrollmentId, active, startDate, endDate, daysToAlert, progress, email] = values;
      
      const compId = parseInt(companyId);
      
      // Only reimport for companies that need it
      if (!companiesNeedReimport.includes(compId)) {
        continue;
      }
      
      // Check if enrollment already exists
      const existing = await client.query(
        'SELECT id FROM enrollments WHERE legacy_id = $1',
        [parseInt(legacyId)]
      );
      
      if (existing.rows.length > 0) {
        skipped++;
        continue;
      }
      
      // Find user by email
      const userResult = await client.query(
        'SELECT id FROM users WHERE email = $1',
        [email?.toLowerCase()]
      );
      
      if (userResult.rows.length === 0) {
        console.log(`User not found for email: ${email}`);
        errors++;
        continue;
      }
      
      const userId = userResult.rows[0].id;
      const lpId = parseInt(learningProjectId);
      
      // Check if learning project exists
      const lpResult = await client.query(
        'SELECT id FROM learning_projects WHERE id = $1',
        [lpId]
      );
      
      if (lpResult.rows.length === 0) {
        console.log(`Learning project not found: ${lpId}`);
        errors++;
        continue;
      }
      
      // Insert enrollment
      try {
        await client.query(`
          INSERT INTO enrollments (
            legacy_id, user_id, company_id, learning_project_id,
            license_code, start_date, end_date, days_to_alert,
            progress, status, created_at
          ) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11)
        `, [
          parseInt(legacyId),
          userId,
          compId,
          lpId,
          licenseCode,
          startDate || null,
          endDate || null,
          parseInt(daysToAlert) || 15,
          parseInt(progress) || 0,
          'not_started',
          createdAt || new Date()
        ]);
        
        imported++;
        console.log(`Imported: ${email} -> LP ${lpId} (company ${compId})`);
      } catch (err) {
        console.error(`Error importing ${email}: ${err.message}`);
        errors++;
      }
    }
    
    console.log(`\n=== REIMPORT COMPLETE ===`);
    console.log(`Imported: ${imported}`);
    console.log(`Skipped (existing): ${skipped}`);
    console.log(`Errors: ${errors}`);
    
  } finally {
    client.release();
    await pool.end();
  }
}

reimportMissingEnrollments().catch(console.error);
