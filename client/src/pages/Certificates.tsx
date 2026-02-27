import { useState, useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, Download, FileText } from "lucide-react";

interface Cert { id: number; userName: string; userEmail: string; fiscalCode: string; courseName: string; companyName: string; tutorName: string; completedAt: string | null; progress: number; licenseCode: string }

export default function Certificates() {
  const [search, setSearch] = useState("");
  const { data: certs = [], isLoading } = useQuery<Cert[]>({
    queryKey: ["certificates"],
    queryFn: () => fetch("/api/attestati", { credentials: "include" }).then((r) => r.json()),
  });

  const filtered = useMemo(() => certs.filter((c) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return c.userName.toLowerCase().includes(q) || c.courseName.toLowerCase().includes(q) || c.companyName.toLowerCase().includes(q) || c.fiscalCode.toLowerCase().includes(q);
  }), [certs, search]);

  const downloadPdf = (id: number) => { window.open(`/api/certificates/${id}/pdf`, "_blank"); };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Attestati</h1>
      </div>

      {/* Yellow toolbar */}
      <div className="bg-yellow-500 rounded-t-xl px-4 py-2.5 flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1.5">
          <span className="text-xs font-medium text-black">Cerca:</span>
          <div className="relative">
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, corso, azienda, CF..."
              className="h-7 w-48 px-2.5 pr-7 border border-yellow-600 rounded text-xs bg-white text-gray-900 placeholder-gray-400" />
            <Search size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>
        <span className="ml-auto text-sm font-bold text-black">Totale: {filtered.length} attestati</span>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun attestato trovato</div> : (
        <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                <th className="p-2.5 text-left w-12">ID</th>
                <th className="p-2.5 text-left">Corsista</th>
                <th className="p-2.5 text-left">Codice Fiscale</th>
                <th className="p-2.5 text-left">Corso</th>
                <th className="p-2.5 text-left">Azienda</th>
                <th className="p-2.5 text-left">Ente Formativo</th>
                <th className="p-2.5 text-left">Completato</th>
                <th className="p-2.5 text-left">PDF</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((c, i) => (
                <tr key={c.id} className={`border-b border-white/5 hover:bg-white/[0.03] ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                  <td className="p-2.5 text-yellow-500 font-bold text-xs">{c.id}</td>
                  <td className="p-2.5 font-medium text-gray-200 text-xs">{c.userName}</td>
                  <td className="p-2.5 text-gray-400 font-mono text-[11px]">{c.fiscalCode}</td>
                  <td className="p-2.5 text-cyan-400 text-xs font-medium">{c.courseName}</td>
                  <td className="p-2.5 text-gray-400 text-xs">{c.companyName}</td>
                  <td className="p-2.5 text-gray-400 text-xs">{c.tutorName || "—"}</td>
                  <td className="p-2.5">
                    <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-green-500 text-white">
                      {c.completedAt ? new Date(c.completedAt).toLocaleDateString("it-IT") : "—"}
                    </span>
                  </td>
                  <td className="p-2.5">
                    <button onClick={() => downloadPdf(c.id)} className="h-6 px-2.5 bg-green-500 hover:bg-green-600 text-white text-[10px] font-bold rounded flex items-center gap-1">
                      <FileText size={11} />PDF
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
