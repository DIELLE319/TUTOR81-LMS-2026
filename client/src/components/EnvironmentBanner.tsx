import { useMemo } from "react";

function isIpHost(hostname: string) {
  return /^\d{1,3}(?:\.\d{1,3}){3}$/.test(hostname);
}

/**
 * Yellow banner to visually differentiate the new LMS instance.
 *
 * It auto-enables on:
 * - direct IP access (common during VPS tests)
 * - lms.tutor81.com
 */
export default function EnvironmentBanner() {
  const banner = useMemo(() => {
    if (typeof window === "undefined") return null;

    const hostname = window.location.hostname;

    const enabled = isIpHost(hostname) || hostname === "lms.tutor81.com";
    if (!enabled) return null;

    return {
      text: isIpHost(hostname) ? "NUOVA LMS (Vultr) — IP" : "NUOVA LMS (Vultr) — lms.tutor81.com",
    };
  }, []);

  if (!banner) return null;

  return (
    <div className="w-full bg-yellow-400 text-black text-xs sm:text-sm font-extrabold uppercase tracking-widest">
      <div className="max-w-screen-2xl mx-auto px-4 py-2 flex items-center justify-center">
        {banner.text}
      </div>
    </div>
  );
}
