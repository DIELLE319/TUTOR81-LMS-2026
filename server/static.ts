import express from "express";
import path from "path";
import fs from "fs";

export function serveStatic(app: express.Express) {
  const distPath = path.resolve(process.cwd(), "dist", "public");
  if (!fs.existsSync(distPath)) {
    console.warn("[static] dist/public not found, skipping static serving");
    return;
  }
  app.use(express.static(distPath, { maxAge: "1y", immutable: true }));
  app.get("*", (_req, res) => {
    res.sendFile(path.join(distPath, "index.html"));
  });
}
