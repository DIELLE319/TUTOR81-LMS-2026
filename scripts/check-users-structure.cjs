const mysql = require('mysql2/promise');

async function main() {
  const connection = await mysql.createConnection({
    host: '135.125.205.19',
    port: 3306,
    user: 'pro_tutor81',
    password: 'hpm0?7C3',
    database: 'pro_tutor81'
  });

  const [cols] = await connection.execute(`DESCRIBE users`);
  console.log('STRUTTURA users:');
  cols.forEach(c => console.log(`${c.Field} - ${c.Type}`));

  // Esempio dati
  const [sample] = await connection.execute(`
    SELECT id, company_id, creator_id, code, name, surname, email, ruoli
    FROM users 
    WHERE ruoli = 0
    LIMIT 5
  `);
  console.log('\nEsempio corsisti (ruoli=0):');
  sample.forEach(s => console.log(`ID ${s.id}: ${s.name} ${s.surname} - Company ${s.company_id} - Creator ${s.creator_id} - Code: ${s.code}`));

  await connection.end();
}

main().catch(console.error);
