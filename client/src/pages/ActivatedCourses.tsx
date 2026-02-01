import { useQuery, useMutation } from "@tanstack/react-query";
import { useState, useMemo } from "react";
import { format } from "date-fns";
import { it } from "date-fns/locale";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Search, Mail, MailOpen, MailX, Send, ChevronsUpDown, Check, X, Calendar, Trash2, Bell, Play, MoreHorizontal } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";

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
}

interface Company {
  id: number;
  businessName: string;
}

export default function ActivatedCourses() {
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [companyFilter, setCompanyFilter] = useState<string>("");
  const [companySearchOpen, setCompanySearchOpen] = useState(false);
  const [search, setSearch] = useState("");
  const [pageSize, setPageSize] = useState("25");
  const [selectedIds, setSelectedIds] = useState<number[]>([]);
  const [showDateDialog, setShowDateDialog] = useState(false);
  const [showDeleteDialog, setShowDeleteDialog] = useState(false);
  const [newEndDate, setNewEndDate] = useState("");
  const { toast } = useToast();

  const { data: enrollments = [], isLoading } = useQuery<Enrollment[]>({
    queryKey: ["/api/enrollments", statusFilter, search],
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ["/api/companies-list"],
  });

  const sortedCompanies = useMemo(() => {
    return companies
      .filter(c => c.businessName)
      .sort((a, b) => (a.businessName || '').localeCompare(b.businessName || ''));
  }, [companies]);

  const selectedCompanyName = useMemo(() => {
    if (!companyFilter) return "";
    const company = companies.find(c => c.id.toString() === companyFilter);
    return company?.businessName || "";
  }, [companyFilter, companies]);

  const sendEmailsMutation = useMutation({
    mutationFn: async (enrollmentIds: number[]) => {
      return apiRequest("POST", "/api/enrollments/send-emails", { enrollmentIds });
    },
    onSuccess: () => {
      toast({ title: "Email inviate con successo!" });
      setSelectedIds([]);
      queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] });
    },
    onError: (error: Error) => {
      toast({ title: "Errore nell'invio", description: error.message, variant: "destructive" });
    },
  });

  const updateEndDateMutation = useMutation({
    mutationFn: async ({ enrollmentIds, endDate }: { enrollmentIds: number[]; endDate: string }) => {
      return apiRequest("POST", "/api/enrollments/update-end-date", { enrollmentIds, endDate });
    },
    onSuccess: () => {
      toast({ title: "Scadenza aggiornata con successo!" });
      setSelectedIds([]);
      setShowDateDialog(false);
      setNewEndDate("");
      queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] });
    },
    onError: (error: Error) => {
      toast({ title: "Errore nell'aggiornamento", description: error.message, variant: "destructive" });
    },
  });

  const deleteEnrollmentsMutation = useMutation({
    mutationFn: async (enrollmentIds: number[]) => {
      return apiRequest("POST", "/api/enrollments/delete", { enrollmentIds });
    },
    onSuccess: () => {
      toast({ title: "Licenze rimosse con successo!" });
      setSelectedIds([]);
      setShowDeleteDialog(false);
      queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] });
    },
    onError: (error: Error) => {
      toast({ title: "Errore nella rimozione", description: error.message, variant: "destructive" });
    },
  });

  const sendReminderMutation = useMutation({
    mutationFn: async (enrollmentIds: number[]) => {
      return apiRequest("POST", "/api/enrollments/send-reminder", { enrollmentIds });
    },
    onSuccess: () => {
      toast({ title: "Solleciti inviati con successo!" });
      setSelectedIds([]);
      queryClient.invalidateQueries({ queryKey: ["/api/enrollments"] });
    },
    onError: (error: Error) => {
      toast({ title: "Errore nell'invio", description: error.message, variant: "destructive" });
    },
  });

  const handleUpdateEndDate = () => {
    if (selectedIds.length === 0 || !newEndDate) return;
    updateEndDateMutation.mutate({ enrollmentIds: selectedIds, endDate: newEndDate });
  };

  const handleDeleteEnrollments = () => {
    if (selectedIds.length === 0) return;
    deleteEnrollmentsMutation.mutate(selectedIds);
  };

  const handleSendReminder = () => {
    if (selectedIds.length === 0) return;
    sendReminderMutation.mutate(selectedIds);
  };

  const handleLaunchCourse = (enrollment: Enrollment) => {
    if (!enrollment.licenseCode) {
      toast({ title: "Codice licenza mancante", variant: "destructive" });
      return;
    }
    const playerUrl = `https://avviacorso.tutor81.com/player.php?course=${enrollment.licenseCode}`;
    window.open(playerUrl, '_blank');
  };

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedIds(displayedEnrollments.map((e) => e.id));
    } else {
      setSelectedIds([]);
    }
  };

  const handleSelectOne = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedIds([...selectedIds, id]);
    } else {
      setSelectedIds(selectedIds.filter((i) => i !== id));
    }
  };

  const handleSendEmails = () => {
    if (selectedIds.length === 0) return;
    sendEmailsMutation.mutate(selectedIds);
  };

  const formatDate = (date: string | null) => {
    if (!date) return "-";
    return format(new Date(date), "dd/MM/yyyy", { locale: it });
  };

  const getProgressColor = (progress: number) => {
    if (progress === 0) return "bg-red-500";
    if (progress < 50) return "bg-orange-500";
    if (progress < 100) return "bg-amber-500";
    return "bg-green-500";
  };

  const filteredEnrollments = enrollments.filter((e) => {
    if (statusFilter === "active" && (e.progress === 0 || e.progress === 100)) return false;
    if (statusFilter === "not_started" && e.progress !== 0) return false;
    if (companyFilter && selectedCompanyName && e.companyName !== selectedCompanyName) return false;
    if (search) {
      const s = search.toLowerCase();
      return (
        e.userName?.toLowerCase().includes(s) ||
        e.userEmail?.toLowerCase().includes(s) ||
        e.companyName?.toLowerCase().includes(s) ||
        e.courseName?.toLowerCase().includes(s)
      );
    }
    return true;
  });

  const displayedEnrollments = filteredEnrollments.slice(0, parseInt(pageSize));

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="bg-black py-4 px-6">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-bold text-yellow-400" data-testid="text-page-title">
            Lista Corsi Attivati
          </h1>
          <div className="flex gap-2">
            <Button
              variant={statusFilter === "active" ? "default" : "outline"}
              className={statusFilter === "active" 
                ? "bg-yellow-500 hover:bg-yellow-400 text-black font-bold" 
                : "bg-transparent text-yellow-400 border-yellow-400 hover:bg-yellow-400 hover:text-black"
              }
              onClick={() => setStatusFilter(statusFilter === "active" ? "all" : "active")}
              data-testid="button-filter-active"
            >
              IN CORSO
            </Button>
            <Button
              variant={statusFilter === "not_started" ? "default" : "outline"}
              className={statusFilter === "not_started"
                ? "bg-red-600 hover:bg-red-700 text-white font-bold"
                : "bg-transparent text-red-400 border-red-400 hover:bg-red-500 hover:text-white"
              }
              onClick={() => setStatusFilter(statusFilter === "not_started" ? "all" : "not_started")}
              data-testid="button-filter-not-started"
            >
              NON AVVIATI
            </Button>
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="flex items-center justify-between mb-4 bg-yellow-400 p-3 rounded-lg">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <span className="text-sm text-black font-medium">Show</span>
              <Select value={pageSize} onValueChange={setPageSize}>
                <SelectTrigger className="w-20 bg-white border-black" data-testid="select-page-size">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="10">10</SelectItem>
                  <SelectItem value="25">25</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-black font-medium">Cerca utente:</span>
              <div className="relative">
                <Input
                  type="text"
                  placeholder="Nome, cognome, email..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-48 h-8 text-sm bg-white text-black border-black pl-2 pr-8 rounded"
                  data-testid="input-search-user"
                />
                <Search className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
              </div>
            </div>
            <div className="flex items-center gap-2">
              <Popover open={companySearchOpen} onOpenChange={setCompanySearchOpen}>
                <PopoverTrigger asChild>
                  <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={companySearchOpen}
                    className="w-80 justify-between bg-white border-black text-black hover:bg-yellow-50"
                    data-testid="select-company-filter"
                  >
                    {selectedCompanyName || "--- Tutte le Aziende ---"}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-80 p-0 bg-white" align="start">
                  <Command>
                    <CommandInput placeholder="Cerca azienda..." className="text-black" />
                    <CommandList>
                      <CommandEmpty className="text-gray-500 py-4 text-center">Nessuna azienda trovata</CommandEmpty>
                      <CommandGroup>
                        {sortedCompanies.map(company => (
                          <CommandItem
                            key={company.id}
                            value={company.businessName}
                            onSelect={() => {
                              setCompanyFilter(company.id.toString());
                              setCompanySearchOpen(false);
                            }}
                            className="text-black hover:bg-yellow-100 cursor-pointer"
                          >
                            <Check className={`mr-2 h-4 w-4 ${companyFilter === company.id.toString() ? "opacity-100" : "opacity-0"}`} />
                            {company.businessName}
                          </CommandItem>
                        ))}
                      </CommandGroup>
                    </CommandList>
                  </Command>
                </PopoverContent>
              </Popover>
              {companyFilter && (
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => setCompanyFilter("")}
                  className="h-9 w-9 text-black hover:bg-yellow-200"
                  data-testid="button-clear-company-filter"
                >
                  <X className="h-4 w-4" />
                </Button>
              )}
            </div>
          </div>
          <div className="flex items-center gap-2">
            {selectedIds.length > 0 && (
              <>
                <Button
                  onClick={() => setShowDateDialog(true)}
                  className="bg-blue-600 hover:bg-blue-700 text-white font-bold"
                  data-testid="button-modify-date"
                >
                  <Calendar className="h-4 w-4 mr-2" />
                  Modifica scadenza ({selectedIds.length})
                </Button>
                <Button
                  onClick={handleSendEmails}
                  disabled={sendEmailsMutation.isPending}
                  className="bg-green-600 hover:bg-green-700 text-white font-bold"
                  data-testid="button-send-emails"
                >
                  <Send className="h-4 w-4 mr-2" />
                  Invia avvia corso ({selectedIds.length})
                </Button>
                <Button
                  onClick={handleSendReminder}
                  disabled={sendReminderMutation.isPending}
                  className="bg-amber-600 hover:bg-amber-700 text-white font-bold"
                  data-testid="button-send-reminder"
                >
                  <Bell className="h-4 w-4 mr-2" />
                  Invia sollecito ({selectedIds.length})
                </Button>
                <Button
                  onClick={() => setShowDeleteDialog(true)}
                  className="bg-red-600 hover:bg-red-700 text-white font-bold"
                  data-testid="button-delete-enrollments"
                >
                  <Trash2 className="h-4 w-4 mr-2" />
                  Rimuovi licenze ({selectedIds.length})
                </Button>
              </>
            )}
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-lg border-2 border-black overflow-hidden">
          <table className="w-full" data-testid="table-enrollments">
            <thead className="bg-black">
              <tr>
                <th className="w-10 p-3">
                  <Checkbox 
                    className="border-yellow-400" 
                    checked={selectedIds.length === displayedEnrollments.length && displayedEnrollments.length > 0}
                    onCheckedChange={(checked) => handleSelectAll(checked as boolean)}
                    data-testid="checkbox-select-all" 
                  />
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Azienda
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Cognome Nome
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Corso
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Email
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Data Attivazione
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Ultimo Accesso
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Termine Programmato
                </th>
                <th className="text-left p-3 text-xs font-bold text-yellow-400 uppercase">
                  Progresso
                </th>
                <th className="text-center p-3 text-xs font-bold text-yellow-400 uppercase">
                  Email
                </th>
                <th className="text-center p-3 text-xs font-bold text-yellow-400 uppercase">
                  Azioni
                </th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={11} className="text-center py-8 text-black">
                    Caricamento...
                  </td>
                </tr>
              ) : displayedEnrollments.length === 0 ? (
                <tr>
                  <td colSpan={11} className="text-center py-8 text-black">
                    Nessun corso attivato trovato
                  </td>
                </tr>
              ) : (
                displayedEnrollments.map((enrollment, idx) => (
                  <tr
                    key={enrollment.id}
                    className={idx % 2 === 0 ? "bg-white" : "bg-yellow-50"}
                    data-testid={`row-enrollment-${enrollment.id}`}
                  >
                    <td className="p-3">
                      <Checkbox 
                        checked={selectedIds.includes(enrollment.id)}
                        onCheckedChange={(checked) => handleSelectOne(enrollment.id, checked as boolean)}
                        data-testid={`checkbox-row-${enrollment.id}`} 
                      />
                    </td>
                    <td className="p-3 text-sm text-black">
                      {enrollment.companyName}
                    </td>
                    <td className="p-3 text-sm font-medium text-black">
                      {enrollment.userName}
                    </td>
                    <td className="p-3 text-sm text-black max-w-xs">
                      {enrollment.courseName}
                    </td>
                    <td className="p-3 text-sm">
                      <a
                        href={`mailto:${enrollment.userEmail}`}
                        className="text-amber-700 hover:underline font-medium"
                        data-testid={`link-email-${enrollment.id}`}
                      >
                        {enrollment.userEmail}
                      </a>
                    </td>
                    <td className="p-3 text-sm text-black">
                      {formatDate(enrollment.emailSentAt)}
                    </td>
                    <td className="p-3 text-sm text-black">
                      {formatDate(enrollment.lastAccessAt)}
                    </td>
                    <td className="p-3 text-sm text-black font-medium">
                      {formatDate(enrollment.endDate)}
                    </td>
                    <td className="p-3">
                      <div className="flex items-center gap-2">
                        <span
                          className={`inline-flex items-center justify-center min-w-[32px] px-2 py-1 rounded text-xs font-bold text-white ${getProgressColor(enrollment.progress)}`}
                          data-testid={`badge-progress-${enrollment.id}`}
                        >
                          {enrollment.progress}
                        </span>
                        {enrollment.progress > 0 && enrollment.progress < 100 && (
                          <div className="w-16 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div
                              className={`h-full ${getProgressColor(enrollment.progress)}`}
                              style={{ width: `${enrollment.progress}%` }}
                            />
                          </div>
                        )}
                      </div>
                    </td>
                    <td className="p-3 text-center">
                      {enrollment.emailOpenedAt ? (
                        <div className="flex flex-col items-center" title={`Letta: ${formatDate(enrollment.emailOpenedAt)}`}>
                          <MailOpen className="h-5 w-5 text-green-600" />
                          <span className="text-xs text-green-600 font-medium">Letta</span>
                        </div>
                      ) : enrollment.emailSentAt ? (
                        <div className="flex flex-col items-center" title={`Inviata: ${formatDate(enrollment.emailSentAt)}`}>
                          <Mail className="h-5 w-5 text-amber-600" />
                          <span className="text-xs text-amber-600 font-medium">Inviata</span>
                        </div>
                      ) : (
                        <div className="flex flex-col items-center" title="Non inviata">
                          <MailX className="h-5 w-5 text-gray-400" />
                          <span className="text-xs text-gray-400">-</span>
                        </div>
                      )}
                    </td>
                    <td className="p-3 text-center">
                      <Button
                        size="sm"
                        onClick={() => handleLaunchCourse(enrollment)}
                        className="bg-green-600 hover:bg-green-700 text-white"
                        data-testid={`button-launch-${enrollment.id}`}
                      >
                        <Play className="h-4 w-4 mr-1" />
                        Avvia
                      </Button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="mt-4 text-sm text-black font-medium">
          Mostrando {displayedEnrollments.length} di {filteredEnrollments.length} risultati
        </div>
      </div>

      <Dialog open={showDateDialog} onOpenChange={setShowDateDialog}>
        <DialogContent className="bg-white">
          <DialogHeader>
            <DialogTitle className="text-black">Modifica Scadenza</DialogTitle>
            <DialogDescription>
              Imposta la nuova data di scadenza per {selectedIds.length} corso/i selezionato/i
            </DialogDescription>
          </DialogHeader>
          <div className="py-4">
            <Input
              type="date"
              value={newEndDate}
              onChange={(e) => setNewEndDate(e.target.value)}
              className="bg-white text-black border-gray-300"
              data-testid="input-new-end-date"
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowDateDialog(false)} data-testid="button-cancel-date">
              Annulla
            </Button>
            <Button
              onClick={handleUpdateEndDate}
              disabled={!newEndDate || updateEndDateMutation.isPending}
              className="bg-blue-600 hover:bg-blue-700 text-white"
              data-testid="button-confirm-date"
            >
              Conferma
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      <AlertDialog open={showDeleteDialog} onOpenChange={setShowDeleteDialog}>
        <AlertDialogContent className="bg-white">
          <AlertDialogHeader>
            <AlertDialogTitle className="text-black">Rimuovi Licenze</AlertDialogTitle>
            <AlertDialogDescription>
              Sei sicuro di voler rimuovere {selectedIds.length} licenza/e? Questa azione non può essere annullata.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel data-testid="button-cancel-delete">Annulla</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteEnrollments}
              className="bg-red-600 hover:bg-red-700 text-white"
              data-testid="button-confirm-delete"
            >
              Rimuovi
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
