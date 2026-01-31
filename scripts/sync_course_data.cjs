const fs = require('fs');
const { Client } = require('pg');

async function syncCourseData() {
  const client = new Client({ connectionString: process.env.DATABASE_URL });
  await client.connect();
  
  // Load data
  const courses = JSON.parse(fs.readFileSync('/tmp/ovh_data/courses.json', 'utf8'));
  const mapping = JSON.parse(fs.readFileSync('/tmp/course_lp_mapping.json', 'utf8'));
  
  console.log(`Loaded ${courses.length} courses and ${Object.keys(mapping).length} mappings`);
  
  let updated = 0;
  for (const course of courses) {
    const lpId = mapping[course.id];
    if (!lpId) continue;
    
    // Parse total_elearning (could be "4 ore" or number)
    let totalElearning = null;
    if (course.total_elearning) {
      const match = course.total_elearning.match(/(\d+)/);
      if (match) totalElearning = parseInt(match[1]);
    }
    
    // Parse max_execution_time
    let maxExecutionTime = null;
    if (course.max_execution_time) {
      maxExecutionTime = parseInt(course.max_execution_time);
      // If it's in minutes (>100), convert to days
      if (maxExecutionTime > 100) maxExecutionTime = Math.ceil(maxExecutionTime / 1440);
    }
    
    // Map course_validity
    let courseValidity = null;
    if (course.course_validity) {
      if (course.course_validity.includes('quinquenn')) courseValidity = 'quinquennale';
      else if (course.course_validity.includes('trienn')) courseValidity = 'triennale';
      else if (course.course_validity.includes('annuale')) courseValidity = 'annuale';
      else courseValidity = course.course_validity;
    }
    
    // Build update
    const updates = [];
    const values = [];
    let paramIndex = 1;
    
    if (course.external_integration) {
      updates.push(`external_integration = $${paramIndex++}`);
      values.push(course.external_integration);
    }
    if (courseValidity) {
      updates.push(`course_validity = $${paramIndex++}`);
      values.push(courseValidity);
    }
    if (totalElearning !== null) {
      updates.push(`total_elearning = $${paramIndex++}`);
      values.push(totalElearning);
    }
    if (maxExecutionTime !== null) {
      updates.push(`max_execution_time = $${paramIndex++}`);
      values.push(maxExecutionTime);
    }
    if (course.percentage_correct_answer_to_pass) {
      updates.push(`percentage_to_pass = $${paramIndex++}`);
      values.push(parseInt(course.percentage_correct_answer_to_pass));
    }
    if (course.law_reference) {
      updates.push(`law_reference = $${paramIndex++}`);
      values.push(course.law_reference);
    }
    if (course.producers) {
      updates.push(`producers = $${paramIndex++}`);
      values.push(course.producers);
    }
    if (course.course_professors) {
      updates.push(`professors = $${paramIndex++}`);
      values.push(course.course_professors);
    }
    if (course.didactics) {
      updates.push(`didactics = $${paramIndex++}`);
      values.push(course.didactics);
    }
    if (course.targets) {
      updates.push(`target_audience = $${paramIndex++}`);
      values.push(course.targets);
    }
    if (course.requirements) {
      updates.push(`prerequisites = $${paramIndex++}`);
      values.push(course.requirements);
    }
    if (course.customers) {
      updates.push(`destinatario = $${paramIndex++}`);
      values.push(course.customers);
    }
    
    if (updates.length > 0) {
      values.push(lpId);
      const sql = `UPDATE learning_projects SET ${updates.join(', ')} WHERE id = $${paramIndex}`;
      try {
        await client.query(sql, values);
        updated++;
      } catch (err) {
        console.error(`Error updating LP ${lpId}:`, err.message);
      }
    }
  }
  
  console.log(`Updated ${updated} learning projects`);
  await client.end();
}

syncCourseData().catch(console.error);
