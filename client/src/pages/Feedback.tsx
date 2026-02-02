import { MessageSquare, Star, ThumbsUp, ThumbsDown } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

export default function Feedback() {
  return (
    <div className="min-h-screen bg-black p-6">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-yellow-400" data-testid="text-page-title">
          Feedback
        </h1>
        <p className="text-gray-400 mt-1">Valutazioni e feedback dei corsisti</p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">Valutazione Media</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <Star className="h-5 w-5 text-yellow-400 fill-yellow-400" />
              <span className="text-2xl font-bold text-white">-</span>
              <span className="text-gray-400">/5</span>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">Feedback Positivi</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <ThumbsUp className="h-5 w-5 text-green-400" />
              <span className="text-2xl font-bold text-white">-</span>
            </div>
          </CardContent>
        </Card>

        <Card className="bg-zinc-900 border-zinc-800">
          <CardHeader className="pb-2">
            <CardDescription className="text-gray-400">Feedback Negativi</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center gap-2">
              <ThumbsDown className="h-5 w-5 text-red-400" />
              <span className="text-2xl font-bold text-white">-</span>
            </div>
          </CardContent>
        </Card>
      </div>

      <Card className="bg-zinc-900 border-zinc-800">
        <CardHeader>
          <CardTitle className="text-white flex items-center gap-2">
            <MessageSquare className="h-5 w-5" />
            Feedback Recenti
          </CardTitle>
          <CardDescription className="text-gray-400">
            Commenti e valutazioni dei corsisti sui corsi
          </CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-gray-500 text-sm text-center py-8">
            I feedback dei corsisti verranno mostrati qui
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
