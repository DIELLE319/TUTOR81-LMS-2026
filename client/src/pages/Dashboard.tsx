import { useQuery } from "@tanstack/react-query";
import { useAuth } from "@/hooks/use-auth";
import { Link } from "wouter";
import { Building2, Users, GraduationCap, FileText, BookOpen, ShoppingCart, Award, ArrowRight, Activity, TrendingUp } from "lucide-react";

export default function Dashboard() {
  const { user } = useAuth();
  const { data: stats } = useQuery<{ tutors: number; clients: number; sales: number; users: number }>({
    queryKey: ["stats"],
    queryFn: () => fetch("/api/stats", { credentials: "include" }).then((r) => r.json()),
  });

  const { data: recentEnrollments = [] } = useQuery<any[]>({
    queryKey: ["enrollments"],
    queryFn: () => fetch("/api/enrollments", { credentials: "include" }).then((r) => r.json()),
  });

  const recent = recentEnrollments.slice(0, 5);

  return (
    <div className="space-y-6">
      {/* Hero banner */}
      <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white p-8">
        <div className="absolute top-0 right-0 w-64 h-64 bg-yellow-500/10 rounded-full -translate-y-1/2 translate-x-1/3" />
        <div className="absolute bottom-0 left-0 w-48 h-48 bg-yellow-500/5 rounded-full translate-y-1/2 -translate-x-1/4" />
        <div className="relative z-10">
          <div className="flex items-center gap-3 mb-2">
            <div className="w-12 h-12 bg-yellow-500 rounded-xl flex items-center justify-center text-black font-black text-lg shadow-lg shadow-yellow-500/30">T</div>
            <div>
              <h1 className="text-2xl font-bold">Benvenuto, {user?.firstName || "Admin"}</h1>
              <p className="text-gray-400 text-sm">{user?.tutorName || "TUTOR 81 LMS"}</p>
            </div>
          </div>
          <p className="text-gray-300 text-sm mt-3 max-w-lg">
            Gestisci i tuoi corsi di formazione, monitora i progressi dei corsisti e genera attestati di completamento.
          </p>
        </div>
      </div>

      {/* Stat cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="relative overflow-hidden bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/20">
          <div className="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/3 translate-x-1/3" />
          <Building2 size={22} className="mb-3 opacity-80" />
          <div className="text-3xl font-black">{stats?.clients?.toLocaleString() ?? "—"}</div>
          <div className="text-blue-100 text-sm font-medium mt-1">Aziende Clienti</div>
        </div>
        <div className="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/20">
          <div className="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/3 translate-x-1/3" />
          <Users size={22} className="mb-3 opacity-80" />
          <div className="text-3xl font-black">{stats?.users?.toLocaleString() ?? "—"}</div>
          <div className="text-emerald-100 text-sm font-medium mt-1">Corsisti Registrati</div>
        </div>
        <div className="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-500 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/20">
          <div className="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/3 translate-x-1/3" />
          <FileText size={22} className="mb-3 opacity-80" />
          <div className="text-3xl font-black">{stats?.sales ?? "—"}</div>
          <div className="text-amber-100 text-sm font-medium mt-1">Vendite Totali</div>
        </div>
        <div className="relative overflow-hidden bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg shadow-purple-500/20">
          <div className="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/3 translate-x-1/3" />
          <GraduationCap size={22} className="mb-3 opacity-80" />
          <div className="text-3xl font-black">{stats?.tutors ?? "—"}</div>
          <div className="text-purple-100 text-sm font-medium mt-1">Enti Formativi</div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Quick actions */}
        <div className="bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <Activity size={18} className="text-yellow-500" />
            Azioni Rapide
          </h2>
          <div className="space-y-2">
            <Link href="/catalog" className="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-yellow-50 hover:border-yellow-200 border border-transparent transition-all group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-yellow-100 rounded-lg flex items-center justify-center"><BookOpen size={16} className="text-yellow-600" /></div>
                <span className="text-sm font-semibold text-gray-700">Catalogo Corsi</span>
              </div>
              <ArrowRight size={16} className="text-gray-300 group-hover:text-yellow-500 transition-colors" />
            </Link>
            <Link href="/activated-courses" className="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-green-50 hover:border-green-200 border border-transparent transition-all group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-green-100 rounded-lg flex items-center justify-center"><ShoppingCart size={16} className="text-green-600" /></div>
                <span className="text-sm font-semibold text-gray-700">Corsi Attivi</span>
              </div>
              <ArrowRight size={16} className="text-gray-300 group-hover:text-green-500 transition-colors" />
            </Link>
            <Link href="/certificates" className="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-blue-50 hover:border-blue-200 border border-transparent transition-all group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-blue-100 rounded-lg flex items-center justify-center"><Award size={16} className="text-blue-600" /></div>
                <span className="text-sm font-semibold text-gray-700">Attestati</span>
              </div>
              <ArrowRight size={16} className="text-gray-300 group-hover:text-blue-500 transition-colors" />
            </Link>
            <Link href="/clients" className="flex items-center justify-between p-3 rounded-xl bg-gray-50 hover:bg-purple-50 hover:border-purple-200 border border-transparent transition-all group">
              <div className="flex items-center gap-3">
                <div className="w-9 h-9 bg-purple-100 rounded-lg flex items-center justify-center"><Building2 size={16} className="text-purple-600" /></div>
                <span className="text-sm font-semibold text-gray-700">Elenco Clienti</span>
              </div>
              <ArrowRight size={16} className="text-gray-300 group-hover:text-purple-500 transition-colors" />
            </Link>
          </div>
        </div>

        {/* Recent enrollments */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-gray-200 p-6 shadow-sm">
          <h2 className="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <TrendingUp size={18} className="text-yellow-500" />
            Iscrizioni Recenti
          </h2>
          {recent.length === 0 ? (
            <div className="text-center py-8 text-gray-400">
              <GraduationCap size={32} className="mx-auto mb-2 opacity-50" />
              <p className="text-sm">Nessuna iscrizione attiva</p>
            </div>
          ) : (
            <div className="space-y-2">
              {recent.map((e: any) => (
                <div key={e.id} className="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                  <div className="flex items-center gap-3 min-w-0">
                    <div className="w-9 h-9 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600 flex-shrink-0">
                      {(e.userName || "??").split(" ").map((n: string) => n[0]).join("").slice(0, 2)}
                    </div>
                    <div className="min-w-0">
                      <div className="text-sm font-semibold text-gray-800 truncate">{e.userName}</div>
                      <div className="text-xs text-gray-500 truncate">{e.courseName}</div>
                    </div>
                  </div>
                  <div className="flex items-center gap-3 flex-shrink-0 ml-3">
                    <div className="w-16 h-1.5 bg-gray-200 rounded-full overflow-hidden">
                      <div className="h-full bg-yellow-500 rounded-full" style={{ width: `${e.progress || 0}%` }} />
                    </div>
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${e.status === "active" ? "bg-green-500 text-white" : e.status === "completed" ? "bg-blue-500 text-white" : "bg-gray-400 text-white"}`}>
                      {e.status === "active" ? "Attivo" : e.status === "completed" ? "Completato" : e.status}
                    </span>
                  </div>
                </div>
              ))}
              <Link href="/activated-courses" className="block text-center text-sm text-yellow-600 font-semibold hover:text-yellow-700 pt-2">
                Vedi tutti i corsi attivi →
              </Link>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
