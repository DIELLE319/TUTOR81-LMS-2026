import { MessageSquare } from "lucide-react";

export default function Feedback() {
  return (
    <div>
      <h1 className="text-xl font-bold text-yellow-500 mb-4">Feedback</h1>
      <div className="bg-[#141414] rounded-xl border border-white/5 p-12 text-center">
        <MessageSquare size={48} className="mx-auto text-gray-600 mb-4" />
        <p className="text-gray-500">Il modulo feedback sarà disponibile a breve.</p>
      </div>
    </div>
  );
}
