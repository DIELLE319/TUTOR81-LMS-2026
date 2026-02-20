import { MessageSquare } from "lucide-react";

export default function Feedback() {
  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Feedback</h1>
      <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
        <MessageSquare size={48} className="mx-auto text-gray-300 mb-4" />
        <p className="text-gray-500">Il modulo feedback sarà disponibile a breve.</p>
      </div>
    </div>
  );
}
