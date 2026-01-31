const fs = require('fs');
const { Client } = require('pg');

async function syncAllData() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  // Load all data files
  const courses = JSON.parse(fs.readFileSync('/tmp/ovh_data/courses.json', 'utf8'));
  const learningProjects = JSON.parse(fs.readFileSync('/tmp/ovh_data/learning_project.json', 'utf8'));
  const mapping = JSON.parse(fs.readFileSync('/tmp/course_lp_mapping.json', 'utf8'));
  const subcategories = JSON.parse(fs.readFileSync('/tmp/ovh_data/subcategories.json', 'utf8'));
  const categories = JSON.parse(fs.readFileSync('/tmp/ovh_data/categories.json', 'utf8'));
  
  console.log(`Loaded: ${courses.length} courses, ${learningProjects.length} learning projects`);
  console.log(`Subcategories: ${subcategories.length}, Categories: ${categories.length}`);
  
  // Create lookup maps
  const subcatMap = {};
  for (const sub of subcategories) {
    subcatMap[sub.id] = sub.name;
  }
  
  const catMap = {};
  for (const cat of categories) {
    catMap[cat.id] = cat.name;
  }
  
  // Create LP id to legacy id map (reverse of mapping)
  const lpToLegacy = {};
  for (const [courseId, lpId] of Object.entries(mapping)) {
    lpToLegacy[lpId] = courseId;
  }
  
  // Create course lookup by id
  const courseById = {};
  for (const course of courses) {
    courseById[course.id] = course;
  }
  
  // Sync learning_project data
  let updated = 0;
  for (const lp of learningProjects) {
    const legacyCourseId = lpToLegacy[lp.id];
    const course = legacyCourseId ? courseById[legacyCourseId] : null;
    
    const updates = [];
    const values = [];
    let paramIndex = 1;
    
    // From learning_project.json
    if (lp.description && lp.description.trim()) {
      updates.push(`description = $${paramIndex++}`);
      values.push(lp.description);
    }
    if (lp.reserved_to && lp.reserved_to.trim()) {
      updates.push(`reserved_to = $${paramIndex++}`);
      values.push(parseInt(lp.reserved_to));
    }
    
    // From course data
    if (course) {
      // Map subcategory_id to name
      if (course.subcategory_id && course.subcategory_id !== '0') {
        const subcatName = subcatMap[course.subcategory_id];
        if (subcatName) {
          updates.push(`subcategory = $${paramIndex++}`);
          values.push(subcatName);
        }
      }
      
      // Map type_id to course_type
      if (course.type_id === '1') {
        updates.push(`course_type = $${paramIndex++}`);
        values.push('Base');
      } else if (course.type_id === '2') {
        updates.push(`course_type = $${paramIndex++}`);
        values.push('Aggiornamento');
      }
      
      // Course program from description
      if (course.description && course.description.trim()) {
        updates.push(`course_program = $${paramIndex++}`);
        values.push(course.description);
      }
      
      // Objectives from targets if not already set
      if (course.targets && course.targets.trim()) {
        updates.push(`objectives = $${paramIndex++}`);
        values.push(course.targets);
      }
      
      // Customers -> destinatario
      if (course.customers && course.customers.trim()) {
        updates.push(`destinatario = $${paramIndex++}`);
        values.push(course.customers);
      }
    }
    
    if (updates.length > 0) {
      values.push(lp.id);
      const sql = `UPDATE learning_projects SET ${updates.join(', ')} WHERE id = $${paramIndex}`;
      try {
        await client.query(sql, values);
        updated++;
      } catch (err) {
        console.error(`Error updating LP ${lp.id}:`, err.message);
      }
    }
  }
  
  console.log(`Updated ${updated} learning projects with additional data`);
  await client.end();
}

syncAllData().catch(console.error);
