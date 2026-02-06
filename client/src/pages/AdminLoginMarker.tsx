import { useMemo } from "react";

export default function AdminLoginMarker() {
  const info = useMemo(() => {
    if (typeof window === "undefined") return null;
    return {
      href: window.location.href,
      host: window.location.host,
      protocol: window.location.protocol,
      userAgent: window.navigator.userAgent,
    };
  }, []);

  return (
    <div className="min-h-screen bg-yellow-300 text-black p-6">
      <div className="max-w-3xl mx-auto">
        <h1 className="text-3xl font-extrabold">TUTOR81 LMS — MARKER</h1>
        <p className="mt-2 text-lg">
          Se vedi questa pagina, sei sulla NUOVA LMS (Vultr) e non sul legacy.
        </p>

        <div className="mt-6 rounded-xl bg-yellow-200/70 border border-yellow-400 p-4">
          <div className="font-mono text-sm whitespace-pre-wrap break-words">
            {info ? (
              [
                `href: ${info.href}`,
                `protocol: ${info.protocol}`,
                `host: ${info.host}`,
                `ua: ${info.userAgent}`,
              ].join("\n")
            ) : (
              "(loading...)"
            )}
          </div>
        </div>

        <div className="mt-6 flex flex-wrap gap-3">
          <a
            className="px-4 py-2 rounded-lg bg-black text-yellow-300 font-semibold"
            href="/api/health"
          >
            /api/health
          </a>
          <a
            className="px-4 py-2 rounded-lg bg-black text-yellow-300 font-semibold"
            href="/dashboard"
          >
            Dashboard
          </a>
          <a
            className="px-4 py-2 rounded-lg bg-black text-yellow-300 font-semibold"
            href="/login"
          >
            Login
          </a>
        </div>

        <p className="mt-6 text-sm">
          Nota: se apri lo stesso path sul legacy, potresti vedere una pagina diversa o una
          “404” del frontend.
        </p>
      </div>
    </div>
  );
}
