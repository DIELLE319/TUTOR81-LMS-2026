import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Search, Mail, Trash2, Calendar, RotateCcw } from "lucide-react";

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

  const filtered = enrollments.filter((e) => {
    if (statusFilter !== "all" && e.status !== statusFilter) return false;
    if (search) {
      const q = search.toLowerCase();
      return e.userName.toLowerCase().includes(q) || e.courseName.toLowerCase().includes(q) || e.companyName.toLowerCase().includes(q) || e.licenseCode.toLowerCase().includes(q);
    }
    return true;
  });

  const toggleSelect = (id: number) => setSelected((prev) => prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]);
  const toggleAll = () => setSelected(selected.length === filtered.length ? [] : filtered.map((e) => e.id));

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Corsi Attivi</h1>

      <div className="flex flex-wrap gap-3 mb-4">
        <div className="relative flex-1 min-w-[200px]">
          <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
          <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cerca..." className="w-full h-10 pl-9 pr-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
        </div>
        <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)} className="h-10 border border-gray-300 rounded-lg px-3 text-sm">
          <option value="all">Tutti</option>
          <option value="active">Attivi</option>
          <option value="completed">Completati</option>
          <option value="expired">Scaduti</option>
        </select>
        {selected.length > 0 && (
          <div className="flex gap-2">
            <button onClick={() => sendEmailMut.mutate(selected)} className="h-10 px-4 bg-blue-600 text-white rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-blue-700"><Mail size={14} />Email ({selected.length})</button>
            <button onClick={() => reminderMut.mutate(selected)} className="h-10 px-4 bg-yellow-500 text-black rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-yellow-600"><RotateCcw size={14} />Promemoria</button>
            <button onClick={() => { if (confirm("Eliminare le iscrizioni selezionate?")) deleteMut.mutate(selected); }} className="h-10 px-4 bg-red-600 text-white rounded-lg text-sm font-medium flex items-center gap-2 hover:bg-red-700"><Trash2 size={14} />Elimina</button>
          </div>
        )}
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Caricamento...</div>
      ) : filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-500">Nessuna iscrizione trovata</div>
      ) : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="p-3 text-left"><input type="checkbox" checked={selected.length === filtered.length && filtered.length > 0} onChange={toggleAll} /></th>
                <th className="p-3 text-left font-medium text-gray-600">Corsista</th>
                <th className="p-3 text-left font-medium text-gray-600">Corso</th>
                <th className="p-3 text-left font-medium text-gray-600">Azienda</th>
                <th className="p-3 text-left font-medium text-gray-600">Progresso</th>
                <th className="p-3 text-left font-medium text-gray-600">Stato</th>
                <th className="p-3 text-left font-medium text-gray-600">Scadenza</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((e) => (
                <tr key={e.id} className="border-b last:border-b-0 hover:bg-gray-50">
                  <td className="p-3"><input type="checkbox" checked={selected.includes(e.id)} onChange={() => toggleSelect(e.id)} /></td>
                  <td className="p-3">
                    <div className="font-medium text-gray-900">{e.userName}</div>
                    <div className="text-xs text-gray-500">{e.userEmail}</div>
                  </td>
                  <td className="p-3 text-gray-700">{e.courseName}</td>
                  <td className="p-3 text-gray-500">{e.companyName}</td>
                  <td className="p-3">
                    <div className="flex items-center gap-2">
                      <div className="w-20 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div className="h-full bg-yellow-500 rounded-full" style={{ width: `${e.progress}%` }} />
                      </div>
                      <span className="text-xs text-gray-500">{e.progress}%</span>
                    </div>
                  </td>
                  <td className="p-3">
                    <span className={`text-xs font-semibold px-2 py-0.5 rounded ${e.status === "active" ? "bg-green-100 text-green-700" : e.status === "completed" ? "bg-blue-100 text-blue-700" : "bg-red-100 text-red-700"}`}>
                      {e.status === "active" ? "Attivo" : e.status === "completed" ? "Completato" : e.status}
                    </span>
                  </td>
                  <td className="p-3 text-gray-500 text-xs">{e.endDate ? new Date(e.endDate).toLocaleDateString("it-IT") : "—"}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
