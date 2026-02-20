import { Link } from "wouter";

export default function PublicHeader({ showLoginCta = false }: { showLoginCta?: boolean }) {
  return (
    <header className="bg-black text-gray-200 border-b border-gray-800">
      <div className="max-w-screen-2xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div className="flex items-center gap-3">
          <div className="h-9 w-9 rounded bg-yellow-500 text-black font-extrabold flex items-center justify-center">T</div>
          <div>
            <div className="font-extrabold tracking-wide text-yellow-500 leading-tight">TUTOR 81 LMS</div>
            <div className="text-[11px] text-gray-400 leading-tight">Via Mazzolari, 45 - Gussago (BS)</div>
          </div>
        </div>
        <nav className="hidden md:flex items-center gap-6 text-sm">
          <a className="hover:text-yellow-400" href="https://tutor81.com/garanzia-certificazione" target="_blank" rel="noreferrer">Garanzia certificazione</a>
          <a className="hover:text-yellow-400" href="https://tutor81.com/istruzioni" target="_blank" rel="noreferrer">Istruzioni corso</a>
        </nav>
        <div className="flex items-center gap-3">
          <Link href="/player-login" className="px-3 py-2 rounded-md bg-yellow-500 hover:bg-yellow-400 text-black font-bold text-sm">Avvia Corso</Link>
          {showLoginCta ? (
            <Link href="/login" className="px-3 py-2 rounded-md border border-yellow-500/60 hover:border-yellow-400 text-yellow-400 font-bold text-sm">Accedi</Link>
          ) : (
            <a href="https://tutor81.com/" target="_blank" rel="noreferrer" className="px-3 py-2 rounded-md border border-yellow-500/60 hover:border-yellow-400 text-yellow-400 font-bold text-sm">Home Tutor</a>
          )}
        </div>
      </div>
    </header>
  );
}
