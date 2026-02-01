const fs = require('fs');
const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL,
});

async function importHours() {
  const csv = fs.readFileSync('attached_assets/corsi_attivi_1769986067570.csv', 'utf-8');
  const lines = csv.split('\n');
  
  let updated = 0;
  let errors = [];
  
  for (const line of lines) {
    const cols = line.split(';');
    if (cols.length < 4) continue;
    
    const tipo = cols[1]?.trim();
    const idStr = cols[2]?.trim();
    const nome = cols[3]?.trim();
    
    if (!idStr || isNaN(parseInt(idStr))) continue;
    
    const id = parseInt(idStr);
    
    // Estrai ore dal titolo (es. "12 ORE", "8 ORE", "4 ORE")
    const hoursMatch = nome.match(/(\d+)\s*ORE/i);
    if (!hoursMatch) {
      console.log(`No hours found in: ${nome}`);
      continue;
    }
    
    const hours = parseInt(hoursMatch[1]);
    
    try {
      const result = await pool.query(
        'UPDATE courses SET hours = $1 WHERE id = $2',
        [hours, id]
      );
      
      if (result.rowCount > 0) {
        console.log(`Updated LP legacy_id=${id}: ${hours} ore - ${nome.substring(0, 50)}`);
        updated++;
      } else {
        errors.push(`Not found: legacy_id=${id}`);
      }
    } catch (err) {
      errors.push(`Error updating ${id}: ${err.message}`);
    }
  }
  
  console.log(`\n=== RISULTATO ===`);
  console.log(`Aggiornati: ${updated} corsi`);
  if (errors.length > 0) {
    console.log(`Errori: ${errors.length}`);
    errors.forEach(e => console.log(`  - ${e}`));
  }
  
  await pool.end();
}

importHours().catch(console.error);
