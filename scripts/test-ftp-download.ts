import * as ftp from "basic-ftp";
import { Writable } from "stream";

async function testDownload() {
  const client = new ftp.Client();
  
  try {
    await client.access({
      host: "135.125.205.19",
      user: process.env.FTP_USERNAME!,
      password: process.env.FTP_PASSWORD!,
      secure: false,
    });
    
    console.log("Connected to FTP");
    
    const testId = 16652;
    const remotePath = `/media/media/attestati/attestato_licenza_${testId}.pdf`;
    
    console.log(`Testing download: ${remotePath}`);
    
    const chunks: Buffer[] = [];
    const writable = new Writable({
      write(chunk: Buffer, encoding: string, callback: () => void) {
        chunks.push(chunk);
        callback();
      }
    });
    
    await client.downloadTo(writable, remotePath);
    
    const fileBuffer = Buffer.concat(chunks);
    console.log(`Downloaded ${fileBuffer.length} bytes`);
    console.log(`First bytes: ${fileBuffer.slice(0, 10).toString('hex')}`);
    
    // Check if it's a PDF (starts with %PDF)
    if (fileBuffer.toString('utf8', 0, 4) === '%PDF') {
      console.log("File is a valid PDF!");
    } else {
      console.log("Warning: File doesn't look like a PDF");
    }
    
  } catch (err) {
    console.error("Error:", err);
  } finally {
    client.close();
  }
}

testDownload();
