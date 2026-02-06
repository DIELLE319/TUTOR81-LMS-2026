import express, { type Express } from "express";
import fs from "fs";
import path from "path";

export function serveStatic(app: Express) {
  const distPath = path.resolve(__dirname, "public");
  if (!fs.existsSync(distPath)) {
    throw new Error(
      `Could not find the build directory: ${distPath}, make sure to build the client first`,
    );
  }

  const indexHtmlPath = path.resolve(distPath, "index.html");

  // Serve built assets (and index.html when requesting "/").
  app.use(express.static(distPath, { index: "index.html" }));

  // SPA fallback: for any non-API route that isn't a real static file, return index.html.
  // Using a RegExp avoids path-pattern edge-cases across Express versions.
  app.get(/^\/(?!api\b).*/, (_req, res) => {
    res.sendFile(indexHtmlPath);
  });
}
