import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useMemo, useRef, useEffect } from "react";
import { useAuth } from "@/hooks/use-auth";
import { format } from "date-fns";
import { it } from "date-fns/locale";
import { Search, Send, X, Calendar, Trash2, Bell, Play, MoreHorizontal } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest, queryClient } from "@/lib/queryClient";

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
  emailSentAt: string | null;
  emailOpenedAt: string | null;
  licenseCode: string | null;
  tutorId: number | null;
  tutorName: string;
}

interface Company {
  id: number;
  businessName: string;
}

function ActionMenu({ enrollment, onEditDate, onDelete, onSendEmail, onLaunch }: {
  enrollment: Enrollment;
  onEditDate: () => void;
  onDelete: () => void;
  onSendEmail: () => void;
  onLaunch: () => void;
}) {
  const [open, setOpen] = useState(false);
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (ref.current && !ref.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  return (
    <div className="relative" ref={ref}>
      <button
        onClick={() => setOpen(!open)}
        className="p-1.5 border border-gray-300 rounded hover:bg-gray-100"
        data-testid={`button-actions-${enrollment.id}`}
      >
        <MoreHorizontal className="h-4 w-4 text-black" />
      </button>
      {open && (
        <div className="absolute right-0 top-full mt-1 w-52 bg-white border border-gray-200 rounded-lg shadow-lg z-50">
          <button onClick={() => { onEditDate(); setOpen(false); }} className="w-full flex items-center gap-2 px-3 py-2 text-sm text-black hover:bg-gray-100">
            <Calendar className="h-4 w-4" /> Modifica scadenza
          </button>
          <button onClick={() => { onDelete(); setOpen(false); }} className="w-full flex items-center gap-2 px-3 py-2 text-sm text-black hover:bg-gray-100">
            <Trash2 className="h-4 w-4" /> Rimuovi
          </button>
          <button onClick={() => { onSendEmail(); setOpen(false); }} className="w-full flex items-center gap-2 px-3 py-2 text-sm text-black hover:bg-gray-100">
            <Send className="h-4 w-4" /> Invia email
          </button>
          <button onClick={() => { onLaunch(); setOpen(false); }} className="w-full flex items-center gap-2 px-3 py-2 text-sm text-green-600 hover:bg-green-50">
            <Play className="h-4 w-4" /> Avvia corso
          </button>
        </div>
      )}
    </div>
  );
}

export default function ActivatedCourses() {
  const { user } = useAuth();
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [companyFilter, setCompanyFilter] = useState<string>("");
  const [companyDropdownOpen, setCompanyDropdownOpen] = useState(false);
  const [companySearchTerm, setCompanySearchTerm] = useState("");
  const [search, setSearch] = useState("");
  const [pageSize, setPageSize] = useState(100);
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [showDateDialog, setShowDateDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [newEndDate, setNewEndDate] = useState("");
  const { toast } = useToast();
  const companyRef = useRef<HTMLDivElement>(null);

  const userTutorId = (user as any)?.tutorId;

  useEffect(() => {
    const handler = (e: MouseEvent) => {
      if (companyRef.current && !companyRef.current.contains(e.target as Node)) setCompanyDropdownOpen(false);
    };
    document.addEventListener("mousedown", handler);
    return () => document.removeEventListener("mousedown", handler);
  }, []);

  const { data: enrollments = [], isLoading } = useQuery<Enrollment[]>({
    queryKey: ["/api/enrollments", userTutorId],
    queryFn: async () => {
      const url = userTutorId ? `/api/enrollments?tutorId=${userTutorId}` : '/api/enrollments';
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) throw new Error('Failed to fetch');
      return res.json();
    },
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ["/api/companies-list"],
  });

  const sortedCompanies = useMemo(() => {
    return companies.filter(c => c.businessName).sort((a, b) => a.businessName.localeCompare(b.businessName));
  }, [companies]);

  const selectedCompanyName = useMemo(() => {
    if (!companyFilter) return "";
    return companies.find(c => c.id.toString() === companyFilter)?.businessName || "";
  }, [companyFilter, companies]);

  const sendEmailsMutation = useMutation({
    mutationFn: async (enrollmentIds: number[]) => apiRequest("POST", "/api/enrollments/send-emails", { enrollmentIds }),
    onSuccess: () => { toast({ title: "Email inviate con successo!" }); setSelectedIds([]); queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] }); },
    onError: (error: Error) => { toast({ title: "Errore nell'invio", description: error.message, variant: "destructive" }); },
  });

  const updateEndDateMutation = useMutation({
    mutationFn: async ({ enrollmentIds, endDate }: { enrollmentIds: number[]; endDate: string }) => apiRequest("POST", "/api/enrollments/update-end-date", { enrollmentIds, endDate }),
    onSuccess: () => { toast({ title: "Scadenza aggiornata!" }); setSelectedIds([]); setShowDateDialog(false); setNewEndDate(""); queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] }); },
    onError: (error: Error) => { toast({ title: "Errore", description: error.message, variant: "destructive" }); },
  });

  const deleteEnrollmentsMutation = useMutation({
    mutationFn: async (enrollmentIds: number[]) => apiRequest("POST", "/api/enrollments/delete", { enrollmentIds }),
    onSuccess: () => { toast({ title: "Licenze rimosse!" }); setSelectedIds([]); setShowDeleteDialog(false); queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] }); },
    onError: (error: Error) => { toast({ title: "Errore", description: error.message, variant: "destructive" }); },
  });

  const sendReminderMutation = useMutation({
    mutationFn: async (enrollmentIds: number[]) => apiRequest("POST", "/api/enrollments/send-reminder", { enrollmentIds }),
    onSuccess: () => { toast({ title: "Solleciti inviati!" }); setSelectedIds([]); queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] }); },
    onError: (error: Error) => { toast({ title: "Errore", description: error.message, variant: "destructive" }); },
  });

  const handleLaunchCourse = () => {
    window.open('https://avviacorso.tutor81.com/', '_blank');
    toast({ title: "Player aperto", description: "Inserisci username e codice fiscale del corsista." });
  };

  const handleSelectAll = (checked: boolean) => {
    setSelectedIds(checked ? displayedEnrollments.map(e => e.id) : []);
  };

  const handleSelectOne = (id: number, checked: boolean) => {
    setSelectedIds(checked ? [...selectedIds, id] : selectedIds.filter(i => i !== id));
  };

  const formatDate = (date: string | null) => {
    if (!date) return "-";
    return format(new Date(date), "dd/MM/yyyy", { locale: it });
  };

  const getProgressColor = (progress: number) => {
    if (progress === 0) return "bg-red-500";
    if (progress < 25) return "bg-orange-500";
    if (progress < 50) return "bg-yellow-500";
    if (progress < 75) return "bg-lime-500";
    return "bg-green-500";
  };

  const filteredEnrollments = enrollments.filter((e) => {
    if (statusFilter === "active" && (e.progress === 0 || e.progress === 100)) return false;
    if (statusFilter === "not_started" && e.progress !== 0) return false;
    if (companyFilter && selectedCompanyName && e.companyName !== selectedCompanyName) return false;
    if (search) {
      const s = search.toLowerCase();
      return e.userName?.toLowerCase().includes(s) || e.userEmail?.toLowerCase().includes(s) || e.companyName?.toLowerCase().includes(s) || e.courseName?.toLowerCase().includes(s);
    }
    return true;
  });

  const displayedEnrollments = filteredEnrollments.slice(0, pageSize);

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="bg-black py-4 px-6">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-bold text-yellow-400" data-testid="text-page-title">Lista Corsi Attivati</h1>
          <div className="flex gap-2">
            <button
              onClick={() => setStatusFilter(statusFilter === "active" ? "all" : "active")}
              className={`px-4 py-2 rounded text-sm font-bold transition-colors ${statusFilter === "active" ? "bg-green-600 text-white" : "border border-green-400 text-green-400 hover:bg-green-500 hover:text-white"}`}
              data-testid="button-filter-active"
            >
              IN CORSO
            </button>
            <button
              onClick={() => setStatusFilter(statusFilter === "not_started" ? "all" : "not_started")}
              className={`px-4 py-2 rounded text-sm font-bold transition-colors ${statusFilter === "not_started" ? "bg-red-600 text-white" : "border border-red-400 text-red-400 hover:bg-red-500 hover:text-white"}`}
              data-testid="button-filter-not-started"
            >
              NON AVVIATI
            </button>
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="flex items-center justify-between mb-4 bg-yellow-400 p-3 rounded-lg flex-wrap gap-2">
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex items-center gap-2">
              <span className="text-sm text-black font-medium">Mostra</span>
              <select
                value={pageSize}
                onChange={(e) => setPageSize(parseInt(e.target.value))}
                className="h-8 bg-white border border-black rounded px-2 text-sm text-black"
                data-testid="select-page-size"
              >
                <option value={10}>10</option>
                <option value={25}>25</option>
                <option value={50}>50</option>
                <option value={100}>100</option>
              </select>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-black font-medium">Cerca:</span>
              <div className="relative">
                <input
                  type="text"
                  placeholder="Nome, cognome, email..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-48 h-8 text-sm bg-white text-black border border-black rounded pl-2 pr-8"
                  data-testid="input-search-user"
                />
                <Search className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
              </div>
            </div>
            <div className="flex items-center gap-2 relative" ref={companyRef}>
              <button
                onClick={() => { setCompanyDropdownOpen(!companyDropdownOpen); setCompanySearchTerm(""); }}
                className="h-8 w-72 flex items-center justify-between bg-white border border-black rounded px-3 text-sm text-black hover:bg-yellow-50"
                data-testid="select-company-filter"
              >
                <span className="truncate">{selectedCompanyName || "--- Tutte le Aziende ---"}</span>
                <X className={`h-3 w-3 ml-1 ${companyFilter ? "text-black" : "hidden"}`} onClick={(e) => { e.stopPropagation(); setCompanyFilter(""); }} />
              </button>
              {companyDropdownOpen && (
                <div className="absolute top-full left-0 mt-1 w-72 bg-white border border-gray-300 rounded-lg shadow-lg z-50">
                  <div className="p-2 border-b">
                    <input
                      type="text"
                      placeholder="Cerca azienda..."
                      value={companySearchTerm}
                      onChange={(e) => setCompanySearchTerm(e.target.value)}
                      className="w-full h-8 text-sm bg-white text-black border border-gray-300 rounded px-2 focus:outline-none focus:ring-1 focus:ring-yellow-400"
                      autoFocus
                    />
                  </div>
                  <div className="max-h-60 overflow-y-auto">
                    <div
                      onClick={() => { setCompanyFilter(""); setCompanyDropdownOpen(false); }}
                      className="px-3 py-2 text-sm font-bold text-black hover:bg-yellow-100 cursor-pointer"
                    >
                      --- Tutte le Aziende ---
                    </div>
                    {sortedCompanies
                      .filter(c => !companySearchTerm || c.businessName.toLowerCase().includes(companySearchTerm.toLowerCase()))
                      .map(c => (
                        <div
                          key={c.id}
                          onClick={() => { setCompanyFilter(c.id.toString()); setCompanyDropdownOpen(false); }}
                          className={`px-3 py-2 text-sm cursor-pointer hover:bg-yellow-100 ${companyFilter === c.id.toString() ? "bg-yellow-50 font-semibold" : "text-black"}`}
                        >
                          {c.businessName}
                        </div>
                      ))}
                  </div>
                </div>
              )}
            </div>
          </div>
          <div className="flex items-center gap-1">
            {selectedIds.length > 0 && (
              <>
                <span className="text-sm text-black font-bold mr-2">{selectedIds.length} sel.</span>
                <button onClick={() => setShowDateDialog(true)} className="flex items-center gap-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded" data-testid="button-modify-date">
                  <Calendar className="h-3 w-3" /> Scadenza
                </button>
                <button onClick={() => sendEmailsMutation.mutate(selectedIds)} disabled={sendEmailsMutation.isPending} className="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-1.5 rounded disabled:opacity-50" data-testid="button-send-emails">
                  <Send className="h-3 w-3" /> Avvia
                </button>
                <button onClick={() => sendReminderMutation.mutate(selectedIds)} disabled={sendReminderMutation.isPending} className="flex items-center gap-1 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-1.5 rounded disabled:opacity-50" data-testid="button-send-reminder">
                  <Bell className="h-3 w-3" /> Sollecito
                </button>
                <button onClick={() => setShowDeleteDialog(true)} className="flex items-center gap-1 bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-3 py-1.5 rounded" data-testid="button-delete-enrollments">
                  <Trash2 className="h-3 w-3" /> Rimuovi
                </button>
              </>
            )}
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-lg border-2 border-black overflow-x-auto">
          <table className="w-full min-w-[1200px]" data-testid="table-enrollments">
            <thead className="bg-black">
              <tr>
                <th className="w-10 p-3">
                  <input
                    type="checkbox"
                    checked={selectedIds.length === displayedEnrollments.length && displayedEnrollments.length > 0}
                    onChange={(e) => handleSelectAll(e.target.checked)}
                    className="accent-yellow-400 w-4 h-4"
                    data-testid="checkbox-select-all"
                  />
                </th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase">ID</th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase"><div>Ente</div><div>Formativo</div></th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase"><div>Data</div><div>Vendita</div></th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase">Azienda</th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase"><div>Cognome</div><div>Nome</div></th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase">Corso</th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase">Email</th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase"><div>Ultimo</div><div>Accesso</div></th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase"><div>Termine</div><div>Programmato</div></th>
                <th className="text-left p-2 text-xs font-bold text-yellow-400 uppercase">Progresso</th>
                <th className="text-center p-2 text-xs font-bold text-yellow-400 uppercase">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr><td colSpan={12} className="text-center py-8 text-black">Caricamento...</td></tr>
              ) : displayedEnrollments.length === 0 ? (
                <tr><td colSpan={12} className="text-center py-8 text-black">Nessun corso attivato trovato</td></tr>
              ) : (
                displayedEnrollments.map((enrollment) => {
                  const isExpired = enrollment.endDate && new Date(enrollment.endDate) < new Date();
                  return (
                    <tr key={enrollment.id} className={isExpired ? "bg-red-100" : "bg-white"} data-testid={`row-enrollment-${enrollment.id}`}>
                      <td className="p-3">
                        <input
                          type="checkbox"
                          checked={selectedIds.includes(enrollment.id)}
                          onChange={(e) => handleSelectOne(enrollment.id, e.target.checked)}
                          className="accent-yellow-400 w-4 h-4"
                          data-testid={`checkbox-row-${enrollment.id}`}
                        />
                      </td>
                      <td className="p-2 text-sm text-black">{enrollment.tutorId}</td>
                      <td className="p-2 text-xs text-black max-w-[120px] truncate" title={enrollment.tutorName}>{enrollment.tutorName}</td>
                      <td className="p-2 text-xs text-black">{formatDate(enrollment.emailSentAt)}</td>
                      <td className="p-2 text-xs text-black max-w-[150px] truncate" title={enrollment.companyName}>{enrollment.companyName}</td>
                      <td className="p-2 text-xs font-medium text-black">{enrollment.userName}</td>
                      <td className="p-2 text-xs text-black max-w-[200px] truncate" title={enrollment.courseName?.replace(/^EL\s*-\s*/i, '')}>{enrollment.courseName?.replace(/^EL\s*-\s*/i, '')}</td>
                      <td className="p-2 text-xs">
                        <a href={`mailto:${enrollment.userEmail}`} className="text-amber-700 hover:underline font-medium">{enrollment.userEmail}</a>
                      </td>
                      <td className="p-3 text-sm text-black">{formatDate(enrollment.lastAccessAt)}</td>
                      <td className="p-3 text-sm text-black font-medium">{formatDate(enrollment.endDate)}</td>
                      <td className="p-3 text-center">
                        <span className={`inline-flex items-center justify-center min-w-[32px] px-2 py-1 rounded text-xs font-bold text-white ${getProgressColor(enrollment.progress)}`}>
                          {enrollment.progress}%
                        </span>
                      </td>
                      <td className="p-3 text-center">
                        <ActionMenu
                          enrollment={enrollment}
                          onEditDate={() => { setSelectedIds([enrollment.id]); setShowDateDialog(true); }}
                          onDelete={() => { setSelectedIds([enrollment.id]); setShowDeleteDialog(true); }}
                          onSendEmail={() => sendEmailsMutation.mutate([enrollment.id])}
                          onLaunch={handleLaunchCourse}
                        />
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>

        <div className="mt-4 text-sm text-black font-medium">
          Mostrando {displayedEnrollments.length} di {filteredEnrollments.length} risultati
        </div>
      </div>

      {showDateDialog && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
            <h2 className="text-lg font-bold text-black mb-2">Modifica Scadenza</h2>
            <p className="text-sm text-gray-600 mb-4">Nuova data di scadenza per {selectedIds.length} corso/i selezionato/i</p>
            <input
              type="date"
              value={newEndDate}
              onChange={(e) => setNewEndDate(e.target.value)}
              className="w-full h-10 bg-white text-black border border-gray-300 rounded px-3 mb-4"
              data-testid="input-new-end-date"
            />
            <div className="flex justify-end gap-2">
              <button onClick={() => { setShowDateDialog(false); setNewEndDate(""); }} className="px-4 py-2 border border-gray-300 rounded text-sm text-black hover:bg-gray-100" data-testid="button-cancel-date">Annulla</button>
              <button
                onClick={() => { if (selectedIds.length && newEndDate) updateEndDateMutation.mutate({ enrollmentIds: selectedIds, endDate: newEndDate }); }}
                disabled={!newEndDate || updateEndDateMutation.isPending}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm font-bold disabled:opacity-50"
                data-testid="button-confirm-date"
              >
                Conferma
              </button>
            </div>
          </div>
        </div>
      )}

      {showDeleteDialog && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
          <div className="bg-white rounded-xl p-6 w-full max-w-md shadow-xl">
            <h2 className="text-lg font-bold text-black mb-2">Rimuovi Licenze</h2>
            <p className="text-sm text-gray-600 mb-4">Sei sicuro di voler rimuovere {selectedIds.length} licenza/e? Questa azione non può essere annullata.</p>
            <div className="flex justify-end gap-2">
              <button onClick={() => setShowDeleteDialog(false)} className="px-4 py-2 border border-gray-300 rounded text-sm text-black hover:bg-gray-100" data-testid="button-cancel-delete">Annulla</button>
              <button
                onClick={() => { if (selectedIds.length) deleteEnrollmentsMutation.mutate(selectedIds); }}
                className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded text-sm font-bold"
                data-testid="button-confirm-delete"
              >
                Rimuovi
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
