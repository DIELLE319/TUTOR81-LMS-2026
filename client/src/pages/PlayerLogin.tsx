import { useState, useRef } from "react";
import { useToast } from "@/hooks/use-toast";
import { User, Key, LogIn, ExternalLink } from "lucide-react";
import EnvironmentBanner from "@/components/EnvironmentBanner";
import PublicHeader from "@/components/PublicHeader";

const OLD_PLAYER_URL = "https://avviacorso.tutor81.com/prelogin.php";

export default function PlayerLogin() {
  const [licenseCode, setLicenseCode] = useState("");
  const [fiscalCode, setFiscalCode] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const { toast } = useToast();
  const formRef = useRef<HTMLFormElement>(null);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const response = await fetch("/api/player/validate-license", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ licenseCode }),
      });

      const data = await response.json();

      if (response.ok && data.valid) {
        toast({ 
          title: "Reindirizzamento al corso...", 
          description: `Corso: ${data.enrollment.courseTitle}` 
        });
        if (formRef.current) {
          formRef.current.submit();
        }
      } else {
        toast({ 
          title: "Codice licenza non valido", 
          description: data.error || "Verifica il codice inserito", 
          variant: "destructive" 
        });
        setIsLoading(false);
      }
    } catch (error) {
      toast({ 
        title: "Errore", 
        description: "Impossibile verificare la licenza", 
        variant: "destructive" 
      });
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex flex-col">
      <EnvironmentBanner />
      <PublicHeader showLoginCta />

      <div className="flex-1 flex items-center justify-center p-4">
        <div className="w-full max-w-md bg-white shadow-2xl rounded-xl overflow-hidden">
          <div className="text-center pt-8 pb-2 px-6">
            <div className="mx-auto w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mb-4">
              <LogIn className="w-8 h-8 text-gray-900" />
            </div>
            <h2 className="text-2xl font-bold text-gray-900">Accedi al Corso</h2>
            <p className="text-gray-600 mt-1">
              Inserisci il codice licenza e il codice fiscale
            </p>
          </div>
          <div className="p-6">
            <form 
              ref={formRef}
              method="POST" 
              action={OLD_PLAYER_URL}
              onSubmit={handleLogin} 
              className="space-y-4"
            >
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Codice Licenza</label>
                <div className="relative">
                  <Key className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    name="course_code"
                    value={licenseCode}
                    onChange={(e) => setLicenseCode(e.target.value.toUpperCase())}
                    placeholder="es: B8j4P"
                    className="w-full h-10 pl-10 pr-3 border border-gray-300 rounded-md text-sm uppercase focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                    data-testid="input-license-code"
                    required
                  />
                </div>
              </div>
              
              <div className="space-y-2">
                <label className="text-sm font-medium text-gray-700">Codice Fiscale</label>
                <div className="relative">
                  <User className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                  <input
                    type="text"
                    name="tax_code"
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
                Il codice licenza ti è stato inviato via email.<br />
                Inseriscilo insieme al tuo codice fiscale per accedere al corso.
              </p>
            </div>

            <div className="mt-4 text-center">
              <a 
                href="mailto:assistenza@tutor81.com" 
                className="text-sm text-blue-600 hover:underline"
              >
                Non hai ricevuto il codice? Contatta l'assistenza
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
