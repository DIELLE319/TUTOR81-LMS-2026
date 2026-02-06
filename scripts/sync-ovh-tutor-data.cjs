const mysql = require('mysql2/promise');
const { Pool } = require('pg');

const { getOvhDbConfig } = require('./ovh-db-config.cjs');

async function syncTutorData() {
  const ovhConn = await mysql.createConnection(getOvhDbConfig());
  const pgPool = new Pool({ connectionString: process.env.DATABASE_URL });

  try {
    console.log('Connesso a OVH MySQL e Replit PostgreSQL');

    // Query tutor → admin → clienti
    const [rows] = await ovhConn.execute(`
      SELECT 
        tutor.id as tutor_id,
        tutor.business_name as tutor_name,
        tutor.email as tutor_email,
        tutor.telephone as tutor_phone,
        tutor.address as tutor_address,
        admin.id as admin_user_id,
        CONCAT(admin.name, ' ', admin.surname) as admin_name,
        admin.email as admin_email,
        admin.role as admin_role,
        client.id as client_id,
        client.business_name as client_name,
        client.email as client_email,
        client.telephone as client_phone,
        client.address as client_address,
        client.vat as client_vat
      FROM companies tutor
      JOIN users admin ON admin.company_id = tutor.id AND admin.role >= 1 AND admin.deleted = 0
      LEFT JOIN companies client ON client.owner_user_id = admin.id AND client.is_tutor = 0 AND client.deleted = 0
      WHERE tutor.is_tutor = 1 AND tutor.deleted = 0
      ORDER BY tutor.id, admin.id, client.id
    `);

    console.log(`Trovate ${rows.length} righe di relazione tutor-admin-cliente`);

    // Raggruppa per tutor
    const tutorMap = new Map();
    for (const row of rows) {
      if (!tutorMap.has(row.tutor_id)) {
        tutorMap.set(row.tutor_id, {
          ovhId: row.tutor_id,
          name: row.tutor_name,
          email: row.tutor_email,
          phone: row.tutor_phone,
          address: row.tutor_address,
          admins: new Map(),
          clients: new Map()
        });
      }
      
      const tutor = tutorMap.get(row.tutor_id);
      
      if (row.admin_user_id && !tutor.admins.has(row.admin_user_id)) {
        tutor.admins.set(row.admin_user_id, {
          ovhUserId: row.admin_user_id,
          name: row.admin_name,
          email: row.admin_email,
          role: row.admin_role
        });
      }
      
      if (row.client_id && !tutor.clients.has(row.client_id)) {
        tutor.clients.set(row.client_id, {
          ovhId: row.client_id,
          name: row.client_name,
          email: row.client_email,
          phone: row.client_phone,
          address: row.client_address,
          vat: row.client_vat,
          ownerAdminId: row.admin_user_id
        });
      }
    }

    console.log(`\nTotale tutor: ${tutorMap.size}`);

    // Mostra riepilogo
    for (const [id, tutor] of tutorMap) {
      console.log(`\nTutor ${id}: ${tutor.name}`);
      console.log(`  Admin: ${tutor.admins.size}`);
      console.log(`  Clienti: ${tutor.clients.size}`);
    }

  } catch (error) {
    console.error('Errore:', error);
  } finally {
    await ovhConn.end();
    await pgPool.end();
  }
}

syncTutorData();
