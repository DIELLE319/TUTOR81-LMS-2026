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
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-gray-300 mb-1">Cerca attestato</label>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, corso, azienda, CF..."
              className="w-full h-9 pl-9 pr-3 bg-white border-0 rounded text-sm text-gray-900" />
          </div>
        </div>
        <div className="text-xs text-gray-300 self-center">{filtered.length} attestati</div>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun attestato trovato</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                <th className="p-3 text-left w-12">ID</th>
                <th className="p-3 text-left">Corsista</th>
                <th className="p-3 text-left">Codice Fiscale</th>
                <th className="p-3 text-left">Corso</th>
                <th className="p-3 text-left">Azienda</th>
                <th className="p-3 text-left">Completato</th>
                <th className="p-3 text-left">PDF</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((c) => (
                <tr key={c.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="p-3 text-gray-400 font-mono text-xs">{c.id}</td>
                  <td className="p-3 font-medium text-gray-900 text-xs">{c.userName}</td>
                  <td className="p-3 text-gray-500 font-mono text-[11px]">{c.fiscalCode}</td>
                  <td className="p-3 text-gray-800 text-xs font-medium">{c.courseName}</td>
                  <td className="p-3 text-gray-600 text-xs">{c.companyName}</td>
                  <td className="p-3 text-gray-500 text-xs">{c.completedAt ? new Date(c.completedAt).toLocaleDateString("it-IT") : "—"}</td>
                  <td className="p-3">
                    <button onClick={() => downloadPdf(c.id)} className="h-7 px-3 bg-green-500 hover:bg-green-600 text-white text-[11px] font-bold rounded flex items-center gap-1">
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
