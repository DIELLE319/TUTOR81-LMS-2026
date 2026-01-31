import * as ftp from "basic-ftp";

async function exploreFTP() {
  const client = new ftp.Client();
  client.ftp.verbose = false;
  
  try {
    await client.access({
      host: "135.125.205.19",
      user: process.env.FTP_USERNAME!,
      password: process.env.FTP_PASSWORD!,
      secure: false,
    });
    
    console.log("=== Connected to FTP ===\n");
    
    // Explore attestati folder
    const attestatiPath = "/media/media/attestati";
    
    try {
      const list = await client.list(attestatiPath);
      console.log(`${attestatiPath} (${list.length} items):`);
      
      // Show first 50 items
      for (const item of list.slice(0, 50)) {
        const type = item.isDirectory ? "[DIR]" : "[FILE]";
        const size = item.size ? ` (${Math.round(item.size/1024)}KB)` : "";
        console.log(`  ${type} ${item.name}${size}`);
      }
      if (list.length > 50) {
        console.log(`  ... and ${list.length - 50} more`);
      }
      
      // If first item is a directory, explore it
      const firstDir = list.find(i => i.isDirectory);
      if (firstDir) {
        const subPath = `${attestatiPath}/${firstDir.name}`;
        const subList = await client.list(subPath);
        console.log(`\n${subPath} (${subList.length} items):`);
        for (const item of subList.slice(0, 20)) {
          const type = item.isDirectory ? "[DIR]" : "[FILE]";
          console.log(`  ${type} ${item.name}`);
        }
      }
      
      // Show PDF file pattern
      const pdfFiles = list.filter(f => f.name.endsWith('.pdf'));
      if (pdfFiles.length > 0) {
        console.log(`\nPDF file examples:`);
        for (const pdf of pdfFiles.slice(0, 10)) {
          console.log(`  ${pdf.name}`);
        }
      }
      
    } catch (e: any) {
      console.log(`Error: ${e.message}`);
    }
    
  } catch (err) {
    console.error("FTP Error:", err);
  } finally {
    client.close();
  }
}

exploreFTP();
