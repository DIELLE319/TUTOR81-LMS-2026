import "dotenv/config";
// Forza la lettura .env anche in produzione
import dotenv from "dotenv";
dotenv.config();
import express, { type Request, Response, NextFunction } from "express";
import { registerRoutes } from "./routes";
import { serveStatic } from "./static";
import { createServer } from "http";

const app = express();
const httpServer = createServer(app);

declare module "http" {
  interface IncomingMessage {
    rawBody: unknown;
  }
}

app.use(
  express.json({
    verify: (req, _res, buf) => {
      req.rawBody = buf;
    },
  }),
);

app.use(express.urlencoded({ extended: false }));

// Optional safety guard: block mutating API requests when running in read-only mode.
// Useful if you temporarily point DATABASE_URL to a production DB and want to prevent
// accidental writes from the web UI.
const dbReadOnly = (process.env.DB_READONLY || "").toLowerCase();
if (dbReadOnly === "1" || dbReadOnly === "true" || dbReadOnly === "yes") {
  app.use((req, res, next) => {
    if (!req.path.startsWith("/api")) return next();
    if (req.path === "/api/health") return next();

    const method = req.method.toUpperCase();
    const isMutating = method === "POST" || method === "PUT" || method === "PATCH" || method === "DELETE";
    if (!isMutating) return next();

    return res.status(503).json({
      error: "Server in read-only mode (DB_READONLY=1). Mutating requests are disabled.",
    });
  });
}

// Optional HTTP Basic Auth gate (useful for staging/VPS access control).
// Enable by setting BASIC_AUTH_USER and BASIC_AUTH_PASSWORD in the environment.
const basicAuthUser = process.env.BASIC_AUTH_USER;
const basicAuthPassword = process.env.BASIC_AUTH_PASSWORD;
if (basicAuthUser && basicAuthPassword) {
  app.use((req, res, next) => {
    // Allow health checks without auth if you want to monitor uptime.
    if (req.path === "/api/health") return next();

    const authHeader = req.headers.authorization;
    if (!authHeader || !authHeader.startsWith("Basic ")) {
      res.setHeader("WWW-Authenticate", 'Basic realm="Tutor81 LMS"');
      return res.status(401).send("Authentication required");
    }

    const raw = Buffer.from(authHeader.slice("Basic ".length), "base64").toString("utf8");
    const idx = raw.indexOf(":");
    const user = idx >= 0 ? raw.slice(0, idx) : raw;
    const pass = idx >= 0 ? raw.slice(idx + 1) : "";

    if (user === basicAuthUser && pass === basicAuthPassword) return next();

    res.setHeader("WWW-Authenticate", 'Basic realm="Tutor81 LMS"');
    return res.status(401).send("Invalid credentials");
  });
}

// Visual marker route (server-side) to distinguish this deployment from legacy.
// Keep it here (before static + SPA catch-all) so it always wins.
app.get("/admin-login", (req, res) => {
  const host = req.headers.host || req.hostname;
  res
    .status(200)
    .set({
      "Content-Type": "text/html; charset=utf-8",
      "Cache-Control": "no-store",
    })
    .send(`<!doctype html>
<html lang="it">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Tutor81 LMS — marker</title>
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
      <h1>Questa è la nuova <strong>Tutor81 LMS</strong></h1>
      <p>Pagina marker per distinguere Vultr dalla piattaforma legacy.</p>
      <div class="row">
        <a href="/api/health">/api/health</a>
        <a class="secondary" href="/">Home</a>
      </div>
      <div class="meta">
        host: <code>${String(host).replace(/</g, "&lt;")}</code><br/>
        node_env: <code>${String(process.env.NODE_ENV ?? "")}</code>
      </div>
    </div>
  </body>
</html>`);
});

export function log(message: string, source = "express") {
  const formattedTime = new Date().toLocaleTimeString("en-US", {
    hour: "numeric",
    minute: "2-digit",
    second: "2-digit",
    hour12: true,
  });

  console.log(`${formattedTime} [${source}] ${message}`);
}

app.use((req, res, next) => {
  const start = Date.now();
  const path = req.path;
  let capturedJsonResponse: Record<string, any> | undefined = undefined;

  const originalResJson = res.json;
  res.json = function (bodyJson, ...args) {
    capturedJsonResponse = bodyJson;
    return originalResJson.apply(res, [bodyJson, ...args]);
  };

  res.on("finish", () => {
    const duration = Date.now() - start;
    if (path.startsWith("/api")) {
      let logLine = `${req.method} ${path} ${res.statusCode} in ${duration}ms`;
      if (capturedJsonResponse) {
        logLine += ` :: ${JSON.stringify(capturedJsonResponse)}`;
      }

      log(logLine);
    }
  });

  next();
});

(async () => {
  await registerRoutes(httpServer, app);

  app.use((err: any, _req: Request, res: Response, next: NextFunction) => {
    const status = err.status || err.statusCode || 500;
    const message = err.message || "Internal Server Error";

    console.error("Internal Server Error:", err);

    if (res.headersSent) {
      return next(err);
    }

    return res.status(status).json({ message });
  });

  // importantly only setup vite in development and after
  // setting up all the other routes so the catch-all route
  // doesn't interfere with the other routes
  if (process.env.NODE_ENV === "production") {
    serveStatic(app);
  } else {
    const { setupVite } = await import("./vite");
    await setupVite(httpServer, app);
  }

  // ALWAYS serve the app on the port specified in the environment variable PORT
  // Other ports are firewalled. Default to 5000 if not specified.
  // this serves both the API and the client.
  // It is the only port that is not firewalled.
  const port = parseInt(process.env.PORT || "5000", 10);
  httpServer.listen(
    {
      port,
      host: "0.0.0.0",
      reusePort: true,
    },
    () => {
      log(`serving on port ${port}`);
    },
  );
})();
