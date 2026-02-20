import "dotenv/config";
import express from "express";
import { createServer } from "http";
import path from "path";
import fs from "fs";
import { setupAuth, registerAuthRoutes } from "./auth";
import { registerAllRoutes } from "./routes";

const app = express();
const server = createServer(app);
const isDev = process.env.NODE_ENV !== "production";

app.use(express.json({ limit: "10mb" }));
app.use(express.urlencoded({ extended: true }));

// Request logger
app.use((req, res, next) => {
  const start = Date.now();
  res.on("finish", () => {
    const ms = Date.now() - start;
    if (req.path.startsWith("/api/")) {
      const body = res.statusCode >= 400 ? JSON.stringify(res.getHeaders()) : "";
      console.log(`${new Date().toLocaleTimeString()} [express] ${req.method} ${req.path} ${res.statusCode} in ${ms}ms ${body ? `:: ${body}` : ""}`);
    }
  });
  next();
});

// Uploads directory
const uploadsDir = process.env.UPLOADS_DIR || path.join(process.cwd(), "uploads");
if (fs.existsSync(uploadsDir)) {
  app.use("/uploads", express.static(uploadsDir));
  console.log("[uploads] Serving from", uploadsDir);
}

(async () => {
  // Auth
  await setupAuth(app);
  registerAuthRoutes(app);

  // API routes
  registerAllRoutes(app);

  // Frontend
  if (isDev) {
    const { setupVite } = await import("./vite");
    await setupVite(server, app);
  } else {
    const { serveStatic } = await import("./static");
    serveStatic(app);
  }

  const port = parseInt(process.env.PORT || "5000", 10);
  server.listen(port, "0.0.0.0", () => {
    console.log(`${new Date().toLocaleTimeString()} [express] serving on port ${port}`);
  });
})();
