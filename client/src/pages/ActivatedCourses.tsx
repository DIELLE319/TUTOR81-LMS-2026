import { useState, useMemo } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { ChevronDown, Search } from "lucide-react";

interface Enrollment {
  id: number;
  companyName: string;
  userName: string;
  userEmail: string;
  courseName: string;
  startDate: string | null;
  endDate: string | null;
  lastAccessAt: string | null;
  progress: number;
  status: string;
  licenseCode: string;
  tutorName: string;
  createdAt: string | null;
}

export default function ActivatedCourses() {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [aziendaFilter, setAziendaFilter] = useState("all");
  const [openAction, setOpenAction] = useState<number | null>(null);
  const [selected, setSelected] = useState<number[]>([]);
  const [pageSize, setPageSize] = useState(100);
  const [page, setPage] = useState(1);
  const qc = useQueryClient();
  const { toast } = useToast();

  const { data: enrollments = [], isLoading } = useQuery<Enrollment[]>({
    queryKey: ["enrollments"],
    queryFn: () => fetch("/api/enrollments", { credentials: "include" }).then((r) => r.json()),
  });

  const deleteMut = useMutation({
    mutationFn: (ids: number[]) => apiRequest("DELETE", "/api/enrollments", { enrollmentIds: ids }),
    onSuccess: () => { toast({ title: "Licenza rimossa" }); qc.invalidateQueries({ queryKey: ["enrollments"] }); },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const aziende = useMemo(() => {
    const set = new Set(enrollments.map((e) => e.companyName).filter(Boolean));
    return Array.from(set).sort();
  }, [enrollments]);

  const filtered = useMemo(() => {
    setPage(1);
    return enrollments.filter((e) => {
      if (statusFilter === "active" && (e.status !== "active" || e.progress === 0)) return false;
      if (statusFilter === "not_started" && e.progress > 0) return false;
      if (aziendaFilter !== "all" && e.companyName !== aziendaFilter) return false;
      if (search) {
        const q = search.toLowerCase();
        return (e.userName || "").toLowerCase().includes(q) || (e.courseName || "").toLowerCase().includes(q) ||
          (e.companyName || "").toLowerCase().includes(q) || (e.userEmail || "").toLowerCase().includes(q);
      }
      return true;
    });
  }, [enrollments, statusFilter, aziendaFilter, search]);

  const totalPages = Math.ceil(filtered.length / pageSize);
  const paged = filtered.slice((page - 1) * pageSize, page * pageSize);

  const inCorso = enrollments.filter((e) => e.status === "active" && e.progress > 0).length;
  const nonAvviati = enrollments.filter((e) => e.progress === 0).length;

  const toggleSelect = (id: number) => setSelected((p) => p.includes(id) ? p.filter((x) => x !== id) : [...p, id]);
  const toggleAll = () => setSelected(selected.length === paged.length ? [] : paged.map((e) => e.id));

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Lista Corsi Attivati</h1>
        <div className="flex items-center gap-2">
          <button onClick={() => setStatusFilter(statusFilter === "active" ? "all" : "active")}
            className={`h-8 px-4 rounded-lg text-xs font-bold border ${statusFilter === "active" ? "bg-green-500 text-white border-green-500" : "bg-transparent text-green-400 border-green-500/50 hover:bg-green-500/10"}`}>
            IN CORSO
          </button>
          <button onClick={() => setStatusFilter(statusFilter === "not_started" ? "all" : "not_started")}
            className={`h-8 px-4 rounded-lg text-xs font-bold border ${statusFilter === "not_started" ? "bg-white text-black border-white" : "bg-transparent text-gray-300 border-white/30 hover:bg-white/10"}`}>
            NON AVVIATI
          </button>
        </div>
      </div>

      {/* Toolbar */}
      <div className="bg-[#1a1a1a] rounded-t-xl border border-white/5 px-4 py-3 flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1.5">
          <span className="text-xs text-gray-500">Show</span>
          <select value={pageSize} onChange={(e) => { setPageSize(parseInt(e.target.value)); setPage(1); }}
            className="h-7 px-2 bg-white/5 border border-white/10 rounded text-xs text-gray-300">
            <option value={25}>25</option>
            <option value={50}>50</option>
            <option value={100}>100</option>
          </select>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="text-xs text-gray-500">Cerca utente:</span>
          <div className="relative">
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, cognome, email..."
              className="h-7 w-48 px-2.5 pr-7 bg-white/5 border border-white/10 rounded text-xs text-gray-200 placeholder-gray-600" />
            <Search size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500" />
          </div>
        </div>
        <select value={aziendaFilter} onChange={(e) => setAziendaFilter(e.target.value)}
          className="h-7 px-2 bg-white/5 border border-white/10 rounded text-xs text-gray-300 max-w-[200px]">
          <option value="all">--- Tutte le Aziende ---</option>
          {aziende.map((a) => <option key={a} value={a}>{a}</option>)}
        </select>
        <span className="ml-auto text-xs text-gray-500">Mostrando {filtered.length} risultati</span>
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Caricamento...</div>
      ) : filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Nessun corso attivato trovato</div>
      ) : (
        <>
          <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                  <th className="p-2 w-8"><input type="checkbox" checked={selected.length === paged.length && paged.length > 0} onChange={toggleAll} /></th>
                  <th className="p-2 text-left">ID</th>
                  <th className="p-2 text-left">Licenza</th>
                  <th className="p-2 text-left">Ente Formativo</th>
                  <th className="p-2 text-left">Data Vendita</th>
                  <th className="p-2 text-left">Azienda</th>
                  <th className="p-2 text-left">Cognome Nome</th>
                  <th className="p-2 text-left">Corso</th>
                  <th className="p-2 text-left">Email</th>
                  <th className="p-2 text-left">Ultimo Accesso</th>
                  <th className="p-2 text-left">Termine Programmato</th>
                  <th className="p-2 text-left">Progresso</th>
                  <th className="p-2 text-left">Azioni</th>
                </tr>
              </thead>
              <tbody>
                {paged.map((e, i) => (
                  <tr key={e.id} className={`border-b border-white/5 ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                    <td className="p-2"><input type="checkbox" checked={selected.includes(e.id)} onChange={() => toggleSelect(e.id)} /></td>
                    <td className="p-2 text-yellow-500 font-bold text-xs">{e.id}</td>
                    <td className="p-2 text-cyan-400 font-mono text-xs" title="Codice per player-login">{e.licenseCode || "—"}</td>
                    <td className="p-2 text-gray-400 text-xs">{e.tutorName || "—"}</td>
                    <td className="p-2 text-gray-400 text-xs">{e.createdAt ? new Date(e.createdAt).toLocaleDateString("it-IT") : e.startDate ? new Date(e.startDate).toLocaleDateString("it-IT") : "—"}</td>
                    <td className="p-2 text-gray-300 text-xs">{e.companyName}</td>
                    <td className="p-2 text-gray-200 text-xs font-medium">{e.userName}</td>
                    <td className="p-2 text-cyan-400 text-xs font-medium">{e.courseName}</td>
                    <td className="p-2 text-gray-400 text-xs">{e.userEmail}</td>
                    <td className="p-2 text-gray-400 text-xs">{e.lastAccessAt ? new Date(e.lastAccessAt).toLocaleDateString("it-IT") : "—"}</td>
                    <td className="p-2 text-gray-400 text-xs">{e.endDate ? new Date(e.endDate).toLocaleDateString("it-IT") : "—"}</td>
                    <td className="p-2">
                      <div className="w-20 h-5 bg-white/5 rounded overflow-hidden relative">
                        <div className={`h-full rounded text-[10px] font-bold flex items-center justify-center text-white ${
                          e.progress >= 100 ? "bg-green-500" : e.progress > 0 ? "bg-orange-500" : "bg-red-500"
                        }`} style={{ width: `${Math.max(e.progress, 15)}%` }}>
                          {e.progress}
                        </div>
                      </div>
                    </td>
                    <td className="p-2 relative">
                      <button onClick={() => setOpenAction(openAction === e.id ? null : e.id)}
                        className="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-gray-300 bg-white/5 rounded">
                        <ChevronDown size={12} />
                      </button>
                      {openAction === e.id && (
                        <div className="absolute right-0 top-8 z-50 bg-[#1a1a1a] border border-white/10 rounded-lg shadow-xl py-1 w-44"
                          onMouseLeave={() => setOpenAction(null)}>
                          <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Modifica scadenza</button>
                          <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Invia avvia corso</button>
                          <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Invia sollecito</button>
                          <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Avvia corso</button>
                          <button onClick={() => { if (confirm("Rimuovere questa licenza?")) { deleteMut.mutate([e.id]); setOpenAction(null); } }}
                            className="w-full text-left px-3 py-1.5 text-xs text-red-400 hover:bg-red-500/10">Rimuovi licenza</button>
                        </div>
                      )}
                    </td>
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
