const fs = require('fs');
const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL
});

async function resyncAllRelations() {
  const client = await pool.connect();
  
  try {
    console.log('=== RISINCRONIZZAZIONE COMPLETA RELAZIONI OVH ===\n');
    
    // Carica dati OVH
    const relations = JSON.parse(fs.readFileSync('/tmp/ovh_data/relations.json', 'utf8'));
    
    console.log('Dati OVH caricati:');
    console.log('- course_course_modules:', relations.course_course_modules.length);
    console.log('- course_module_lessons:', relations.course_module_lessons.length);
    console.log('- lesson_learning_objects:', relations.lesson_learning_objects.length);
    
    // Ottieni mapping dal DB (legacy_id -> id)
    const { rows: modules } = await client.query(`SELECT id, legacy_id FROM modules WHERE legacy_id IS NOT NULL`);
    const moduleMap = new Map(modules.map(m => [m.legacy_id, m.id]));
    console.log('\nModuli mappati:', moduleMap.size);
    
    const { rows: lessons } = await client.query(`SELECT id, legacy_id FROM lessons WHERE legacy_id IS NOT NULL`);
    const lessonMap = new Map(lessons.map(l => [l.legacy_id, l.id]));
    console.log('Lezioni mappate:', lessonMap.size);
    
    const { rows: los } = await client.query(`SELECT id, legacy_id FROM learning_objects WHERE legacy_id IS NOT NULL`);
    const loMap = new Map(los.map(l => [l.legacy_id, l.id]));
    console.log('Learning Objects mappati:', loMap.size);
    
    const { rows: projects } = await client.query(`SELECT id, sort_order FROM learning_projects`);
    const projectMap = new Map(projects.map(p => [p.sort_order, p.id])); // sort_order = legacy course id
    console.log('Learning Projects mappati:', projectMap.size);
    
    // ========== 1. RISINCRONIZZA course_modules ==========
    console.log('\n--- RISINCRONIZZAZIONE course_modules ---');
    
    // Pulisci e reinserisci
    await client.query('DELETE FROM course_modules');
    console.log('Tabella course_modules svuotata');
    
    let cmInserted = 0;
    let cmErrors = 0;
    
    for (const rel of relations.course_course_modules) {
      const legacyCourseId = parseInt(rel.course_id);
      const legacyModuleId = parseInt(rel.course_module_id);
      const position = parseInt(rel.position) || 1;
      const legacyId = parseInt(rel.id);
      
      const projectId = projectMap.get(legacyCourseId);
      const moduleId = moduleMap.get(legacyModuleId);
      
      if (!projectId) {
        // console.log(`  Skip: corso legacy ${legacyCourseId} non trovato`);
        cmErrors++;
        continue;
      }
      if (!moduleId) {
        // console.log(`  Skip: modulo legacy ${legacyModuleId} non trovato`);
        cmErrors++;
        continue;
      }
      
      await client.query(`
        INSERT INTO course_modules (legacy_id, learning_project_id, module_id, position, legacy_course_id)
        VALUES ($1, $2, $3, $4, $5)
        ON CONFLICT DO NOTHING
      `, [legacyId, projectId, moduleId, position, legacyCourseId]);
      cmInserted++;
    }
    console.log(`Inseriti: ${cmInserted}, Errori/Skip: ${cmErrors}`);
    
    // ========== 2. RISINCRONIZZA module_lessons ==========
    console.log('\n--- RISINCRONIZZAZIONE module_lessons ---');
    
    await client.query('DELETE FROM module_lessons');
    console.log('Tabella module_lessons svuotata');
    
    let mlInserted = 0;
    let mlErrors = 0;
    
    for (const rel of relations.course_module_lessons) {
      const legacyModuleId = parseInt(rel.course_module_id);
      const legacyLessonId = parseInt(rel.lesson_id);
      const position = parseInt(rel.position) || 1;
      const legacyId = parseInt(rel.id);
      
      const moduleId = moduleMap.get(legacyModuleId);
      const lessonId = lessonMap.get(legacyLessonId);
      
      if (!moduleId) {
        mlErrors++;
        continue;
      }
      if (!lessonId) {
        mlErrors++;
        continue;
      }
      
      await client.query(`
        INSERT INTO module_lessons (legacy_id, module_id, lesson_id, position)
        VALUES ($1, $2, $3, $4)
        ON CONFLICT DO NOTHING
      `, [legacyId, moduleId, lessonId, position]);
      mlInserted++;
    }
    console.log(`Inseriti: ${mlInserted}, Errori/Skip: ${mlErrors}`);
    
    // ========== 3. RISINCRONIZZA lesson_learning_objects ==========
    console.log('\n--- RISINCRONIZZAZIONE lesson_learning_objects ---');
    
    await client.query('DELETE FROM lesson_learning_objects');
    console.log('Tabella lesson_learning_objects svuotata');
    
    let lloInserted = 0;
    let lloErrors = 0;
    
    for (const rel of relations.lesson_learning_objects) {
      const legacyLessonId = parseInt(rel.lesson_id);
      const legacyLoId = parseInt(rel.learning_object_id);
      const position = parseInt(rel.position) || 1;
      const legacyId = parseInt(rel.id);
      
      const lessonId = lessonMap.get(legacyLessonId);
      const loId = loMap.get(legacyLoId);
      
      if (!lessonId) {
        lloErrors++;
        continue;
      }
      if (!loId) {
        lloErrors++;
        continue;
      }
      
      await client.query(`
        INSERT INTO lesson_learning_objects (id, lesson_id, learning_object_id, position)
        VALUES ($1, $2, $3, $4)
        ON CONFLICT DO NOTHING
      `, [legacyId, lessonId, loId, position]);
      lloInserted++;
    }
    console.log(`Inseriti: ${lloInserted}, Errori/Skip: ${lloErrors}`);
    
    // ========== VERIFICA FINALE ==========
    console.log('\n=== VERIFICA FINALE ===');
    
    const { rows: [cmCount] } = await client.query('SELECT COUNT(*) as c FROM course_modules');
    const { rows: [mlCount] } = await client.query('SELECT COUNT(*) as c FROM module_lessons');
    const { rows: [lloCount] } = await client.query('SELECT COUNT(*) as c FROM lesson_learning_objects');
    
    console.log('course_modules:', cmCount.c);
    console.log('module_lessons:', mlCount.c);
    console.log('lesson_learning_objects:', lloCount.c);
    
    // Test corso 263
    console.log('\n=== TEST CORSO 263 ===');
    const { rows: test263 } = await client.query(`
      SELECT 
        lp.id as project_id, lp.title as project_title,
        m.id as module_id, m.title as module_title, m.legacy_id as module_legacy,
        l.id as lesson_id, l.title as lesson_title, l.legacy_id as lesson_legacy,
        COUNT(llo.id) as num_los
      FROM learning_projects lp
      JOIN course_modules cm ON cm.learning_project_id = lp.id
      JOIN modules m ON m.id = cm.module_id
      LEFT JOIN module_lessons ml ON ml.module_id = m.id
      LEFT JOIN lessons l ON l.id = ml.lesson_id
      LEFT JOIN lesson_learning_objects llo ON llo.lesson_id = l.id
      WHERE lp.sort_order = 263
      GROUP BY lp.id, lp.title, m.id, m.title, m.legacy_id, l.id, l.title, l.legacy_id
    `);
    
    if (test263.length > 0) {
      console.log('Corso:', test263[0].project_title);
      for (const row of test263) {
        console.log(`  Modulo: ${row.module_title} (legacy: ${row.module_legacy})`);
        console.log(`    Lezione: ${row.lesson_title} (legacy: ${row.lesson_legacy}) - ${row.num_los} LO`);
      }
    } else {
      console.log('Corso 263 non trovato o senza moduli');
    }
    
    console.log('\n=== COMPLETATO ===');
    
  } finally {
    client.release();
    await pool.end();
  }
}

resyncAllRelations().catch(console.error);
