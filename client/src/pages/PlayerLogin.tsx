import { useState } from "react";
import { useLocation } from "wouter";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { useToast } from "@/hooks/use-toast";
import { User, Key, LogIn } from "lucide-react";

export default function PlayerLogin() {
  const [username, setUsername] = useState("");
  const [fiscalCode, setFiscalCode] = useState("");
  const [isLoading, setIsLoading] = useState(false);
  const [, navigate] = useLocation();
  const { toast } = useToast();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsLoading(true);

    try {
      const response = await fetch("/api/player/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, fiscalCode }),
      });

      const data = await response.json();

      if (response.ok && data.success) {
        localStorage.setItem("playerUser", JSON.stringify(data.user));
        localStorage.setItem("playerEnrollment", JSON.stringify(data.enrollment));
        toast({ title: "Accesso effettuato", description: `Benvenuto ${data.user.firstName}!` });
        navigate(`/player/${data.enrollment.id}`);
      } else {
        toast({ 
          title: "Errore di accesso", 
          description: data.error || "Credenziali non valide", 
          variant: "destructive" 
        });
      }
    } catch (error) {
      toast({ 
        title: "Errore", 
        description: "Impossibile effettuare l'accesso", 
        variant: "destructive" 
      });
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 flex items-center justify-center p-4">
      <Card className="w-full max-w-md bg-white shadow-2xl">
        <CardHeader className="text-center pb-2">
          <div className="mx-auto w-16 h-16 bg-yellow-500 rounded-full flex items-center justify-center mb-4">
            <LogIn className="w-8 h-8 text-gray-900" />
          </div>
          <CardTitle className="text-2xl font-bold text-gray-900">Accedi al Corso</CardTitle>
          <CardDescription className="text-gray-600">
            Inserisci le tue credenziali per accedere al player
          </CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleLogin} className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Username</label>
              <div className="relative">
                <User className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                <Input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  placeholder="es: demo.demo"
                  className="pl-10"
                  data-testid="input-username"
                  required
                />
              </div>
            </div>
            
            <div className="space-y-2">
              <label className="text-sm font-medium text-gray-700">Codice Fiscale</label>
              <div className="relative">
                <Key className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5" />
                <Input
                  type="text"
                  value={fiscalCode}
                  onChange={(e) => setFiscalCode(e.target.value)}
                  placeholder="es: 1"
                  className="pl-10"
                  data-testid="input-fiscal-code"
                  required
                />
              </div>
            </div>

            <Button 
              type="submit" 
              className="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold"
              disabled={isLoading}
              data-testid="btn-login"
            >
              {isLoading ? "Accesso in corso..." : "Accedi"}
            </Button>
          </form>

          <div className="mt-6 p-4 bg-gray-100 rounded-lg">
            <p className="text-sm text-gray-600 text-center">
              <strong>Credenziali Demo:</strong><br />
              Username: <code className="bg-gray-200 px-1 rounded">demo.demo</code><br />
              Codice Fiscale: <code className="bg-gray-200 px-1 rounded">1</code>
            </p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
