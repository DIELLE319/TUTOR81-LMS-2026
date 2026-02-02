import { Octokit } from '@octokit/rest';

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
  const accessToken = await getAccessToken();
  const octokit = new Octokit({ auth: accessToken });
  
  const { data: user } = await octokit.users.getAuthenticated();
  console.log('Logged in as:', user.login);
  
  try {
    const { data: repo } = await octokit.repos.createForAuthenticatedUser({
      name: 'LMS-TUTOR81-2026',
      description: 'LMS TUTOR81 2026 - E-Learning Management Platform',
      private: true,
      auto_init: false
    });
    console.log('Repository created:', repo.html_url);
  } catch (error: any) {
    if (error.status === 422) {
      console.log('Repository already exists');
      const { data: repo } = await octokit.repos.get({
        owner: user.login,
        repo: 'LMS-TUTOR81-2026'
      });
      console.log('Repository URL:', repo.html_url);
    } else {
      throw error;
    }
  }
}

main().catch(console.error);
