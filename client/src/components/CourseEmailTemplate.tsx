interface CourseEmailTemplateProps {
  tutorLogo?: string;
  tutorName: string;
  tutorAddress?: string;
  tutorEmail?: string;
  courseName: string;
  userName: string;
  userEmail: string;
  startDate: string;
  endDate: string;
  referentName?: string;
  referentEmail?: string;
  username: string;
  courseUrl?: string;
  instructionsUrl?: string;
  trackingId?: string;
  baseUrl?: string;
}

export default function CourseEmailTemplate({
  tutorLogo,
  tutorName,
  tutorAddress,
  tutorEmail,
  courseName,
  userName,
  startDate,
  endDate,
  referentName,
  referentEmail,
  username,
  courseUrl = '#',
  instructionsUrl = '#'
}: CourseEmailTemplateProps) {
  return (
    <div style={{ 
      fontFamily: 'Arial, sans-serif', 
      maxWidth: '600px', 
      margin: '0 auto',
      backgroundColor: '#FFFFFF',
      border: '2px solid #000'
    }}>
      <div style={{ 
        backgroundColor: '#000', 
        padding: '20px', 
        textAlign: 'center' as const 
      }}>
        {tutorLogo ? (
          <img src={tutorLogo} alt={tutorName} style={{ maxHeight: '60px', maxWidth: '200px' }} />
        ) : (
          <div style={{ 
            color: '#FFFFFF', 
            fontSize: '24px', 
            fontWeight: 'bold' 
          }}>
            {tutorName}
          </div>
        )}
      </div>

      <div style={{ 
        backgroundColor: '#000', 
        color: '#FFFFFF', 
        padding: '15px', 
        textAlign: 'center' as const,
        fontSize: '18px',
        fontWeight: 'bold',
        borderTop: '3px solid #FFFFFF'
      }}>
        Devi svolgere un corso obbligatorio
      </div>

      <div style={{ padding: '30px', backgroundColor: '#FFFFFF' }}>
        <div style={{ 
          backgroundColor: '#000', 
          color: '#FFFFFF', 
          padding: '12px 20px',
          marginBottom: '25px',
          fontSize: '16px',
          fontWeight: 'bold'
        }}>
          Licenza per il corso: {courseName}
        </div>

        <div style={{ color: '#000', fontSize: '15px', lineHeight: '1.8' }}>
          <p style={{ margin: '0 0 15px 0' }}>
            <strong>Buongiorno {userName},</strong>
          </p>
          
          <p style={{ margin: '0 0 10px 0' }}>
            Sei stato iscritto al seguente corso: <strong>{courseName}</strong>
          </p>
          
          <p style={{ margin: '0 0 10px 0' }}>
            Potrai iniziare a partire dal giorno: <strong>{startDate}</strong>
          </p>
          
          <p style={{ margin: '0 0 10px 0' }}>
            E terminare entro il giorno: <strong>{endDate}</strong>
          </p>
          
          {referentName && (
            <p style={{ margin: '0 0 20px 0' }}>
              Il tuo referente per questo corso è: {' '}
              <a href={`mailto:${referentEmail}`} style={{ color: '#000', fontWeight: 'bold' }}>
                {referentName} - {referentEmail}
              </a>
            </p>
          )}
        </div>
      </div>

      <div style={{ 
        backgroundColor: '#000', 
        padding: '25px', 
        textAlign: 'center' as const,
        borderTop: '3px solid #FFFFFF'
      }}>
        <p style={{ 
          color: '#FFFFFF', 
          margin: '0 0 20px 0',
          fontSize: '15px'
        }}>
          Per accedere al corso clicca su avvia corso e<br />
          inserisci il tuo nome utente in questo modo:
        </p>
        
        <div style={{ 
          backgroundColor: '#FFFFFF', 
          color: '#000', 
          padding: '15px 30px',
          fontSize: '20px',
          fontWeight: 'bold',
          display: 'inline-block',
          marginBottom: '25px'
        }}>
          {username}
        </div>
        
        <p style={{ 
          color: '#FFFFFF', 
          margin: '0 0 15px 0',
          fontSize: '14px'
        }}>
          Se vuoi avviare il corso clicca qui
        </p>
        
        <a 
          href={courseUrl}
          style={{
            display: 'inline-block',
            backgroundColor: '#22C55E',
            color: '#FFFFFF',
            padding: '15px 50px',
            fontSize: '18px',
            fontWeight: 'bold',
            textDecoration: 'none',
            border: '2px solid #22C55E',
            borderRadius: '4px'
          }}
        >
          AVVIA CORSO
        </a>
      </div>

      <div style={{ 
        backgroundColor: '#FFFFFF', 
        padding: '30px', 
        textAlign: 'center' as const,
        borderTop: '3px solid #000'
      }}>
        <a 
          href={instructionsUrl}
          style={{
            color: '#000',
            fontSize: '18px',
            fontWeight: 'bold',
            textDecoration: 'underline'
          }}
        >
          ISTRUZIONI PER IL CORSO
        </a>
      </div>

      <div style={{ 
        backgroundColor: '#FFFFFF', 
        padding: '25px 30px',
        color: '#000',
        fontSize: '14px',
        lineHeight: '1.8',
        borderTop: '2px solid #000'
      }}>
        <p style={{ margin: '0 0 15px 0' }}>
          Il tuo referente per questo corso può essere contattato per E-Mail scrivendo a <strong>{tutorName}</strong>
          {tutorAddress && <><br />{tutorAddress}</>}
          {tutorEmail && <><br />E-Mail: <a href={`mailto:${tutorEmail}`} style={{ color: '#000' }}>{tutorEmail}</a></>}
        </p>
        
        <p style={{ margin: '0 0 15px 0' }}>
          Al termine del corso potrai scaricare il tracciato di avvenuta formazione
        </p>
        
        <p style={{ margin: '0 0 15px 0' }}>
          <strong>IL CORSO PUÒ ESSERE INTERROTTO</strong> con il pulsante ESCI in alto a sinistra. Riaccendendo al corso questo ripartirà dall'ultimo punto utile.
        </p>
        
        <p style={{ margin: '0 0 15px 0' }}>
          <strong>PAUSA:</strong> puoi fermare temporaneamente il corso con il pulsante Ferma, ma solo per 30 secondi, terminati i quali il corso viene interrotto.
        </p>
        
        <p style={{ margin: '0' }}>
          <strong>ASSISTENZA TECNICA:</strong> In ogni momento è possibile inviare una segnalazione anche tramite mail dal pulsante Richiedi Assistenza oppure scrivete a{' '}
          {tutorEmail && <a href={`mailto:${tutorEmail}`} style={{ color: '#000' }}>{tutorEmail}</a>}
        </p>
      </div>

      <div style={{ 
        backgroundColor: '#000', 
        padding: '15px', 
        textAlign: 'center' as const
      }}>
        <p style={{ 
          color: '#FFFFFF', 
          margin: '0',
          fontSize: '14px',
          fontWeight: 'bold'
        }}>
          {tutorName}{tutorAddress && ` - ${tutorAddress}`}
        </p>
      </div>
    </div>
  );
}

