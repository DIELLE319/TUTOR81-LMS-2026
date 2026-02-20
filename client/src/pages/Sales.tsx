import { useState, useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, Download } from "lucide-react";

interface Sale { id: number; tutorName: string | null; companyName: string | null; courseTitle: string | null; qta: number; price: string; creationDate: string | null; code: string | null; invoiced: boolean | null }

export default function Sales() {
  const [search, setSearch] = useState("");
  const { data: sales = [], isLoading } = useQuery<Sale[]>({
    queryKey: ["sales"],
    queryFn: () => fetch("/api/sales", { credentials: "include" }).then((r) => r.json()),
  });

  const filtered = useMemo(() => sales.filter((s) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (s.courseTitle || "").toLowerCase().includes(q) || (s.companyName || "").toLowerCase().includes(q) || (s.tutorName || "").toLowerCase().includes(q);
  }), [sales, search]);

  const exportCsv = () => {
    const header = "ID;Data;Ente;Azienda;Corso;Qtà;Prezzo;Fatturato\n";
    const rows = filtered.map((s) => `${s.id};${s.creationDate || ""};${s.tutorName || ""};${s.companyName || ""};${s.courseTitle || ""};${s.qta};${s.price};${s.invoiced ? "Sì" : "No"}`).join("\n");
    const blob = new Blob([header + rows], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a"); a.href = url; a.download = "vendite.csv"; a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div>
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-gray-300 mb-1">Cerca vendita</label>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Corso, azienda, ente..."
              className="w-full h-9 pl-9 pr-3 bg-white border-0 rounded text-sm text-gray-900" />
          </div>
        </div>
        <div className="text-xs text-gray-300 self-center">{filtered.length} vendite</div>
        <button onClick={exportCsv} className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
          <Download size={14} />CSV
        </button>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessuna vendita</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                <th className="p-3 text-left w-12">ID</th>
                <th className="p-3 text-left">Data</th>
                <th className="p-3 text-left">Ente</th>
                <th className="p-3 text-left">Azienda</th>
                <th className="p-3 text-left">Corso</th>
                <th className="p-3 text-left">Qtà</th>
                <th className="p-3 text-left">Prezzo</th>
                <th className="p-3 text-left">Fatt.</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((s) => (
                <tr key={s.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="p-3 text-gray-400 font-mono text-xs">{s.id}</td>
                  <td className="p-3 text-gray-500 text-xs">{s.creationDate ? new Date(s.creationDate).toLocaleDateString("it-IT") : "—"}</td>
                  <td className="p-3 text-gray-700 text-xs">{s.tutorName || "—"}</td>
                  <td className="p-3 text-gray-700 text-xs">{s.companyName || "—"}</td>
                  <td className="p-3 text-gray-900 text-xs font-medium">{s.courseTitle || "—"}</td>
                  <td className="p-3 text-gray-800 font-bold text-center">{s.qta}</td>
                  <td className="p-3 text-green-600 font-bold text-xs">€{s.price}</td>
                  <td className="p-3">
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${s.invoiced ? "bg-green-500 text-white" : "bg-gray-400 text-white"}`}>
                      {s.invoiced ? "Sì" : "No"}
                    </span>
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
