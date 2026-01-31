import { db } from '../server/db';
import * as schema from '../shared/schema';
import * as fs from 'fs';

async function importUsers() {
  const csv = fs.readFileSync('./attached_assets/users_1769819796930.csv', 'utf-8');
  const lines = csv.split('\n').slice(1);
  
  let imported = 0;
  let skipped = 0;
  const batchSize = 500;
  let batch: any[] = [];
  
  for (const line of lines) {
    if (!line.trim()) continue;
    
    const parts = line.split(';');
    const [id, name, surname, born_date, creation_date, creator_id, address, cap, code, email, suspended, password, force_reset, deleted, last_access_date, username, company_id, language_id, tax_code, business_function_id, job, phone, department, role] = parts;
    
    if (deleted === '1') {
      skipped++;
      continue;
    }
    
    if (!email || email.trim() === '') {
      skipped++;
      continue;
    }
    
    batch.push({
      id: id,
      email: email.trim(),
      firstName: name?.trim() || null,
      lastName: surname?.trim() || null,
      role: parseInt(role) || 0,
      idcompany: company_id ? parseInt(company_id) : null,
      fiscalCode: tax_code?.trim() || null,
      phone: phone?.trim() || null,
      createdAt: creation_date && creation_date !== '0000-00-00 00:00:00' ? new Date(creation_date) : new Date(),
    });
    
    if (batch.length >= batchSize) {
      try {
        await db.insert(schema.users).values(batch).onConflictDoNothing();
        imported += batch.length;
        console.log(`Imported ${imported} users...`);
      } catch (e: any) {
        console.error('Batch error:', e.message);
      }
      batch = [];
    }
  }
  
  if (batch.length > 0) {
    try {
      await db.insert(schema.users).values(batch).onConflictDoNothing();
      imported += batch.length;
    } catch (e: any) {
      console.error('Final batch error:', e.message);
    }
  }
  
  console.log(`\nDone! Imported: ${imported}, Skipped: ${skipped}`);
  process.exit(0);
}

importUsers();
