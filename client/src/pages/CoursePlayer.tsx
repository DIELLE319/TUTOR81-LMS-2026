import { useState, useEffect } from "react";
import { useLocation } from "wouter";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { Play, Mail, LogOut } from "lucide-react";

interface UserData {
  id: number;
  firstName: string;
  lastName: string;
  fiscalCode: string;
}

interface EnrollmentData {
  id: number;
  learningProjectId: number;
  courseName: string;
  startDate: string;
  endDate: string;
  progress: number;
  status: string;
}

export default function CoursePlayer() {
  const [, setLocation] = useLocation();
  const [userData, setUserData] = useState<UserData | null>(null);
  const [enrollment, setEnrollment] = useState<EnrollmentData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedUser = localStorage.getItem("playerUser");
    const storedEnrollment = localStorage.getItem("playerEnrollment");
    
    if (!storedUser || !storedEnrollment) {
      setLocation("/player-login");
      return;
    }

    try {
      setUserData(JSON.parse(storedUser));
      setEnrollment(JSON.parse(storedEnrollment));
    } catch (e) {
      setLocation("/player-login");
      return;
    }
    
    setLoading(false);
  }, [setLocation]);

  const handleStartCourse = () => {
    if (enrollment) {
      setLocation(`/player/course/${enrollment.learningProjectId}`);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem("playerUser");
    localStorage.removeItem("playerEnrollment");
    setLocation("/player-login");
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-white flex items-center justify-center">
        <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-white">
      <header className="border-b border-gray-200 py-4">
        <div className="max-w-5xl mx-auto px-4 flex items-center justify-between">
          <div className="text-3xl font-bold">
            <span className="text-gray-800">tutor</span>
            <span className="text-yellow-500">81</span>
          </div>
          <nav className="flex items-center gap-6 text-sm text-gray-600">
            <a href="#" className="hover:text-gray-900 flex items-center gap-1">
              Assistenza <Mail className="h-4 w-4" />
            </a>
            <Button 
              variant="ghost" 
              size="sm" 
              onClick={handleLogout}
              className="text-gray-600 hover:text-gray-900"
              data-testid="button-logout"
            >
              <LogOut className="h-4 w-4 mr-2" />
              Esci
            </Button>
          </nav>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-8">
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">I TUOI CORSI</h1>
          <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p className="text-gray-700">
              <strong>Nominativo:</strong> {userData?.firstName} {userData?.lastName}
            </p>
            <p className="text-gray-700">
              <strong>Codice Fiscale:</strong> {userData?.fiscalCode}
            </p>
          </div>
        </div>

        {enrollment && (
          <Card className="border-2 border-yellow-400 overflow-hidden">
            <CardContent className="p-0">
              <div className="flex items-center">
                <div className="flex-1 p-6">
                  <h3 className="text-xl font-bold text-gray-900 mb-2">
                    {enrollment.courseName}
                  </h3>
                  <div className="flex gap-6 text-sm text-gray-600 mb-4">
                    <span>Inizio: {new Date(enrollment.startDate).toLocaleDateString('it-IT')}</span>
                    <span>Fine: {new Date(enrollment.endDate).toLocaleDateString('it-IT')}</span>
                  </div>
                  <div className="flex items-center gap-4">
                    <Progress value={enrollment.progress} className="flex-1 h-3" />
                    <span className="text-sm font-medium text-gray-700">
                      {enrollment.progress}%
                    </span>
                  </div>
                </div>
                <div className="p-6 bg-yellow-50">
                  <Button 
                    onClick={handleStartCourse}
                    className="bg-yellow-500 hover:bg-yellow-600 text-white h-16 w-16 rounded-full"
                    data-testid="button-play-course"
                  >
                    <Play className="h-8 w-8" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        )}

        <Card className="mt-8 bg-yellow-50 border-yellow-200">
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
                <strong>I TEST hanno una durata temporizzata di 30 secondi</strong>, trascorsi i quali il corso si interrompe e rientrando riprenderai dal punto di interruzione. 
                Al termine ti saranno riproposti i test per darti la possibilità di recuperare le domande errate.
              </p>
              <p>
                <strong>RIVEDERE PARTI DEL CORSO</strong> Nella barra di avanzamento puoi tornare indietro e rivedere parti del corso
              </p>
            </div>
          </CardContent>
        </Card>
      </main>

      <footer className="border-t border-gray-200 py-4 mt-8">
        <div className="max-w-5xl mx-auto px-4 text-center text-sm text-gray-500">
          &copy; {new Date().getFullYear()} Tutor81. Tutti i diritti riservati.
        </div>
      </footer>
    </div>
  );
}
