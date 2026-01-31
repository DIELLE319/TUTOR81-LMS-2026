const fs = require('fs');
const { Pool } = require('pg');

const pool = new Pool({
  connectionString: process.env.DATABASE_URL
});

async function fixMappings() {
  const client = await pool.connect();
  
  try {
    // Carica dati OVH
    const relations = JSON.parse(fs.readFileSync('/tmp/ovh_data/relations.json', 'utf8'));
    const ovhCourses = JSON.parse(fs.readFileSync('/tmp/ovh_data/courses.json', 'utf8'));
    const ovhModules = JSON.parse(fs.readFileSync('/tmp/ovh_data/course_modules.json', 'utf8'));
    const ovhLessons = JSON.parse(fs.readFileSync('/tmp/ovh_data/lessons.json', 'utf8'));
    
    console.log('=== VERIFICA E CORREZIONE MAPPING CORSO -> MODULO -> LEZIONE ===\n');
    
    // Crea lookup per dati OVH
    const ovhCourseMap = new Map(ovhCourses.map(c => [parseInt(c.id), c]));
    const ovhModuleMap = new Map(ovhModules.map(m => [parseInt(m.id), m]));
    const ovhLessonMap = new Map(ovhLessons.map(l => [parseInt(l.id), l]));
    
    // Ottieni tutti i learning_projects dal DB
    const { rows: projects } = await client.query(`
      SELECT id, title, sort_order FROM learning_projects ORDER BY sort_order
    `);
    
    // Ottieni mapping moduli (legacy_id -> id)
    const { rows: modules } = await client.query(`
      SELECT id, legacy_id, title FROM modules
    `);
    const moduleByLegacy = new Map(modules.map(m => [m.legacy_id, m]));
    
    // Ottieni mapping lezioni (legacy_id -> id)
    const { rows: lessons } = await client.query(`
      SELECT id, legacy_id, title FROM lessons
    `);
    const lessonByLegacy = new Map(lessons.map(l => [l.legacy_id, l]));
    
    let errors = 0;
    let fixed = 0;
    
    for (const project of projects) {
      const legacyCourseId = project.sort_order; // sort_order = legacy course id
      
      // Trova relazioni OVH per questo corso
      const ovhCourseModules = relations.course_course_modules.filter(
        r => parseInt(r.course_id) === legacyCourseId
      );
      
      if (ovhCourseModules.length === 0) {
        continue; // Nessun modulo nel sistema legacy
      }
      
      // Verifica ogni modulo
      for (const ovhCM of ovhCourseModules) {
        const legacyModuleId = parseInt(ovhCM.course_module_id);
        const ovhModule = ovhModuleMap.get(legacyModuleId);
        
        // Trova lezioni per questo modulo in OVH
        const ovhModuleLessons = relations.course_module_lessons.filter(
          r => parseInt(r.course_module_id) === legacyModuleId
        );
        
        // Trova il modulo corrispondente nel nostro DB
        const dbModule = moduleByLegacy.get(legacyModuleId);
        
        if (!dbModule) {
          console.log(`ERRORE: Modulo legacy ${legacyModuleId} non trovato nel DB per corso ${project.id} (${project.title})`);
          errors++;
          continue;
        }
        
        // Verifica course_modules nel nostro DB
        const { rows: dbCM } = await client.query(`
          SELECT * FROM course_modules WHERE legacy_course_id = $1
        `, [legacyCourseId]);
        
        if (dbCM.length === 0) {
          console.log(`MANCANTE: course_modules per corso legacy ${legacyCourseId} (${project.title})`);
          errors++;
          continue;
        }
        
        // Verifica che il modulo sia corretto
        if (dbCM[0].module_id !== dbModule.id) {
          console.log(`CORREZIONE: Corso ${project.id} "${project.title.substring(0, 40)}..."`);
          console.log(`  - Modulo attuale: ${dbCM[0].module_id}`);
          console.log(`  - Modulo corretto: ${dbModule.id} (legacy: ${legacyModuleId}, "${ovhModule?.title || 'N/A'}")`);
          
          await client.query(`
            UPDATE course_modules SET module_id = $1 WHERE id = $2
          `, [dbModule.id, dbCM[0].id]);
          fixed++;
        }
        
        // Verifica module_lessons
        for (const ovhML of ovhModuleLessons) {
          const legacyLessonId = parseInt(ovhML.lesson_id);
          const dbLesson = lessonByLegacy.get(legacyLessonId);
          const ovhLesson = ovhLessonMap.get(legacyLessonId);
          
          if (!dbLesson) {
            console.log(`  ERRORE: Lezione legacy ${legacyLessonId} non trovata nel DB`);
            errors++;
            continue;
          }
          
          // Verifica che la lezione sia collegata al modulo
          const { rows: dbML } = await client.query(`
            SELECT * FROM module_lessons WHERE module_id = $1 AND lesson_id = $2
          `, [dbModule.id, dbLesson.id]);
          
          if (dbML.length === 0) {
            console.log(`  MANCANTE: Lezione ${dbLesson.id} (legacy: ${legacyLessonId}, "${ovhLesson?.title || 'N/A'}") non collegata al modulo ${dbModule.id}`);
            
            // Aggiungi il collegamento
            await client.query(`
              INSERT INTO module_lessons (module_id, lesson_id, position, legacy_id)
              VALUES ($1, $2, $3, $4)
              ON CONFLICT DO NOTHING
            `, [dbModule.id, dbLesson.id, parseInt(ovhML.position), parseInt(ovhML.id)]);
            fixed++;
          }
        }
      }
    }
    
    console.log(`\n=== RIEPILOGO ===`);
    console.log(`Errori trovati: ${errors}`);
    console.log(`Correzioni applicate: ${fixed}`);
    
  } finally {
    client.release();
    await pool.end();
  }
}

fixMappings().catch(console.error);
