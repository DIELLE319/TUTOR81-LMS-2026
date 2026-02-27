import { useState, useMemo, useEffect, Fragment } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Download, FileSpreadsheet, Search, ChevronDown, ChevronRight, Users } from "lucide-react";

interface Sale {
  id: number; tutorName: string | null; companyName: string | null; courseTitle: string | null;
  qta: number; price: string; creationDate: string | null; code: string | null;
  invoiced: boolean | null; listPrice: string | null;
  tutorId: number | null; customerCompanyId: number | null; learningProjectId: number | null;
}

interface Enrollment {
  id: number; companyName: string; userName: string; userEmail: string; courseName: string;
  startDate: string | null; endDate: string | null; lastAccessAt: string | null;
  progress: number; status: string; licenseCode: string; tutorName: string;
}

export default function Sales() {
  const [search, setSearch] = useState("");
  const [pageSize, setPageSize] = useState(100);
  const [page, setPage] = useState(1);
  const [enteFilter, setEnteFilter] = useState("all");
  const [aziendaFilter, setAziendaFilter] = useState("all");
  const [expanded, setExpanded] = useState<number[]>([]);
  const [openAction, setOpenAction] = useState<number | null>(null);
  const qc = useQueryClient();
  const { toast } = useToast();

  const { data: sales = [], isLoading: loadingSales, error: salesError } = useQuery<Sale[]>({
    queryKey: ["sales"],
    queryFn: async () => { const r = await fetch("/api/sales", { credentials: "include" }); if (!r.ok) throw new Error(`Errore ${r.status}`); const d = await r.json(); return Array.isArray(d) ? d : []; },
    retry: 1,
  });

  const { data: enrollments = [], isLoading: loadingEnr } = useQuery<Enrollment[]>({
    queryKey: ["enrollments"],
    queryFn: async () => { const r = await fetch("/api/enrollments", { credentials: "include" }); if (!r.ok) throw new Error(`Errore ${r.status}`); const d = await r.json(); return Array.isArray(d) ? d : []; },
    retry: 1,
  });

  const deleteMut = useMutation({
    mutationFn: (ids: number[]) => apiRequest("DELETE", "/api/enrollments", { enrollmentIds: ids }),
    onSuccess: () => { toast({ title: "Licenza rimossa" }); qc.invalidateQueries({ queryKey: ["enrollments"] }); },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const isLoading = loadingSales || loadingEnr;
  const safeSales = Array.isArray(sales) ? sales : [];
  const safeEnrollments = Array.isArray(enrollments) ? enrollments : [];

  const enrollmentsBySale = useMemo(() => {
    const map: Record<number, Enrollment[]> = {};
    for (const s of safeSales) {
      const matched = safeEnrollments.filter((e) =>
        e.courseName === s.courseTitle && e.companyName === (s.companyName || "")
      );
      if (matched.length > 0) map[s.id] = matched;
    }
    return map;
  }, [safeSales, safeEnrollments]);

  const enti = useMemo(() => {
    const set = new Set(safeSales.map((s) => s.tutorName).filter(Boolean) as string[]);
    return Array.from(set).sort();
  }, [safeSales]);

  const aziende = useMemo(() => {
    const set = new Set(safeSales.map((s) => s.companyName).filter(Boolean) as string[]);
    return Array.from(set).sort();
  }, [safeSales]);

  const filtered = useMemo(() => {
    return safeSales.filter((s) => {
      if (enteFilter !== "all" && s.tutorName !== enteFilter) return false;
      if (aziendaFilter !== "all" && s.companyName !== aziendaFilter) return false;
      if (search) {
        const q = search.toLowerCase();
        const matchSale = (s.courseTitle || "").toLowerCase().includes(q) || (s.companyName || "").toLowerCase().includes(q) ||
          (s.tutorName || "").toLowerCase().includes(q) || String(s.id).includes(q);
        const matchEnr = (enrollmentsBySale[s.id] || []).some((e) =>
          e.userName.toLowerCase().includes(q) || e.userEmail.toLowerCase().includes(q)
        );
        return matchSale || matchEnr;
      }
      return true;
    });
  }, [safeSales, search, enteFilter, aziendaFilter, enrollmentsBySale]);

  useEffect(() => { setPage(1); }, [search, enteFilter, aziendaFilter]);

  const totalPages = Math.ceil(filtered.length / pageSize);
  const paged = filtered.slice((page - 1) * pageSize, page * pageSize);

  const toggleExpand = (id: number) => setExpanded((p) => p.includes(id) ? p.filter((x) => x !== id) : [...p, id]);

  const exportCsv = () => {
    const header = "N.Ordine;Data;Ente Formativo;Cliente;Corso;QTA;Tuo Costo;Corsista;Email;Progresso\n";
    const rows: string[] = [];
    for (const s of filtered) {
      const enrs = enrollmentsBySale[s.id] || [];
      if (enrs.length === 0) {
        rows.push(`${s.id};${s.creationDate || ""};${s.tutorName || ""};${s.companyName || ""};${s.courseTitle || ""};${s.qta};${s.price};;;`);
      } else {
        for (const e of enrs) {
          rows.push(`${s.id};${s.creationDate || ""};${s.tutorName || ""};${s.companyName || ""};${s.courseTitle || ""};${s.qta};${s.price};${e.userName};${e.userEmail};${e.progress}%`);
        }
      }
    }
    const blob = new Blob([header + rows.join("\n")], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a"); a.href = url; a.download = "corsi-venduti.csv"; a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Corsi Venduti</h1>
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
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Corso, cliente, corsista..."
              className="h-7 w-44 px-2.5 pr-7 border border-yellow-600 rounded text-xs bg-white text-gray-900 placeholder-gray-400" />
            <Search size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>
        <select value={enteFilter} onChange={(e) => setEnteFilter(e.target.value)}
          className="h-7 px-2 border border-yellow-600 rounded text-xs bg-white text-gray-900 max-w-[180px]">
          <option value="all">--- Tutti gli Enti ---</option>
          {enti.map((e) => <option key={e} value={e}>{e}</option>)}
        </select>
        <select value={aziendaFilter} onChange={(e) => setAziendaFilter(e.target.value)}
          className="h-7 px-2 border border-yellow-600 rounded text-xs bg-white text-gray-900 max-w-[180px]">
          <option value="all">--- Tutte le Aziende ---</option>
          {aziende.map((a) => <option key={a} value={a}>{a}</option>)}
        </select>
        <div className="ml-auto flex items-center gap-2">
          <span className="text-sm font-bold text-black">Totale: {filtered.length} vendite</span>
          <button onClick={exportCsv} className="h-7 px-3 bg-white border border-gray-300 rounded text-xs font-medium text-gray-700 flex items-center gap-1 hover:bg-gray-50">
            <Download size={12} />CSV
          </button>
          <button onClick={exportCsv} className="h-7 px-3 bg-green-600 text-white rounded text-xs font-bold flex items-center gap-1 hover:bg-green-700">
            <FileSpreadsheet size={12} />Excel
          </button>
        </div>
      </div>

      {salesError ? <div className="text-center py-12 text-red-400 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Errore: {(salesError as Error).message}</div> : isLoading ? <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Nessun corso venduto</div> : (
        <>
          <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
            <table className="w-full text-sm">
              <thead>
                <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                  <th className="p-2.5 w-8"></th>
                  <th className="p-2.5 text-left">N. Ordine</th>
                  <th className="p-2.5 text-left">Data</th>
                  <th className="p-2.5 text-left">Ente Formativo</th>
                  <th className="p-2.5 text-left">Cliente</th>
                  <th className="p-2.5 text-left">Corso</th>
                  <th className="p-2.5 text-center">QTA</th>
                  <th className="p-2.5 text-right">Tuo Costo</th>
                </tr>
              </thead>
              <tbody>
                {paged.map((s, i) => {
                  const enrs = enrollmentsBySale[s.id] || [];
                  const isExp = expanded.includes(s.id);
                  return (
                    <Fragment key={s.id}>
                      <tr className={`border-b border-white/5 cursor-pointer ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"} hover:bg-white/[0.03]`}
                        onClick={() => enrs.length > 0 && toggleExpand(s.id)}>
                        <td className="p-2.5 text-center">
                          {enrs.length > 0 ? (
                            isExp ? <ChevronDown size={14} className="text-yellow-500" /> : <ChevronRight size={14} className="text-gray-500" />
                          ) : <span className="text-gray-700">—</span>}
                        </td>
                        <td className="p-2.5 text-yellow-500 font-bold text-xs">{s.id}</td>
                        <td className="p-2.5 text-gray-300 text-xs whitespace-nowrap">
                          {s.creationDate ? new Date(s.creationDate).toLocaleDateString("it-IT") : "—"}
                          <br/>
                          <span className="text-gray-600 text-[10px]">{s.creationDate ? new Date(s.creationDate).toLocaleTimeString("it-IT", { hour: "2-digit", minute: "2-digit" }) : ""}</span>
                        </td>
                        <td className="p-2.5">
                          <span className="text-[10px] font-bold px-1.5 py-0.5 rounded bg-purple-500/20 text-purple-400">{s.tutorName || "—"}</span>
                        </td>
                        <td className="p-2.5 text-gray-300 text-xs font-medium">{s.companyName || "—"}</td>
                        <td className="p-2.5">
                          <div className="text-gray-100 text-xs font-medium">{s.courseTitle || "—"}</div>
                          {enrs.length > 0 && (
                            <div className="text-[10px] text-gray-500 mt-0.5 flex items-center gap-1">
                              <Users size={10} />{enrs.length} corsist{enrs.length === 1 ? "a" : "i"}
                            </div>
                          )}
                        </td>
                        <td className="p-2.5 text-gray-200 font-bold text-center">{s.qta}</td>
                        <td className="p-2.5 text-green-400 font-bold text-xs text-right">{parseFloat(s.price).toFixed(2)} €</td>
                      </tr>
                      {isExp && enrs.map((e) => (
                        <tr key={`enr-${e.id}`} className="bg-[#0f0f0f] border-b border-white/[0.03]">
                          <td className="p-0"></td>
                          <td colSpan={7} className="px-4 py-2">
                            <div className="flex items-center gap-4">
                              <div className="flex-1 grid grid-cols-[1fr_1fr_100px_100px_80px_auto] items-center gap-3 text-xs">
                                <div>
                                  <div className="text-gray-200 font-medium">{e.userName}</div>
                                  <div className="text-gray-500 text-[10px]">{e.userEmail}</div>
                                </div>
                                <div className="text-gray-400 text-[10px]">
                                  Licenza: <span className="text-yellow-500 font-bold">{e.licenseCode || "—"}</span>
                                </div>
                                <div className="text-gray-500 text-[10px]">
                                  Inizio: {e.startDate ? new Date(e.startDate).toLocaleDateString("it-IT") : "—"}
                                </div>
                                <div className="text-gray-500 text-[10px]">
                                  Scad: {e.endDate ? new Date(e.endDate).toLocaleDateString("it-IT") : "—"}
                                </div>
                                <div>
                                  <div className="w-16 h-4 bg-white/5 rounded overflow-hidden">
                                    <div className={`h-full rounded text-[9px] font-bold flex items-center justify-center text-white ${
                                      e.progress >= 100 ? "bg-green-500" : e.progress > 0 ? "bg-orange-500" : "bg-red-500"
                                    }`} style={{ width: `${Math.max(e.progress, 18)}%` }}>
                                      {e.progress}%
                                    </div>
                                  </div>
                                </div>
                                <div className="relative">
                                  <button onClick={(ev) => { ev.stopPropagation(); setOpenAction(openAction === e.id ? null : e.id); }}
                                    className="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-gray-300 bg-white/5 rounded">
                                    <ChevronDown size={11} />
                                  </button>
                                  {openAction === e.id && (
                                    <div className="absolute right-0 top-7 z-50 bg-[#1a1a1a] border border-white/10 rounded-lg shadow-xl py-1 w-40"
                                      onMouseLeave={() => setOpenAction(null)}>
                                      <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Modifica scadenza</button>
                                      <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Invia avvia corso</button>
                                      <button className="w-full text-left px-3 py-1.5 text-xs text-gray-300 hover:bg-white/5">Invia sollecito</button>
                                      <button onClick={() => { if (confirm("Rimuovere questa licenza?")) { deleteMut.mutate([e.id]); setOpenAction(null); } }}
                                        className="w-full text-left px-3 py-1.5 text-xs text-red-400 hover:bg-red-500/10">Rimuovi licenza</button>
                                    </div>
                                  )}
                                </div>
                              </div>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </Fragment>
                  );
                })}
              </tbody>
            </table>
          </div>

          {totalPages > 1 && (
            <div className="flex items-center justify-between mt-3 px-1">
              <span className="text-xs text-gray-500">
                {(page - 1) * pageSize + 1}–{Math.min(page * pageSize, filtered.length)} di {filtered.length}
              </span>
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
