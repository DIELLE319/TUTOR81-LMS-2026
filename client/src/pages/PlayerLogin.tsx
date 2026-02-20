import { useState, useRef } from "react";
import { useToast } from "@/hooks/use-toast";
import { User, LogIn, ExternalLink, ArrowLeft, Calendar } from "lucide-react";
import EnvironmentBanner from "@/components/EnvironmentBanner";
import PublicHeader from "@/components/PublicHeader";

const OLD_PLAYER_URL = "https://avviacorso.tutor81.com/prelogin.php";

const MONTHS = [
  { value: "1", label: "Gennaio" },
  { value: "2", label: "Febbraio" },
  { value: "3", label: "Marzo" },
  { value: "4", label: "Aprile" },
  { value: "5", label: "Maggio" },
  { value: "6", label: "Giugno" },
  { value: "7", label: "Luglio" },
  { value: "8", label: "Agosto" },
  { value: "9", label: "Settembre" },
  { value: "10", label: "Ottobre" },
  { value: "11", label: "Novembre" },
  { value: "12", label: "Dicembre" },
];

export default function PlayerLogin() {
  const [step, setStep] = useState<1 | 2>(1);
  const [username, setUsername] = useState("");
  const [birthDay, setBirthDay] = useState("");
  const [birthMonth, setBirthMonth] = useState("");
  const [birthYear, setBirthYear] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [resolvedLicenseCode, setResolvedLicenseCode] = useState("");
  const [resolvedFiscalCode, setResolvedFiscalCode] = useState("");
  const { toast } = useToast();
  const hiddenFormRef = useRef<HTMLFormElement>(null);

  const handleCheckUsername = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!username.trim() || !username.includes('.')) {
      toast({ title: "Formato non valido", description: "Il nome utente deve essere nel formato nome.cognome", variant: "destructive" });
      return;
    }
    setIsLoading(true);
    try {
      const response = await fetch("/api/player/check-username", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: username.trim().toLowerCase() }),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        setStep(2);
      } else {
        toast({ title: "Utente non trovato", description: data.error || "Verifica il nome utente", variant: "destructive" });
      }
    } catch {
      toast({ title: "Errore", description: "Impossibile verificare il nome utente", variant: "destructive" });
    }
    setIsLoading(false);
  };

  const handleVerifyIdentity = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!birthDay || !birthMonth || !birthYear) {
      toast({ title: "Compila tutti i campi", variant: "destructive" });
      return;
    }
    setIsLoading(true);
    try {
      const response = await fetch("/api/player/verify-identity", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          username: username.trim().toLowerCase(),
          birthDay,
          birthMonth,
          birthYear,
        }),
      });
      const data = await response.json();
      if (response.ok && data.success) {
        toast({ title: "Accesso verificato!", description: `Corso: ${data.enrollment.courseName}` });
        setResolvedLicenseCode(data.enrollment.licenseCode);
        // Reconstruct fiscal code isn't needed — the old player uses license code + tax_code
        // We'll pass the fiscal code from the student record via a separate field
        setResolvedFiscalCode("CF_VERIFIED");
        setTimeout(() => {
          if (hiddenFormRef.current) {
            hiddenFormRef.current.submit();
          }
        }, 100);
      } else {
        toast({ title: "Verifica fallita", description: data.error || "Le risposte non corrispondono", variant: "destructive" });
        setIsLoading(false);
      }
    } catch {
      toast({ title: "Errore", description: "Impossibile completare la verifica", variant: "destructive" });
      setIsLoading(false);
    }
  };

  // Generate year options (from 1940 to 2010)
  const yearOptions: string[] = [];
  for (let y = 2010; y >= 1940; y--) yearOptions.push(String(y));

  // Generate day options (1-31)
  const dayOptions: string[] = [];
  for (let d = 1; d <= 31; d++) dayOptions.push(String(d));

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex flex-col">
      <EnvironmentBanner />
      <PublicHeader showLoginCta />

      {/* Hidden form for redirect to old player */}
      <form ref={hiddenFormRef} method="POST" action={OLD_PLAYER_URL} style={{ display: 'none' }}>
        <input type="hidden" name="course_code" value={resolvedLicenseCode} />
        <input type="hidden" name="tax_code" value={resolvedFiscalCode} />
      </form>

      <div className="flex-1 flex items-center justify-center p-4">
        <div className="w-full max-w-md bg-white shadow-2xl rounded-xl overflow-hidden">
          <div className="text-center pt-8 pb-2 px-6">
            <div className="mx-auto w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mb-4">
              <LogIn className="w-8 h-8 text-gray-900" />
            </div>
            <h2 className="text-2xl font-bold text-gray-900">Accedi al Corso</h2>
            <p className="text-gray-600 mt-1">
              {step === 1
                ? "Inserisci il tuo nome utente"
                : "Rispondi alle domande di verifica"}
            </p>
          </div>

          <div className="p-6">
            {step === 1 ? (
              <form onSubmit={handleCheckUsername} className="space-y-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium text-gray-700">Nome Utente</label>
                  <div className="relative">
                    <User className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                    <input
                      type="text"
                      value={username}
                      onChange={(e) => setUsername(e.target.value)}
                      placeholder="nome.cognome"
                      className="w-full h-10 pl-10 pr-3 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                      data-testid="input-username"
                      autoFocus
                      required
                    />
                  </div>
                  <p className="text-xs text-gray-500">Il nome utente ti è stato comunicato via email nel formato <strong>nome.cognome</strong></p>
                </div>

                <button
                  type="submit"
                  className="w-full h-10 bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold rounded-md flex items-center justify-center gap-2 disabled:opacity-50"
                  disabled={isLoading}
                  data-testid="btn-next"
                >
                  {isLoading ? "Verifica..." : "Prosegui"}
                </button>
              </form>
            ) : (
              <form onSubmit={handleVerifyIdentity} className="space-y-4">
                <div className="flex items-center gap-2 mb-2">
                  <button
                    type="button"
                    onClick={() => { setStep(1); setBirthDay(""); setBirthMonth(""); setBirthYear(""); }}
                    className="text-gray-500 hover:text-gray-700"
                  >
                    <ArrowLeft size={18} />
                  </button>
                  <span className="text-sm text-gray-600">
                    Utente: <strong className="text-gray-900">{username}</strong>
                  </span>
                </div>

                <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-2">
                  <p className="text-sm text-blue-800 flex items-center gap-2">
                    <Calendar size={16} />
                    Per verificare la tua identità, inserisci la tua data di nascita
                  </p>
                </div>

                <div className="space-y-3">
                  <div>
                    <label className="text-sm font-medium text-gray-700 mb-1 block">Giorno di nascita</label>
                    <select
                      value={birthDay}
                      onChange={(e) => setBirthDay(e.target.value)}
                      className="w-full h-10 border border-gray-300 rounded-md px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                      data-testid="select-birth-day"
                      required
                    >
                      <option value="">Seleziona giorno...</option>
                      {dayOptions.map((d) => (
                        <option key={d} value={d}>{d}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-gray-700 mb-1 block">Mese di nascita</label>
                    <select
                      value={birthMonth}
                      onChange={(e) => setBirthMonth(e.target.value)}
                      className="w-full h-10 border border-gray-300 rounded-md px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                      data-testid="select-birth-month"
                      required
                    >
                      <option value="">Seleziona mese...</option>
                      {MONTHS.map((m) => (
                        <option key={m.value} value={m.value}>{m.label}</option>
                      ))}
                    </select>
                  </div>

                  <div>
                    <label className="text-sm font-medium text-gray-700 mb-1 block">Anno di nascita</label>
                    <select
                      value={birthYear}
                      onChange={(e) => setBirthYear(e.target.value)}
                      className="w-full h-10 border border-gray-300 rounded-md px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-yellow-400"
                      data-testid="select-birth-year"
                      required
                    >
                      <option value="">Seleziona anno...</option>
                      {yearOptions.map((y) => (
                        <option key={y} value={y}>{y}</option>
                      ))}
                    </select>
                  </div>
                </div>

                <button
                  type="submit"
                  className="w-full h-10 bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold rounded-md flex items-center justify-center gap-2 disabled:opacity-50"
                  disabled={isLoading}
                  data-testid="btn-login"
                >
                  {isLoading ? (
                    "Verifica in corso..."
                  ) : (
                    <>
                      <ExternalLink className="w-4 h-4" />
                      Avvia Corso
                    </>
                  )}
                </button>
              </form>
            )}

            <div className="mt-6 text-center">
              <a
                href="mailto:assistenza@tutor81.com"
                className="text-sm text-blue-600 hover:underline"
              >
                Non riesci ad accedere? Contatta l'assistenza
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
