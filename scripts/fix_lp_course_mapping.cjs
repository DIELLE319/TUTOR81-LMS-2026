const { Pool } = require('pg');
const fs = require('fs');

const pool = new Pool({ connectionString: process.env.DATABASE_URL });

async function main() {
  // Load OVH data
  const unities = JSON.parse(fs.readFileSync('/tmp/ovh_data/unities_lo.json', 'utf8')).unities;
  const relations = JSON.parse(fs.readFileSync('/tmp/ovh_data/relations.json', 'utf8'));
  const ovhLP = JSON.parse(fs.readFileSync('/tmp/ovh_data/learning_project.json', 'utf8'));
  
  // Build LP -> Course IDs map from unities_lo
  const lpToCourses = new Map();
  for (const u of unities) {
    const lpId = parseInt(u.learning_project_id);
    const courseId = parseInt(u.course_id);
    if (!lpToCourses.has(lpId)) lpToCourses.set(lpId, new Set());
    lpToCourses.get(lpId).add(courseId);
  }
  console.log('LP to Courses map built:', lpToCourses.size, 'LPs');
  
  // Map OVH LP ID to title for matching
  const ovhLPById = new Map();
  for (const lp of ovhLP) {
    ovhLPById.set(parseInt(lp.id), lp.title);
  }
  
  const dbLPs = await pool.query('SELECT id, title, sort_order, legacy_course_id FROM learning_projects ORDER BY id');
  console.log('DB Learning Projects:', dbLPs.rows.length);
  
  // Try to match by title
  const lpMapping = [];
  for (const dbLP of dbLPs.rows) {
    const normalizedTitle = dbLP.title.trim().toLowerCase();
    for (const [ovhId, ovhTitle] of ovhLPById) {
      if (ovhTitle.trim().toLowerCase() === normalizedTitle) {
        lpMapping.push({ dbId: dbLP.id, ovhLpId: ovhId, title: dbLP.title });
        break;
      }
    }
  }
  console.log('Matched LPs by title:', lpMapping.length);
  
  // Clear all old course_modules
  await pool.query('DELETE FROM course_modules');
  console.log('Cleared old course_modules');
  
  let inserted = 0, errors = 0;
  
  for (const mapping of lpMapping) {
    const courses = lpToCourses.get(mapping.ovhLpId);
    if (!courses || courses.size === 0) continue;
    
    // Collect all module IDs for this LP's courses
    const moduleIds = [];
    for (const courseId of courses) {
      const courseMods = relations.course_course_modules.filter(r => parseInt(r.course_id) === courseId);
      for (const cm of courseMods) {
        moduleIds.push({
          ovhModuleId: parseInt(cm.course_module_id),
          position: parseInt(cm.order_course) || 0,
          legacyCourseId: courseId
        });
      }
    }
    
    if (moduleIds.length === 0) continue;
    
    for (const mod of moduleIds) {
      const dbMod = await pool.query('SELECT id FROM modules WHERE legacy_id = $1', [mod.ovhModuleId]);
      if (dbMod.rows.length === 0) {
        errors++;
        continue;
      }
      
      await pool.query(
        'INSERT INTO course_modules (learning_project_id, module_id, position, legacy_course_id) VALUES ($1, $2, $3, $4) ON CONFLICT DO NOTHING',
        [mapping.dbId, dbMod.rows[0].id, mod.position, mod.legacyCourseId]
      );
      inserted++;
    }
  }
  
  console.log(`\nInserted ${inserted} course_module relations`);
  console.log(`Errors (modules not found): ${errors}`);
  
  // Count final
  const count = await pool.query('SELECT COUNT(*) FROM course_modules');
  console.log('Total course_modules now:', count.rows[0].count);
  
  // Verify RLS aggiornamento
  console.log('\n=== VERIFICA RLS AGGIORNAMENTO ===');
  const rlsLP = dbLPs.rows.find(lp => lp.title.toLowerCase().includes('rls') && lp.title.toLowerCase().includes('aggiorn'));
  if (rlsLP) {
    const mods = await pool.query(`
      SELECT m.id, m.legacy_id, m.title 
      FROM course_modules cm 
      JOIN modules m ON m.id = cm.module_id 
      WHERE cm.learning_project_id = $1
    `, [rlsLP.id]);
    console.log(`LP "${rlsLP.title}" (ID ${rlsLP.id}):`);
    mods.rows.forEach(m => console.log('  Module legacy_id', m.legacy_id, '-', m.title));
  }
  
  await pool.end();
}

main().catch(console.error);
