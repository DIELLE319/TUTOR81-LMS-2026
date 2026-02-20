import crypto from "crypto";
import session from "express-session";
import type { Express, RequestHandler } from "express";
import connectPg from "connect-pg-simple";
import { authStorage } from "./storage";
import { users } from "@shared/models/auth";
import { db } from "../../db";
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
  console.log("[Session] Config:", {
    NODE_ENV: process.env.NODE_ENV,
    secure: isProduction,
    proxy: isProduction,
  });
  return session({
    secret: process.env.SESSION_SECRET || "tutor81-secret-change-me",
    store: sessionStore,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      secure: isProduction,
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

  // GET /api/admin-logout
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
