import * as ftp from "basic-ftp";

async function exploreFTP() {
  const client = new ftp.Client();
  client.ftp.verbose = false;
  const host = process.env.FTP_HOST || "135.125.205.19";
  
  try {
    await client.access({
      host,
      user: process.env.FTP_USERNAME!,
      password: process.env.FTP_PASSWORD!,
      secure: false,
    });
    
    console.log("=== Connected to FTP ===\n");
    
    const attestatiPath = "/media/media/attestati";
    
    const list = await client.list(attestatiPath);
    console.log(`${attestatiPath} - Totale: ${list.length} files\n`);
    
    // Count PDF files
    const pdfFiles = list.filter(f => f.name.endsWith('.pdf'));
    console.log(`PDF files: ${pdfFiles.length}`);
    
    // Show first 100 files
    console.log(`\nPrimi 100 files:`);
    for (const item of pdfFiles.slice(0, 100)) {
      const sizeKB = Math.round((item.size || 0) / 1024);
      console.log(`  ${item.name} (${sizeKB}KB)`);
    }
    
    // Extract IDs to check range
    const ids = pdfFiles
      .map(f => {
        const match = f.name.match(/attestato_licenza_(\d+)\.pdf/);
        return match ? parseInt(match[1]) : null;
      })
      .filter(id => id !== null) as number[];
    
    if (ids.length > 0) {
      console.log(`\n--- Statistiche ID ---`);
      console.log(`ID minimo: ${Math.min(...ids)}`);
      console.log(`ID massimo: ${Math.max(...ids)}`);
    }
    
  } catch (err) {
    console.error("FTP Error:", err);
  } finally {
    client.close();
  }
}

exploreFTP();
