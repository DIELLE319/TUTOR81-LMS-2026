import express from "express";
import path from "path";
import fs from "fs";

export function serveStatic(app: express.Express) {
  const distPath = path.resolve(process.cwd(), "dist", "public");
  if (!fs.existsSync(distPath)) {
    console.warn("[static] dist/public not found, skipping static serving");
    return;
  }
  // Hashed assets: cache forever
  app.use("/assets", express.static(path.join(distPath, "assets"), { maxAge: "1y", immutable: true }));
  // Everything else (index.html, favicon, etc.): no cache
  app.use(express.static(distPath, { maxAge: 0 }));
  app.get("{*path}", (_req, res) => {
    res.setHeader("Cache-Control", "no-cache, no-store, must-revalidate");
    res.sendFile(path.join(distPath, "index.html"));
  });
}
