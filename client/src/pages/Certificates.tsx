import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, Download, Award } from "lucide-react";

interface Cert { id: number; userName: string; userEmail: string; fiscalCode: string; courseName: string; companyName: string; tutorName: string; completedAt: string | null; progress: number; licenseCode: string }

export default function Certificates() {
  const [search, setSearch] = useState("");
  const { data: certs = [], isLoading } = useQuery<Cert[]>({
    queryKey: ["certificates"],
    queryFn: () => fetch("/api/attestati", { credentials: "include" }).then((r) => r.json()),
  });

  const filtered = certs.filter((c) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return c.userName.toLowerCase().includes(q) || c.courseName.toLowerCase().includes(q) || c.companyName.toLowerCase().includes(q);
  });

  const downloadPdf = (id: number) => { window.open(`/api/certificates/${id}/pdf`, "_blank"); };

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Attestati</h1>
      <div className="relative mb-4">
        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cerca attestato..." className="w-full h-10 pl-9 pr-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
      </div>
      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun attestato trovato</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b"><tr>
              <th className="p-3 text-left font-medium text-gray-600">Corsista</th>
              <th className="p-3 text-left font-medium text-gray-600">Corso</th>
              <th className="p-3 text-left font-medium text-gray-600">Azienda</th>
              <th className="p-3 text-left font-medium text-gray-600">Completato</th>
              <th className="p-3 text-left font-medium text-gray-600">PDF</th>
            </tr></thead>
            <tbody>
              {filtered.map((c) => (
                <tr key={c.id} className="border-b last:border-0 hover:bg-gray-50">
                  <td className="p-3">
                    <div className="font-medium text-gray-900">{c.userName}</div>
                    <div className="text-xs text-gray-500">{c.fiscalCode}</div>
                  </td>
                  <td className="p-3 text-gray-700">{c.courseName}</td>
                  <td className="p-3 text-gray-500">{c.companyName}</td>
                  <td className="p-3 text-gray-500 text-xs">{c.completedAt ? new Date(c.completedAt).toLocaleDateString("it-IT") : "—"}</td>
                  <td className="p-3"><button onClick={() => downloadPdf(c.id)} className="text-blue-600 hover:text-blue-800"><Download size={16} /></button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
