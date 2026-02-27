const express = require('express');
const path = require('path');
const { Pool } = require('pg');

const app = express();
const PORT = process.env.PORT || 5000;

app.use(express.json());
app.use(express.static(path.join(__dirname, 'public')));

// ========== DATABASE ==========
const pool = new Pool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: parseInt(process.env.DB_PORT || '5432'),
  user: process.env.DB_USER || 'tutor81',
  password: process.env.DB_PASS || 'tutor81pass',
  database: process.env.DB_NAME || 'tutor81',
  max: 10,
  connectionTimeoutMillis: 5000,
});

// ========== CODICE FISCALE ==========
const CF_MONTH = { A:1,B:2,C:3,D:4,E:5,H:6,L:7,M:8,P:9,R:10,S:11,T:12 };
const MESI = ['','Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];

function parseCF(cf) {
  if (!cf || cf.length < 16) return null;
  const u = cf.toUpperCase();
  const year2 = parseInt(u.substring(6, 8), 10);
  const monthLetter = u.charAt(8);
  const dayRaw = parseInt(u.substring(9, 11), 10);
  const month = CF_MONTH[monthLetter];
  if (!month) return null;
  const isFemale = dayRaw > 40;
  return {
    year: year2 > 30 ? 1900 + year2 : 2000 + year2,
    month,
    day: isFemale ? dayRaw - 40 : dayRaw,
    gender: isFemale ? 'F' : 'M',
  };
}

function randomCFQuestions(cf) {
  const all = [
    { id: 'birth_day', label: 'In che giorno sei nato/a?', options: Array.from({length:31},(_,i)=>({value:String(i+1),label:String(i+1)})) },
    { id: 'birth_month', label: 'In che mese sei nato/a?', options: MESI.slice(1).map((m,i)=>({value:String(i+1),label:m})) },
    { id: 'birth_year', label: 'In che anno sei nato/a?', options: Array.from({length:71},(_,i)=>({value:String(2010-i),label:String(2010-i)})) },
    { id: 'gender', label: 'Qual è il tuo sesso?', options: [{value:'M',label:'Maschio'},{value:'F',label:'Femmina'}] },
  ];
  for (let i = all.length - 1; i > 0; i--) { const j = Math.floor(Math.random()*(i+1)); [all[i],all[j]] = [all[j],all[i]]; }
  return all.slice(0, 2);
}

function cfAnswer(cf, qid) {
  switch(qid) {
    case 'birth_day': return String(cf.day);
    case 'birth_month': return String(cf.month);
    case 'birth_year': return String(cf.year);
    case 'gender': return cf.gender;
  }
}

// ========== FILE SERVING (slides, docs, web from CMS uploads) ==========
const UPLOADS_DIR = process.env.UPLOADS_DIR || '/var/www/tutor81-cms/uploads';
app.get('/files/:filename', (req, res) => {
  const filePath = path.join(UPLOADS_DIR, req.params.filename);
  res.sendFile(filePath, (err) => { if(err){ var bare=req.params.filename.replace(/^[0-9]+_/,''); if(bare!==req.params.filename){ res.sendFile(path.join(UPLOADS_DIR,bare),(e2)=>{if(e2)res.status(404).send('File non trovato');}); }else{ res.status(404).send('File non trovato'); }} });
});
app.use('/web', express.static(path.join(UPLOADS_DIR, 'web')));

// ========== ROUTES: PAGES ==========
app.get('/', (_req, res) => res.redirect('/login'));
app.get('/login', (_req, res) => res.sendFile(path.join(__dirname, 'public', 'login.html')));
app.get('/player/course/:id', (_req, res) => res.sendFile(path.join(__dirname, 'public', 'player.html')));

// ========== API: CHECK USERNAME (nome.cognome → 1 domanda CF) ==========
app.post('/api/player/check-username', async (req, res) => {
  try {
    const { username } = req.body;
    if (!username) return res.status(400).json({ error: 'Username richiesto' });
    const parts = username.toLowerCase().trim().split('.');
    if (parts.length < 2) return res.status(400).json({ error: 'Inserisci nome.cognome' });
    const firstName = parts[0];
    const lastName = parts.slice(1).join('.');

    const { rows } = await pool.query('SELECT id, fiscal_code FROM students WHERE LOWER(first_name) = $1 AND LOWER(last_name) = $2 LIMIT 1', [firstName, lastName]);
    if (rows.length === 0) return res.status(404).json({ error: 'Utente non trovato. Verifica il nome utente.' });

    const cf = parseCF(rows[0].fiscal_code || '');
    if (!cf) return res.status(400).json({ error: 'Dati utente incompleti. Contatta l\'assistenza.' });

    const questions = randomCFQuestions(cf);
    res.json({ success: true, question: questions[0] });
  } catch (err) {
    console.error('Check username error:', err.message);
    res.status(500).json({ error: 'Errore durante la verifica' });
  }
});

// ========== API: VERIFY IDENTITY (username + risposta → login) ==========
app.post('/api/player/verify-identity', async (req, res) => {
  try {
    const { username, questionId, answer } = req.body;
    if (!username || !questionId || !answer) return res.status(400).json({ error: 'Tutti i campi sono obbligatori' });

    const parts = username.toLowerCase().trim().split('.');
    if (parts.length < 2) return res.status(400).json({ error: 'Username non valido' });
    const firstName = parts[0];
    const lastName = parts.slice(1).join('.');

    const { rows: stuRows } = await pool.query('SELECT * FROM students WHERE LOWER(first_name) = $1 AND LOWER(last_name) = $2 LIMIT 1', [firstName, lastName]);
    if (stuRows.length === 0) return res.status(401).json({ error: 'Utente non trovato.' });
    const stu = stuRows[0];

    const cf = parseCF(stu.fiscal_code || '');
    if (!cf) return res.status(400).json({ error: 'Dati utente incompleti.' });

    const expected = cfAnswer(cf, questionId);
    if (String(answer) !== expected) return res.status(401).json({ error: 'Risposta errata. Riprova.' });

    const { rows: enrRows } = await pool.query(
      `SELECT e.id, e.course_id, e.license_code, e.progress, e.status, e.start_date, e.end_date, c.title as course_title FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.student_id = $1 AND e.status = 'active' LIMIT 1`, [stu.id]
    );
    if (enrRows.length === 0) return res.status(404).json({ error: 'Nessun corso attivo trovato.' });
    const enr = enrRows[0];

    await pool.query('UPDATE enrollments SET last_access_at = NOW() WHERE id = $1', [enr.id]);

    const { rows: tutRows } = await pool.query(
      `SELECT t.business_name, t.address, t.city, t.cap, t.province, t.contact_person, t.phone, t.email FROM tutors t WHERE t.id = (SELECT tutor_id FROM enrollments WHERE id = $1)`, [enr.id]
    );
    const tut = tutRows[0] || {};

    const { rows: compRows } = await pool.query('SELECT business_name FROM companies WHERE id = $1', [stu.company_id]);
    const comp = compRows[0] || {};

    res.json({
      success: true,
      user: { id: stu.id, firstName: stu.first_name, lastName: stu.last_name, fiscalCode: stu.fiscal_code, company: comp.business_name || '' },
      enrollment: { id: enr.id, courseId: enr.course_id, courseName: enr.course_title, licenseCode: enr.license_code, progress: enr.progress || 0, startDate: enr.start_date, endDate: enr.end_date },
      tutor: { name: tut.business_name||'', contact: tut.contact_person||'', address: tut.address||'', city: tut.city||'', cap: tut.cap||'', province: tut.province||'', phone: tut.phone||'', email: tut.email||'' },
    });
  } catch (err) {
    console.error('Verify identity error:', err.message);
    res.status(500).json({ error: 'Errore durante la verifica' });
  }
});

// ========== API: LOGIN SEMPLICE (licenza + CF) ==========
app.post('/api/player/login-simple', async (req, res) => {
  try {
    const { licenseCode, fiscalCode } = req.body;
    if (!licenseCode || !fiscalCode) return res.status(400).json({ error: 'Codice licenza e codice fiscale obbligatori' });

    const { rows: enrRows } = await pool.query(
      `SELECT e.id, e.student_id, e.course_id, e.license_code, e.progress, e.status, e.start_date, e.end_date, c.title as course_title FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE UPPER(e.license_code) = $1 LIMIT 1`,
      [licenseCode.toUpperCase()]
    );
    if (enrRows.length === 0) return res.status(404).json({ error: 'Codice licenza non trovato.' });
    const enr = enrRows[0];

    const { rows: stuRows } = await pool.query('SELECT id, first_name, last_name, fiscal_code, company_id FROM students WHERE id = $1', [enr.student_id]);
    if (stuRows.length === 0) return res.status(404).json({ error: 'Studente non trovato.' });
    const stu = stuRows[0];

    if (!stu.fiscal_code || stu.fiscal_code.toUpperCase() !== fiscalCode.toUpperCase()) {
      return res.status(401).json({ error: 'Codice fiscale non corrispondente.' });
    }

    await pool.query('UPDATE enrollments SET last_access_at = NOW() WHERE id = $1', [enr.id]);

    const { rows: tutRows } = await pool.query(
      `SELECT t.business_name, t.address, t.city, t.cap, t.province, t.contact_person, t.phone, t.email FROM tutors t WHERE t.id = (SELECT tutor_id FROM enrollments WHERE id = $1)`, [enr.id]
    );
    const tut = tutRows[0] || {};

    const { rows: compRows } = await pool.query(
      `SELECT business_name FROM companies WHERE id = $1`, [stu.company_id]
    );
    const comp = compRows[0] || {};

    res.json({
      success: true,
      user: { id: stu.id, firstName: stu.first_name, lastName: stu.last_name, fiscalCode: stu.fiscal_code, company: comp.business_name || '' },
      enrollment: { id: enr.id, courseId: enr.course_id, courseName: enr.course_title, licenseCode: enr.license_code, progress: enr.progress || 0, startDate: enr.start_date, endDate: enr.end_date },
      tutor: { name: tut.business_name||'', contact: tut.contact_person||'', address: tut.address||'', city: tut.city||'', cap: tut.cap||'', province: tut.province||'', phone: tut.phone||'', email: tut.email||'' },
    });
  } catch (err) {
    console.error('Login error:', err.message);
    res.status(500).json({ error: 'Errore durante l\'accesso' });
  }
});

// ========== API: STRUTTURA CORSO ==========
app.get('/api/player/course/:id/structure', async (req, res) => {
  try {
    const courseId = parseInt(req.params.id);
    if (isNaN(courseId)) return res.status(400).json({ error: 'Invalid course ID' });

    const { rows: courseRows } = await pool.query('SELECT id, title, description FROM courses WHERE id = $1', [courseId]);
    if (courseRows.length === 0) return res.status(404).json({ error: 'Course not found' });

    const { rows: modRows } = await pool.query(
      `SELECT cm.module_id, m.title, m.description FROM course_modules cm JOIN modules m ON m.id = cm.module_id WHERE cm.course_id = $1 ORDER BY cm.position, cm.id`, [courseId]
    );

    const modules = [];
    for (const mod of modRows) {
      // Legge LO direttamente dal modulo (corso → modulo → LO)
      const { rows: loRows } = await pool.query(
        `SELECT mlo.learning_object_id, lo.title, lo.object_type, lo.jwplayer_code, lo.video_filename, lo.slide_filename, lo.document_filename, lo.web_filename, lo.duration
         FROM module_learning_objects mlo
         JOIN learning_objects lo ON lo.id = mlo.learning_object_id
         WHERE mlo.module_id = $1 ORDER BY mlo.position, mlo.id`, [mod.module_id]
      );
      const los = [];
      for (const lo of loRows) {
        const questions = [];
        const { rows: slideQ } = await pool.query('SELECT id, question_text, time_seconds, sort_order FROM lesson_slide_questions WHERE learning_object_id = $1 ORDER BY sort_order, id', [lo.learning_object_id]);
        for (const q of slideQ) {
          const { rows: ans } = await pool.query('SELECT id, answer_text, is_correct, sort_order FROM lesson_slide_question_answers WHERE slide_question_id = $1 ORDER BY sort_order, id', [q.id]);
          questions.push({ id: q.id, question_text: q.question_text, time_seconds: q.time_seconds || 0, answers: ans });
        }
        const { rows: docQ } = await pool.query('SELECT id, question_text, time_ms, sort_order FROM doc_questions WHERE learning_object_id = $1 ORDER BY COALESCE(time_ms, 999999999), sort_order, id', [lo.learning_object_id]);
        for (const q of docQ) {
          const { rows: ans } = await pool.query('SELECT id, answer_text, is_correct, sort_order FROM doc_question_answers WHERE doc_question_id = $1 ORDER BY sort_order, id', [q.id]);
          questions.push({ id: q.id, question_text: q.question_text, time_seconds: q.time_ms != null ? Math.floor(Number(q.time_ms) / 1000) : 0, answers: ans });
        }
        los.push({ id: lo.learning_object_id, title: lo.title, objectType: lo.object_type || 'video', jwplayerCode: lo.jwplayer_code, videoFilename: lo.video_filename, slideFilename: lo.slide_filename, documentFilename: lo.document_filename, webFilename: lo.web_filename, duration: lo.duration, questions });
      }
      // Struttura piatta: 1 modulo = 1 lezione virtuale con tutti gli LO
      modules.push({ id: mod.module_id, title: mod.title, lessons: [{ id: mod.module_id, title: mod.title, learningObjects: los }] });
    }

    console.log(`[Player] Course ${courseId}: ${modules.length} modules, ${modules.reduce((s,m) => s + m.lessons[0].learningObjects.length, 0)} LOs`);
    res.json({ course: courseRows[0], modules });
  } catch (err) {
    console.error('Structure error:', err.message);
    res.status(500).json({ error: 'Failed to fetch course structure' });
  }
});

// ========== API: SALVA PROGRESSO LO ==========
app.post('/api/player/save-progress', async (req, res) => {
  try {
    const { enrollmentId, learningObjectId, watchedSeconds, completed, action, videoPosition } = req.body;
    if (!enrollmentId || !learningObjectId) return res.status(400).json({ error: 'Missing fields' });

    if (action === 'start') {
      await pool.query('INSERT INTO enrollment_progress (enrollment_id, learning_object_id, started_at, video_position_seconds, watched_seconds, completed) VALUES ($1,$2,NOW(),$3,0,false)', [enrollmentId, learningObjectId, videoPosition || 0]);
    } else if (action === 'leave') {
      await pool.query('UPDATE enrollment_progress SET left_at=NOW(), video_position_seconds=$1, watched_seconds=$4 WHERE id = (SELECT id FROM enrollment_progress WHERE enrollment_id=$2 AND learning_object_id=$3 ORDER BY id DESC LIMIT 1)', [videoPosition || 0, enrollmentId, learningObjectId, watchedSeconds || 0]);
    } else if (completed) {
      await pool.query('UPDATE enrollment_progress SET completed=true, completed_at=NOW(), watched_seconds=$1, video_position_seconds=$2 WHERE id = (SELECT id FROM enrollment_progress WHERE enrollment_id=$3 AND learning_object_id=$4 ORDER BY id DESC LIMIT 1)', [watchedSeconds || 0, videoPosition || 0, enrollmentId, learningObjectId]);
    }

    res.json({ success: true });
  } catch (err) {
    console.error('Save progress error:', err.message);
    res.status(500).json({ error: 'Failed to save progress' });
  }
});

// ========== API: SESSIONI ==========
app.post('/api/player/session/start', async (req, res) => {
  try {
    const { enrollmentId, studentId, courseId } = req.body;
    const { rows } = await pool.query('INSERT INTO session_logs (enrollment_id, student_id, course_id, login_at) VALUES ($1,$2,$3,NOW()) RETURNING id', [enrollmentId, studentId, courseId]);
    res.json({ success: true, sessionId: rows[0].id });
  } catch (err) {
    console.error('Session start error:', err.message);
    res.status(500).json({ error: 'Failed to start session' });
  }
});

app.post('/api/player/session/end', async (req, res) => {
  try {
    const { sessionId, durationSeconds, lastLoId, lastLoIndex } = req.body;
    if (!sessionId) return res.status(400).json({ error: 'sessionId required' });
    await pool.query('UPDATE session_logs SET logout_at=NOW(), duration_seconds=$1, last_lo_id=$2, last_lo_index=$3 WHERE id=$4', [durationSeconds, lastLoId, lastLoIndex, sessionId]);
    res.json({ success: true });
  } catch (err) {
    console.error('Session end error:', err.message);
    res.status(500).json({ error: 'Failed to end session' });
  }
});

// ========== API: QUIZ ==========
app.post('/api/player/quiz/answer', async (req, res) => {
  try {
    const { enrollmentId, studentId, questionId, answerId, isCorrect, timedOut, responseTimeSeconds, learningObjectId, sessionLogId, questionText, answerText, videoSecond } = req.body;
    await pool.query(
      'INSERT INTO quiz_responses (enrollment_id, student_id, question_id, answer_id, is_correct, timed_out, response_time_seconds, learning_object_id, session_log_id, question_text, answer_text, video_second) VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12)',
      [enrollmentId, studentId, questionId, answerId, isCorrect||false, timedOut||false, responseTimeSeconds||0, learningObjectId, sessionLogId, questionText||'', answerText||'', videoSecond||0]
    );
    res.json({ success: true });
  } catch (err) {
    console.error('Quiz answer error:', err.message);
    res.status(500).json({ error: 'Failed to save quiz answer' });
  }
});

// ========== API: PROGRESSO LO ==========
app.get('/api/player/lo-progress/:enrollmentId', async (req, res) => {
  try {
    const eid = parseInt(req.params.enrollmentId);
    const { rows } = await pool.query('SELECT * FROM enrollment_progress WHERE enrollment_id = $1 ORDER BY id', [eid]);
    res.json(rows);
  } catch (err) {
    res.status(500).json({ error: 'Failed to fetch LO progress' });
  }
});

// ========== API: RICALCOLA PROGRESSO ==========
app.post('/api/player/recalc-progress/:enrollmentId', async (req, res) => {
  try {
    const eid = parseInt(req.params.enrollmentId);
    const { rows: enrRows } = await pool.query('SELECT course_id FROM enrollments WHERE id = $1', [eid]);
    if (enrRows.length === 0) return res.status(404).json({ error: 'Enrollment not found' });

    const courseId = enrRows[0].course_id;
    const { rows: loCount } = await pool.query(
      `SELECT COUNT(DISTINCT mlo.learning_object_id) as total FROM course_modules cm JOIN module_learning_objects mlo ON mlo.module_id = cm.module_id WHERE cm.course_id = $1`, [courseId]
    );
    const total = parseInt(loCount[0].total) || 0;

    const { rows: doneCount } = await pool.query(
      `SELECT COUNT(DISTINCT learning_object_id) as done FROM enrollment_progress WHERE enrollment_id = $1 AND completed = true`, [eid]
    );
    const done = parseInt(doneCount[0].done) || 0;

    const progress = total > 0 ? Math.round((done / total) * 100) : 0;
    const isCompleted = progress >= 100;

    if (isCompleted) {
      await pool.query('UPDATE enrollments SET progress=$1, completed_at=NOW(), status=$2 WHERE id=$3', [progress, 'completed', eid]);
    } else {
      await pool.query('UPDATE enrollments SET progress=$1 WHERE id=$2', [progress, eid]);
    }

    res.json({ success: true, progress, completed: isCompleted, totalLOs: total, completedLOs: done });
  } catch (err) {
    console.error('Recalc progress error:', err.message);
    res.status(500).json({ error: 'Failed to recalculate progress' });
  }
});

// ========== START ==========
app.listen(PORT, () => {
  console.log(`[Tutor81 Player] Running on port ${PORT}`);
});

// ========== API: GET ENROLLMENT PROGRESS ==========
app.get('/api/player/enrollment-progress/:enrollmentId', async (req, res) => {
  try {
    const eid = parseInt(req.params.enrollmentId);
    const { rows } = await pool.query('SELECT progress FROM enrollments WHERE id = $1', [eid]);
    if (rows.length === 0) return res.status(404).json({ error: 'Not found' });
    res.json({ progress: rows[0].progress || 0 });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});
