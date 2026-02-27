import puppeteer from "puppeteer";
import { PDFDocument } from "pdf-lib";
import fs from "fs";
import path from "path";

const LOGOS_DIR = path.resolve(process.cwd(), "uploads", "logos");

function imgB64(filePath: string): string | null {
  try {
    if (!fs.existsSync(filePath)) return null;
    const ext = path.extname(filePath).slice(1).toLowerCase();
    const mime = ext === "jpg" || ext === "jpeg" ? "image/jpeg" : "image/png";
    return `data:${mime};base64,${fs.readFileSync(filePath).toString("base64")}`;
  } catch {
    return null;
  }
}

function findLogo(name: string): string | null {
  for (const ext of [".png", ".jpg", ".jpeg"]) {
    const p = path.join(LOGOS_DIR, name + ext);
    if (fs.existsSync(p)) return imgB64(p);
  }
  return null;
}

export interface AttData {
  certNumber: number;
  abrvCompany: string;
  // Student
  learnerName: string;
  learnerTaxcode: string;
  learnerBusinessFunction: string;
  // Company (client)
  companyName: string;
  companyAteco: string;
  // Trainer
  trainer: string;
  // Course
  courseTitle: string;
  courseDescription: string;
  courseTargetAudience: string;
  courseLawReference: string;
  courseValidity: string;
  courseTotalElearning: string;
  courseMaxExecutionTime: string;
  courseProfessors: string;
  courseRequirements: string;
  courseChecking: string;
  coursePercentageToPass: string;
  courseDidactics: string;
  courseProgram: string;
  // Ente (accredited)
  enteName: string;
  enteAddress: string;
  enteCity: string;
  enteProvince: string;
  enteTelephone: string;
  enteEmail: string;
  enteRegionalAuth: string;
  // TutorVendor (organizer, empty if ente has auth)
  tvName: string;
  tvAddress: string;
  tvCity: string;
  tvProvince: string;
  tvTelephone: string;
  tvEmail: string;
  // Dates
  startCourseDate: string;
  endCourseDate: string;
  printDate: string;
  // Flags
  isDL81: boolean;
  // Logos (OVH company IDs or file names)
  tutorLogoFile: string;     // header left logo (Prometeo = "2")
  tutor81LogoFile: string;   // header right logo
  enteLogoFile: string;      // ente logo for pergamena
  firmaEnteFile: string;     // firma ente (e.g. "firma.1333")
  firmaTutorFile: string;    // firma tutor vendor
  // Events
  events: { start: string; title: string; end: string }[];
  // Quiz
  questions: { dateTime: string; text: string; answer: string; isCorrect: boolean; hasAnswer: boolean }[];
}

