const mysql = require('mysql2/promise');
const { getOvhDbConfig } = require('./ovh-db-config.cjs');

async function main() {
  const connection = await mysql.createConnection(getOvhDbConfig());

  const [cols] = await connection.execute(`DESCRIBE tutors_purchases`);
  console.log('STRUTTURA tutors_purchases:');
  cols.forEach(c => console.log(`${c.Field} - ${c.Type} - ${c.Null} - ${c.Key}`));

  await connection.end();
}

main().catch(console.error);
