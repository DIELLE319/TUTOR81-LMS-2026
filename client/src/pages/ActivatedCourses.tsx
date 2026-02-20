import { useState, useMemo } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Search, Mail, Trash2, RotateCcw, Download } from "lucide-react";

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
}

function statusBadge(s: string) {
  switch (s) {
    case "active": return { label: "Attivo", cls: "bg-green-500 text-white" };
    case "completed": return { label: "Completato", cls: "bg-blue-500 text-white" };
    case "expired": return { label: "Scaduto", cls: "bg-red-500 text-white" };
    default: return { label: s, cls: "bg-gray-400 text-white" };
  }
}

export default function ActivatedCourses() {
  const [search, setSearch] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");
  const [selected, setSelected] = useState<number[]>([]);
  const qc = useQueryClient();
  const { toast } = useToast();

  const { data: enrollments = [], isLoading } = useQuery<Enrollment[]>({
    queryKey: ["enrollments"],
    queryFn: () => fetch("/api/enrollments", { credentials: "include" }).then((r) => r.json()),
  });

  const sendEmailMut = useMutation({
    mutationFn: (ids: number[]) => apiRequest("POST", "/api/enrollments/send-emails", { enrollmentIds: ids }),
    onSuccess: (d) => { toast({ title: `${d.sent} email inviate` }); setSelected([]); },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const deleteMut = useMutation({
    mutationFn: (ids: number[]) => apiRequest("DELETE", "/api/enrollments", { enrollmentIds: ids }),
    onSuccess: () => { toast({ title: "Iscrizioni eliminate" }); qc.invalidateQueries({ queryKey: ["enrollments"] }); setSelected([]); },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const reminderMut = useMutation({
    mutationFn: (ids: number[]) => apiRequest("POST", "/api/enrollments/send-reminder", { enrollmentIds: ids }),
    onSuccess: (d) => { toast({ title: `${d.sent} promemoria inviati` }); setSelected([]); },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const filtered = useMemo(() => enrollments.filter((e) => {
    if (statusFilter !== "all" && e.status !== statusFilter) return false;
    if (search) {
      const q = search.toLowerCase();
      return e.userName.toLowerCase().includes(q) || e.courseName.toLowerCase().includes(q) || e.companyName.toLowerCase().includes(q) || e.licenseCode.toLowerCase().includes(q);
    }
    return true;
  }), [enrollments, statusFilter, search]);

  const toggleSelect = (id: number) => setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
  const toggleAll = () => setSelected(selected.length === filtered.length ? [] : filtered.map((e) => e.id));

  const exportCsv = () => {
    const header = "Corsista;Email;Corso;Azienda;Progresso;Stato;Scadenza;Codice\n";
    const rows = filtered.map((e) =>
      `${e.userName};${e.userEmail};${e.courseName};${e.companyName};${e.progress}%;${e.status};${e.endDate || ""};${e.licenseCode}`
    ).join("\n");
    const blob = new Blob([header + rows], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url; a.download = "corsi-attivi.csv"; a.click();
    URL.revokeObjectURL(url);
  };

  return (
    <div>
      {/* Filter bar */}
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-gray-300 mb-1">Cerca</label>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, corso, azienda, codice..."
              className="w-full h-9 pl-9 pr-3 bg-white border-0 rounded text-sm text-gray-900" />
          </div>
        </div>
        <div className="min-w-[150px]">
          <label className="block text-xs text-gray-300 mb-1">Stato</label>
          <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}
            className="w-full h-9 px-3 bg-white border-0 rounded text-sm text-gray-900">
            <option value="all">Tutti</option>
            <option value="active">Attivi</option>
            <option value="completed">Completati</option>
            <option value="expired">Scaduti</option>
          </select>
        </div>
        {selected.length > 0 && (
          <>
            <button onClick={() => sendEmailMut.mutate(selected)} className="h-9 px-4 bg-blue-500 text-white rounded text-sm font-bold flex items-center gap-2 hover:bg-blue-600">
              <Mail size={14} />Email ({selected.length})
            </button>
            <button onClick={() => reminderMut.mutate(selected)} className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
              <RotateCcw size={14} />Promemoria
            </button>
            <button onClick={() => { if (confirm("Eliminare le iscrizioni selezionate?")) deleteMut.mutate(selected); }} className="h-9 px-4 bg-red-500 text-white rounded text-sm font-bold flex items-center gap-2 hover:bg-red-600">
              <Trash2 size={14} />Elimina
            </button>
          </>
        )}
        <button onClick={exportCsv} className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
          <Download size={14} />CSV
        </button>
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Caricamento...</div>
      ) : filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-500">Nessuna iscrizione trovata</div>
      ) : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          {/* Header */}
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                <th className="p-3 text-left w-10"><input type="checkbox" checked={selected.length === filtered.length && filtered.length > 0} onChange={toggleAll} /></th>
                <th className="p-3 text-left">Corsista</th>
                <th className="p-3 text-left">Corso</th>
                <th className="p-3 text-left">Azienda</th>
                <th className="p-3 text-left">Progresso</th>
                <th className="p-3 text-left">Stato</th>
                <th className="p-3 text-left">Scadenza</th>
                <th className="p-3 text-left">Codice</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((e) => {
                const badge = statusBadge(e.status);
                return (
                  <tr key={e.id} className="border-b border-gray-100 hover:bg-gray-50">
                    <td className="p-3"><input type="checkbox" checked={selected.includes(e.id)} onChange={() => toggleSelect(e.id)} /></td>
                    <td className="p-3">
                      <div className="font-medium text-gray-900 text-xs">{e.userName}</div>
                      <div className="text-[11px] text-gray-500">{e.userEmail}</div>
                    </td>
                    <td className="p-3 text-gray-800 text-xs font-medium">{e.courseName}</td>
                    <td className="p-3 text-gray-500 text-xs">{e.companyName}</td>
                    <td className="p-3">
                      <div className="flex items-center gap-2">
                        <div className="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                          <div className="h-full bg-yellow-500 rounded-full transition-all" style={{ width: `${e.progress}%` }} />
                        </div>
                        <span className="text-xs font-bold text-gray-700">{e.progress}%</span>
                      </div>
                    </td>
                    <td className="p-3">
                      <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${badge.cls}`}>{badge.label}</span>
                    </td>
                    <td className="p-3 text-gray-500 text-xs">{e.endDate ? new Date(e.endDate).toLocaleDateString("it-IT") : "—"}</td>
                    <td className="p-3 text-gray-400 font-mono text-[11px]">{e.licenseCode}</td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
