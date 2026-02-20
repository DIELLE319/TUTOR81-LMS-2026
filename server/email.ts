import { Resend } from 'resend';

// Inizializza Resend solo se la API key è configurata
const resend = process.env.RESEND_API_KEY
  ? new Resend(process.env.RESEND_API_KEY)
  : null;

const FROM_EMAIL = process.env.FROM_EMAIL || 'noreply@tutor81.com';
const FROM_NAME = process.env.FROM_NAME || 'Tutor81';

// ============================================================
// TIPI
// ============================================================

interface CourseEmailParams {
  to: string;
  userName: string;
  username: string;
  courseName: string;
  startDate: string;
  endDate: string;
  tutorName: string;
  tutorEmail?: string;
  tutorAddress?: string;
  tutorLogo?: string;
  referentName?: string;
  referentEmail?: string;
}

interface EmailResult {
  success: boolean;
  error?: string;
  id?: string;
}

// ============================================================
// TEMPLATE HTML — EMAIL CORSO
// ============================================================

function buildCourseEmailHTML(p: CourseEmailParams): string {
  const courseUrl = 'https://avviacorso.tutor81.com';

  return `<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Avvia il tuo corso</title>
</head>
<body style="margin:0;padding:20px;background-color:#f5f5f5;font-family:Arial,sans-serif;">
  <div style="max-width:600px;margin:0 auto;background-color:#FFFFFF;border:30px solid #EAB308;">

    <div style="background-color:#000;padding:20px;text-align:center;">
      ${p.tutorLogo
        ? `<img src="${p.tutorLogo}" alt="${p.tutorName}" style="max-height:60px;max-width:200px;" />`
        : `<div style="color:#FFFFFF;font-size:24px;font-weight:bold;">${p.tutorName}</div>`}
    </div>

    <div style="background-color:#000;color:#FFFFFF;padding:20px;text-align:center;border-top:3px solid #FFFFFF;">
      <div style="font-size:14px;margin-bottom:8px;">IL TUO NOME UTENTE &Egrave;:</div>
      <div style="font-size:28px;font-weight:bold;letter-spacing:1px;">${p.username}</div>
    </div>

    <div style="padding:30px;background-color:#FFFFFF;">
      <div style="background-color:#000;color:#FFFFFF;padding:12px 20px;margin-bottom:25px;font-size:16px;font-weight:bold;">
        Licenza per il corso: ${p.courseName}
      </div>
      <div style="color:#000;font-size:15px;line-height:1.8;">
        <p style="margin:0 0 15px 0;"><strong>Buongiorno ${p.userName},</strong></p>
        <p style="margin:0 0 10px 0;">Sei stato iscritto al seguente corso: <strong>${p.courseName}</strong></p>
        <p style="margin:0 0 10px 0;">Potrai iniziare a partire dal giorno: <strong>${p.startDate}</strong></p>
        <p style="margin:0 0 10px 0;">E terminare entro il giorno: <strong>${p.endDate}</strong></p>
        ${p.referentName ? `<p style="margin:0 0 20px 0;">Il tuo referente per questo corso &egrave;: <a href="mailto:${p.referentEmail}" style="color:#000;font-weight:bold;">${p.referentName} - ${p.referentEmail}</a></p>` : ''}
      </div>
    </div>

    <div style="background-color:#000;padding:25px;text-align:center;border-top:3px solid #FFFFFF;">
      <p style="color:#FFFFFF;margin:0 0 20px 0;font-size:15px;">Per accedere al corso clicca su AVVIA CORSO e inserisci:</p>
      <div style="background-color:#FFFFFF;color:#000;padding:15px 30px;font-size:16px;font-weight:bold;display:inline-block;margin-bottom:15px;text-align:left;">
        <div><span style="color:#666;">Nome utente:</span> ${p.username}</div>
      </div>
      <br/>
      <p style="color:#FFFFFF;margin:0 0 15px 0;font-size:14px;">Se vuoi avviare il corso clicca qui</p>
      <a href="${courseUrl}" style="display:inline-block;background-color:#22C55E;color:#FFFFFF;padding:15px 50px;font-size:18px;font-weight:bold;text-decoration:none;border:2px solid #22C55E;border-radius:4px;">AVVIA CORSO</a>
    </div>

    <div style="background-color:#FFFFFF;padding:25px 30px;color:#000;font-size:14px;line-height:1.8;border-top:2px solid #000;">
      <p style="margin:0 0 15px 0;">
        Il tuo referente per questo corso pu&ograve; essere contattato scrivendo a <strong>${p.tutorName}</strong>
        ${p.tutorAddress ? `<br/>${p.tutorAddress}` : ''}
        ${p.tutorEmail ? `<br/>E-Mail: <a href="mailto:${p.tutorEmail}" style="color:#000;">${p.tutorEmail}</a>` : ''}
      </p>
      <p style="margin:0 0 15px 0;">Al termine del corso potrai scaricare il tracciato di avvenuta formazione</p>
      <p style="margin:0 0 15px 0;"><strong>IL CORSO PU&Ograve; ESSERE INTERROTTO</strong> con il pulsante ESCI in alto a sinistra. Riaccendendo al corso questo ripartir&agrave; dall'ultimo punto utile.</p>
      <p style="margin:0 0 15px 0;"><strong>PAUSA:</strong> puoi fermare temporaneamente il corso con il pulsante Ferma, ma solo per 30 secondi, terminati i quali il corso viene interrotto.</p>
      <p style="margin:0;"><strong>ASSISTENZA TECNICA:</strong> In ogni momento &egrave; possibile inviare una segnalazione dal pulsante Richiedi Assistenza${p.tutorEmail ? ` oppure scrivete a <a href="mailto:${p.tutorEmail}" style="color:#000;">${p.tutorEmail}</a>` : ''}</p>
    </div>

    <div style="background-color:#000;padding:15px;text-align:center;">
      <p style="color:#FFFFFF;margin:0;font-size:14px;font-weight:bold;">${p.tutorName}${p.tutorAddress ? ` - ${p.tutorAddress}` : ''}</p>
    </div>

  </div>
</body>
</html>`;
}

