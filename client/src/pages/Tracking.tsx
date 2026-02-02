import { BarChart2, Users, Clock, CheckCircle } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Tracking() {
  return (
    <div className="min-h-screen bg-black p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-yellow-400" data-testid="text-page-title">
          Tracciamento
        </h1>
        <p className="text-gray-400 mt-1">Monitora il progresso dei corsisti</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">Corsisti Totali</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Users className="h-5 w-5 text-yellow-400" />
              <span className="text-2xl font-bold text-white">-</span>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">In Corso</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Clock className="h-5 w-5 text-blue-400" />
              <span className="text-2xl font-bold text-white">-</span>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">Completati</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <CheckCircle className="h-5 w-5 text-green-400" />
              <span className="text-2xl font-bold text-white">-</span>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">Tempo Medio</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <BarChart2 className="h-5 w-5 text-purple-400" />
              <span className="text-2xl font-bold text-white">-</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card className="bg-zinc-900 border-zinc-800">
        <CardHeader>
          <CardTitle className="text-white">Attività Recenti</CardTitle>
          <CardDescription className="text-gray-400">
            Ultimi accessi e progressi dei corsisti
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500 text-sm text-center py-8">
            I dati di tracciamento verranno mostrati qui
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
