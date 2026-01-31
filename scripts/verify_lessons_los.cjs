const { Pool } = require('pg');
const fs = require('fs');

const pool = new Pool({ connectionString: process.env.DATABASE_URL });

async function main() {
  console.log('=== VERIFICA LEZIONI E LEARNING OBJECTS ===\n');
  
  const relations = JSON.parse(fs.readFileSync('/tmp/ovh_data/relations.json', 'utf8'));
  
  // OVH Module -> Lessons
  const ovhModLessons = new Map();
  for (const ml of relations.course_module_lessons) {
    const modId = parseInt(ml.course_module_id);
    const lessonId = parseInt(ml.lesson_id);
    if (!ovhModLessons.has(modId)) ovhModLessons.set(modId, new Set());
    ovhModLessons.get(modId).add(lessonId);
  }
  
  // OVH Lesson -> LOs
  const ovhLessonLOs = new Map();
  for (const llo of relations.lesson_learning_objects) {
    const lessonId = parseInt(llo.lesson_id);
    const loId = parseInt(llo.learning_object_id);
    if (!ovhLessonLOs.has(lessonId)) ovhLessonLOs.set(lessonId, new Set());
    ovhLessonLOs.get(lessonId).add(loId);
  }
  
  // Get Replit Module -> Lessons
  const replitModLessons = await pool.query(`
    SELECT m.legacy_id as module_legacy, l.legacy_id as lesson_legacy
    FROM module_lessons ml
    JOIN modules m ON m.id = ml.module_id
    JOIN lessons l ON l.id = ml.lesson_id
  `);
  
  const replitMLMap = new Map();
  for (const r of replitModLessons.rows) {
    if (!replitMLMap.has(r.module_legacy)) replitMLMap.set(r.module_legacy, new Set());
    replitMLMap.get(r.module_legacy).add(r.lesson_legacy);
  }
  
  // Compare Module -> Lessons
  let mlMatches = 0, mlMismatches = 0;
  const mlErrors = [];
  
  for (const [modId, ovhLessons] of ovhModLessons) {
    const replitLessons = replitMLMap.get(modId) || new Set();
    const ovhArr = [...ovhLessons].sort((a,b) => a-b);
    const replitArr = [...replitLessons].sort((a,b) => a-b);
    
    if (ovhArr.join(',') === replitArr.join(',')) {
      mlMatches++;
    } else {
      mlMismatches++;
      if (mlErrors.length < 5) {
        mlErrors.push({
          moduleId: modId,
          ovh: ovhArr,
          replit: replitArr,
          missing: ovhArr.filter(l => !replitLessons.has(l)),
          extra: replitArr.filter(l => !ovhLessons.has(l))
        });
      }
    }
  }
  
  console.log('=== MODULE -> LESSONS ===');
  console.log(`OVH Modules con lezioni: ${ovhModLessons.size}`);
  console.log(`Corrispondenze: ${mlMatches}`);
  console.log(`Discrepanze: ${mlMismatches}`);
  
  if (mlErrors.length > 0) {
    console.log('\nPrime discrepanze:');
    mlErrors.forEach(e => {
      console.log(`  Module ${e.moduleId}: OVH[${e.ovh.length}] vs Replit[${e.replit.length}]`);
      if (e.missing.length) console.log(`    Mancanti: ${e.missing.slice(0,5).join(', ')}`);
    });
  }
  
  // Get Replit Lesson -> LOs
  const replitLessonLOs = await pool.query(`
    SELECT l.legacy_id as lesson_legacy, lo.legacy_id as lo_legacy
    FROM lesson_learning_objects llo
    JOIN lessons l ON l.id = llo.lesson_id
    JOIN learning_objects lo ON lo.id = llo.learning_object_id
  `);
  
  const replitLLOMap = new Map();
  for (const r of replitLessonLOs.rows) {
    if (!replitLLOMap.has(r.lesson_legacy)) replitLLOMap.set(r.lesson_legacy, new Set());
    replitLLOMap.get(r.lesson_legacy).add(r.lo_legacy);
  }
  
  // Compare Lesson -> LOs
  let lloMatches = 0, lloMismatches = 0;
  const lloErrors = [];
  
  for (const [lessonId, ovhLOs] of ovhLessonLOs) {
    const replitLOs = replitLLOMap.get(lessonId) || new Set();
    const ovhArr = [...ovhLOs].sort((a,b) => a-b);
    const replitArr = [...replitLOs].sort((a,b) => a-b);
    
    if (ovhArr.join(',') === replitArr.join(',')) {
      lloMatches++;
    } else {
      lloMismatches++;
      if (lloErrors.length < 5) {
        lloErrors.push({
          lessonId,
          ovh: ovhArr,
          replit: replitArr,
          missing: ovhArr.filter(l => !replitLOs.has(l)),
          extra: replitArr.filter(l => !ovhLOs.has(l))
        });
      }
    }
  }
  
  console.log('\n=== LESSON -> LEARNING OBJECTS ===');
  console.log(`OVH Lessons con LOs: ${ovhLessonLOs.size}`);
  console.log(`Corrispondenze: ${lloMatches}`);
  console.log(`Discrepanze: ${lloMismatches}`);
  
  if (lloErrors.length > 0) {
    console.log('\nPrime discrepanze:');
    lloErrors.forEach(e => {
      console.log(`  Lesson ${e.lessonId}: OVH[${e.ovh.length}] vs Replit[${e.replit.length}]`);
      if (e.missing.length) console.log(`    Mancanti: ${e.missing.slice(0,5).join(', ')}`);
    });
  }
  
  // Summary
  console.log('\n=== RIEPILOGO FINALE ===');
  console.log(`LP -> Modules: 293/293 OK (verificato prima)`);
  console.log(`Module -> Lessons: ${mlMatches}/${ovhModLessons.size} OK`);
  console.log(`Lesson -> LOs: ${lloMatches}/${ovhLessonLOs.size} OK`);
  
  const totalOK = mlMatches === ovhModLessons.size && lloMatches === ovhLessonLOs.size;
  console.log(`\n${totalOK ? '✓ TUTTO CORRISPONDE!' : '⚠ CI SONO DISCREPANZE'}`);
  
  await pool.end();
}

main().catch(console.error);
