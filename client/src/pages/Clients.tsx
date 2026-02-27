import { useState, useMemo, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link } from "wouter";
import { Plus, Search, Download, FileSpreadsheet, Star } from "lucide-react";

interface Client { id: number; businessName: string; city: string | null; phone: string | null; email: string | null; contactPerson: string | null }
interface TutorGroup { tutor: { id: number; businessName: string; subscriptionType: string | null }; clients: Client[] }

async function fetchClients(): Promise<TutorGroup[]> {
  const r = await fetch("/api/clients", { credentials: "include" });
  if (!r.ok) throw new Error(`Errore ${r.status}`);
  const data = await r.json();
  return Array.isArray(data) ? data : [];
}

export default function Clients() {
  const [search, setSearch] = useState("");
  const [enteFilter, setEnteFilter] = useState("all");
  const [pageSize, setPageSize] = useState(100);
  const [page, setPage] = useState(1);

  const { data: groups = [], isLoading, error } = useQuery<TutorGroup[]>({
    queryKey: ["clients"],
    queryFn: fetchClients,
    retry: 1,
  });

  const safeGroups = Array.isArray(groups) ? groups : [];

  const allClients = useMemo(() => {
    const list: (Client & { tutorLabel: string })[] = [];
    for (const g of safeGroups) {
      if (!g?.tutor || !Array.isArray(g.clients)) continue;
      for (const c of g.clients) {
        list.push({ ...c, tutorLabel: `#${g.tutor.id} ${g.tutor.businessName}` });
      }
    }
    return list;
  }, [safeGroups]);

  const enti = useMemo(() => {
    const set = new Set(safeGroups.map((g) => g?.tutor?.businessName).filter(Boolean) as string[]);
    return Array.from(set).sort();
  }, [safeGroups]);

  const filtered = useMemo(() => {
    return allClients.filter((c) => {
      if (enteFilter !== "all" && !c.tutorLabel.includes(enteFilter)) return false;
      if (search) {
        const q = search.toLowerCase();
        return (c.businessName || "").toLowerCase().includes(q) || (c.city || "").toLowerCase().includes(q) || (c.email || "").toLowerCase().includes(q) || (c.contactPerson || "").toLowerCase().includes(q);
      }
      return true;
    });
  }, [allClients, search, enteFilter]);

  useEffect(() => { setPage(1); }, [search, enteFilter]);

  const totalPages = Math.ceil(filtered.length / pageSize);
  const paged = filtered.slice((page - 1) * pageSize, page * pageSize);

  const exportCsv = () => {
    const header = "ID;Ente Formativo;Cliente;Città;Tel;Email\n";
    const rows = filtered.map((c) => `${c.id};${c.tutorLabel};${c.businessName};${c.city || ""};${c.phone || ""};${c.email || ""}`).join("\n");
    const blob = new Blob([header + rows], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a"); a.href = url; a.download = "aziende-clienti.csv"; a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div>
      {/* Page header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Aziende Clienti</h1>
        <Link href="/create-company" className="h-8 px-4 bg-yellow-500 text-black rounded-lg text-xs font-bold flex items-center gap-1.5 hover:bg-yellow-600">
          <Star size={13} />Nuovo Cliente
        </Link>
      </div>

      {/* Yellow toolbar */}
      <div className="bg-yellow-500 rounded-t-xl px-4 py-2.5 flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1.5">
          <span className="text-xs font-medium text-black">Show</span>
          <select value={pageSize} onChange={(e) => { setPageSize(parseInt(e.target.value)); setPage(1); }}
            className="h-7 px-2 border border-yellow-600 rounded text-xs bg-white text-gray-900">
            <option value={25}>25</option>
            <option value={50}>50</option>
            <option value={100}>100</option>
            <option value={500}>500</option>
          </select>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="text-xs font-medium text-black">Cerca:</span>
          <div className="relative">
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Azienda, città, email..."
              className="h-7 w-48 px-2.5 pr-7 border border-yellow-600 rounded text-xs bg-white text-gray-900 placeholder-gray-400" />
            <Search size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>
        <select value={enteFilter} onChange={(e) => setEnteFilter(e.target.value)}
          className="h-7 px-2 border border-yellow-600 rounded text-xs bg-white text-gray-900 max-w-[200px]">
          <option value="all">--- Tutti gli Enti ---</option>
          {enti.map((e) => <option key={e} value={e}>{e}</option>)}
        </select>
        <div className="ml-auto flex items-center gap-2">
          <span className="text-sm font-bold text-black">Totale: {filtered.length} aziende</span>
          <button onClick={exportCsv} className="h-7 px-3 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 flex items-center gap-1 hover:bg-gray-50">
            <Download size={12} />CSV
          </button>
          <button onClick={exportCsv} className="h-7 px-3 bg-green-600 text-white rounded text-xs font-bold flex items-center gap-1 hover:bg-green-700">
            <FileSpreadsheet size={12} />Excel
          </button>
        </div>
      </div>

      {/* Sub-bar with Nuova Azienda */}
      <div className="bg-[#141414] border-x border-white/5 px-4 py-2 flex justify-end">
        <Link href="/create-company" className="h-7 px-3 bg-white/5 border border-white/10 text-gray-300 rounded text-xs flex items-center gap-1.5 hover:bg-white/10">
          <Plus size={12} />Nuova Azienda
        </Link>
      </div>

      {error ? <div className="text-center py-12 text-red-400 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Errore: {(error as Error).message}</div> : isLoading ? <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Nessun cliente trovato</div> : (
        <>
          <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                  <th className="p-2.5 text-left">ID</th>
                  <th className="p-2.5 text-left">Ente Formativo</th>
                  <th className="p-2.5 text-left">Cliente</th>
                  <th className="p-2.5 text-left">Città</th>
                  <th className="p-2.5 text-left">Tel</th>
                  <th className="p-2.5 text-left">Email</th>
                </tr>
              </thead>
              <tbody>
                {paged.map((c, i) => (
                  <tr key={c.id} className={`border-b border-white/5 hover:bg-white/[0.03] ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                    <td className="p-2.5 text-yellow-500 font-bold text-xs">{c.id}</td>
                    <td className="p-2.5">
                      <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-400">{c.tutorLabel}</span>
                    </td>
                    <td className="p-2.5 text-gray-200 text-xs font-medium">{c.businessName}</td>
                    <td className="p-2.5 text-gray-400 text-xs">{c.city || "—"}</td>
                    <td className="p-2.5 text-gray-400 text-xs">{c.phone || "—"}</td>
                    <td className="p-2.5 text-blue-400 text-xs">{c.email || "—"}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          {totalPages > 1 && (
            <div className="flex items-center justify-between mt-3 px-1">
              <span className="text-xs text-gray-500">{(page - 1) * pageSize + 1}–{Math.min(page * pageSize, filtered.length)} di {filtered.length}</span>
              <div className="flex items-center gap-1">
                <button onClick={() => setPage(1)} disabled={page === 1} className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">«</button>
                <button onClick={() => setPage(page - 1)} disabled={page === 1} className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">‹</button>
                <span className="px-3 text-xs text-gray-400">{page}/{totalPages}</span>
                <button onClick={() => setPage(page + 1)} disabled={page === totalPages} className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">›</button>
                <button onClick={() => setPage(totalPages)} disabled={page === totalPages} className="h-7 px-2 text-xs rounded bg-white/5 text-gray-400 hover:bg-white/10 disabled:opacity-30">»</button>
              </div>
            </div>
          )}
        </>
      )}
    </div>
  );
}
