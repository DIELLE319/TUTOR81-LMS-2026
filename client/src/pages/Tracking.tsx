import { BarChart3 } from "lucide-react";

export default function Tracking() {
  return (
    <div>
      <h1 className="text-xl font-bold text-yellow-500 mb-4">Tracciamento</h1>
      <div className="bg-[#141414] rounded-xl border border-white/5 p-12 text-center">
        <BarChart3 size={48} className="mx-auto text-gray-600 mb-4" />
        <p className="text-gray-500">Il modulo di tracciamento dettagliato sarà disponibile a breve.</p>
        <p className="text-sm text-gray-600 mt-2">I dati di progresso sono visibili nella sezione Corsi Attivati.</p>
      </div>
    </div>
  );
}
