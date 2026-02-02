import { Octokit } from '@octokit/rest';

let connectionSettings: any;

async function getAccessToken() {
  if (connectionSettings && connectionSettings.settings.expires_at && new Date(connectionSettings.settings.expires_at).getTime() > Date.now()) {
    return connectionSettings.settings.access_token;
  }
  
  const hostname = process.env.REPLIT_CONNECTORS_HOSTNAME;
  const xReplitToken = process.env.REPL_IDENTITY 
    ? 'repl ' + process.env.REPL_IDENTITY 
    : process.env.WEB_REPL_RENEWAL 
    ? 'depl ' + process.env.WEB_REPL_RENEWAL 
    : null;

  if (!xReplitToken) {
    throw new Error('X_REPLIT_TOKEN not found');
  }

  connectionSettings = await fetch(
    'https://' + hostname + '/api/v2/connection?include_secrets=true&connector_names=github',
    {
      headers: {
        'Accept': 'application/json',
        'X_REPLIT_TOKEN': xReplitToken
      }
    }
  ).then(res => res.json()).then(data => data.items?.[0]);

  const accessToken = connectionSettings?.settings?.access_token || connectionSettings.settings?.oauth?.credentials?.access_token;

  if (!connectionSettings || !accessToken) {
    throw new Error('GitHub not connected');
  }
  return accessToken;
}

async function main() {
  const accessToken = await getAccessToken();
  const octokit = new Octokit({ auth: accessToken });
  
  const { data: user } = await octokit.users.getAuthenticated();
  console.log('Logged in as:', user.login);
  
  try {
    const { data: repo } = await octokit.repos.createForAuthenticatedUser({
      name: 'tutor81-lms',
      description: 'Tutor81 LMS - E-Learning Management Platform',
      private: true,
      auto_init: false
    });
    console.log('Repository created:', repo.html_url);
    console.log('Clone URL:', repo.clone_url);
  } catch (error: any) {
    if (error.status === 422) {
      console.log('Repository already exists');
      const { data: repo } = await octokit.repos.get({
        owner: user.login,
        repo: 'tutor81-lms'
      });
      console.log('Repository URL:', repo.html_url);
    } else {
      throw error;
    }
  }
}

main().catch(console.error);
