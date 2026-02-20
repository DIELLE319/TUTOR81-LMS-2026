import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { format } from "date-fns";
import { it } from "date-fns/locale";
import { useAuth } from "@/hooks/use-auth";
import { Search, Download, FileText, Printer, FileSpreadsheet } from "lucide-react";

interface Attestato {
  id: number;
  legacy_id: number;
  legacy_user_id: number;
  start_date: string | null;
  end_date: string | null;
  accreditation_code: string | null;
  progress: number;
  user_first_name: string | null;
  user_last_name: string | null;
  user_email: string | null;
  user_fiscal_code: string | null;
  course_title: string | null;
  course_hours: number | null;
  company_name: string | null;
  tutor_name: string | null;
}

export default function Certificates() {
  const { user } = useAuth();
  const [search, setSearch] = useState("");
  const [pageSize, setPageSize] = useState("50");
  const [selectedCompany, setSelectedCompany] = useState("all");
  const [selectedUser, setSelectedUser] = useState("all");
  const [selectedTutor, setSelectedTutor] = useState("all");
  
  // Se l'utente è venditore (admin tutor), filtra per il suo tutorId
  const isVenditore = user?.role === 1;
  const userTutorId = (user as any)?.tutorId;

  const { data: attestati = [], isLoading } = useQuery<Attestato[]>({
    queryKey: ["/api/attestati", userTutorId],
    queryFn: async () => {
      const url = userTutorId ? `/api/attestati?tutorId=${userTutorId}` : '/api/attestati';
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) throw new Error('Failed to fetch');
      return res.json();
    },
  });

  // Ottieni lista aziende uniche
  const companies = Array.from(new Set(attestati.map(a => a.company_name).filter((c): c is string => c !== null))).sort();
  
  // Ottieni lista corsisti unici (Cognome Nome)
  const users = Array.from(new Set(attestati.map(a => {
    if (a.user_last_name || a.user_first_name) {
      return `${a.user_last_name || ''} ${a.user_first_name || ''}`.trim();
    }
    return null;
  }).filter((u): u is string => u !== null && u !== ''))).sort();

  // Ottieni lista enti formativi unici
  const tutors = Array.from(new Set(attestati.map(a => a.tutor_name).filter((t): t is string => t !== null))).sort();

  const formatDate = (date: string | null) => {
    if (!date) return "-";
    return format(new Date(date), "dd/MM/yyyy", { locale: it });
  };

  const formatCourseTitle = (title: string | null) => {
    if (!title) return "-";
    return title.replace(/^EL\s+/i, '').replace(/^EL-\s*/i, '').replace(/^EL_/i, '');
  };

  const filteredAttestati = attestati.filter((a) => {
    // Filtro ente formativo
    if (selectedTutor !== "all" && a.tutor_name !== selectedTutor) return false;
    
    // Filtro azienda
    if (selectedCompany !== "all" && a.company_name !== selectedCompany) return false;
    
    // Filtro corsista
    if (selectedUser !== "all") {
      const userName = `${a.user_last_name || ''} ${a.user_first_name || ''}`.trim();
      if (userName !== selectedUser) return false;
    }
    
    // Filtro ricerca
    if (!search) return true;
    const s = search.toLowerCase();
    return (
      (a.user_first_name?.toLowerCase() || "").includes(s) ||
      (a.user_last_name?.toLowerCase() || "").includes(s) ||
      (a.user_email?.toLowerCase() || "").includes(s) ||
      (a.user_fiscal_code?.toLowerCase() || "").includes(s) ||
      (a.course_title?.toLowerCase() || "").includes(s) ||
      (a.company_name?.toLowerCase() || "").includes(s) ||
      (a.tutor_name?.toLowerCase() || "").includes(s)
    );
  });

  const displayedAttestati = filteredAttestati.slice(0, parseInt(pageSize));

  const handleDownload = async (legacyId: number) => {
    try {
      const response = await fetch(`/api/attestato/${legacyId}/download`, {
        credentials: "include",
      });
      if (!response.ok) {
        throw new Error("Download failed");
      }
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = `attestato_${legacyId}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (error) {
      console.error("Download error:", error);
      alert("Errore durante il download dell'attestato");
    }
  };

  const handleExport = () => {
    const headers = ["ID", "Cognome", "Nome", "Codice Fiscale", "Azienda", "Corso", "Data Fine", "Ente Formativo"];
    const rows = filteredAttestati.map(a => [
      a.legacy_id,
      a.user_last_name || "",
      a.user_first_name || "",
      a.user_fiscal_code || "",
      a.company_name || "",
      formatCourseTitle(a.course_title),
      formatDate(a.end_date),
      a.tutor_name || ""
    ]);
    
    const csvContent = [headers.join(";"), ...rows.map(r => r.join(";"))].join("\n");
    const blob = new Blob(["\ufeff" + csvContent], { type: "text/csv;charset=utf-8;" });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `attestati_${format(new Date(), "yyyy-MM-dd")}.csv`;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
  };

  const handlePrint = () => {
    window.print();
  };

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="bg-yellow-500 py-4 px-6 border-b-2 border-black/70">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <div className="flex items-center gap-4">
            <h1 className="text-xl font-semibold text-gray-800" data-testid="text-page-title">
              Attestati
            </h1>
            <select
              value={selectedTutor}
              onChange={(e) => setSelectedTutor(e.target.value)}
              className="h-9 w-64 bg-white border border-gray-300 rounded px-2 text-sm text-black"
              data-testid="select-tutor"
            >
              <option value="all">Tutti gli enti formativi</option>
              {tutors.map((tutor) => (
                <option key={tutor} value={tutor}>{tutor}</option>
              ))}
            </select>
          </div>
          <div className="flex items-center gap-4">
            <div className="text-sm text-gray-700">
              {filteredAttestati.length.toLocaleString()} attestati disponibili
            </div>
            <button
              onClick={handleExport}
              className="flex items-center gap-1 px-3 py-1.5 text-sm rounded bg-green-600 hover:bg-green-700 text-white font-bold"
              data-testid="button-export"
            >
              <FileSpreadsheet className="w-4 h-4" />
              Esporta
            </button>
            <button
              onClick={handlePrint}
              className="flex items-center gap-1 px-3 py-1.5 text-sm rounded bg-blue-600 hover:bg-blue-700 text-white font-bold"
              data-testid="button-print"
            >
              <Printer className="w-4 h-4" />
              Stampa
            </button>
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="flex items-center justify-between mb-4 flex-wrap gap-3">
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600">Azienda</span>
              <select
                value={selectedCompany}
                onChange={(e) => setSelectedCompany(e.target.value)}
                className="h-9 w-64 bg-white border border-gray-300 rounded px-2 text-sm text-black"
                data-testid="select-company"
              >
                <option value="all">Tutte le aziende</option>
                {companies.map((company) => (
                  <option key={company} value={company}>{company}</option>
                ))}
              </select>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600">Corsista</span>
              <select
                value={selectedUser}
                onChange={(e) => setSelectedUser(e.target.value)}
                className="h-9 w-56 bg-white border border-gray-300 rounded px-2 text-sm text-black"
                data-testid="select-user"
              >
                <option value="all">Tutti i corsisti</option>
                {users.map((u) => (
                  <option key={u} value={u}>{u}</option>
                ))}
              </select>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600">Mostra</span>
              <select
                value={pageSize}
                onChange={(e) => setPageSize(e.target.value)}
                className="h-9 w-20 bg-white border border-gray-300 rounded px-2 text-sm text-black"
                data-testid="select-page-size"
              >
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
              </select>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">Cerca:</span>
            <div className="relative">
              <input
                type="text"
                placeholder="Nome, email, corso..."
                value={search}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearch(e.target.value)}
                className="w-64 h-9 bg-white border border-gray-300 rounded pl-2 pr-8 text-sm text-black"
                data-testid="input-search"
              />
              <Search className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-xl overflow-hidden border-2 border-black/70">
          <table className="w-full" data-testid="table-attestati">
            <thead className="bg-yellow-500 border-b border-yellow-600/30">
              <tr>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">ID</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Cognome Nome</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Codice Fiscale</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Azienda</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Corso</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Data Fine</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Ente Formativo</th>
                <th className="text-center p-3 text-xs font-bold text-black uppercase">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-500">
                    <div className="animate-spin w-6 h-6 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto mb-2"></div>
                    Caricamento attestati...
                  </td>
                </tr>
              ) : displayedAttestati.length === 0 ? (
                <tr>
                  <td colSpan={8} className="text-center py-8 text-gray-500">
                    <FileText className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                    Nessun attestato trovato
                  </td>
                </tr>
              ) : (
                displayedAttestati.map((attestato, idx) => (
                  <tr
                    key={attestato.id}
                    className={idx % 2 === 0 ? "bg-white" : "bg-gray-50"}
                    data-testid={`row-attestato-${attestato.legacy_id}`}
                  >
                    <td className="p-3 text-sm text-gray-600">{attestato.legacy_id}</td>
                    <td className="p-3 text-sm font-medium text-gray-900">{attestato.user_last_name} {attestato.user_first_name}</td>
                    <td className="p-3 text-sm text-gray-600 font-mono">{attestato.user_fiscal_code || "-"}</td>
                    <td className="p-3 text-sm text-gray-800">{attestato.company_name || "-"}</td>
                    <td className="p-3 text-sm text-gray-800 max-w-xs truncate" title={formatCourseTitle(attestato.course_title)}>{formatCourseTitle(attestato.course_title)}</td>
                    <td className="p-3 text-sm text-gray-600">{formatDate(attestato.end_date)}</td>
                    <td className="p-3 text-sm text-gray-800">{attestato.tutor_name || "-"}</td>
                    <td className="p-3 text-center">
                      <button
                        onClick={() => handleDownload(attestato.legacy_id)}
                        className="inline-flex items-center gap-1 px-2 py-1 text-sm rounded bg-green-500 hover:bg-green-600 text-white font-bold"
                        data-testid={`button-download-${attestato.legacy_id}`}
                      >
                        <Download className="w-4 h-4" />
                        PDF
                      </button>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="mt-4 text-sm text-gray-600">
          Mostrando {displayedAttestati.length} di {filteredAttestati.length} risultati
        </div>
      </div>
    </div>
  );
}
