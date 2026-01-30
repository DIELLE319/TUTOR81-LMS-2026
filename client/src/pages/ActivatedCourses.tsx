import { useQuery } from "@tanstack/react-query";
import { useState } from "react";
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
import { Search } from "lucide-react";

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
}

interface Company {
  id: number;
  businessName: string;
}

export default function ActivatedCourses() {
  const [statusFilter, setStatusFilter] = useState<string>("all");
  const [companyFilter, setCompanyFilter] = useState<string>("all");
  const [search, setSearch] = useState("");
  const [pageSize, setPageSize] = useState("25");

  const { data: enrollments = [], isLoading } = useQuery<Enrollment[]>({
    queryKey: ["/api/enrollments", statusFilter, search],
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ["/api/companies-list"],
  });

  const formatDate = (date: string | null) => {
    if (!date) return "-";
    return format(new Date(date), "dd/MM/yyyy", { locale: it });
  };

  const getProgressColor = (progress: number) => {
    if (progress === 0) return "bg-red-500";
    if (progress < 50) return "bg-orange-500";
    if (progress < 100) return "bg-yellow-500";
    return "bg-green-500";
  };

  const filteredEnrollments = enrollments.filter((e) => {
    if (statusFilter === "active" && (e.progress === 0 || e.progress === 100)) return false;
    if (statusFilter === "not_started" && e.progress !== 0) return false;
    if (companyFilter !== "all" && !e.companyName.toLowerCase().includes(companyFilter.toLowerCase())) return false;
    if (search) {
      const s = search.toLowerCase();
      return (
        e.userName.toLowerCase().includes(s) ||
        e.userEmail.toLowerCase().includes(s) ||
        e.companyName.toLowerCase().includes(s) ||
        e.courseName.toLowerCase().includes(s)
      );
    }
    return true;
  });

  const displayedEnrollments = filteredEnrollments.slice(0, parseInt(pageSize));

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="bg-yellow-500 py-4 px-6">
        <div className="flex items-center justify-between">
          <h1 className="text-xl font-semibold text-gray-800" data-testid="text-page-title">
            Lista Corsi Attivati
          </h1>
          <div className="flex gap-2">
            <Button
              variant={statusFilter === "active" ? "default" : "outline"}
              className={statusFilter === "active" 
                ? "bg-blue-600 hover:bg-blue-700 text-white" 
                : "bg-white text-blue-600 border-blue-600 hover:bg-blue-50"
              }
              onClick={() => setStatusFilter(statusFilter === "active" ? "all" : "active")}
              data-testid="button-filter-active"
            >
              IN CORSO
            </Button>
            <Button
              variant={statusFilter === "not_started" ? "default" : "outline"}
              className={statusFilter === "not_started"
                ? "bg-red-600 hover:bg-red-700 text-white"
                : "bg-white text-red-600 border-red-600 hover:bg-red-50"
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
        <div className="flex items-center justify-between mb-4">
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <span className="text-sm text-gray-600">Show</span>
              <Select value={pageSize} onValueChange={setPageSize}>
                <SelectTrigger className="w-20 bg-white" data-testid="select-page-size">
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
            <Select value={companyFilter} onValueChange={setCompanyFilter}>
              <SelectTrigger className="w-64 bg-white" data-testid="select-company-filter">
                <SelectValue placeholder="Tutte le Aziende" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">Tutte le Aziende</SelectItem>
                {companies.slice(0, 100).map((c) => (
                  <SelectItem key={c.id} value={c.businessName}>
                    {c.businessName}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">Search:</span>
            <div className="relative">
              <Input
                type="text"
                placeholder="Cerca..."
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="w-48 bg-white pr-8"
                data-testid="input-search"
              />
              <Search className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            </div>
          </div>
        </div>

        <div className="bg-white rounded-lg shadow overflow-hidden">
          <table className="w-full" data-testid="table-enrollments">
            <thead className="bg-gray-50 border-b">
              <tr>
                <th className="w-10 p-3">
                  <Checkbox data-testid="checkbox-select-all" />
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Azienda
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Cognome Nome
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Corso
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Email
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Data Inizio
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Ultimo Accesso
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Termine Programmato
                </th>
                <th className="text-left p-3 text-xs font-semibold text-gray-600 uppercase">
                  Progresso
                </th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={9} className="text-center py-8 text-gray-500">
                    Caricamento...
                  </td>
                </tr>
              ) : displayedEnrollments.length === 0 ? (
                <tr>
                  <td colSpan={9} className="text-center py-8 text-gray-500">
                    Nessun corso attivato trovato
                  </td>
                </tr>
              ) : (
                displayedEnrollments.map((enrollment, idx) => (
                  <tr
                    key={enrollment.id}
                    className={idx % 2 === 0 ? "bg-white" : "bg-gray-50"}
                    data-testid={`row-enrollment-${enrollment.id}`}
                  >
                    <td className="p-3">
                      <Checkbox data-testid={`checkbox-row-${enrollment.id}`} />
                    </td>
                    <td className="p-3 text-sm text-gray-800">
                      {enrollment.companyName}
                    </td>
                    <td className="p-3 text-sm font-medium text-gray-900">
                      {enrollment.userName}
                    </td>
                    <td className="p-3 text-sm text-gray-800 max-w-xs">
                      {enrollment.courseName}
                    </td>
                    <td className="p-3 text-sm">
                      <a
                        href={`mailto:${enrollment.userEmail}`}
                        className="text-blue-600 hover:underline"
                        data-testid={`link-email-${enrollment.id}`}
                      >
                        {enrollment.userEmail}
                      </a>
                    </td>
                    <td className="p-3 text-sm text-gray-600">
                      {formatDate(enrollment.startDate)}
                    </td>
                    <td className="p-3 text-sm text-gray-600">
                      {formatDate(enrollment.lastAccessAt)}
                    </td>
                    <td className="p-3 text-sm text-gray-900 font-medium">
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
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>

        <div className="mt-4 text-sm text-gray-600">
          Mostrando {displayedEnrollments.length} di {filteredEnrollments.length} risultati
        </div>
      </div>
    </div>
  );
}
