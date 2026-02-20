import { BarChart3 } from "lucide-react";

export default function Tracking() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Tracciamento</h1>
      <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <BarChart3 size={48} className="mx-auto text-gray-300 mb-4" />
        <p className="text-gray-500">Il modulo di tracciamento dettagliato sarà disponibile a breve.</p>
        <p className="text-sm text-gray-400 mt-2">I dati di progresso sono visibili nella sezione Corsi Attivi.</p>
      </div>
    </div>
  );
}
