interface CourseEmailTemplateProps {
  tutorLogo?: string;
  tutorName: string;
  courseName: string;
  userName: string;
  userEmail: string;
  startDate: string;
  endDate: string;
  referentName?: string;
  referentEmail?: string;
  username: string;
  courseUrl?: string;
}

export default function CourseEmailTemplate({
  tutorLogo,
  tutorName,
  courseName,
  userName,
  userEmail,
  startDate,
  endDate,
  referentName,
  referentEmail,
  username,
  courseUrl = '#'
}: CourseEmailTemplateProps) {
  return (
    <div style={{ 
      fontFamily: 'Arial, sans-serif', 
      maxWidth: '600px', 
      margin: '0 auto',
      backgroundColor: '#FCD34D',
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
            color: '#FCD34D', 
            fontSize: '24px', 
            fontWeight: 'bold' 
          }}>
            {tutorName}
          </div>
        )}
      </div>

      <div style={{ 
        backgroundColor: '#000', 
        color: '#FCD34D', 
        padding: '15px', 
        textAlign: 'center' as const,
        fontSize: '18px',
        fontWeight: 'bold',
        borderTop: '3px solid #FCD34D'
      }}>
        Devi svolgere un corso obbligatorio
      </div>

      <div style={{ padding: '30px', backgroundColor: '#FCD34D' }}>
        <div style={{ 
          backgroundColor: '#000', 
          color: '#FCD34D', 
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
        borderTop: '3px solid #FCD34D'
      }}>
        <p style={{ 
          color: '#FCD34D', 
          margin: '0 0 20px 0',
          fontSize: '15px'
        }}>
          Per accedere al corso clicca su avvia corso e<br />
          inserisci il tuo nome utente in questo modo:
        </p>
        
        <div style={{ 
          backgroundColor: '#FCD34D', 
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
          color: '#FCD34D', 
          margin: '0 0 15px 0',
          fontSize: '14px'
        }}>
          Se vuoi avviare il corso clicca qui
        </p>
        
        <a 
          href={courseUrl}
          style={{
            display: 'inline-block',
            backgroundColor: '#FCD34D',
            color: '#000',
            padding: '15px 50px',
            fontSize: '18px',
            fontWeight: 'bold',
            textDecoration: 'none',
            border: '2px solid #FCD34D'
          }}
        >
          AVVIA CORSO
        </a>
      </div>

      <div style={{ 
        backgroundColor: '#000', 
        padding: '15px', 
        textAlign: 'center' as const,
        borderTop: '2px solid #FCD34D'
      }}>
        <p style={{ 
          color: '#666', 
          margin: '0',
          fontSize: '12px'
        }}>
          Questa email è stata inviata automaticamente da {tutorName}
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
  <div style="max-width: 600px; margin: 0 auto; background-color: #FCD34D; border: 2px solid #000;">
    
    <div style="background-color: #000; padding: 20px; text-align: center;">
      ${props.tutorLogo 
        ? `<img src="${props.tutorLogo}" alt="${props.tutorName}" style="max-height: 60px; max-width: 200px;" />`
        : `<div style="color: #FCD34D; font-size: 24px; font-weight: bold;">${props.tutorName}</div>`
      }
    </div>

    <div style="background-color: #000; color: #FCD34D; padding: 15px; text-align: center; font-size: 18px; font-weight: bold; border-top: 3px solid #FCD34D;">
      Devi svolgere un corso obbligatorio
    </div>

    <div style="padding: 30px; background-color: #FCD34D;">
      <div style="background-color: #000; color: #FCD34D; padding: 12px 20px; margin-bottom: 25px; font-size: 16px; font-weight: bold;">
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

    <div style="background-color: #000; padding: 25px; text-align: center; border-top: 3px solid #FCD34D;">
      <p style="color: #FCD34D; margin: 0 0 20px 0; font-size: 15px;">
        Per accedere al corso clicca su avvia corso e<br />
        inserisci il tuo nome utente in questo modo:
      </p>
      
      <div style="background-color: #FCD34D; color: #000; padding: 15px 30px; font-size: 20px; font-weight: bold; display: inline-block; margin-bottom: 25px;">
        ${props.username}
      </div>
      
      <p style="color: #FCD34D; margin: 0 0 15px 0; font-size: 14px;">
        Se vuoi avviare il corso clicca qui
      </p>
      
      <a href="${props.courseUrl || '#'}" style="display: inline-block; background-color: #FCD34D; color: #000; padding: 15px 50px; font-size: 18px; font-weight: bold; text-decoration: none; border: 2px solid #FCD34D;">
        AVVIA CORSO
      </a>
    </div>

    <div style="background-color: #000; padding: 15px; text-align: center; border-top: 2px solid #FCD34D;">
      <p style="color: #666; margin: 0; font-size: 12px;">
        Questa email è stata inviata automaticamente da ${props.tutorName}
      </p>
    </div>
  </div>
</body>
</html>
  `;
}
