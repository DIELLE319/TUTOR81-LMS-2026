import { Video, ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Videoconference() {
  const openVideoconference = () => {
    window.open('https://meet.jit.si/tutor81-formazione', '_blank');
  };

  return (
    <div className="min-h-screen bg-black p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-yellow-400" data-testid="text-page-title">
          Videoconferenza
        </h1>
        <p className="text-gray-400 mt-1">Gestione aule virtuali per formazione in videoconferenza</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader>
            <CardTitle className="text-white flex items-center gap-2">
              <Video className="h-5 w-5 text-red-500" />
              Avvia Videoconferenza
            </CardTitle>
            <CardDescription className="text-gray-400">
              Crea una nuova stanza per la formazione
            </CardDescription>
          </CardHeader>
          <CardContent>
            <Button 
              onClick={openVideoconference}
              className="w-full bg-red-600 hover:bg-red-700 text-white"
              data-testid="button-start-videoconference"
            >
              <Video className="h-4 w-4 mr-2" />
              Avvia Aula Virtuale
              <ExternalLink className="h-4 w-4 ml-2" />
            </Button>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader>
            <CardTitle className="text-white">Prossime Lezioni</CardTitle>
            <CardDescription className="text-gray-400">
              Lezioni programmate
            </CardDescription>
          </CardHeader>
          <CardContent>
            <p className="text-gray-500 text-sm">Nessuna lezione programmata</p>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader>
            <CardTitle className="text-white">Storico</CardTitle>
            <CardDescription className="text-gray-400">
              Lezioni passate
            </CardDescription>
          </CardHeader>
          <CardContent>
            <p className="text-gray-500 text-sm">Nessuna lezione registrata</p>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
