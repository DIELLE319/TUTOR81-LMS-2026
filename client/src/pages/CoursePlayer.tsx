import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { AlertCircle, Play, HelpCircle, Mail } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

type Step = "username" | "verify" | "courses" | "playing";

interface UserCourse {
  id: number;
  title: string;
  progress: number;
  status: string;
}

export default function CoursePlayer() {
  const [step, setStep] = useState<Step>("username");
  const [username, setUsername] = useState("");
  const [birthDay, setBirthDay] = useState("");
  const [birthMonth, setBirthMonth] = useState("");
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [verificationError, setVerificationError] = useState("");
  const [userData, setUserData] = useState<any>(null);
  const { toast } = useToast();

  const days = Array.from({ length: 31 }, (_, i) => i + 1);
  const months = [
    "Gennaio", "Febbraio", "Marzo", "Aprile", "Maggio", "Giugno",
    "Luglio", "Agosto", "Settembre", "Ottobre", "Novembre", "Dicembre"
  ];

  const handleUsernameSubmit = async () => {
    if (!username.trim()) {
      toast({ title: "Errore", description: "Inserisci il nome utente", variant: "destructive" });
      return;
    }
    if (!termsAccepted) {
      toast({ title: "Errore", description: "Devi accettare i termini e condizioni", variant: "destructive" });
      return;
    }
    
    // TODO: Verificare username nel DB
    // Per ora simuliamo il passaggio alla verifica
    setStep("verify");
  };

  const handleVerification = async () => {
    if (!birthDay || !birthMonth) {
      toast({ title: "Errore", description: "Inserisci giorno e mese di nascita", variant: "destructive" });
      return;
    }
    
    // TODO: Verificare con il CF dell'utente
    // Per ora simuliamo il passaggio alla lista corsi
    setStep("courses");
  };

  return (
    <div className="min-h-screen bg-white">
      <header className="border-b border-gray-200 py-4">
        <div className="max-w-4xl mx-auto px-4 flex items-center justify-between">
          <div className="text-3xl font-bold">
            <span className="text-gray-800">tutor</span>
            <span className="text-yellow-500">81</span>
          </div>
          <nav className="flex items-center gap-6 text-sm text-gray-600">
            <a href="#" className="hover:text-gray-900">Requisiti tecnici</a>
            <a href="#" className="hover:text-gray-900">Come si avvia il corso</a>
            <a href="#" className="hover:text-gray-900 flex items-center gap-1">
              Assistenza <Mail className="h-4 w-4" />
            </a>
          </nav>
        </div>
      </header>

      <main className="max-w-4xl mx-auto px-4 py-12">
        {step === "username" && (
          <div className="text-center">
            <h1 className="text-4xl font-bold text-gray-900 mb-8">AVVIA IL TUO CORSO</h1>
            
            <div className="max-w-md mx-auto mb-8">
              <p className="text-lg text-gray-700 mb-2">Inserisci qui il tuo nome utente</p>
              <p className="text-sm text-gray-500 mb-4">non ricordo il mio nome utente</p>
              
              <div className="relative mb-4">
                <Input
                  type="text"
                  placeholder="nome.cognome"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="text-lg py-6 pr-12 border-2 border-yellow-400 focus:border-yellow-500"
                  data-testid="input-username"
                />
                <HelpCircle className="absolute right-4 top-1/2 -translate-y-1/2 h-5 w-5 text-yellow-500" />
              </div>
              
              <div className="flex items-center justify-between mb-6">
                <div className="flex items-center gap-2">
                  <Checkbox 
                    id="terms" 
                    checked={termsAccepted}
                    onCheckedChange={(checked) => setTermsAccepted(checked as boolean)}
                    data-testid="checkbox-terms"
                  />
                  <Label htmlFor="terms" className="text-sm text-gray-600">
                    Accettazione termini e condizioni d'uso
                  </Label>
                </div>
                <Button 
                  onClick={handleUsernameSubmit}
                  className="bg-yellow-500 hover:bg-yellow-600 text-white px-8"
                  data-testid="button-inizia"
                >
                  Inizia
                </Button>
              </div>
            </div>

            <Card className="bg-yellow-50 border-yellow-200 text-left">
              <CardContent className="py-6">
                <h2 className="text-lg font-semibold text-gray-800 mb-4">
                  Alcune avvertenze nel corso di e-learning
                </h2>
                <div className="space-y-3 text-sm text-gray-700">
                  <p>
                    <strong>IL CORSO PUO' ESSERE INTERROTTO</strong> con il pulsante ESCI/STOP in alto a destra. 
                    Riaccedendo al corso questo ripartirà dall'ultimo punto utile.
                  </p>
                  <p>
                    <strong>PER ACCEDERE AL CORSO</strong> inserisci il nome utente (nome.cognome controlla l'email li trovi il nome utente corretto)
                    Successivamente dovrai rispondere alle domande riferite al codice fiscale
                  </p>
                  <p>
                    <strong>PAGINA CORSI</strong> i tuoi corsi saranno memorizzati, quello che devi svolgere visualizza il pulsante PLAY
                  </p>
                  <p>
                    <strong>I TEST hanno una durata temporizzata di 30 secondi</strong>, trascorsi i quali il corso si interrompe e rientrando riprenderai dal punto di interruzione. 
                    Al termine ti saranno riproposti i test per darti la possibilità di recuperare le domande errate.
                  </p>
                  <p>
                    <strong>RIVEDERE PARTI DEL CORSO</strong> Nella barra di avanzamento puoi tornare indietro e rivedere parti del corso
                  </p>
                </div>
              </CardContent>
            </Card>
          </div>
        )}

        {step === "verify" && (
          <div className="text-center">
            <h1 className="text-4xl font-bold text-gray-900 mb-8">VERIFICA IDENTITÀ</h1>
            
            <div className="max-w-md mx-auto">
              <p className="text-lg text-gray-700 mb-6">
                Benvenuto <strong>{username}</strong>
              </p>
              <p className="text-gray-600 mb-6">
                Per verificare la tua identità, rispondi alle seguenti domande relative al tuo codice fiscale:
              </p>
              
              <div className="space-y-4 mb-6">
                <div>
                  <Label className="text-left block mb-2">Giorno di nascita</Label>
                  <Select value={birthDay} onValueChange={setBirthDay}>
                    <SelectTrigger className="border-2 border-yellow-400" data-testid="select-day">
                      <SelectValue placeholder="Seleziona giorno" />
                    </SelectTrigger>
                    <SelectContent>
                      {days.map((day) => (
                        <SelectItem key={day} value={String(day)}>{day}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                
                <div>
                  <Label className="text-left block mb-2">Mese di nascita</Label>
                  <Select value={birthMonth} onValueChange={setBirthMonth}>
                    <SelectTrigger className="border-2 border-yellow-400" data-testid="select-month">
                      <SelectValue placeholder="Seleziona mese" />
                    </SelectTrigger>
                    <SelectContent>
                      {months.map((month, idx) => (
                        <SelectItem key={month} value={String(idx + 1)}>{month}</SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>

              {verificationError && (
                <div className="flex items-center gap-2 text-red-600 mb-4">
                  <AlertCircle className="h-5 w-5" />
                  <span>{verificationError}</span>
                </div>
              )}
              
              <div className="flex gap-4 justify-center">
                <Button 
                  variant="outline"
                  onClick={() => setStep("username")}
                >
                  Indietro
                </Button>
                <Button 
                  onClick={handleVerification}
                  className="bg-yellow-500 hover:bg-yellow-600 text-white px-8"
                  data-testid="button-verifica"
                >
                  Verifica
                </Button>
              </div>
            </div>
          </div>
        )}

        {step === "courses" && (
          <div>
            <h1 className="text-4xl font-bold text-gray-900 mb-2 text-center">I TUOI CORSI</h1>
            <p className="text-gray-600 mb-8 text-center">
              Benvenuto <strong>{username}</strong> - Seleziona il corso da avviare
            </p>
            
            <div className="space-y-4">
              <Card className="border-2 border-gray-200 hover:border-yellow-400 transition-colors">
                <CardContent className="py-4 flex items-center justify-between">
                  <div>
                    <h3 className="font-semibold text-gray-900">EL - LAVORATORE - FORMAZIONE GENERALE - 4 ORE</h3>
                    <p className="text-sm text-gray-500">Progresso: 0%</p>
                  </div>
                  <Button className="bg-green-500 hover:bg-green-600 text-white" data-testid="button-play-course">
                    <Play className="h-5 w-5 mr-2" />
                    PLAY
                  </Button>
                </CardContent>
              </Card>
              
              <Card className="border-2 border-gray-200 bg-gray-50">
                <CardContent className="py-4 flex items-center justify-between">
                  <div>
                    <h3 className="font-semibold text-gray-700">EL - RLS - AGGIORNAMENTO - 4 ORE</h3>
                    <p className="text-sm text-green-600">Completato il 15/01/2026</p>
                  </div>
                  <Button variant="outline" className="text-gray-500">
                    Attestato
                  </Button>
                </CardContent>
              </Card>
            </div>
          </div>
        )}
      </main>
    </div>
  );
}