// ============================================================
// TEMPLATE HTML — PROMEMORIA
// ============================================================

function buildReminderHTML(userName: string, courseName: string, endDate: string, tutorName: string): string {
  return `<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:20px;background-color:#f5f5f5;font-family:Arial,sans-serif;">
  <div style="max-width:600px;margin:0 auto;background-color:#FFFFFF;border:4px solid #EAB308;border-radius:8px;overflow:hidden;">
    <div style="background-color:#000;padding:20px;text-align:center;">
      <div style="color:#EAB308;font-size:22px;font-weight:bold;">${tutorName}</div>
    </div>
    <div style="padding:30px;">
      <h2 style="color:#000;margin:0 0 20px 0;">Promemoria corso</h2>
      <p>Buongiorno <strong>${userName}</strong>,</p>
      <p>Ti ricordiamo che il corso <strong>${courseName}</strong> deve essere completato entro il <strong>${endDate}</strong>.</p>
      <p>Accedi subito per completare il corso:</p>
      <div style="text-align:center;margin:25px 0;">
        <a href="https://avviacorso.tutor81.com" style="display:inline-block;background-color:#22C55E;color:#FFFFFF;padding:15px 50px;font-size:18px;font-weight:bold;text-decoration:none;border-radius:4px;">AVVIA CORSO</a>
      </div>
    </div>
    <div style="background-color:#000;padding:10px;text-align:center;">
      <p style="color:#FFFFFF;margin:0;font-size:12px;">${tutorName}</p>
    </div>
  </div>
</body>
</html>`;
}

// ============================================================
// FUNZIONI PUBBLICHE
// ============================================================

export function isEmailConfigured(): boolean {
  return resend !== null;
}

export async function sendCourseEmail(params: CourseEmailParams): Promise<EmailResult> {
  if (!resend) {
    console.warn('[Email] RESEND_API_KEY mancante. Email NON inviata a:', params.to);
    return { success: false, error: 'RESEND_API_KEY non configurato' };
  }

  try {
    const html = buildCourseEmailHTML(params);

    const { data, error } = await resend.emails.send({
      from: `${FROM_NAME} <${FROM_EMAIL}>`,
      to: params.to,
      subject: `Licenza corso: ${params.courseName} - ${params.tutorName}`,
      html,
    });

    if (error) {
      console.error('[Email] Errore Resend:', error);
      return { success: false, error: error.message };
    }

    console.log(`[Email] Corso inviata a ${params.to} — ID: ${data?.id}`);
    return { success: true, id: data?.id };
  } catch (err: any) {
    console.error('[Email] Errore invio corso:', err);
    return { success: false, error: err.message || 'Errore sconosciuto' };
  }
}

export async function sendReminderEmail(
  to: string,
  userName: string,
  courseName: string,
  endDate: string,
  tutorName: string
): Promise<EmailResult> {
  if (!resend) {
    console.warn('[Email] RESEND_API_KEY mancante. Promemoria NON inviato a:', to);
    return { success: false, error: 'RESEND_API_KEY non configurato' };
  }

  try {
    const html = buildReminderHTML(userName, courseName, endDate, tutorName);

    const { data, error } = await resend.emails.send({
      from: `${FROM_NAME} <${FROM_EMAIL}>`,
      to,
      subject: `Promemoria: completa il corso "${courseName}"`,
      html,
    });

    if (error) {
      console.error('[Email] Errore Resend promemoria:', error);
      return { success: false, error: error.message };
    }

    console.log(`[Email] Promemoria inviato a ${to} — ID: ${data?.id}`);
    return { success: true, id: data?.id };
  } catch (err: any) {
    console.error('[Email] Errore invio promemoria:', err);
    return { success: false, error: err.message || 'Errore sconosciuto' };
  }
}
