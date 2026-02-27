import { useState, useMemo, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, Download } from "lucide-react";

interface Student { id: number; firstName: string | null; lastName: string | null; email: string; fiscalCode: string | null; phone: string | null; companyName: string; isActive: boolean | null }

const PAGE_SIZE = 100;

async function fetchStudents(): Promise<Student[]> {
  const r = await fetch("/api/students", { credentials: "include" });
  if (!r.ok) throw new Error(`Errore ${r.status}`);
  const data = await r.json();
  return Array.isArray(data) ? data : [];
}

export default function Users() {
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const { data: students = [], isLoading, error } = useQuery<Student[]>({
    queryKey: ["students"],
    queryFn: fetchStudents,
    retry: 1,
  });

  const safeStudents = Array.isArray(students) ? students : [];

  const filtered = useMemo(() => {
    return safeStudents.filter((s) => {
      if (!search) return true;
      const q = search.toLowerCase();
      return (s.firstName || "").toLowerCase().includes(q) || (s.lastName || "").toLowerCase().includes(q) || (s.email || "").toLowerCase().includes(q) || (s.fiscalCode || "").toLowerCase().includes(q) || (s.companyName || "").toLowerCase().includes(q);
    });
  }, [safeStudents, search]);

  useEffect(() => { setPage(1); }, [search]);

  const totalPages = Math.ceil(filtered.length / PAGE_SIZE);
  const paged = filtered.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

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
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Elenco Utenti</h1>
      </div>

      {/* Yellow toolbar */}
      <div className="bg-yellow-500 rounded-t-xl px-4 py-2.5 flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1.5">
          <span className="text-xs font-medium text-black">Cerca:</span>
          <div className="relative">
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, email, CF, azienda..."
              className="h-7 w-48 px-2.5 pr-7 border border-yellow-600 rounded text-xs bg-white text-gray-900 placeholder-gray-400" />
            <Search size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>
        <div className="ml-auto flex items-center gap-2">
          <span className="text-sm font-bold text-black">Totale: {filtered.length} utenti</span>
          <button onClick={exportCsv} className="h-7 px-3 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 flex items-center gap-1 hover:bg-gray-50">
            <Download size={12} />CSV
          </button>
        </div>
      </div>

      {error ? <div className="text-center py-12 text-red-400 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Errore: {(error as Error).message}</div> : isLoading ? <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Nessun utente trovato</div> : (
        <>
          <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                  <th className="p-2.5 text-left w-12">ID</th>
                  <th className="p-2.5 text-left">Cognome</th>
                  <th className="p-2.5 text-left">Nome</th>
                  <th className="p-2.5 text-left">Email</th>
                  <th className="p-2.5 text-left">Codice Fiscale</th>
                  <th className="p-2.5 text-left">Azienda</th>
                  <th className="p-2.5 text-left">Stato</th>
                </tr>
              </thead>
              <tbody>
                {paged.map((s, i) => (
                  <tr key={s.id} className={`border-b border-white/5 hover:bg-white/[0.03] ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                    <td className="p-2.5 text-yellow-500 font-bold text-xs">{s.id}</td>
                    <td className="p-2.5 font-medium text-gray-200 text-xs">{s.lastName || "—"}</td>
                    <td className="p-2.5 text-gray-300 text-xs">{s.firstName || "—"}</td>
                    <td className="p-2.5 text-blue-400 text-xs">{s.email}</td>
                    <td className="p-2.5 text-gray-400 font-mono text-[11px]">{s.fiscalCode || "—"}</td>
                    <td className="p-2.5">
                      <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-400">{s.companyName || "—"}</span>
                    </td>
                    <td className="p-2.5">
                      <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${s.isActive ? "bg-green-500 text-white" : "bg-red-500 text-white"}`}>
                        {s.isActive ? "Attivo" : "Disattivo"}
                      </span>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          {totalPages > 1 && (
            <div className="flex items-center justify-between mt-3 px-1">
              <span className="text-xs text-gray-500">
                {(page - 1) * PAGE_SIZE + 1}–{Math.min(page * PAGE_SIZE, filtered.length)} di {filtered.length}
              </span>
              <div className="flex items-center gap-1">
                <button onClick={() => setPage(1)} disabled={page === 1}
                  className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">«</button>
                <button onClick={() => setPage(page - 1)} disabled={page === 1}
                  className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">‹</button>
                <span className="px-3 text-xs text-gray-400">{page}/{totalPages}</span>
                <button onClick={() => setPage(page + 1)} disabled={page === totalPages}
                  className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">›</button>
                <button onClick={() => setPage(totalPages)} disabled={page === totalPages}
                  className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">»</button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}
