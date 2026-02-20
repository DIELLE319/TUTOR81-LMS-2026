import crypto from "crypto";
import session from "express-session";
import type { Express, RequestHandler } from "express";
import connectPg from "connect-pg-simple";
import { authStorage } from "./storage";
import { users } from "@shared/models/auth";
import { db } from "../db";
import { eq } from "drizzle-orm";

// In-memory cache to avoid DB lookup on every request
const userCache = new Map<string, { data: any; ts: number }>();
const CACHE_TTL = 60_000; // 1 min

export function getSession() {
  const sessionTtl = 7 * 24 * 60 * 60 * 1000; // 1 week
  const pgStore = connectPg(session);
  const sessionStore = new pgStore({
    conString: process.env.DATABASE_URL,
    createTableIfMissing: false,
    ttl: sessionTtl,
    tableName: "sessions",
  });
  const isProduction = process.env.NODE_ENV === "production";
  const port = parseInt(process.env.PORT || "5000", 10);
  // Staging (port 3003) runs behind plain HTTP — no secure cookie
  const useSecureCookie = isProduction && port !== 3003;
  console.log("[Session] Config:", {
    NODE_ENV: process.env.NODE_ENV,
    port,
    secure: useSecureCookie,
    proxy: isProduction,
  });
  return session({
    secret: process.env.SESSION_SECRET || "tutor81-secret-change-me",
    store: sessionStore,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      secure: useSecureCookie,
      sameSite: "lax",
      maxAge: sessionTtl,
    },
  });
}

function hashPassword(password: string): string {
  return crypto.createHash("sha256").update(password).digest("hex");
}

export async function setupAuth(app: Express) {
  app.set("trust proxy", 1);
  app.use(getSession());

  // POST /api/admin-login — email + password
  app.post("/api/admin-login", async (req, res) => {
    try {
      const { username, password } = req.body;
      console.log("[LOGIN]", new Date().toISOString(), {
        username,
        ip: req.ip,
      });

      if (!username || !password) {
        return res
          .status(400)
          .json({ error: "Email e password sono obbligatori." });
      }

      const email = username.trim().toLowerCase();
      const [user] = await db
        .select()
        .from(users)
        .where(eq(users.email, email));

      if (!user) {
        return res
          .status(401)
          .json({ error: "Credenziali non valide. Utente non trovato." });
      }

      if (!user.passwordHash) {
        return res
          .status(401)
          .json({ error: "Password non impostata per questo utente." });
      }

      const hash = hashPassword(password);
      if (hash !== user.passwordHash) {
        return res.status(401).json({ error: "Password errata." });
      }

      // Store user info in session
      const sessionUser = {
        claims: {
          sub: user.id,
          email: user.email,
          first_name: user.firstName,
          last_name: user.lastName,
          profile_image_url: user.profileImageUrl,
        },
        expires_at:
          Math.floor(Date.now() / 1000) + 7 * 24 * 60 * 60, // 1 week
      };

      (req.session as any).passport = { user: sessionUser };

      // Invalidate cache
      userCache.delete(user.id);

      console.log("[LOGIN] Success:", user.email, "role:", user.role);
      res.json({ ok: true });
    } catch (error) {
      console.error("[LOGIN] Error:", error);
      res.status(500).json({ error: "Errore interno del server." });
    }
  });

  // GET /api/login — serve HTML login form (replaces old Replit OAuth redirect)
  app.get("/api/login", (_req, res) => {
    res.set("Content-Type", "text/html; charset=utf-8").send(`<!doctype html>
<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — Tutor81 LMS</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#0f172a;font-family:system-ui,-apple-system,sans-serif;color:#e2e8f0}
.card{width:min(400px,calc(100vw - 32px));background:#1e293b;border-radius:16px;padding:32px;box-shadow:0 20px 60px rgba(0,0,0,.4)}
h1{font-size:22px;margin-bottom:24px;text-align:center;color:#fbbf24}
label{display:block;font-size:13px;margin-bottom:4px;color:#94a3b8}
input{width:100%;padding:10px 12px;border:1px solid #334155;border-radius:8px;background:#0f172a;color:#e2e8f0;font-size:15px;margin-bottom:16px;outline:none}
input:focus{border-color:#fbbf24}
button{width:100%;padding:12px;border:none;border-radius:8px;background:#fbbf24;color:#0f172a;font-size:15px;font-weight:700;cursor:pointer}
button:hover{background:#f59e0b}
.error{background:#7f1d1d;color:#fca5a5;padding:10px;border-radius:8px;margin-bottom:16px;font-size:13px;display:none}
</style></head><body>
<div class="card">
<h1>TUTOR 81 LMS</h1>
<div class="error" id="err"></div>
<form id="f">
<label>Email</label><input type="email" id="u" required autofocus>
<label>Password</label><input type="password" id="p" required>
<button type="submit">Accedi</button>
</form>
</div>
<script>
document.getElementById('f').onsubmit=async e=>{
  e.preventDefault();
  const err=document.getElementById('err');
  err.style.display='none';
  try{
    const r=await fetch('/api/admin-login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:document.getElementById('u').value,password:document.getElementById('p').value})});
    const d=await r.json();
    if(r.ok){window.location.href='/';}else{err.textContent=d.error||'Errore';err.style.display='block';}
  }catch(x){err.textContent='Errore di connessione';err.style.display='block';}
};
</script></body></html>`);
  });

  // GET /api/logout — destroy session and redirect
  app.get("/api/logout", (req, res) => {
    const userId = (req.session as any)?.passport?.user?.claims?.sub;
    if (userId) userCache.delete(userId);
    req.session.destroy((err) => {
      if (err) console.error("[LOGOUT] Error:", err);
      res.redirect("/api/login");
    });
  });

  // GET /api/admin-logout (alias)
  app.get("/api/admin-logout", (req, res) => {
    const userId = (req.session as any)?.passport?.user?.claims?.sub;
    if (userId) {
      userCache.delete(userId);
      console.log("[LOGOUT] Cache invalidated for user:", userId);
    }
    req.session.destroy((err) => {
      if (err) console.error("[LOGOUT] Error:", err);
      res.redirect("/");
    });
  });
}

