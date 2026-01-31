const fs = require('fs');
const { Pool } = require('pg');

const pool = new Pool({ connectionString: process.env.DATABASE_URL });

async function importData() {
  const client = await pool.connect();
  
  try {
    // Load data files
    const courseModulesData = JSON.parse(fs.readFileSync('/tmp/ovh_data/course_modules.json', 'utf8'));
    const lessonsData = JSON.parse(fs.readFileSync('/tmp/ovh_data/lessons.json', 'utf8'));
    const relationsData = JSON.parse(fs.readFileSync('/tmp/ovh_data/relations.json', 'utf8'));
    
    console.log('Data loaded:');
    console.log('- Modules:', courseModulesData.length);
    console.log('- Lessons:', lessonsData.length);
    console.log('- Course-Module relations:', relationsData.course_course_modules?.length);
    console.log('- Module-Lesson relations:', relationsData.course_module_lessons?.length);
    console.log('- Lesson-LO relations:', relationsData.lesson_learning_objects?.length);
    
    await client.query('BEGIN');
    
    // 1. Import modules
    console.log('\n1. Importing modules...');
    let modulesInserted = 0;
    for (const m of courseModulesData) {
      await client.query(`
        INSERT INTO modules (legacy_id, title, description, duration, max_execution_time)
        VALUES ($1, $2, $3, $4, $5)
        ON CONFLICT DO NOTHING
      `, [
        parseInt(m.id),
        m.title || 'Modulo senza titolo',
        m.description || '',
        parseInt(m.duration) || 0,
        parseInt(m.max_execution_time) || 0
      ]);
      modulesInserted++;
    }
    console.log(`   Inserted ${modulesInserted} modules`);
    
    // 2. Import lessons
    console.log('\n2. Importing lessons...');
    let lessonsInserted = 0;
    for (const l of lessonsData) {
      await client.query(`
        INSERT INTO lessons (legacy_id, title, description, duration, percentage_to_pass, code, owner_user_id, suspended, closed)
        VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
        ON CONFLICT DO NOTHING
      `, [
        parseInt(l.id),
        l.title || 'Lezione senza titolo',
        l.description || '',
        parseInt(l.duration) || 0,
        parseInt(l.percentage_correct_answer_to_pass) || 60,
        l.code || null,
        parseInt(l.owner_user_id) || null,
        l.suspended === '1',
        l.closed === '1'
      ]);
      lessonsInserted++;
    }
    console.log(`   Inserted ${lessonsInserted} lessons`);
    
    // Create lookup maps
    const moduleMap = new Map();
    const moduleRows = await client.query('SELECT id, legacy_id FROM modules WHERE legacy_id IS NOT NULL');
    moduleRows.rows.forEach(r => moduleMap.set(r.legacy_id, r.id));
    
    const lessonMap = new Map();
    const lessonRows = await client.query('SELECT id, legacy_id FROM lessons WHERE legacy_id IS NOT NULL');
    lessonRows.rows.forEach(r => lessonMap.set(r.legacy_id, r.id));
    
    const loMap = new Map();
    const loRows = await client.query('SELECT id, legacy_id FROM learning_objects WHERE legacy_id IS NOT NULL');
    loRows.rows.forEach(r => loMap.set(r.legacy_id, r.id));
    
    // Get learning_projects by legacy course_id (courses were merged into learning_projects)
    const lpMap = new Map();
    const lpRows = await client.query('SELECT id FROM learning_projects');
    lpRows.rows.forEach((r, i) => lpMap.set(i + 1, r.id)); // Approximate mapping
    
    // 3. Import course-module relations (with legacy_course_id, FK will be resolved later)
    console.log('\n3. Importing course-module relations...');
    let cmInserted = 0;
    for (const rel of (relationsData.course_course_modules || [])) {
      const moduleId = moduleMap.get(parseInt(rel.course_module_id));
      const legacyCourseId = parseInt(rel.course_id);
      
      if (moduleId && legacyCourseId) {
        await client.query(`
          INSERT INTO course_modules (legacy_id, legacy_course_id, module_id, position)
          VALUES ($1, $2, $3, $4)
          ON CONFLICT DO NOTHING
        `, [
          parseInt(rel.id),
          legacyCourseId,
          moduleId,
          parseInt(rel.position) || 0
        ]);
        cmInserted++;
      }
    }
    console.log(`   Inserted ${cmInserted} course-module relations`);
    
    // 4. Import module-lesson relations
    console.log('\n4. Importing module-lesson relations...');
    let mlInserted = 0;
    for (const rel of (relationsData.course_module_lessons || [])) {
      const moduleId = moduleMap.get(parseInt(rel.course_module_id));
      const lessonId = lessonMap.get(parseInt(rel.lesson_id));
      
      if (moduleId && lessonId) {
        await client.query(`
          INSERT INTO module_lessons (legacy_id, module_id, lesson_id, position)
          VALUES ($1, $2, $3, $4)
          ON CONFLICT DO NOTHING
        `, [
          parseInt(rel.id),
          moduleId,
          lessonId,
          parseInt(rel.position) || 0
        ]);
        mlInserted++;
      }
    }
    console.log(`   Inserted ${mlInserted} module-lesson relations`);
    
    // 5. Import lesson-learning_object relations
    console.log('\n5. Importing lesson-LO relations...');
    let lloInserted = 0;
    for (const rel of (relationsData.lesson_learning_objects || [])) {
      const lessonId = lessonMap.get(parseInt(rel.lesson_id));
      const loId = loMap.get(parseInt(rel.learning_object_id));
      
      if (lessonId && loId) {
        await client.query(`
          INSERT INTO lesson_learning_objects (legacy_id, lesson_id, learning_object_id, position)
          VALUES ($1, $2, $3, $4)
          ON CONFLICT DO NOTHING
        `, [
          parseInt(rel.id),
          lessonId,
          loId,
          parseInt(rel.position) || 0
        ]);
        lloInserted++;
      }
    }
    console.log(`   Inserted ${lloInserted} lesson-LO relations`);
    
    await client.query('COMMIT');
    console.log('\n✅ Import completed successfully!');
    
    // Print summary
    const summary = await client.query(`
      SELECT 
        (SELECT COUNT(*) FROM modules) as modules,
        (SELECT COUNT(*) FROM lessons) as lessons,
        (SELECT COUNT(*) FROM course_modules) as course_modules,
        (SELECT COUNT(*) FROM module_lessons) as module_lessons,
        (SELECT COUNT(*) FROM lesson_learning_objects) as lesson_los
    `);
    console.log('\nDatabase totals:', summary.rows[0]);
    
  } catch (err) {
    await client.query('ROLLBACK');
    console.error('Import failed:', err);
    throw err;
  } finally {
    client.release();
    await pool.end();
  }
}

importData().catch(console.error);
