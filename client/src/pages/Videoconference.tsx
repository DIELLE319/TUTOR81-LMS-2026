import { Video } from "lucide-react";

export default function Videoconference() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Videoconferenza</h1>
      <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <Video size={48} className="mx-auto text-gray-300 mb-4" />
        <p className="text-gray-500">Il modulo videoconferenza (Jitsi) sarà disponibile a breve.</p>
      </div>
    </div>
  );
}