export const isAuthenticated: RequestHandler = async (req, res, next) => {
  // Dev mode bypass
  const authDisabled = /^(true|1|yes)$/i.test(process.env.DISABLE_AUTH ?? "");
  if (authDisabled) {
    if (!process.env.DATABASE_URL) {
      return res.status(503).json({
        message: "Auth bypass enabled but DATABASE_URL is missing",
      });
    }
    const devUserId = process.env.DEV_USER_ID || "dev-user";
    const devEmail = process.env.DEV_EMAIL || "dev@localhost";
    const devFirstName = process.env.DEV_FIRST_NAME || "Dev";
    const devLastName = process.env.DEV_LAST_NAME || "User";
    const devProfileImageUrl = process.env.DEV_PROFILE_IMAGE_URL || null;
    const roleRaw = process.env.DEV_ROLE || "1000";
    const devRole = Number.isFinite(Number(roleRaw))
      ? parseInt(roleRaw, 10)
      : 1000;
    const idcompanyRaw = process.env.DEV_IDCOMPANY;
    const devIdcompany =
      idcompanyRaw && Number.isFinite(Number(idcompanyRaw))
        ? parseInt(idcompanyRaw, 10)
        : undefined;

    (req as any).isAuthenticated = () => true;
    (req as any).user = {
      claims: {
        sub: devUserId,
        email: devEmail,
        first_name: devFirstName,
        last_name: devLastName,
        profile_image_url: devProfileImageUrl,
      },
      expires_at:
        Math.floor(Date.now() / 1000) + 10 * 365 * 24 * 60 * 60,
    };
    try {
      await authStorage.upsertUser({
        id: devUserId,
        email: devEmail,
        firstName: devFirstName,
        lastName: devLastName,
        profileImageUrl: devProfileImageUrl ?? undefined,
        role: devRole,
        idcompany: devIdcompany,
      });
    } catch (e) {
      console.error("[auth] DEV user upsert failed:", e);
      return res.status(500).json({ message: "Failed to init dev user" });
    }
    return next();
  }

  // Check session
  const sessionUser = (req.session as any)?.passport?.user;
  if (!sessionUser?.claims?.sub) {
    return res.status(401).json({ message: "Unauthorized" });
  }

  const now = Math.floor(Date.now() / 1000);
  if (sessionUser.expires_at && now > sessionUser.expires_at) {
    return res.status(401).json({ message: "Session expired" });
  }

  // Attach user to request (compatible with existing route handlers)
  (req as any).user = sessionUser;
  (req as any).isAuthenticated = () => true;
  return next();
};
