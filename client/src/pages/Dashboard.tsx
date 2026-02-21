import { useQuery } from "@tanstack/react-query";
import { useAuth } from "@/hooks/use-auth";
import { Award, Activity, AlertTriangle, Clock, Mail, Users, Shield, FileCheck } from "lucide-react";

export default function Dashboard() {
  const { user } = useAuth();
  const now = new Date();
  const dateStr = now.toLocaleDateString("it-IT", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

  const { data: stats } = useQuery<{ tutors: number; clients: number; sales: number; users: number }>({
    queryKey: ["stats"],
    queryFn: () => fetch("/api/stats", { credentials: "include" }).then((r) => r.json()),
  });

  const { data: recentEnrollments = [] } = useQuery<any[]>({
    queryKey: ["enrollments"],
    queryFn: () => fetch("/api/enrollments", { credentials: "include" }).then((r) => r.json()),
  });

  const { data: tutorData } = useQuery<any>({
    queryKey: ["my-tutor", user?.tutorId],
    queryFn: () => fetch(`/api/tutors/${user!.tutorId}`, { credentials: "include" }).then((r) => r.json()),
    enabled: !!user?.tutorId,
  });

  const completed = recentEnrollments.filter((e: any) => e.status === "completed").length;
  const active = recentEnrollments.filter((e: any) => e.status === "active" && e.progress > 0).length;
  const expired = recentEnrollments.filter((e: any) => {
    if (!e.endDate) return false;
    return new Date(e.endDate).getTime() < Date.now();
  }).length;
  const neverStarted = recentEnrollments.filter((e: any) => e.status === "active" && e.progress === 0).length;

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-white">Dashboard LMS</h1>
          <p className="text-sm text-gray-500">Benvenuto nella piattaforma Tutor81</p>
        </div>
        <div className="text-sm text-gray-500">{dateStr}</div>
      </div>

      {/* 4 Stat cards: Attestati (green), In Attività (blue), Scaduti (red), Mai Avviati (dark) */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="rounded-2xl bg-gradient-to-br from-green-500 to-green-600 p-5 text-white">
          <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mb-3">
            <Award size={20} />
          </div>
          <div className="text-xs font-bold uppercase tracking-wide opacity-80">Attestati</div>
          <div className="text-4xl font-black mt-1">{completed}</div>
        </div>
        <div className="rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 p-5 text-white">
          <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mb-3">
            <Activity size={20} />
          </div>
          <div className="text-xs font-bold uppercase tracking-wide opacity-80">In Attività</div>
          <div className="text-4xl font-black mt-1">{active}</div>
        </div>
        <div className="rounded-2xl bg-gradient-to-br from-red-500 to-red-600 p-5 text-white">
          <div className="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center mb-3">
            <AlertTriangle size={20} />
          </div>
          <div className="text-xs font-bold uppercase tracking-wide opacity-80">Scaduti</div>
          <div className="text-4xl font-black mt-1">{expired}</div>
        </div>
        <div className="rounded-2xl bg-[#1a1a1a] border border-white/10 p-5 text-white">
          <div className="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center mb-3">
            <Clock size={20} className="text-gray-400" />
          </div>
          <div className="text-xs font-bold uppercase tracking-wide text-gray-400">Mai Avviati</div>
          <div className="text-4xl font-black mt-1">{neverStarted}</div>
        </div>
      </div>

      {/* 3-column: Attività Recenti | Ente Formativo (yellow) | Riepilogo */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Attività Recenti */}
        <div className="bg-[#141414] rounded-2xl border border-white/5 p-6 min-h-[300px]">
          <h2 className="text-base font-bold text-white mb-4 flex items-center gap-2">
            <Activity size={16} className="text-yellow-500" />
            Attività Recenti
          </h2>
          {recentEnrollments.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-12 text-gray-600">
              <Mail size={40} className="mb-3 opacity-30" />
              <span className="text-sm">Nessuna attività recente</span>
            </div>
          ) : (
            <div className="divide-y divide-white/5">
              {recentEnrollments.slice(0, 6).map((e: any) => (
                <div key={e.id} className="py-3 first:pt-0 last:pb-0">
                  <div className="text-xs font-medium text-white">{e.userName}</div>
                  <div className="text-[11px] text-yellow-500">{e.courseName}</div>
                  <div className="text-[10px] text-gray-600">
                    {e.lastAccessAt ? new Date(e.lastAccessAt).toLocaleDateString("it-IT") : "—"}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* LA TUA LICENZA - white card */}
        <div className="bg-white rounded-2xl p-6 flex flex-col items-center justify-center text-center min-h-[300px]">
          <div className="text-[10px] text-gray-400 uppercase tracking-widest mb-4">La Tua Licenza</div>
          {tutorData?.logoUrl ? (
            <img src={tutorData.logoUrl} alt="" className="h-14 max-w-[160px] object-contain mb-4" />
          ) : (
            <div className="w-14 h-14 rounded-xl bg-gray-100 flex items-center justify-center mb-4">
              <Shield size={28} className="text-gray-400" />
            </div>
          )}
          <div className="text-lg font-black text-gray-900">{tutorData?.businessName || user?.tutorName || "TUTOR 81 LMS"}</div>
          <div className="text-xs text-gray-500 mt-1">{tutorData?.email || "assistenza@tutor81.it"}</div>
          <div className="mt-3 inline-block">
            <span className={`text-[10px] font-bold px-2 py-1 rounded ${
              tutorData?.subscriptionType === "CONSULENTI 1500" ? "bg-blue-100 text-blue-700" :
              tutorData?.subscriptionType === "ENTI AUTORIZZATI 1500" ? "bg-purple-100 text-purple-700" :
              "bg-yellow-100 text-yellow-700"
            }`}>{tutorData?.subscriptionType || "—"}</span>
          </div>
          <div className="mt-4 pt-4 border-t border-gray-200 w-full space-y-1">
            <div className="text-xs text-gray-600 font-medium">{user?.tutorName || "Superadmin Tutor81"}</div>
            <div className="text-[10px] text-gray-400 uppercase">AMMINISTRATORE</div>
          </div>
          {tutorData?.subscriptionStart && (
            <div className="mt-3 flex items-center gap-2 text-xs">
              <span className="text-gray-400">Licenza dal:</span>
              <span className="text-gray-700 font-bold">{new Date(tutorData.subscriptionStart).toLocaleDateString("it-IT")}</span>
            </div>
          )}
        </div>

        {/* RIEPILOGO */}
        <div className="space-y-4">
          <h2 className="text-sm font-bold text-white flex items-center gap-2">
            <FileCheck size={14} className="text-yellow-500" />
            RIEPILOGO
          </h2>

          <div className="bg-[#141414] rounded-xl border border-white/5 p-4 flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center flex-shrink-0">
              <Users size={18} className="text-purple-400" />
            </div>
            <div className="flex-1">
              <div className="text-xs text-gray-400">N° Amministratori</div>
            </div>
            <div className="text-xl font-black text-white">{stats?.users ?? 0}</div>
          </div>

          <div className="bg-[#141414] rounded-xl border border-white/5 p-4 flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
              <Shield size={18} className="text-yellow-400" />
            </div>
            <div className="flex-1">
              <div className="text-xs text-gray-400">N° Clienti</div>
            </div>
            <div className="text-xl font-black text-white">{stats?.clients ?? 0}</div>
          </div>

          <div className="bg-[#141414] rounded-xl border border-white/5 p-4 flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-red-500/20 flex items-center justify-center flex-shrink-0">
              <Award size={18} className="text-red-400" />
            </div>
            <div className="flex-1">
              <div className="text-xs text-gray-400">Attestati</div>
            </div>
            <div className="text-xl font-black text-white">{completed}</div>
          </div>
        </div>
      </div>
    </div>
  );
}
