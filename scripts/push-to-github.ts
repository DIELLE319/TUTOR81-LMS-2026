import { Octokit } from '@octokit/rest';
import { execSync } from 'child_process';
import * as fs from 'fs';
import * as path from 'path';

let connectionSettings: any;

async function getAccessToken() {
  const hostname = process.env.REPLIT_CONNECTORS_HOSTNAME;
  const xReplitToken = process.env.REPL_IDENTITY 
    ? 'repl ' + process.env.REPL_IDENTITY 
    : process.env.WEB_REPL_RENEWAL 
    ? 'depl ' + process.env.WEB_REPL_RENEWAL 
    : null;

  if (!xReplitToken) throw new Error('X_REPLIT_TOKEN not found');

  connectionSettings = await fetch(
    'https://' + hostname + '/api/v2/connection?include_secrets=true&connector_names=github',
    { headers: { 'Accept': 'application/json', 'X_REPLIT_TOKEN': xReplitToken } }
  ).then(res => res.json()).then(data => data.items?.[0]);

  return connectionSettings?.settings?.access_token || connectionSettings.settings?.oauth?.credentials?.access_token;
}

async function main() {
  const token = await getAccessToken();
  const octokit = new Octokit({ auth: token });
  
  const { data: user } = await octokit.users.getAuthenticated();
  console.log('User:', user.login);
  
  // Configure git remote with token
  const repoUrl = `https://${token}@github.com/${user.login}/tutor81-lms.git`;
  
  try {
    execSync('git remote remove github', { stdio: 'pipe' });
  } catch {}
  
  execSync(`git remote add github "${repoUrl}"`, { stdio: 'inherit' });
  console.log('Remote added');
  
  // Push
  execSync('git push -u github main', { stdio: 'inherit' });
  console.log('Push completed!');
  console.log(`Repository: https://github.com/${user.login}/tutor81-lms`);
}

main().catch(console.error);
