import crypto from "crypto";
import session from "express-session";
import type { Express, RequestHandler } from "express";
import connectPg from "connect-pg-simple";
import { authStorage } from "./storage";
import { users } from "@shared/models/auth";
import { db } from "../db";
import { eq } from "drizzle-orm";

const userCache = new Map<string, { data: any; ts: number }>();
const CACHE_TTL = 60_000;

export function getSession() {
  const sessionTtl = 7 * 24 * 60 * 60 * 1000;
  const pgStore = connectPg(session);
  const sessionStore = new pgStore({
    conString: process.env.DATABASE_URL,
    createTableIfMissing: false,
    ttl: sessionTtl,
    tableName: "sessions",
  });
  const isProduction = process.env.NODE_ENV === "production";
  const port = parseInt(process.env.PORT || "5000", 10);
  const useSecureCookie = isProduction && port !== 3003;
  console.log("[Session] Config:", { NODE_ENV: process.env.NODE_ENV, port, secure: useSecureCookie, proxy: isProduction });
  return session({
    secret: process.env.SESSION_SECRET || "tutor81-secret-change-me",
    store: sessionStore,
    resave: false,
    saveUninitialized: false,
    cookie: { httpOnly: true, secure: useSecureCookie, sameSite: "lax", maxAge: sessionTtl },
  });
}

function hashPassword(password: string): string {
  return crypto.createHash("sha256").update(password).digest("hex");
}

export async function setupAuth(app: Express) {
  app.set("trust proxy", 1);
  app.use(getSession());

  app.post("/api/admin-login", async (req, res) => {
    try {
      const { username, password } = req.body;
      console.log("[LOGIN]", new Date().toISOString(), { username, ip: req.ip });
      if (!username || !password) return res.status(400).json({ error: "Email e password sono obbligatori." });

      const email = username.trim().toLowerCase();
      const [user] = await db.select().from(users).where(eq(users.email, email));
      if (!user) return res.status(401).json({ error: "Credenziali non valide. Utente non trovato." });
      if (!user.passwordHash) return res.status(401).json({ error: "Password non impostata per questo utente." });

      const hash = hashPassword(password);
      if (hash !== user.passwordHash) return res.status(401).json({ error: "Password errata." });

      (req.session as any).passport = {
        user: {
          claims: { sub: user.id, email: user.email, first_name: user.firstName, last_name: user.lastName, profile_image_url: user.profileImageUrl },
          expires_at: Math.floor(Date.now() / 1000) + 7 * 24 * 60 * 60,
        },
      };
      userCache.delete(user.id);
      console.log("[LOGIN] Success:", user.email, "role:", user.role);
      res.json({ ok: true });
    } catch (error) {
      console.error("[LOGIN] Error:", error);
      res.status(500).json({ error: "Errore interno del server." });
    }
  });

  app.get("/api/login", (_req, res) => {
    res.set("Content-Type", "text/html; charset=utf-8").send(`<!doctype html>
<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — Tutor81 LMS</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:#030712;font-family:system-ui,-apple-system,sans-serif;color:#e2e8f0;overflow:hidden;position:relative}
body::before{content:'';position:absolute;top:-50%;right:-30%;width:80vw;height:80vw;border-radius:50%;background:radial-gradient(circle,rgba(251,191,36,.08) 0%,transparent 70%)}
body::after{content:'';position:absolute;bottom:-40%;left:-20%;width:60vw;height:60vw;border-radius:50%;background:radial-gradient(circle,rgba(251,191,36,.04) 0%,transparent 70%)}
.wrap{position:relative;z-index:1;width:min(420px,calc(100vw - 32px))}
.card{background:linear-gradient(145deg,#111827,#1e293b);border-radius:24px;padding:40px;box-shadow:0 25px 80px rgba(0,0,0,.5),0 0 0 1px rgba(255,255,255,.05);backdrop-filter:blur(20px)}
.logo{width:56px;height:56px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:900;color:#0f172a;margin:0 auto 16px;box-shadow:0 8px 30px rgba(251,191,36,.3)}
h1{font-size:20px;text-align:center;color:#fff;font-weight:700;letter-spacing:.5px}
.sub{text-align:center;color:#64748b;font-size:12px;margin-top:4px;margin-bottom:28px;letter-spacing:2px;text-transform:uppercase;font-weight:600}
label{display:block;font-size:12px;margin-bottom:6px;color:#94a3b8;font-weight:600;letter-spacing:.5px;text-transform:uppercase}
input{width:100%;padding:12px 14px;border:2px solid #1e293b;border-radius:12px;background:#0f172a;color:#f1f5f9;font-size:15px;margin-bottom:18px;outline:none;transition:border-color .2s,box-shadow .2s}
input:focus{border-color:#fbbf24;box-shadow:0 0 0 3px rgba(251,191,36,.15)}
input::placeholder{color:#475569}
button{width:100%;padding:14px;border:none;border-radius:12px;background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#0f172a;font-size:15px;font-weight:800;cursor:pointer;letter-spacing:.5px;transition:transform .15s,box-shadow .15s;box-shadow:0 4px 20px rgba(251,191,36,.3)}
button:hover{transform:translateY(-1px);box-shadow:0 8px 30px rgba(251,191,36,.4)}
button:active{transform:translateY(0)}
.error{background:rgba(127,29,29,.6);color:#fca5a5;padding:12px;border-radius:10px;margin-bottom:16px;font-size:13px;display:none;border:1px solid rgba(239,68,68,.2)}
.footer{text-align:center;margin-top:24px;font-size:12px;color:#475569}
.footer a{color:#fbbf24;text-decoration:none}
.footer a:hover{text-decoration:underline}
</style></head><body>
<div class="wrap"><div class="card">
<div class="logo">T</div>
<h1>TUTOR 81</h1>
<div class="sub">Piattaforma LMS</div>
<div class="error" id="err"></div>
<form id="f">
<label>Email</label><input type="email" id="u" required autofocus placeholder="admin@tutor81.com">
<label>Password</label><input type="password" id="p" required placeholder="••••••••">
<button type="submit">Accedi alla piattaforma</button>
</form>
<div class="footer">Problemi di accesso? <a href="mailto:assistenza@tutor81.com">Contatta l'assistenza</a></div>
</div></div>
<script>
document.getElementById('f').onsubmit=async e=>{e.preventDefault();const err=document.getElementById('err');const btn=e.target.querySelector('button');err.style.display='none';btn.textContent='Accesso in corso...';btn.disabled=true;try{const r=await fetch('/api/admin-login',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:document.getElementById('u').value,password:document.getElementById('p').value})});const d=await r.json();if(r.ok){window.location.href='/';}else{err.textContent=d.error||'Errore';err.style.display='block';btn.textContent='Accedi alla piattaforma';btn.disabled=false;}}catch(x){err.textContent='Errore di connessione';err.style.display='block';btn.textContent='Accedi alla piattaforma';btn.disabled=false;}};
</script></body></html>`);
  });

  app.get("/api/logout", (req, res) => {
    const userId = (req.session as any)?.passport?.user?.claims?.sub;
    if (userId) userCache.delete(userId);
    req.session.destroy((err) => {
      if (err) console.error("[LOGOUT] Error:", err);
      res.redirect("/api/login");
    });
  });

  app.get("/api/admin-logout", (req, res) => {
    const userId = (req.session as any)?.passport?.user?.claims?.sub;
    if (userId) userCache.delete(userId);
    req.session.destroy((err) => {
      if (err) console.error("[LOGOUT] Error:", err);
      res.redirect("/");
    });
  });
}

