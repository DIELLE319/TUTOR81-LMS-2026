const mysql = require('mysql2/promise');
const { getOvhDbConfig } = require('./ovh-db-config.cjs');

async function main() {
  const connection = await mysql.createConnection(getOvhDbConfig());

  const [cols] = await connection.execute(`DESCRIBE companies`);
  console.log('STRUTTURA companies:');
  cols.forEach(c => console.log(`${c.Field} - ${c.Type}`));

  await connection.end();
}

main().catch(console.error);
