import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
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
import { Search, Download, FileText, ExternalLink } from "lucide-react";

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
}

export default function Certificates() {
  const [search, setSearch] = useState("");
  const [pageSize, setPageSize] = useState("50");
  const [selectedCompany, setSelectedCompany] = useState("all");

  const { data: attestati = [], isLoading } = useQuery<Attestato[]>({
    queryKey: ["/api/attestati"],
  });

  // Ottieni lista aziende uniche
  const companies = Array.from(new Set(attestati.map(a => a.company_name).filter((c): c is string => c !== null))).sort();

  const formatDate = (date: string | null) => {
    if (!date) return "-";
    return format(new Date(date), "dd/MM/yyyy", { locale: it });
  };

  const filteredAttestati = attestati.filter((a) => {
    // Filtro azienda
    if (selectedCompany !== "all" && a.company_name !== selectedCompany) return false;
    
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
      (a.accreditation_code?.toLowerCase() || "").includes(s)
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

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="bg-yellow-500 py-4 px-6">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold text-gray-800" data-testid="text-page-title">
            Attestati
          </h1>
          <div className="text-sm text-gray-700">
            {filteredAttestati.length.toLocaleString()} attestati disponibili
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600">Azienda</span>
              <Select value={selectedCompany} onValueChange={setSelectedCompany}>
                <SelectTrigger className="w-64 bg-white" data-testid="select-company">
                  <SelectValue placeholder="Tutte le aziende" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Tutte le aziende</SelectItem>
                  {companies.map((company) => (
                    <SelectItem key={company} value={company}>
                      {company}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600">Mostra</span>
              <Select value={pageSize} onValueChange={setPageSize}>
                <SelectTrigger className="w-20 bg-white" data-testid="select-page-size">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="25">25</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                  <SelectItem value="500">500</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">Cerca:</span>
            <div className="relative">
              <Input
                type="text"
                placeholder="Nome, email, corso..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-64 bg-white pr-8"
                data-testid="input-search"
              />
              <Search className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow overflow-hidden">
          <table className="w-full" data-testid="table-attestati">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  ID
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Cognome Nome
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Codice Fiscale
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Azienda
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Corso
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Data Fine
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Cod. Accreditamento
                </th>
                <th className="text-center p-3 text-xs font-semibold text-gray-600 uppercase">
                  Azioni
                </th>
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
                    <td className="p-3 text-sm text-gray-600">
                      {attestato.legacy_id}
                    </td>
                    <td className="p-3 text-sm font-medium text-gray-900">
                      {attestato.user_last_name} {attestato.user_first_name}
                    </td>
                    <td className="p-3 text-sm text-gray-600 font-mono">
                      {attestato.user_fiscal_code || "-"}
                    </td>
                    <td className="p-3 text-sm text-gray-800">
                      {attestato.company_name || "-"}
                    </td>
                    <td className="p-3 text-sm text-gray-800 max-w-xs truncate" title={attestato.course_title || ""}>
                      {attestato.course_title || "-"}
                    </td>
                    <td className="p-3 text-sm text-gray-600">
                      {formatDate(attestato.end_date)}
                    </td>
                    <td className="p-3 text-sm text-gray-600 font-mono">
                      {attestato.accreditation_code || "-"}
                    </td>
                    <td className="p-3 text-center">
                      <Button
                        size="sm"
                        variant="outline"
                        className="bg-green-500 hover:bg-green-600 text-white border-0"
                        onClick={() => handleDownload(attestato.legacy_id)}
                        data-testid={`button-download-${attestato.legacy_id}`}
                      >
                        <Download className="w-4 h-4 mr-1" />
                        PDF
                      </Button>
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