export const isAuthenticated: RequestHandler = async (req, res, next) => {
  const authDisabled = /^(true|1|yes)$/i.test(process.env.DISABLE_AUTH ?? "");
  if (authDisabled) {
    if (!process.env.DATABASE_URL) return res.status(503).json({ message: "Auth bypass enabled but DATABASE_URL is missing" });
    const devUserId = process.env.DEV_USER_ID || "dev-user";
    const devEmail = process.env.DEV_EMAIL || "dev@localhost";
    const devFirstName = process.env.DEV_FIRST_NAME || "Dev";
    const devLastName = process.env.DEV_LAST_NAME || "User";
    const devRole = parseInt(process.env.DEV_ROLE || "1000", 10);
    const idcompanyRaw = process.env.DEV_IDCOMPANY;
    const devIdcompany = idcompanyRaw && Number.isFinite(Number(idcompanyRaw)) ? parseInt(idcompanyRaw, 10) : undefined;

    (req as any).isAuthenticated = () => true;
    (req as any).user = {
      claims: { sub: devUserId, email: devEmail, first_name: devFirstName, last_name: devLastName, profile_image_url: null },
      expires_at: Math.floor(Date.now() / 1000) + 10 * 365 * 24 * 60 * 60,
    };
    try {
      await authStorage.upsertUser({ id: devUserId, email: devEmail, firstName: devFirstName, lastName: devLastName, role: devRole, idcompany: devIdcompany });
    } catch (e) {
      console.error("[auth] DEV user upsert failed:", e);
      return res.status(500).json({ message: "Failed to init dev user" });
    }
    return next();
  }

  const sessionUser = (req.session as any)?.passport?.user;
  if (!sessionUser?.claims?.sub) return res.status(401).json({ message: "Unauthorized" });

  const now = Math.floor(Date.now() / 1000);
  if (sessionUser.expires_at && now > sessionUser.expires_at) return res.status(401).json({ message: "Session expired" });

  (req as any).user = sessionUser;
  (req as any).isAuthenticated = () => true;
  return next();
};
