import { Link } from "wouter";

type PublicHeaderProps = {
  /** When true, shows the "Accedi" button (useful on /player-login). */
  showLoginCta?: boolean;
};

const DEFAULT_LINKS = {
  warranty: "https://tutor81.com/garanzia-certificazione",
  instructions: "https://tutor81.com/istruzioni",
  homeTutor: "https://tutor81.com/",
};

function getLink(key: keyof typeof DEFAULT_LINKS) {
  // Allow per-environment override without code changes
  // e.g. VITE_PUBLIC_INSTRUCTIONS_URL=https://...
  const envKey = `VITE_PUBLIC_${key.toUpperCase()}_URL` as const;
  const fromEnv = (import.meta as any).env?.[envKey] as string | undefined;
  return fromEnv || DEFAULT_LINKS[key];
}

export default function PublicHeader({ showLoginCta = false }: PublicHeaderProps) {
  return (
    <header className="bg-black text-gray-200 border-b border-gray-800">
      <div className="max-w-screen-2xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div className="flex items-center gap-3 min-w-0">
          <div className="h-9 w-9 rounded bg-yellow-500 text-black font-extrabold flex items-center justify-center flex-shrink-0">
            T
          </div>
          <div className="min-w-0">
            <div className="font-extrabold tracking-wide text-yellow-500 leading-tight">
              TUTOR 81 LMS
            </div>
            <div className="text-[11px] text-gray-400 truncate leading-tight">
              Via Mazzolari, 45 - Gussago (BS)
            </div>
          </div>
        </div>

        <nav className="hidden md:flex items-center gap-6 text-sm">
          <a
            className="hover:text-yellow-400 transition-colors"
            href={getLink("warranty")}
            target="_blank"
            rel="noreferrer"
          >
            Garanzia certificazione
          </a>
          <a
            className="hover:text-yellow-400 transition-colors"
            href={getLink("instructions")}
            target="_blank"
            rel="noreferrer"
          >
            Istruzioni corso
          </a>
        </nav>

        <div className="flex items-center gap-3">
          <Link
            href="/player-login"
            className="px-3 py-2 rounded-md bg-yellow-500 hover:bg-yellow-400 text-black font-bold text-sm transition-colors"
          >
            Avvia Corso
          </Link>

          {showLoginCta ? (
            <Link
              href="/login"
              className="px-3 py-2 rounded-md border border-yellow-500/60 hover:border-yellow-400 text-yellow-400 hover:text-yellow-300 font-bold text-sm transition-colors"
            >
              Accedi
            </Link>
          ) : (
            <a
              href={getLink("homeTutor")}
              target="_blank"
              rel="noreferrer"
              className="px-3 py-2 rounded-md border border-yellow-500/60 hover:border-yellow-400 text-yellow-400 hover:text-yellow-300 font-bold text-sm transition-colors"
            >
              Home Tutor
            </a>
          )}
        </div>
      </div>

      <div className="md:hidden px-4 pb-3 flex items-center gap-4 text-xs text-gray-300">
        <a className="hover:text-yellow-400" href={getLink("warranty")} target="_blank" rel="noreferrer">
          Garanzia
        </a>
        <a className="hover:text-yellow-400" href={getLink("instructions")} target="_blank" rel="noreferrer">
          Istruzioni
        </a>
      </div>
    </header>
  );
}
