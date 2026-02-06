import * as client from "openid-client";
import { Strategy, type VerifyFunction } from "openid-client/passport";

import passport from "passport";
import session from "express-session";
import type { Express, RequestHandler } from "express";
import memoize from "memoizee";
import connectPg from "connect-pg-simple";
import { authStorage } from "./storage";

const getOidcConfig = memoize(
  async () => {
    return await client.discovery(
      new URL(process.env.ISSUER_URL ?? "https://replit.com/oidc"),
      process.env.REPL_ID!
    );
  },
  { maxAge: 3600 * 1000 }
);

export function getSession() {
  const sessionTtl = 7 * 24 * 60 * 60 * 1000; // 1 week
  const pgStore = connectPg(session);
  const sessionStore = new pgStore({
    conString: process.env.DATABASE_URL,
    createTableIfMissing: false,
    ttl: sessionTtl,
    tableName: "sessions",
  });
  return session({
    secret: process.env.SESSION_SECRET!,
    store: sessionStore,
    resave: false,
    saveUninitialized: false,
    cookie: {
      httpOnly: true,
      secure: true,
      sameSite: "lax",
      maxAge: sessionTtl,
    },
  });
}

function updateUserSession(
  user: any,
  tokens: client.TokenEndpointResponse & client.TokenEndpointResponseHelpers
) {
  user.claims = tokens.claims();
  user.access_token = tokens.access_token;
  user.refresh_token = tokens.refresh_token;
  user.expires_at = user.claims?.exp;
}

async function upsertUser(claims: any) {
  await authStorage.upsertUser({
    id: claims["sub"],
    email: claims["email"],
    firstName: claims["first_name"],
    lastName: claims["last_name"],
    profileImageUrl: claims["profile_image_url"],
  });
}

export async function setupAuth(app: Express) {
  app.set("trust proxy", 1);
  app.use(getSession());
  app.use(passport.initialize());
  app.use(passport.session());

  const config = await getOidcConfig();

  const verify: VerifyFunction = async (
    tokens: client.TokenEndpointResponse & client.TokenEndpointResponseHelpers,
    verified: passport.AuthenticateCallback
  ) => {
    const user = {};
    updateUserSession(user, tokens);
    await upsertUser(tokens.claims());
    verified(null, user);
  };

  // Keep track of registered strategies
  const registeredStrategies = new Set<string>();

  // Helper function to ensure strategy exists for a domain
  const ensureStrategy = (domain: string) => {
    const strategyName = `replitauth:${domain}`;
    if (!registeredStrategies.has(strategyName)) {
      const strategy = new Strategy(
        {
          name: strategyName,
          config,
          scope: "openid email profile offline_access",
          callbackURL: `https://${domain}/api/callback`,
        },
        verify
      );
      passport.use(strategy);
      registeredStrategies.add(strategyName);
    }
  };

  passport.serializeUser((user: Express.User, cb) => cb(null, user));
  passport.deserializeUser((user: Express.User, cb) => cb(null, user));

  app.get("/api/login", (req, res, next) => {
    ensureStrategy(req.hostname);
    passport.authenticate(`replitauth:${req.hostname}`, {
      prompt: "login consent",
      scope: ["openid", "email", "profile", "offline_access"],
    })(req, res, next);
  });

  app.get("/api/callback", (req, res, next) => {
    console.log("[Auth] Callback received for domain:", req.hostname);
    ensureStrategy(req.hostname);
    passport.authenticate(`replitauth:${req.hostname}`, {
      successReturnToOrRedirect: "/",
      failureRedirect: "/login",
    })(req, res, (err: any) => {
      if (err) {
        console.error("[Auth] Callback error:", err);
      }
      next(err);
    });
  });

  app.get("/api/logout", (req, res) => {
    req.logout(() => {
      res.redirect(
        client.buildEndSessionUrl(config, {
          client_id: process.env.REPL_ID!,
          post_logout_redirect_uri: `${req.protocol}://${req.hostname}`,
        }).href
      );
    });
  });
}

export const isAuthenticated: RequestHandler = async (req, res, next) => {
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
    const devRole = Number.isFinite(Number(roleRaw)) ? parseInt(roleRaw, 10) : 1000;

    const idcompanyRaw = process.env.DEV_IDCOMPANY;
    const devIdcompany =
      idcompanyRaw && Number.isFinite(Number(idcompanyRaw))
        ? parseInt(idcompanyRaw, 10)
        : undefined;

    // Ensure downstream code relying on req.user/claims works.
    (req as any).isAuthenticated = () => true;
    (req as any).user = {
      claims: {
        sub: devUserId,
        email: devEmail,
        first_name: devFirstName,
        last_name: devLastName,
        profile_image_url: devProfileImageUrl,
      },
      // "valid" for 10 years
      expires_at: Math.floor(Date.now() / 1000) + 10 * 365 * 24 * 60 * 60,
      access_token: null,
      refresh_token: null,
    };

    // Upsert the user record so role-based checks can work in routes.
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

  const user = req.user as any;

  if (!req.isAuthenticated?.() || !user?.expires_at) {
    return res.status(401).json({ message: "Unauthorized" });
  }

  const now = Math.floor(Date.now() / 1000);
  if (now <= user.expires_at) {
    return next();
  }

  const refreshToken = user.refresh_token;
  if (!refreshToken) {
    res.status(401).json({ message: "Unauthorized" });
    return;
  }

  try {
    const config = await getOidcConfig();
    const tokenResponse = await client.refreshTokenGrant(config, refreshToken);
    updateUserSession(user, tokenResponse);
    return next();
  } catch (error) {
    res.status(401).json({ message: "Unauthorized" });
    return;
  }
};
