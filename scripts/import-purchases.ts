import { db } from '../server/db';
import * as schema from '../shared/schema';
import * as fs from 'fs';
import * as path from 'path';

function parseCSVLine(line: string): string[] {
  const result: string[] = [];
  let current = '';
  let inQuotes = false;
  
  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    
    if (char === '"') {
      inQuotes = !inQuotes;
    } else if (char === ',' && !inQuotes) {
      result.push(current.trim());
      current = '';
    } else {
      current += char;
    }
  }
  result.push(current.trim());
  
  return result;
}

async function importPurchases() {
  const purchasesPath = path.join(process.cwd(), 'attached_assets/tutors_purchases-2_1769814503769.csv');
  const usersPath = path.join(process.cwd(), 'attached_assets/users_1769813021731.csv');
  
  const purchasesContent = fs.readFileSync(purchasesPath, 'utf-8');
  const usersContent = fs.readFileSync(usersPath, 'utf-8');
  
  const purchaseLines = purchasesContent.split('\n');
  const userLines = usersContent.split('\n');
  
  console.log(`Found ${purchaseLines.length} purchase lines`);
  console.log(`Found ${userLines.length} user lines`);
  
  const userToCompanyMap = new Map<string, number>();
  for (let i = 1; i < userLines.length; i++) {
    const line = userLines[i].trim();
    if (!line) continue;
    const parts = line.split(';');
    if (parts.length < 17) continue;
    const userId = parts[0];
    const companyId = parseInt(parts[16]) || null;
    if (userId && companyId) {
      userToCompanyMap.set(userId, companyId);
    }
  }
  console.log(`User to company mappings: ${userToCompanyMap.size}`);
  
  const existingCompanyIds = new Set<number>();
  const existingProjectIds = new Set<number>();
  const existingUserIds = new Set<string>();
  
  const companies = await db.select({ id: schema.companies.id }).from(schema.companies);
  companies.forEach(c => existingCompanyIds.add(c.id));
  
  const projects = await db.select({ id: schema.learningProjects.id }).from(schema.learningProjects);
  projects.forEach(p => existingProjectIds.add(p.id));
  
  const users = await db.select({ id: schema.users.id }).from(schema.users);
  users.forEach(u => existingUserIds.add(u.id));
  
  console.log(`Existing companies: ${existingCompanyIds.size}`);
  console.log(`Existing projects: ${existingProjectIds.size}`);
  
  let imported = 0;
  let skippedNoTutor = 0;
  let skippedNoClient = 0;
  let errors = 0;

  for (let i = 1; i < purchaseLines.length; i++) {
    const line = purchaseLines[i].trim();
    if (!line) continue;
    
    const parts = parseCSVLine(line);
    if (parts.length < 8) continue;
    
    const tutorIdRaw = parts[1] || null;
    const customerCompanyIdRaw = parseInt(parts[2] || '0') || null;
    const userCompanyRefRaw = parts[3] || null;
    const learningProjectIdRaw = parseInt(parts[4] || '0') || null;
    const qta = parseInt(parts[5] || '1') || 1;
    const price = parts[6] || '0';
    const creationDate = parts[7] || null;
    
    if (i < 4) {
      console.log(`Line ${i}: tutor="${tutorIdRaw}", customer=${customerCompanyIdRaw}, project=${learningProjectIdRaw}`);
    }
    
    let tutorCompanyId: number | null = null;
    if (tutorIdRaw) {
      const parsedTutorId = parseInt(tutorIdRaw);
      if (existingCompanyIds.has(parsedTutorId)) {
        tutorCompanyId = parsedTutorId;
      } else if (userToCompanyMap.has(tutorIdRaw)) {
        const mappedCompanyId = userToCompanyMap.get(tutorIdRaw)!;
        if (existingCompanyIds.has(mappedCompanyId)) {
          tutorCompanyId = mappedCompanyId;
        }
      }
    }
    
    if (!tutorCompanyId) {
      skippedNoTutor++;
      continue;
    }
    
    let customerCompanyId: number | null = null;
    if (customerCompanyIdRaw) {
      if (existingCompanyIds.has(customerCompanyIdRaw)) {
        customerCompanyId = customerCompanyIdRaw;
      } else {
        skippedNoClient++;
        continue;
      }
    }
    
    let learningProjectId: number | null = null;
    if (learningProjectIdRaw && existingProjectIds.has(learningProjectIdRaw)) {
      learningProjectId = learningProjectIdRaw;
    }
    
    let userCompanyRef: string | null = null;
    if (userCompanyRefRaw && existingUserIds.has(userCompanyRefRaw)) {
      userCompanyRef = userCompanyRefRaw;
    }
    
    try {
      await db.insert(schema.tutorsPurchases).values({
        tutorId: tutorCompanyId,
        customerCompanyId,
        userCompanyRef,
        learningProjectId,
        qta,
        price,
        createdAt: creationDate ? new Date(creationDate) : new Date(),
        status: 'active',
      });
      imported++;
      
      if (imported % 2000 === 0) {
        console.log(`Imported ${imported} purchases...`);
      }
    } catch (err) {
      errors++;
      if (errors < 3) {
        console.error(`Error on line ${i}:`, err);
      }
    }
  }
  
  console.log(`\nImport complete!`);
  console.log(`Imported: ${imported}`);
  console.log(`Skipped (no tutor mapping): ${skippedNoTutor}`);
  console.log(`Skipped (no client): ${skippedNoClient}`);
  console.log(`Errors: ${errors}`);
  
  process.exit(0);
}

importPurchases().catch(console.error);