export function generateEmailHTML(props: CourseEmailTemplateProps): string {
  return `
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Avvia il tuo corso</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #f5f5f5; font-family: Arial, sans-serif;">
  <div style="max-width: 600px; margin: 0 auto; background-color: #FFFFFF; border: 2px solid #000;">
    
    <div style="background-color: #000; padding: 20px; text-align: center;">
      ${props.tutorLogo 
        ? `<img src="${props.tutorLogo}" alt="${props.tutorName}" style="max-height: 60px; max-width: 200px;" />`
        : `<div style="color: #FFFFFF; font-size: 24px; font-weight: bold;">${props.tutorName}</div>`
      }
    </div>

    <div style="background-color: #000; color: #FFFFFF; padding: 15px; text-align: center; font-size: 18px; font-weight: bold; border-top: 3px solid #FFFFFF;">
      Devi svolgere un corso obbligatorio
    </div>

    <div style="padding: 30px; background-color: #FFFFFF;">
      <div style="background-color: #000; color: #FFFFFF; padding: 12px 20px; margin-bottom: 25px; font-size: 16px; font-weight: bold;">
        Licenza per il corso: ${props.courseName}
      </div>

      <div style="color: #000; font-size: 15px; line-height: 1.8;">
        <p style="margin: 0 0 15px 0;">
          <strong>Buongiorno ${props.userName},</strong>
        </p>
        
        <p style="margin: 0 0 10px 0;">
          Sei stato iscritto al seguente corso: <strong>${props.courseName}</strong>
        </p>
        
        <p style="margin: 0 0 10px 0;">
          Potrai iniziare a partire dal giorno: <strong>${props.startDate}</strong>
        </p>
        
        <p style="margin: 0 0 10px 0;">
          E terminare entro il giorno: <strong>${props.endDate}</strong>
        </p>
        
        ${props.referentName ? `
        <p style="margin: 0 0 20px 0;">
          Il tuo referente per questo corso è: 
          <a href="mailto:${props.referentEmail}" style="color: #000; font-weight: bold;">
            ${props.referentName} - ${props.referentEmail}
          </a>
        </p>
        ` : ''}
      </div>
    </div>

    <div style="background-color: #000; padding: 25px; text-align: center; border-top: 3px solid #FFFFFF;">
      <p style="color: #FFFFFF; margin: 0 0 20px 0; font-size: 15px;">
        Per accedere al corso clicca su avvia corso e<br />
        inserisci il tuo nome utente in questo modo:
      </p>
      
      <div style="background-color: #FFFFFF; color: #000; padding: 15px 30px; font-size: 20px; font-weight: bold; display: inline-block; margin-bottom: 25px;">
        ${props.username}
      </div>
      
      <p style="color: #FFFFFF; margin: 0 0 15px 0; font-size: 14px;">
        Se vuoi avviare il corso clicca qui
      </p>
      
      <a href="${props.courseUrl || '#'}" style="display: inline-block; background-color: #22C55E; color: #FFFFFF; padding: 15px 50px; font-size: 18px; font-weight: bold; text-decoration: none; border: 2px solid #22C55E; border-radius: 4px;">
        AVVIA CORSO
      </a>
    </div>

    <div style="background-color: #FFFFFF; padding: 30px; text-align: center; border-top: 3px solid #000;">
      <a href="${props.instructionsUrl || '#'}" style="color: #000; font-size: 18px; font-weight: bold; text-decoration: underline;">
        ISTRUZIONI PER IL CORSO
      </a>
    </div>

    <div style="background-color: #FFFFFF; padding: 25px 30px; color: #000; font-size: 14px; line-height: 1.8; border-top: 2px solid #000;">
      <p style="margin: 0 0 15px 0;">
        Il tuo referente per questo corso può essere contattato per E-Mail scrivendo a <strong>${props.tutorName}</strong>
        ${props.tutorAddress ? `<br />${props.tutorAddress}` : ''}
        ${props.tutorEmail ? `<br />E-Mail: <a href="mailto:${props.tutorEmail}" style="color: #000;">${props.tutorEmail}</a>` : ''}
      </p>
      
      <p style="margin: 0 0 15px 0;">
        Al termine del corso potrai scaricare il tracciato di avvenuta formazione
      </p>
      
      <p style="margin: 0 0 15px 0;">
        <strong>IL CORSO PUÒ ESSERE INTERROTTO</strong> con il pulsante ESCI in alto a sinistra. Riaccendendo al corso questo ripartirà dall'ultimo punto utile.
      </p>
      
      <p style="margin: 0 0 15px 0;">
        <strong>PAUSA:</strong> puoi fermare temporaneamente il corso con il pulsante Ferma, ma solo per 30 secondi, terminati i quali il corso viene interrotto.
      </p>
      
      <p style="margin: 0;">
        <strong>ASSISTENZA TECNICA:</strong> In ogni momento è possibile inviare una segnalazione anche tramite mail dal pulsante Richiedi Assistenza oppure scrivete a 
        ${props.tutorEmail ? `<a href="mailto:${props.tutorEmail}" style="color: #000;">${props.tutorEmail}</a>` : ''}
      </p>
    </div>

    <div style="background-color: #000; padding: 15px; text-align: center;">
      <p style="color: #FFFFFF; margin: 0; font-size: 14px; font-weight: bold;">
        ${props.tutorName}${props.tutorAddress ? ` - ${props.tutorAddress}` : ''}
      </p>
    </div>
    ${props.trackingId && props.baseUrl ? `<img src="${props.baseUrl}/api/email-track/${props.trackingId}" width="1" height="1" style="display:none;" alt="" />` : ''}
  </div>
</body>
</html>
  `;
}
