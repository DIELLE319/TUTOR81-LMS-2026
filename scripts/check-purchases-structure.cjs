const mysql = require('mysql2/promise');

async function main() {
  const connection = await mysql.createConnection({
    host: '135.125.205.19',
    port: 3306,
    user: 'pro_tutor81',
    password: 'hpm0?7C3',
    database: 'pro_tutor81'
  });

  const [cols] = await connection.execute(`DESCRIBE tutors_purchases`);
  console.log('STRUTTURA tutors_purchases:');
  cols.forEach(c => console.log(`${c.Field} - ${c.Type} - ${c.Null} - ${c.Key}`));

  await connection.end();
}

main().catch(console.error);
