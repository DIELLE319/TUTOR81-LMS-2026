import { useState, useRef } from "react";
import { useToast } from "@/hooks/use-toast";
import { User, LogIn, ExternalLink } from "lucide-react";
import EnvironmentBanner from "@/components/EnvironmentBanner";
import PublicHeader from "@/components/PublicHeader";

const OLD_PLAYER_URL = "https://avviacorso.tutor81.com/prelogin.php";

export default function PlayerLogin() {
  const [username, setUsername] = useState("");
  const [fiscalCode, setFiscalCode] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [resolvedLicenseCode, setResolvedLicenseCode] = useState("");
  const { toast } = useToast();
  const hiddenFormRef = useRef<HTMLFormElement>(null);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const response = await fetch("/api/player/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: username.trim().toLowerCase(), fiscalCode: fiscalCode.trim().toUpperCase() }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        toast({ 
          title: "Reindirizzamento al corso...", 
          description: `Corso: ${data.enrollment.courseName}` 
        });
        setResolvedLicenseCode(data.enrollment.licenseCode);
        // Wait for state update, then submit hidden form to old player
        setTimeout(() => {
          if (hiddenFormRef.current) {
            hiddenFormRef.current.submit();
          }
        }, 100);
      } else {
        toast({ 
          title: "Accesso negato", 
          description: data.error || "Verifica nome utente e codice fiscale", 
          variant: "destructive" 
        });
        setIsLoading(false);
      }
    } catch (error) {
      toast({ 
        title: "Errore", 
        description: "Impossibile verificare le credenziali", 
        variant: "destructive" 
      });
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex flex-col">
      <EnvironmentBanner />
      <PublicHeader showLoginCta />

      {/* Hidden form for redirect to old player */}
      <form ref={hiddenFormRef} method="POST" action={OLD_PLAYER_URL} style={{ display: 'none' }}>
        <input type="hidden" name="course_code" value={resolvedLicenseCode} />
        <input type="hidden" name="tax_code" value={fiscalCode.trim().toUpperCase()} />
      </form>

      <div className="flex-1 flex items-center justify-center p-4">
        <div className="w-full max-w-md bg-white shadow-2xl rounded-xl overflow-hidden">
          <div className="text-center pt-8 pb-2 px-6">
            <div className="mx-auto w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mb-4">
              <LogIn className="w-8 h-8 text-gray-900" />
            </div>
            <h2 className="text-2xl font-bold text-gray-900">Accedi al Corso</h2>
            <p className="text-gray-600 mt-1">
              Inserisci il tuo nome utente e codice fiscale
            </p>
          </div>
          <div className="p-6">
            <form onSubmit={handleLogin} className="space-y-4">
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Nome Utente</label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    placeholder="es: mario.rossi"
                    className="w-full h-10 pl-10 pr-3 border border-gray-300 rounded-md text-sm lowercase focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                    data-testid="input-username"
                    required
                  />
                </div>
              </div>
              
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Codice Fiscale</label>
                <div className="relative">
                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-bold">CF</span>
                  <input
                    type="text"
                    value={fiscalCode}
                    onChange={(e) => setFiscalCode(e.target.value.toUpperCase())}
                    placeholder="es: RSSMRA80A01H501U"
                    className="w-full h-10 pl-10 pr-3 border border-gray-300 rounded-md text-sm uppercase focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                    data-testid="input-fiscal-code"
                    required
                  />
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

            <div className="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
              <p className="text-sm text-gray-700 text-center">
                <strong>Come funziona:</strong><br />
                Il nome utente ti è stato comunicato via email nel formato <strong>nome.cognome</strong>.<br />
                Inseriscilo insieme al tuo codice fiscale per accedere al corso.
              </p>
            </div>

            <div className="mt-4 text-center">
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
