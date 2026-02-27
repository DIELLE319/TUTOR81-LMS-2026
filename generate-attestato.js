const PDFDocument = require('pdfkit');
const { Pool } = require('pg');
const fs = require('fs');

const pool = new Pool({
  host: process.env.PG_HOST || 'localhost',
  port: parseInt(process.env.PG_PORT || '5432'),
  user: process.env.PG_USER || 'tutor81',
  password: process.env.PG_PASSWORD || 'tutor81pass',
  database: process.env.PG_DATABASE || 'tutor81'
});

function fmtDate(d) {
  if (!d) return '';
  const dt = new Date(d);
  return `${String(dt.getDate()).padStart(2,'0')}-${String(dt.getMonth()+1).padStart(2,'0')}-${dt.getFullYear()}`;
}
function fmtDateTime(d) {
  if (!d) return '';
  const dt = new Date(d);
  return `${String(dt.getDate()).padStart(2,'0')}-${String(dt.getMonth()+1).padStart(2,'0')}-${dt.getFullYear()} ${String(dt.getHours()).padStart(2,'0')}:${String(dt.getMinutes()).padStart(2,'0')}:${String(dt.getSeconds()).padStart(2,'0')}`;
}

async function generateAttestato(enrollmentId, outputStream) {
  const { rows: [enr] } = await pool.query(`
    SELECT e.*, s.first_name, s.last_name, s.fiscal_code,
           c.title as course_title, c.description as course_desc,
           c.rivolto_a, c.riferimento_normativo, c.validita,
           c.durata_minima_elearning, c.tempo_massimo_conclusione,
           c.relatori_docenti, c.verifica_apprendimento, c.sector,
           t.business_name as tutor_name, t.address as tutor_addr, t.city as tutor_city,
           t.cap as tutor_cap, t.province as tutor_prov, t.phone as tutor_phone,
           t.website as tutor_web, t.contact_person as tutor_contact, t.regional_authorization
    FROM enrollments e
    JOIN students s ON s.id = e.student_id
    JOIN courses c ON c.id = e.course_id
    LEFT JOIN tutors t ON t.id = e.tutor_id
    WHERE e.id = $1`, [enrollmentId]);
  if (!enr) throw new Error('Enrollment non trovato');

  const { rows: mlos } = await pool.query(`
    SELECT lo.id, lo.title, lo.duration FROM course_modules cm
    JOIN modules m ON m.id = cm.module_id
    JOIN module_learning_objects mlo ON mlo.module_id = m.id
    JOIN learning_objects lo ON lo.id = mlo.learning_object_id
    WHERE cm.course_id = $1 ORDER BY cm.position, mlo.position`, [enr.course_id]);

  const { rows: progress } = await pool.query(`
    SELECT ep.*, lo.title as lo_title FROM enrollment_progress ep
    JOIN learning_objects lo ON lo.id = ep.learning_object_id
    WHERE ep.enrollment_id = $1 AND ep.completed = true ORDER BY ep.started_at`, [enrollmentId]);

  const { rows: quizzes } = await pool.query(`
    SELECT * FROM quiz_responses WHERE enrollment_id = $1 ORDER BY created_at`, [enrollmentId]);

  const totQ = quizzes.length, okQ = quizzes.filter(q => q.is_correct).length;
  const pctQ = totQ > 0 ? Math.round((okQ / totQ) * 100) : 0;
  const nome = `${enr.first_name||''} ${enr.last_name||''}`.toUpperCase().trim();
  const cf = (enr.fiscal_code || '').toUpperCase();
  const titolo = enr.course_title || '';
  const durH = enr.durata_minima_elearning || '';
  const maxD = enr.tempo_massimo_conclusione || '';
  const normR = enr.riferimento_normativo || '';
  const valid = enr.validita || '';
  const org = '';
  const tutN = enr.tutor_name || '';
  const tutAddr = [enr.tutor_addr, [enr.tutor_cap, enr.tutor_city].filter(Boolean).join(' '),
    enr.tutor_prov ? `(${enr.tutor_prov})` : ''].filter(Boolean).join(', ');
  const progTxt = mlos.map(lo => `(${lo.id})${lo.title}(${lo.duration||0} min)`).join('\n');
  const dates = progress.filter(p=>p.started_at).map(p=>new Date(p.started_at));
  const dMin = dates.length ? fmtDate(new Date(Math.min(...dates))) : fmtDate(new Date());
  const dMax = dates.length ? fmtDate(new Date(Math.max(...dates))) : fmtDate(new Date());
  const num = enrollmentId;

  const doc = new PDFDocument({ size: 'A4', margin: 40, bufferPages: true });
  doc.pipe(outputStream);
  const W = 595.276, H = 841.89, ML = 40, MR = 40, CW = W - ML - MR;

  // ==================== PAGE 1 ====================
  doc.fontSize(14).font('Helvetica-Bold').text(`TRACCIATO N\u00b0 ${num} T81`, ML, 40, { align: 'center', width: CW });
  doc.moveDown(0.2);
  doc.fontSize(14).font('Helvetica-Bold').text(`TRACCIATO N\u00b0 ${num} T81`, { align: 'center', width: CW });
  doc.moveDown(0.2);
  doc.fontSize(10).font('Helvetica-Bold').text('DI AVVENUTA FORMAZIONE IN ELEARNING', { align: 'center', width: CW });
  doc.moveDown(0.4);
  doc.fontSize(8).font('Helvetica').text("L'infrastruttura tecnologica TUTOR81 LMS in conformit\u00e0 all'Accordo tra Stato e Regioni del 7 luglio 2016", { align: 'center', width: CW });
  doc.text('certifica il completamento del corso in e-learning da parte di:', { align: 'center', width: CW });
  doc.moveDown(0.6);

  let y = doc.y;
  const LX = ML, VX = ML + 150;
  function infoRow(lab, val) {
    doc.fontSize(9).font('Helvetica-Bold').text(lab, LX, y, { width: 145 });
    doc.fontSize(9).font('Helvetica').text(val || '', VX, y, { width: CW - 155 });
    y += Math.max(18, doc.heightOfString(val || '', { width: CW - 155, fontSize: 9 }) + 4);
  }
  infoRow('Nominativo', nome);
  infoRow('Codice fiscale', cf);
  infoRow('In qualit\u00e0 di', 'LAVORATORE');
  infoRow('Organizzatore del corso', org);
  infoRow('Soggetto formatore autorizzato', `${tutN}\n${tutAddr}`);
  y += 8;

  doc.fontSize(11).font('Helvetica-Bold').text('Scheda progettuale del corso in e-learning', ML, y, { width: CW });
  y += 20;
  function sRow(lab, val) {
    const sy = y;
    doc.fontSize(8).font('Helvetica-Bold').text(lab, LX, y, { width: 145 });
    doc.fontSize(8).font('Helvetica').text(val || '', VX, y, { width: CW - 155 });
    y = sy + Math.max(16, doc.heightOfString(val || '', { width: CW - 155, fontSize: 8 }) + 3);
  }
  sRow('Titolo del corso:', titolo);
  sRow('Rivolto a:', enr.target_audience || '');
  sRow('Riferimento normativo:', normR);
  sRow('Validit\u00e0 corso:', valid);
  sRow('Descrizione del corso:', enr.course_desc || progTxt);
  sRow('Durata del corso in e-learning:', `${durH}`);
  sRow('Tempo massimo per la conclusione:', `${maxD}`);
  sRow('Relatori e Docenti:', 'I relatori/docenti che hanno contribuito alla redazione dei contenuti di ciascuna unit\u00e0 didattica sono in possesso dei requisiti previsti dal decreto interministeriale del 6 marzo 2013 "Criteri di qualificazione della figura del formatore per la salute e sicurezza nei luoghi di lavoro".');
  sRow('Requisiti minimi per accedere al corso:', 'conclusione del corso di formazione generale');
  doc.fontSize(8).font('Helvetica').text('Pagina 1/6', ML, H - 30, { align: 'center', width: CW });

  // ==================== PAGE 2 ====================
  doc.addPage(); y = 40;
  doc.fontSize(14).font('Helvetica-Bold').text(`TRACCIATO N\u00b0 ${num} T81`, ML, y, { align: 'center', width: CW }); y += 28;
  function r2(lab, val) {
    const sy = y;
    doc.fontSize(8).font('Helvetica-Bold').text(lab, LX, y, { width: 145 });
    doc.fontSize(8).font('Helvetica').text(val || '', VX, y, { width: CW - 155 });
    y = sy + Math.max(16, doc.heightOfString(val || '', { width: CW - 155, fontSize: 8 }) + 3);
  }
  r2('Verifica di apprendimento:', "La verifica di apprendimento principale privilegiata nell'ambiente Tutor81 \u00e8 la verifica in in itinere. Si tratta di test a tempo trasmessi frequentemente e con lo scopo non solo di controllare la presenza del partecipante ma di stimolarne l'attenzione. Il corsista riceve immediato riscontro alla risposta rilasciata. Ogni quesito viene tracciato dal sistema e riportato nell'attestato finale. I test sono trasmessi in modalit\u00e0 random, ci\u00f2 significa che per la stessa domanda esistono varie alternative. In caso il risultato finale dei test, sia inferiore alla soglia minima prevista dei test corretti, l'attestato non viene generato dal sistema. Il soggetto formatore valuter\u00e0 le modalit\u00e0 che riterr\u00e0 pi\u00f9 idonee per approfondire gli errori e rivalutarne l'apprendimento.");
  r2('Soglia minima per il superamento del corso:', '80 %');
  r2('Caratteristiche tecniche della piattaforma:', "TUTOR81 LMS \u00e8 una piattaforma LMS con sistema di tracciamento proprietario, conforme alla normativa attualmente in vigore (Accordo Stato Regioni del 7 luglio 2016) in tema di formazione e-learning riguardante la tutela della sicurezza e della salute dei lavoratori. Ogni corso con Tutor81 \u00e8 monitorato rispettando i requisiti previsti dall'Allegato 2 Accordo Stato Regioni 07.07.2016 al termine di ogni corso \u00e8 quindi possibile certificare e documentare quanto segue: \u2022 Lo svolgimento e il completamento delle attivit\u00e0 didattiche di ciascun utente \u2022 Le modalit\u00e0 e il superamento delle valutazioni di apprendimento \u2022 La partecipazione attiva del discente; \u2022 La tracciabilit\u00e0 di ogni attivit\u00e0 svolta durante il collegamento al sistema e la durata; \u2022 La tracciabilit\u00e0 dell'utilizzo anche delle singole unit\u00e0 didattiche. la regolarit\u00e0 e la progressivit\u00e0 di utilizzo del sistema da parte dell'utente;");
  r2('Programma del corso:', enr.course_desc || progTxt);
  doc.fontSize(8).font('Helvetica').text('Pagina 2/6', ML, H - 30, { align: 'center', width: CW });

  // ==================== PAGE 3 ====================
  doc.addPage(); y = 40;
  doc.fontSize(14).font('Helvetica-Bold').text(`TRACCIATO N\u00b0 ${num} T81`, ML, y, { align: 'center', width: CW }); y += 26;
  doc.fontSize(11).font('Helvetica-Bold').text('Tracciamento del percorso formativo', ML, y); y += 20;
  const c3a = ML, c3b = ML + 130, c3c = ML + 400;
  doc.fontSize(8).font('Helvetica-Bold');
  doc.text('Collegamento', c3a, y); doc.text('Materiale didattico svolto', c3b, y); doc.text('Termine', c3c, y);
  y += 13; doc.moveTo(ML, y).lineTo(W - MR, y).lineWidth(0.5).stroke(); y += 4;
  doc.fontSize(7).font('Helvetica');
  for (const p of progress) {
    if (y > H - 50) { doc.addPage(); y = 50; }
    doc.text(fmtDateTime(p.started_at), c3a, y, { width: 125 });
    doc.text(p.lo_title || '', c3b, y, { width: 260 });
    doc.text(fmtDateTime(p.completed_at || p.left_at), c3c, y, { width: 130 });
    y += 12;
  }
  doc.fontSize(8).font('Helvetica').text('Pagina 3/6', ML, H - 30, { align: 'center', width: CW });

  // ==================== PAGE 4 ====================
  doc.addPage(); y = 40;
  doc.fontSize(14).font('Helvetica-Bold').text(`TRACCIATO N\u00b0 ${num} T81`, ML, y, { align: 'center', width: CW }); y += 26;
  doc.fontSize(11).font('Helvetica-Bold').text("Valutazione dell'apprendimento", ML, y); y += 20;
  const c4a = ML, c4b = ML + 100, c4c = ML + 310, c4d = ML + 445;
  doc.fontSize(8).font('Helvetica-Bold');
  doc.text('Data/ora', c4a, y); doc.text('Quesito', c4b, y); doc.text('Risposta corsista', c4c, y); doc.text('Valutazione', c4d, y);
  y += 13; doc.moveTo(ML, y).lineTo(W - MR, y).lineWidth(0.5).stroke(); y += 4;
  doc.fontSize(7).font('Helvetica');
  for (const q of quizzes) {
    if (y > H - 80) { doc.addPage(); y = 50; }
    const qh = Math.max(
      doc.heightOfString(q.question_text || '', { width: 205, fontSize: 7 }),
      doc.heightOfString(q.answer_text || '', { width: 130, fontSize: 7 }), 10);
    doc.text(fmtDateTime(q.created_at), c4a, y, { width: 95 });
    doc.text(q.question_text || '', c4b, y, { width: 205 });
    doc.text(q.answer_text || '', c4c, y, { width: 130 });
    doc.text(q.is_correct ? 'CORRETTA' : 'ERRATA', c4d, y, { width: 70 });
    y += qh + 4;
  }
  y += 14;
  doc.fontSize(9).font('Helvetica-Bold').text(`Data: ${dMax}`, ML, y); y += 16;
  doc.fontSize(9).font('Helvetica').text('Firma del corsista', ML, y);
  doc.fontSize(8).font('Helvetica').text('Pagina 4/6', ML, H - 30, { align: 'center', width: CW });

  // ==================== PAGE 5 ====================
  doc.addPage();
  doc.fontSize(14).font('Helvetica-Bold').text(`TRACCIATO N\u00b0 ${num} T81`, ML, 40, { align: 'center', width: CW });
  doc.fontSize(10).font('Helvetica').text('Questa pagina \u00e8 stata lasciata intenzionalmente vuota', ML, H / 2 - 20, { align: 'center', width: CW });
  doc.fontSize(8).text('Pagina 5/6', ML, H - 30, { align: 'center', width: CW });

  // ==================== PAGE 6 (LANDSCAPE) ====================
  doc.addPage({ size: [H, W] }); // landscape A4
  const LW = H, LH = W; // landscape dims
  const lml = 60, lcw = LW - 120;
  y = 50;
  doc.fontSize(22).font('Helvetica-Bold').text('ATTESTATO DI FREQUENZA', lml, y, { align: 'center', width: lcw }); y += 30;
  doc.fontSize(12).font('Helvetica').text(`corso e-learning N\u00b0 ${num}`, lml, y, { align: 'center', width: lcw }); y += 18;
  doc.fontSize(9).font('Helvetica').text('(ai sensi dell\'art. 37 del decreto legislativo 9 aprile 2008 n. 81)', lml, y, { align: 'center', width: lcw }); y += 14;
  doc.fontSize(9).font('Helvetica-Oblique').text('Il documento \u00e8 valido su tutto il territorio nazionale', lml, y, { align: 'center', width: lcw }); y += 24;

  doc.fontSize(10).font('Helvetica').text('si attesta che:', lml, y, { align: 'center', width: lcw }); y += 18;
  doc.fontSize(16).font('Helvetica-Bold').text(nome, lml, y, { align: 'center', width: lcw }); y += 22;
  doc.fontSize(10).font('Helvetica').text(`Codice fiscale: ${cf}`, lml, y, { align: 'center', width: lcw }); y += 20;

  doc.fontSize(9).font('Helvetica').text('ha superato il corso di formazione e ha superato la prova finale di apprendimento del corso:', lml, y, { align: 'center', width: lcw }); y += 18;
  doc.fontSize(13).font('Helvetica-Bold').text(titolo, lml, y, { align: 'center', width: lcw }); y += 22;

  doc.fontSize(9).font('Helvetica');
  doc.text(`Percent. test validazione corso: ${pctQ}%`, lml, y, { align: 'center', width: lcw }); y += 14;
  doc.text(`Riferimento normativo: ${normR}`, lml, y, { align: 'center', width: lcw }); y += 14;
  doc.text(`Durata del corso in elearning: ${durH} ore`, lml, y, { align: 'center', width: lcw }); y += 14;
  doc.text(`Periodo di svolgimento: dal ${dMin} al ${dMax}`, lml, y, { align: 'center', width: lcw }); y += 14;
  doc.text(`Organizzato da: ${org}`, lml, y, { align: 'center', width: lcw }); y += 14;
  if (enr.target_audience) { doc.text(`Settore Ateco: ${enr.target_audience}`, lml, y, { align: 'center', width: lcw }); y += 14; }
  y += 20;

  // Two columns: ente formazione | formatore
  const halfW = lcw / 2;
  doc.fontSize(8).font('Helvetica-Bold');
  doc.text('Ente di formazione accreditato', lml, y, { width: halfW, align: 'center' });
  doc.text('Formatore', lml + halfW, y, { width: halfW, align: 'center' });
  y += 14;
  doc.fontSize(8).font('Helvetica');
  doc.text(tutN, lml, y, { width: halfW, align: 'center' });
  doc.text(org, lml + halfW, y, { width: halfW, align: 'center' });
  y += 12;
  if (tutAddr) { doc.text(tutAddr, lml, y, { width: halfW, align: 'center' }); y += 10; }
  if (enr.tutor_phone) { doc.text(`Tel. ${enr.tutor_phone}`, lml, y, { width: halfW, align: 'center' }); y += 10; }
  if (enr.tutor_web) { doc.text(enr.tutor_web, lml, y, { width: halfW, align: 'center' }); y += 10; }

  y = LH - 60;
  doc.fontSize(7).font('Helvetica-Oblique').text("L'attestato rilasciato ai sensi dell'Accordo del 17 aprile 2025 sancito in conferenza permanente per i rapporti tra lo Stato, le Regioni e le Province Autonome di Trento e Bolzano \u00e8 valido su tutto il territorio nazionale", lml, y, { align: 'center', width: lcw });

  doc.end();
  return new Promise((resolve, reject) => {
    if (outputStream.path) {
      outputStream.on('finish', resolve);
      outputStream.on('error', reject);
    } else {
      resolve();
    }
  });
}

// CLI usage or module export
if (require.main === module) {
  const eid = parseInt(process.argv[2]);
  if (!eid) { console.error('Usage: node generate-attestato.js <enrollment_id>'); process.exit(1); }
  const out = process.argv[3] || `attestato_${eid}.pdf`;
  generateAttestato(eid, fs.createWriteStream(out))
    .then(() => { console.log('Attestato generato:', out); pool.end(); })
    .catch(e => { console.error(e); pool.end(); process.exit(1); });
}

module.exports = { generateAttestato };