function esc(s: string): string {
  return (s || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
}

function portraitHtml(d: AttData): string {
  const tutorLogo = findLogo(d.tutorLogoFile) || "";
  const t81Logo = findLogo(d.tutor81LogoFile) || "";
  const tracciato = `${d.abrvCompany} ${d.certNumber} T81`.trim();

  const headerHtml = `
    <div class="header">
      <div class="header-inner">
        <div class="header-left">${tutorLogo ? `<img src="${tutorLogo}" class="h-logo"/>` : ""}</div>
        <div class="header-center">
          <div class="header-string">TRACCIATO N° ${esc(tracciato)}</div>
        </div>
        <div class="header-right">${t81Logo ? `<img src="${t81Logo}" class="h-logo"/>` : ""}</div>
      </div>
      <div class="header-line"></div>
    </div>`;

  // === PAGE 1 ===
  const page1 = `
  <div class="page">
    ${headerHtml}
    <div class="content">
      <table border="0" width="100%"><tr><td colspan="2" align="center" style="font-size:10pt;">
        <b>TRACCIATO N° ${esc(tracciato)}<br/>DI AVVENUTA FORMAZIONE IN ELEARNING</b>
      </td></tr></table>

      <p style="font-size:9pt; text-align:center; margin:6pt 0;">
        L'infrastruttura tecnologica TUTOR81 LMS${d.isDL81 ? " in conformità all'Accordo tra Stato e Regioni del 7 luglio 2016" : ""} certifica il completamento del corso in e-learning da parte di:
      </p>

      <table class="info-table" cellpadding="10" cellspacing="0" border="1">
        <tr><td class="label-col">Nominativo</td><td>${esc(d.learnerName)}</td></tr>
        <tr><td class="label-col">Codice fiscale</td><td>${esc(d.learnerTaxcode)}</td></tr>
        <tr><td class="label-col">In qualità di</td><td>${esc(d.learnerBusinessFunction)}</td></tr>
        <tr><td class="label-col">Organizzatore del corso</td><td>${esc(d.companyName)}</td></tr>
        <tr><td class="label-col">Soggetto formatore autorizzato</td><td>${esc(d.trainer)}</td></tr>
      </table>

      <h2 style="text-align:center; font-size:13pt; margin:16pt 0 8pt;">Scheda progettuale del corso in e-learning</h2>

      <table class="detail-table" cellpadding="5" cellspacing="0" border="0">
        <tr><td class="dt-label"><b>Titolo del corso:</b></td><td>${esc(d.courseTitle)}</td></tr>
        <tr><td class="dt-label"><b>Rivolto a:</b></td><td>${esc(d.courseTargetAudience)}</td></tr>
        <tr><td class="dt-label"><b>Riferimento normativo:</b></td><td>${esc(d.courseLawReference)}</td></tr>
        <tr><td class="dt-label"><b>Validità corso:</b></td><td>${esc(d.courseValidity)}</td></tr>
        <tr><td class="dt-label"><b>Descrizione del corso:</b></td><td>${esc(d.courseDescription)}</td></tr>
        <tr><td class="dt-label"><b>Durata del corso in e-learning:</b></td><td>${esc(d.courseTotalElearning)}</td></tr>
        <tr><td class="dt-label"><b>Tempo massimo per la conclusione:</b></td><td>${esc(d.courseMaxExecutionTime)}</td></tr>
        <tr><td class="dt-label"><b>Relatori e Docenti:</b></td><td>${esc(d.courseProfessors)}</td></tr>
        <tr><td class="dt-label"><b>Requisiti minimi per accedere al corso:</b></td><td>${esc(d.courseRequirements)}</td></tr>
        <tr><td class="dt-label"><b>Verifica di apprendimento:</b></td><td>${esc(d.courseChecking)}</td></tr>
        <tr><td class="dt-label"><b>Soglia minima per il superamento del corso:</b></td><td>${esc(d.coursePercentageToPass)} %</td></tr>
        <tr><td class="dt-label"><b>Caratteristiche tecniche della piattaforma:</b></td><td>${esc(d.courseDidactics)}</td></tr>
        <tr><td class="dt-label"><b>Programma del corso:</b></td><td>${d.courseProgram}</td></tr>
      </table>
    </div>
    <div class="footer">Pagina 1/5</div>
  </div>`;

  // === PAGE 2: Tracciamento ===
  let evtRows = "";
  for (const e of d.events) {
    evtRows += `<tr><td style="width:150px">${esc(e.start)}</td><td style="width:335px">${esc(e.title)}</td><td style="width:150px">${esc(e.end)}</td></tr>`;
  }
  const page2 = `
  <div class="page">
    ${headerHtml}
    <div class="content">
      <h2 style="text-align:center; font-size:13pt; margin:0 0 8pt;">Tracciamento del percorso formativo</h2>
      <table class="track-table" cellpadding="2" cellspacing="0" border="1" style="font-size:6pt;">
        <tr style="background:#ccc;">
          <td style="width:150px"><b>Collegamento</b></td>
          <td style="width:335px"><b>Materiale didattico svolto</b></td>
          <td style="width:150px"><b>Termine</b></td>
        </tr>
        ${evtRows}
      </table>
    </div>
    <div class="footer">Pagina 2/5</div>
  </div>`;

  // === PAGE 3: Valutazione ===
  let qRows = "";
  for (const q of d.questions) {
    const valText = q.hasAnswer ? (q.isCorrect ? '<b style="color:green">CORRETTA</b>' : '<b style="color:red">ERRATA</b>') : '<b style="color:red">SENZA RISPOSTA</b>';
    qRows += `<tr>
      <td style="width:150px">${esc(q.dateTime)}</td>
      <td style="width:235px">${esc(q.text)}</td>
      <td style="width:150px">${q.hasAnswer ? esc(q.answer) : "&nbsp;"}</td>
      <td style="width:100px">${valText}</td>
    </tr>`;
  }
  const page3 = `
  <div class="page">
    ${headerHtml}
    <div class="content">
      <h2 style="text-align:center; font-size:13pt; margin:0 0 8pt;">Valutazione dell'apprendimento</h2>
      <table class="track-table" cellpadding="2" cellspacing="0" border="1" style="font-size:6pt;">
        <tr style="background:#ccc;">
          <td style="width:150px"><b>Data/ora</b></td>
          <td style="width:235px"><b>Quesito</b></td>
          <td style="width:150px"><b>Risposta corsista</b></td>
          <td style="width:100px"><b>Valutazione</b></td>
        </tr>
        ${qRows}
      </table>
      <p style="font-size:9pt; text-align:center; margin:12pt 0;">Data: <b>${esc(d.printDate)}</b></p>
      <table cellpadding="0" cellspacing="0" border="1" style="width:50%; margin:0 auto; text-align:center;">
        <tr><td style="background:#ccc; font-size:9pt; padding:4pt;">Firma del corsista</td></tr>
        <tr><td style="height:60pt;">&nbsp;</td></tr>
      </table>
    </div>
    <div class="footer">Pagina 3/5</div>
  </div>`;

  // === PAGE 4: Blank ===
  const page4 = `
  <div class="page">
    ${headerHtml}
    <div class="content" style="display:flex; align-items:center; justify-content:center; height:80%;">
      <p style="font-size:11pt;"><b>Questa pagina &egrave; stata lasciata intenzionalmente vuota</b></p>
    </div>
    <div class="footer">Pagina 4/5</div>
  </div>`;

  return `<!DOCTYPE html><html><head><meta charset="utf-8"/><style>
    @page { size: 210mm 297mm; margin: 27mm 15mm 25mm 15mm; }
    * { box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif; font-size: 10pt; margin: 0; padding: 0; color: #000; }
    .page { page-break-after: always; position: relative; }
    .page:last-child { page-break-after: auto; }
    .header { margin-bottom: 8pt; }
    .header-inner { display: flex; align-items: center; justify-content: space-between; }
    .header-left, .header-right { width: 80px; }
    .header-center { flex: 1; text-align: center; font-size: 8pt; }
    .header-string { font-size: 8pt; }
    .h-logo { height: 30px; max-width: 120px; }
    .header-line { border-top: 0.85pt solid #000; margin-top: 4pt; }
    .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 8pt; font-style: italic; }
    .info-table { width: 100%; border-collapse: collapse; font-size: 10pt; margin: 12pt 0; }
    .info-table td { border: 1px solid #000; padding: 6pt 10pt; }
    .label-col { width: 33%; }
    .detail-table { width: 100%; border-collapse: collapse; font-size: 10pt; }
    .detail-table td { padding: 3pt 5pt; vertical-align: top; }
    .dt-label { width: 33%; text-align: right; }
    .track-table { width: 100%; border-collapse: collapse; }
    .track-table td { border: 1px solid #000; padding: 2pt 4pt; vertical-align: top; }
    .content { min-height: calc(100% - 80pt); }
    h2 { margin: 0; font-weight: bold; }
  </style></head><body>${page1}${page2}${page3}${page4}</body></html>`;
}

function pergamenaHtml(d: AttData): string {
  const t81Logo = findLogo(d.tutor81LogoFile);
  const tutorLogo = findLogo(d.tutorLogoFile);
  const enteLogo = findLogo(d.enteLogoFile);
  const firmaEnte = findLogo(d.firmaEnteFile);
  const firmaTutor = findLogo(d.firmaTutorFile);

  const tvCityLine = d.tvProvince ? `${esc(d.tvCity)} (${esc(d.tvProvince)})` : "";

  return `<!DOCTYPE html><html><head><meta charset="utf-8"/><style>
    @page { size: 297mm 210mm; margin: 8mm; }
    * { box-sizing: border-box; }
    body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11pt; margin: 0; padding: 0; color: #000; }
    b { font-family: 'Helvetica', 'Arial', sans-serif; }
    .border-box { border: 0.3cm solid #354163; padding: 10px 20px; min-height: calc(210mm - 16mm - 20px); position: relative; }
    .logo-tl { position: absolute; top: 10px; left: 15px; height: 35px; }
    .logo-tr { position: absolute; top: 10px; right: 15px; max-width: 150px; max-height: 35px; }
    .logo-br { position: absolute; bottom: 40px; right: 15px; max-width: 150px; max-height: 40px; }
    .firma-ente { position: absolute; bottom: 35px; left: 200px; max-width: 150px; max-height: 50px; }
    .firma-tutor { position: absolute; bottom: 35px; right: 200px; max-width: 150px; max-height: 50px; }
    table { border-collapse: collapse; width: 100%; }
    td { vertical-align: top; padding: 2px 0; }
  </style></head><body>
  <div class="border-box">
    ${t81Logo ? `<img src="${t81Logo}" class="logo-tl"/>` : ""}
    ${tutorLogo ? `<img src="${tutorLogo}" class="logo-tr"/>` : ""}
    ${enteLogo ? `<img src="${enteLogo}" class="logo-br"/>` : ""}
    ${firmaEnte ? `<img src="${firmaEnte}" class="firma-ente"/>` : ""}
    ${firmaTutor ? `<img src="${firmaTutor}" class="firma-tutor"/>` : ""}

    <table>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr><td colspan="16" style="text-align:center; font-size:23px; line-height:120%;">
        ATTESTATO DI FREQUENZA<br/>
        <span style="font-size:19px;">corso e-learning N° ${d.certNumber}</span>
      </td></tr>
      <tr><td colspan="16" style="text-align:center; font-size:13px; line-height:120%;">
        <br/>${d.isDL81 ? "(ai sensi dell'art. 37 del decreto legislativo 9 aprile 2008 n. 81)<br/>" : ""}
        Il documento è valido su tutto il territorio nazionale
        ${!d.isDL81 ? "<br/>" : ""}
      </td></tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr style="line-height:150%;">
        <td colspan="4">si attesta che: </td>
        <td colspan="12"><b>${esc(d.learnerName)}</b></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="4">Codice fiscale: </td>
        <td colspan="12"><b>${esc(d.learnerTaxcode)}</b></td>
      </tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr style="line-height:150%;">
        <td colspan="16" style="text-align:center;">ha superato il corso di formazione e ha superato la prova finale di apprendimento del corso:</td>
      </tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr>
        <td colspan="16" style="padding:0; font-size:17px; background-color:#d6d6d6; line-height:300%; text-align:center;">
          ${esc(d.courseTitle.toUpperCase())}
        </td>
      </tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr style="line-height:150%;">
        <td colspan="4">Percent. test validazione corso:</td>
        <td colspan="12"><b>${esc(d.coursePercentageToPass)}%</b></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="4">Riferimento normativo: </td>
        <td colspan="12"><b>${esc(d.courseLawReference)}</b></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="4">Durata del corso in elearning: </td>
        <td colspan="12"><b>${esc(d.courseTotalElearning)} ore</b></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="4">Periodo di svolgimento: </td>
        <td colspan="12"><b>dal ${esc(d.startCourseDate)} al ${esc(d.endCourseDate)}</b></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="4">Organizzato da: </td>
        <td colspan="12"><b>${esc(d.companyName)}</b></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="4">Settore Ateco: </td>
        <td colspan="12"><b>${esc(d.companyAteco)}</b></td>
      </tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr style="line-height:150%;">
        <td colspan="7" style="text-align:center;"><u>Ente di formazione accreditato</u></td>
        <td colspan="7" style="text-align:center;">${d.tvName ? "<u>Organizzatore del corso</u>" : ""}</td>
        <td colspan="2"></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="7"><b>${esc(d.enteName)}</b></td>
        <td colspan="7"><b>${esc(d.tvName)}</b></td>
        <td colspan="2"></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="7"><b>${esc(d.enteAddress)}</b></td>
        <td colspan="7"><b>${esc(d.tvAddress)}</b></td>
        <td colspan="2"></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="7"><b>${d.enteProvince ? `${esc(d.enteCity)} (${esc(d.enteProvince)})` : esc(d.enteCity)}</b></td>
        <td colspan="7"><b>${tvCityLine}</b></td>
        <td colspan="2"></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="7"><b>${esc(d.enteTelephone)}</b></td>
        <td colspan="7"><b>${esc(d.tvTelephone)}</b></td>
        <td colspan="2"></td>
      </tr>
      <tr style="line-height:150%;">
        <td colspan="7"><b>${esc(d.enteEmail)}</b></td>
        <td colspan="7"><b>${esc(d.tvEmail)}</b></td>
        <td colspan="2"></td>
      </tr>
      <tr>
        <td colspan="7"><b>${esc(d.enteRegionalAuth)}</b></td>
        <td colspan="7"></td>
        <td colspan="2"></td>
      </tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr><td colspan="16">&nbsp;</td></tr>
      <tr><td colspan="16" style="text-align:center; font-size:13px; line-height:120%;">
        L'attestato ${d.isDL81 ? `rilasciato ai sensi dell'Accordo del 17 aprile 2025 sancito in conferenza permanente per i rapporti tra lo Stato, le Regioni e le Province Autonome di Trento e Bolzano` : ""} è valido su tutto il territorio nazionale
        ${!d.isDL81 ? "<br/>" : ""}
      </td></tr>
      <tr><td colspan="16">&nbsp;</td></tr>
    </table>
  </div>
  </body></html>`;
}

let browserInstance: any = null;

async function getBrowser() {
  if (!browserInstance) {
    browserInstance = await puppeteer.launch({
      headless: true,
      args: ["--no-sandbox", "--disable-setuid-sandbox", "--disable-dev-shm-usage"],
    });
  }
  return browserInstance;
}

export async function generateAttestatoPdf(d: AttData): Promise<Buffer> {
  const browser = await getBrowser();

  // Render portrait pages 1-4
  const page1 = await browser.newPage();
  await page1.setContent(portraitHtml(d), { waitUntil: "networkidle0" });
  const portraitBuf = await page1.pdf({
    format: "A4",
    printBackground: true,
    margin: { top: "27mm", bottom: "25mm", left: "15mm", right: "15mm" },
    displayHeaderFooter: false,
  });
  await page1.close();

  // Render landscape page 5 (pergamena)
  const page5 = await browser.newPage();
  await page5.setContent(pergamenaHtml(d), { waitUntil: "networkidle0" });
  const landscapeBuf = await page5.pdf({
    width: "297mm",
    height: "210mm",
    printBackground: true,
    margin: { top: "8mm", bottom: "8mm", left: "8mm", right: "8mm" },
    displayHeaderFooter: false,
  });
  await page5.close();

  // Merge PDFs
  const merged = await PDFDocument.create();
  const pdf1 = await PDFDocument.load(portraitBuf);
  const pdf2 = await PDFDocument.load(landscapeBuf);

  const pages1 = await merged.copyPages(pdf1, pdf1.getPageIndices());
  for (const p of pages1) merged.addPage(p);

  const pages2 = await merged.copyPages(pdf2, pdf2.getPageIndices());
  for (const p of pages2) merged.addPage(p);

  const finalBuf = await merged.save();
  return Buffer.from(finalBuf);
}
