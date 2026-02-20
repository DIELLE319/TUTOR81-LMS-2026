import type { Express } from "express";
import { createServer, type Server } from "http";
import { setupAuth, registerAuthRoutes } from "../auth";
import { registerTutorsRoutes } from "./tutors";
import { registerCompaniesRoutes } from "./companies";
import { registerStudentsRoutes } from "./students";
import { registerCoursesRoutes } from "./courses";
import { registerEnrollmentsRoutes } from "./enrollments";
import { registerCertificatesRoutes } from "./certificates";
import { registerInvoicesRoutes } from "./invoices";
import { registerPlayerRoutes } from "./player";

export async function registerRoutes(
  httpServer: Server,
  app: Express
): Promise<Server> {
  const hasAuthEnv = Boolean(
    process.env.SESSION_SECRET && process.env.DATABASE_URL,
  );

  if (hasAuthEnv) {
    await setupAuth(app);
    registerAuthRoutes(app);
  } else {
    console.warn("[auth] Missing SESSION_SECRET / DATABASE_URL. Auth routes are disabled.");
    app.get("/api/auth/user", (_req, res) => res.json(null));
  }

  // Health check
  app.get("/api/health", (_req, res) => {
    res.json({ ok: true });
  });

  // Marker page
  app.get("/admin-login", (req, res) => {
    const host = req.headers.host || req.hostname;
    res
      .status(200)
      .set({ "Content-Type": "text/html; charset=utf-8", "Cache-Control": "no-store" })
      .send(`<!doctype html>
<html lang="it">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tutor81 LMS</title>
    <style>
      :root { --bg: #FFD400; --fg: #111827; --card: rgba(255,255,255,.78); }
      body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--bg); color:var(--fg); font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial; }
      .card { width:min(860px, calc(100vw - 32px)); background:var(--card); border:1px solid rgba(0,0,0,.12); border-radius:18px; padding:24px; box-shadow: 0 20px 60px rgba(0,0,0,.18); }
      h1 { margin:0 0 8px; font-size:28px; letter-spacing:-0.02em; }
      p { margin:0 0 16px; opacity:.9; }
      .row { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
      a { display:inline-block; padding:10px 12px; border-radius:12px; background:#111827; color:#fff; text-decoration:none; font-weight:700; }
      a.secondary { background: rgba(17,24,39,.08); color:#111827; border:1px solid rgba(17,24,39,.18); }
      code { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; background: rgba(17,24,39,.08); padding:2px 6px; border-radius:8px; }
      .meta { margin-top:14px; font-size:13px; opacity:.75; line-height:1.35; }
    </style>
  </head>
  <body>
    <div class="card">
      <h1>Tutor81 LMS v2</h1>
      <p>Backend modulare — Reset totale</p>
      <div class="row">
        <a href="/">Apri App</a>
        <a class="secondary" href="/api/health">/api/health</a>
        <a class="secondary" href="/api/auth/user">/api/auth/user</a>
      </div>
      <div class="meta">
        Host: <code>${String(host).replace(/</g, "&lt;")}</code><br/>
        NODE_ENV: <code>${String(process.env.NODE_ENV || "").replace(/</g, "&lt;")}</code>
      </div>
    </div>
  </body>
</html>`);
  });

  // Email tracking pixel
  app.get("/api/email-track/:id", async (req, res) => {
    res.setHeader("Content-Type", "image/gif");
    res.setHeader("Cache-Control", "no-cache, no-store, must-revalidate");
    const pixel = Buffer.from("R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7", "base64");
    res.send(pixel);
  });

  // Export endpoint
  app.get("/api/export/tutor-gerarchia", async (_req, res) => {
    res.json({ message: "Export endpoint - to be implemented" });
  });

  // Register all route modules
  registerTutorsRoutes(app);
  registerCompaniesRoutes(app);
  registerStudentsRoutes(app);
  registerCoursesRoutes(app);
  registerEnrollmentsRoutes(app);
  registerCertificatesRoutes(app);
  registerInvoicesRoutes(app);
  registerPlayerRoutes(app);

  return httpServer;
}
