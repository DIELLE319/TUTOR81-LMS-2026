import { useMemo } from "react";

export default function EnvironmentBanner() {
  const banner = useMemo(() => {
    if (typeof window === "undefined") return null;
    const h = window.location.hostname;
    const isIp = /^\d{1,3}(?:\.\d{1,3}){3}$/.test(h);
    if (!isIp && h !== "lms.tutor81.com") return null;
    return isIp ? "NUOVA LMS (Vultr) — IP" : "NUOVA LMS (Vultr) — lms.tutor81.com";
  }, []);

  if (!banner) return null;
  return (
    <div className="w-full bg-yellow-400 text-black text-xs font-extrabold uppercase tracking-widest text-center py-2">
      {banner}
    </div>
  );
}
