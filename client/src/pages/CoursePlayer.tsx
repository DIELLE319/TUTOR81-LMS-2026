import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Progress } from "@/components/ui/progress";
import { AlertCircle, Play, HelpCircle, Mail, Users } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

type Step = "username" | "verify" | "courses" | "playing";

interface UserData {
  nominativo: string;
  codiceFiscale: string;
  natoIl: string;
  datoreLavoro: string;
}

interface UserCourse {
  id: number;
  title: string;
  duration: string;
  startDate: string;
  endDate: string;
  progress: number;
  status: "active" | "completed" | "not_started";
  completedDate?: string;
}

export default function CoursePlayer() {
  const [step, setStep] = useState<Step>("username");
  const [username, setUsername] = useState("");
  const [birthDay, setBirthDay] = useState("");
  const [birthMonth, setBirthMonth] = useState("");
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [verificationError, setVerificationError] = useState("");
  const [userData, setUserData] = useState<UserData | null>(null);
  const [userCourses, setUserCourses] = useState<UserCourse[]>([]);
  const [, setLocation] = useLocation();
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
    setStep("verify");
  };

  const handleVerification = async () => {
    if (!birthDay || !birthMonth) {
      toast({ title: "Errore", description: "Inserisci giorno e mese di nascita", variant: "destructive" });
      return;
    }
    
    // Simula dati utente (in produzione verrà da API)
    setUserData({
      nominativo: "PATERNO LUIGI PTRLGU80A01A944A",
      codiceFiscale: "PTRLGU80A01A944A",
      natoIl: "",
      datoreLavoro: "AZIENDA DIMOSTRATIVA SPA"
    });
    
    setUserCourses([
      {
        id: 1,
        title: "CORSO DIMOSTRATIVO TUTOR81 2022",
        duration: "1 ore",
        startDate: "01/02/2026",
        endDate: "01/05/2026",
        progress: 0,
        status: "not_started"
      }
    ]);
    
    setStep("courses");
  };

  const handleStartCourse = (courseId: number) => {
    setLocation(`/player/course/${courseId}`);
  };

  return (
    <div className="min-h-screen bg-white">
      <header className="border-b border-gray-200 py-4">
        <div className="max-w-5xl mx-auto px-4 flex items-center justify-between">
          <div className="text-3xl font-bold">
            <span className="text-gray-800">tutor</span>
            <span className="text-yellow-500">81</span>
          </div>
          <nav className="flex items-center gap-6 text-sm text-gray-600">
            {step === "username" && (
              <>
                <a href="#" className="hover:text-gray-900">Requisiti tecnici</a>
                <a href="#" className="hover:text-gray-900">Come si avvia il corso</a>
              </>
            )}
            <a href="#" className="hover:text-gray-900 flex items-center gap-1">
              Assistenza <Mail className="h-4 w-4" />
            </a>
          </nav>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-8">
        {step === "username" && (
          <div className="text-center">
            <h1 className="text-4xl font-bold text-gray-900 mb-8">AVVIA IL TUO CORSO</h1>
            
            <div className="max-w-md mx-auto mb-8">
              <p className="text-lg text-gray-700 mb-2">Inserisci qui il tuo nome utente</p>
              <p className="text-sm text-gray-500 mb-4 cursor-pointer hover:underline">non ricordo il mio nome utente</p>
              
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

        {step === "courses" && userData && (
          <div>
            <div className="flex items-start gap-6 mb-10">
              <div className="w-20 h-20 bg-blue-100 rounded-lg flex items-center justify-center">
                <Users className="h-12 w-12 text-blue-500" />
              </div>
              <div className="grid grid-cols-2 gap-x-8 gap-y-1 text-sm">
                <span className="text-gray-500">Nominativo:</span>
                <span className="font-bold text-gray-900">{userData.nominativo}</span>
                <span className="text-gray-500">Codice fiscale:</span>
                <span className="font-bold text-gray-900">{userData.codiceFiscale}</span>
                <span className="text-gray-500">Nato il:</span>
                <span className="font-bold text-gray-900">{userData.natoIl || "-"}</span>
                <span className="text-gray-500">Datore di lavoro:</span>
                <span className="font-bold text-gray-900">{userData.datoreLavoro}</span>
              </div>
            </div>

            <h2 className="text-2xl italic text-gray-700 mb-6">
              Questi sono i corsi che attualmente stai svolgendo
            </h2>
            
            {userCourses.filter(c => c.status !== "completed").map((course) => (
              <div key={course.id} className="border-2 border-yellow-400 mb-8" data-testid={`course-card-${course.id}`}>
                <div className="grid grid-cols-2">
                  <div className="bg-yellow-400 text-center py-2 font-semibold text-gray-800">
                    Il corso
                  </div>
                  <div className="bg-yellow-400 text-center py-2 font-semibold text-gray-800">
                    La tua attività
                  </div>
                </div>
                <div className="grid grid-cols-2">
                  <div className="p-4 border-r border-yellow-400">
                    <p className="text-sm text-gray-500">Titolo del corso:</p>
                    <p className="font-bold text-gray-900 mb-4">{course.title}</p>
                    
                    <p className="text-sm text-gray-500">Durata:</p>
                    <p className="font-bold text-gray-900 mb-4">{course.duration}</p>
                    
                    <p className="text-sm text-gray-500">Programmato/avviato:</p>
                    <p className="font-bold text-gray-900 mb-4">{course.startDate}</p>
                    
                    <p className="text-sm text-gray-500">Da terminare entro:</p>
                    <p className="font-bold text-gray-900">{course.endDate}</p>
                  </div>
                  <div className="p-4 flex flex-col items-center justify-center">
                    <div className="w-full max-w-xs mb-2">
                      <Progress value={course.progress} className="h-4" />
                    </div>
                    <p className="text-gray-600 mb-2">
                      Hai svolto lo {course.progress}% del corso
                    </p>
                    <p className="text-gray-500 text-sm mb-6">
                      {course.progress === 0 ? "Corso non ancora avviato" : "In corso"}
                    </p>
                    
                    <div className="text-center">
                      <p className="text-xl font-bold text-gray-700 mb-2">AVVIA CORSO</p>
                      <button 
                        onClick={() => handleStartCourse(course.id)}
                        className="w-20 h-20 bg-blue-500 hover:bg-blue-600 rounded-full flex items-center justify-center shadow-lg transition-transform hover:scale-105"
                        data-testid={`button-play-${course.id}`}
                      >
                        <Play className="h-10 w-10 text-white ml-1" fill="white" />
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            ))}

            <h2 className="text-2xl italic text-gray-700 mb-4 mt-12">
              Questi sono i corsi che hai già completato
            </h2>
            
            {userCourses.filter(c => c.status === "completed").length === 0 ? (
              <p className="text-gray-500">
                Nessun corso e-learning è stato concluso in questa piattaforma
              </p>
            ) : (
              userCourses.filter(c => c.status === "completed").map((course) => (
                <Card key={course.id} className="border-gray-200 mb-4">
                  <CardContent className="py-4 flex items-center justify-between">
                    <div>
                      <h3 className="font-semibold text-gray-900">{course.title}</h3>
                      <p className="text-sm text-green-600">Completato il {course.completedDate}</p>
                    </div>
                    <Button variant="outline">
                      Scarica Attestato
                    </Button>
                  </CardContent>
                </Card>
              ))
            )}
          </div>
        )}

        {step === "playing" && (
          <div className="text-center py-20">
            <h1 className="text-2xl font-bold text-gray-900 mb-4">Player del corso</h1>
            <p className="text-gray-600">Il player video verrà implementato qui...</p>
            <Button 
              variant="outline" 
              className="mt-8"
              onClick={() => setStep("courses")}
            >
              Torna ai corsi
            </Button>
          </div>
        )}
      </main>
    </div>
  );
}
