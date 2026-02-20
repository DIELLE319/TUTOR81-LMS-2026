import { useState, useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, Download } from "lucide-react";

interface Student { id: number; firstName: string | null; lastName: string | null; email: string; fiscalCode: string | null; phone: string | null; companyName: string; isActive: boolean | null }

export default function Users() {
  const [search, setSearch] = useState("");
  const { data: students = [], isLoading } = useQuery<Student[]>({
    queryKey: ["students"],
    queryFn: () => fetch("/api/students", { credentials: "include" }).then((r) => r.json()),
  });

  const filtered = useMemo(() => students.filter((s) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (s.firstName || "").toLowerCase().includes(q) || (s.lastName || "").toLowerCase().includes(q) || s.email.toLowerCase().includes(q) || (s.fiscalCode || "").toLowerCase().includes(q) || s.companyName.toLowerCase().includes(q);
  }), [students, search]);

  const exportCsv = () => {
    const header = "ID;Cognome;Nome;Email;CF;Azienda;Stato\n";
    const rows = filtered.map((s) => `${s.id};${s.lastName};${s.firstName};${s.email};${s.fiscalCode || ""};${s.companyName};${s.isActive ? "Attivo" : "Disattivo"}`).join("\n");
    const blob = new Blob([header + rows], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a"); a.href = url; a.download = "utenti.csv"; a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div>
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-gray-300 mb-1">Cerca utente</label>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, email, CF, azienda..."
              className="w-full h-9 pl-9 pr-3 bg-white border-0 rounded text-sm text-gray-900" />
          </div>
        </div>
        <div className="text-xs text-gray-300 self-center">{filtered.length} utenti</div>
        <button onClick={exportCsv} className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
          <Download size={14} />CSV
        </button>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun utente trovato</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                <th className="p-3 text-left w-12">ID</th>
                <th className="p-3 text-left">Cognome</th>
                <th className="p-3 text-left">Nome</th>
                <th className="p-3 text-left">Email</th>
                <th className="p-3 text-left">Codice Fiscale</th>
                <th className="p-3 text-left">Azienda</th>
                <th className="p-3 text-left">Stato</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((s) => (
                <tr key={s.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="p-3 text-gray-400 font-mono text-xs">{s.id}</td>
                  <td className="p-3 font-medium text-gray-900 text-xs">{s.lastName || "—"}</td>
                  <td className="p-3 text-gray-700 text-xs">{s.firstName || "—"}</td>
                  <td className="p-3 text-gray-600 text-xs">{s.email}</td>
                  <td className="p-3 text-gray-500 font-mono text-[11px]">{s.fiscalCode || "—"}</td>
                  <td className="p-3 text-gray-600 text-xs">{s.companyName}</td>
                  <td className="p-3">
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${s.isActive ? "bg-green-500 text-white" : "bg-red-500 text-white"}`}>
                      {s.isActive ? "Attivo" : "Disattivo"}
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
