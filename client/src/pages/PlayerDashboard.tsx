import { useState, useEffect } from "react";
import { useLocation } from "wouter";
import { BookOpen, Clock, CheckCircle, AlertTriangle, LogOut, Play } from "lucide-react";

interface PlayerSession {
  user: { id?: number; firstName: string; lastName: string; company?: string };
  enrollment: { id: number; courseName?: string; courseTitle?: string; licenseCode: string };
}

interface Enrollment {
  id: number;
  courseId: number;
  courseTitle: string;
  courseHours: number | null;
  courseCategory: string | null;
  courseSubcategory: string | null;
  licenseCode: string;
  progress: number | null;
  status: string;
  startDate: string | null;
  endDate: string | null;
  lastAccessAt: string | null;
  completedAt: string | null;
}

export default function PlayerDashboard() {
  const [, navigate] = useLocation();
  const [session, setSession] = useState<PlayerSession | null>(null);
  const [enrollments, setEnrollments] = useState<Enrollment[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const raw = sessionStorage.getItem("playerSession");
    if (!raw) { navigate("/player-login"); return; }
    const parsed: PlayerSession = JSON.parse(raw);
    setSession(parsed);

    // Load enrollments for student
    const studentId = parsed.user.id;
    if (studentId) {
      fetch(`/api/player/student/${studentId}/enrollments`)
        .then((r) => r.json())
        .then((data) => { setEnrollments(data); setLoading(false); })
        .catch(() => setLoading(false));
    } else {
      // If no student ID, show the single enrollment from session
      setEnrollments([{
        id: parsed.enrollment.id,
        courseId: 0,
        courseTitle: parsed.enrollment.courseName || parsed.enrollment.courseTitle || "Corso",
        courseHours: null,
        courseCategory: null,
        courseSubcategory: null,
        licenseCode: parsed.enrollment.licenseCode,
        progress: 0,
        status: "active",
        startDate: null,
        endDate: null,
        lastAccessAt: null,
        completedAt: null,
      }]);
      setLoading(false);
    }
  }, [navigate]);

  const logout = () => {
    sessionStorage.removeItem("playerSession");
    navigate("/player-login");
  };

  if (!session) return null;
  const name = `${session.user.firstName} ${session.user.lastName}`;
  const initials = `${(session.user.firstName?.[0] || "").toUpperCase()}${(session.user.lastName?.[0] || "").toUpperCase()}`;

  const active = enrollments.filter((e) => e.status === "active");
  const completed = enrollments.filter((e) => e.status === "completed");

  return (
    <div className="min-h-screen bg-[#030712]">
      {/* Header */}
      <header className="bg-gray-900/80 backdrop-blur-md border-b border-white/5 sticky top-0 z-20">
        <div className="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-9 h-9 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center text-black font-black text-sm shadow-md shadow-yellow-500/20">T</div>
            <span className="font-bold text-white text-sm">TUTOR 81</span>
          </div>
          <div className="flex items-center gap-4">
            <div className="flex items-center gap-2">
              <div className="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-xs font-bold text-yellow-400">{initials}</div>
              <span className="text-sm text-gray-300 hidden sm:block">{name}</span>
            </div>
            <button onClick={logout} className="text-gray-500 hover:text-red-400 transition-colors" title="Esci">
              <LogOut size={18} />
            </button>
          </div>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 py-8">
        {/* Welcome */}
        <div className="mb-8">
          <h1 className="text-2xl font-bold text-white">Ciao, {session.user.firstName}!</h1>
          <p className="text-gray-400 text-sm mt-1">
            {session.user.company ? `${session.user.company} — ` : ""}I tuoi corsi di formazione
          </p>
        </div>

        {loading ? (
          <div className="flex items-center justify-center py-20">
            <div className="animate-spin w-8 h-8 border-4 border-yellow-500 border-t-transparent rounded-full" />
          </div>
        ) : (
          <div className="space-y-8">
            {/* Active courses */}
            {active.length > 0 && (
              <div>
                <h2 className="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                  <BookOpen size={14} className="text-yellow-500" /> Corsi In Corso ({active.length})
                </h2>
                <div className="grid gap-4">
                  {active.map((e) => (
                    <div key={e.id} className="bg-gray-900/60 border border-white/5 rounded-2xl p-5 hover:border-yellow-500/30 transition-all group">
                      <div className="flex items-start justify-between gap-4">
                        <div className="min-w-0 flex-1">
                          <h3 className="text-white font-semibold text-lg truncate">{e.courseTitle}</h3>
                          <div className="flex items-center gap-3 mt-2">
                            {e.courseCategory && <span className="text-xs text-gray-500">{e.courseCategory}</span>}
                            {e.courseHours && (
                              <span className="text-xs text-gray-500 flex items-center gap-1">
                                <Clock size={11} /> {e.courseHours}h
                              </span>
                            )}
                            {e.endDate && (
                              <span className="text-xs text-gray-500 flex items-center gap-1">
                                <AlertTriangle size={11} /> Scade: {new Date(e.endDate).toLocaleDateString("it-IT")}
                              </span>
                            )}
                          </div>
                          {/* Progress bar */}
                          <div className="mt-4">
                            <div className="flex items-center justify-between mb-1.5">
                              <span className="text-xs text-gray-400">Progresso</span>
                              <span className="text-xs font-bold text-yellow-500">{e.progress || 0}%</span>
                            </div>
                            <div className="w-full h-2 bg-gray-800 rounded-full overflow-hidden">
                              <div className="h-full bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-full transition-all duration-500" style={{ width: `${e.progress || 0}%` }} />
                            </div>
                          </div>
                        </div>
                        <button onClick={() => navigate(`/player/course/${e.id}`)}
                          className="flex-shrink-0 w-14 h-14 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl flex items-center justify-center shadow-lg shadow-yellow-500/20 group-hover:shadow-yellow-500/40 transition-all">
                          <Play size={24} className="text-black ml-0.5" fill="black" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Completed courses */}
            {completed.length > 0 && (
              <div>
                <h2 className="text-sm font-bold text-gray-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                  <CheckCircle size={14} className="text-green-500" /> Corsi Completati ({completed.length})
                </h2>
                <div className="grid gap-3">
                  {completed.map((e) => (
                    <div key={e.id} className="bg-gray-900/40 border border-white/5 rounded-xl p-4 flex items-center justify-between">
                      <div className="min-w-0">
                        <h3 className="text-gray-300 font-medium truncate">{e.courseTitle}</h3>
                        <p className="text-xs text-gray-500 mt-1">
                          Completato il {e.completedAt ? new Date(e.completedAt).toLocaleDateString("it-IT") : "—"}
                        </p>
                      </div>
                      <div className="flex items-center gap-2">
                        <span className="text-[11px] font-bold px-2 py-1 rounded-lg bg-green-500/20 text-green-400">Completato</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {enrollments.length === 0 && (
              <div className="text-center py-16">
                <BookOpen size={48} className="mx-auto text-gray-700 mb-4" />
                <p className="text-gray-500">Nessun corso assegnato</p>
                <p className="text-gray-600 text-sm mt-1">Contatta il tuo ente formativo per maggiori informazioni</p>
              </div>
            )}
          </div>
        )}
      </main>
    </div>
  );
}
