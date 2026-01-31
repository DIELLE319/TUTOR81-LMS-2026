const { Pool } = require('pg');
const fs = require('fs');

const pool = new Pool({ connectionString: process.env.DATABASE_URL });

async function main() {
  console.log('=== VERIFICA COMPLETA LP OVH vs REPLIT ===\n');
  
  // Load OVH data
  const unities = JSON.parse(fs.readFileSync('/tmp/ovh_data/unities_lo.json', 'utf8')).unities;
  const relations = JSON.parse(fs.readFileSync('/tmp/ovh_data/relations.json', 'utf8'));
  const ovhLP = JSON.parse(fs.readFileSync('/tmp/ovh_data/learning_project.json', 'utf8'));
  const ovhModules = JSON.parse(fs.readFileSync('/tmp/ovh_data/course_modules.json', 'utf8'));
  const ovhLessons = JSON.parse(fs.readFileSync('/tmp/ovh_data/lessons.json', 'utf8'));
  
  // Build maps
  const lpToCourses = new Map();
  for (const u of unities) {
    const lpId = parseInt(u.learning_project_id);
    const courseId = parseInt(u.course_id);
    if (!lpToCourses.has(lpId)) lpToCourses.set(lpId, new Set());
    lpToCourses.get(lpId).add(courseId);
  }
  
  const ovhLPById = new Map();
  for (const lp of ovhLP) ovhLPById.set(parseInt(lp.id), lp);
  
  // Get Replit LPs
  const dbLPs = await pool.query('SELECT id, title FROM learning_projects ORDER BY id');
  
  // Match by title and compare structures
  const mismatches = [];
  const matches = [];
  const missing = [];
  
  for (const dbLP of dbLPs.rows) {
    const normalizedTitle = dbLP.title.trim().toLowerCase();
    
    // Find matching OVH LP
    let ovhLpId = null;
    for (const [id, lp] of ovhLPById) {
      if (lp.title.trim().toLowerCase() === normalizedTitle) {
        ovhLpId = id;
        break;
      }
    }
    
    if (!ovhLpId) {
      missing.push({ dbId: dbLP.id, title: dbLP.title, reason: 'No OVH match found' });
      continue;
    }
    
    // Get OVH structure: LP -> Courses -> Modules
    const ovhCourses = lpToCourses.get(ovhLpId) || new Set();
    const ovhModuleIds = new Set();
    for (const courseId of ovhCourses) {
      const courseMods = relations.course_course_modules.filter(r => parseInt(r.course_id) === courseId);
      for (const cm of courseMods) {
        ovhModuleIds.add(parseInt(cm.course_module_id));
      }
    }
    
    // Get Replit structure
    const replitMods = await pool.query(`
      SELECT m.legacy_id 
      FROM course_modules cm 
      JOIN modules m ON m.id = cm.module_id 
      WHERE cm.learning_project_id = $1
    `, [dbLP.id]);
    const replitModuleIds = new Set(replitMods.rows.map(r => r.legacy_id));
    
    // Compare
    const ovhModsArr = [...ovhModuleIds].sort((a,b) => a-b);
    const replitModsArr = [...replitModuleIds].sort((a,b) => a-b);
    
    const ovhStr = ovhModsArr.join(',');
    const replitStr = replitModsArr.join(',');
    
    if (ovhStr === replitStr) {
      matches.push({ 
        dbId: dbLP.id, 
        ovhLpId, 
        title: dbLP.title.substring(0, 40), 
        modules: ovhModsArr.length 
      });
    } else {
      // Check what's different
      const inOvhNotReplit = ovhModsArr.filter(m => !replitModuleIds.has(m));
      const inReplitNotOvh = replitModsArr.filter(m => !ovhModuleIds.has(m));
      
      mismatches.push({
        dbId: dbLP.id,
        ovhLpId,
        title: dbLP.title.substring(0, 40),
        ovhModules: ovhModsArr,
        replitModules: replitModsArr,
        inOvhNotReplit,
        inReplitNotOvh
      });
    }
  }
  
  // Report
  console.log('=== RISULTATI ===');
  console.log(`LP Verificati: ${dbLPs.rows.length}`);
  console.log(`Corrispondenze ESATTE: ${matches.length}`);
  console.log(`Discrepanze: ${mismatches.length}`);
  console.log(`Non trovati in OVH: ${missing.length}`);
  
  if (mismatches.length > 0) {
    console.log('\n=== DISCREPANZE DETTAGLIATE ===');
    for (const m of mismatches) {
      console.log(`\nLP ${m.dbId} (OVH: ${m.ovhLpId}) - ${m.title}`);
      console.log(`  OVH Modules: [${m.ovhModules.join(', ')}]`);
      console.log(`  Replit Modules: [${m.replitModules.join(', ')}]`);
      if (m.inOvhNotReplit.length > 0) console.log(`  In OVH ma NON in Replit: [${m.inOvhNotReplit.join(', ')}]`);
      if (m.inReplitNotOvh.length > 0) console.log(`  In Replit ma NON in OVH: [${m.inReplitNotOvh.join(', ')}]`);
    }
  }
  
  if (missing.length > 0) {
    console.log('\n=== LP NON TROVATI IN OVH ===');
    for (const m of missing) {
      console.log(`  LP ${m.dbId}: ${m.title}`);
    }
  }
  
  // Sample of exact matches
  console.log('\n=== ESEMPIO CORRISPONDENZE ESATTE (primi 10) ===');
  matches.slice(0, 10).forEach(m => {
    console.log(`  LP ${m.dbId} (OVH: ${m.ovhLpId}) - ${m.title} - ${m.modules} moduli`);
  });
  
  await pool.end();
}

main().catch(console.error);
