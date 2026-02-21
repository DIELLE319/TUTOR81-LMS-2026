import { useState, useEffect, useRef, useCallback } from "react";
import { useLocation, useRoute } from "wouter";
import {
  ArrowLeft, Play, Pause, CheckCircle, Circle, Clock, BookOpen,
  ChevronDown, ChevronRight, Volume2, Maximize, SkipForward, LogOut,
} from "lucide-react";

interface LearningObject {
  id: number;
  title: string;
  objectType: string;
  jwplayerCode: string | null;
  videoFilename: string | null;
  duration: number | null;
  questions?: any[];
}

interface Lesson {
  id: number;
  title: string;
  learningObjects: LearningObject[];
}

interface Module {
  id: number;
  title: string;
  lessons: Lesson[];
}

interface CourseStructure {
  course: { id: number; title: string; description: string | null; hours: number | null; percentageToPass: number | null };
  modules: Module[];
}

interface EnrollmentData {
  id: number;
  courseId: number;
  courseTitle: string;
  courseDescription: string | null;
  courseHours: number | null;
  progress: number | null;
  status: string;
}

interface LOProgress {
  learningObjectId: number;
  watchedSeconds: number;
  completed: boolean;
}

export default function PlayerCourse() {
  const [, navigate] = useLocation();
  const [, params] = useRoute("/player/course/:id");
  const enrollmentId = params?.id ? parseInt(params.id) : 0;

  const [enrollment, setEnrollment] = useState<EnrollmentData | null>(null);
  const [structure, setStructure] = useState<CourseStructure | null>(null);
  const [loProgress, setLoProgress] = useState<LOProgress[]>([]);
  const [currentLO, setCurrentLO] = useState<LearningObject | null>(null);
  const [expandedModules, setExpandedModules] = useState<Set<number>>(new Set());
  const [sessionId, setSessionId] = useState<number | null>(null);
  const [playing, setPlaying] = useState(false);
  const [watchedSec, setWatchedSec] = useState(0);
  const [loading, setLoading] = useState(true);
  const timerRef = useRef<any>(null);
  const saveRef = useRef<any>(null);

  // Flatten all LOs in order
  const allLOs: { lo: LearningObject; moduleTitle: string; lessonTitle: string }[] = [];
  if (structure) {
    for (const mod of structure.modules) {
      for (const lesson of mod.lessons) {
        for (const lo of lesson.learningObjects) {
          allLOs.push({ lo, moduleTitle: mod.title, lessonTitle: lesson.title });
        }
      }
    }
  }

  const isLoCompleted = (loId: number) => loProgress.some((p) => p.learningObjectId === loId && p.completed);
  const completedCount = loProgress.filter((p) => p.completed).length;
  const totalCount = allLOs.length;
  const overallProgress = totalCount > 0 ? Math.round((completedCount / totalCount) * 100) : (enrollment?.progress || 0);

  // Load enrollment + structure + progress
  useEffect(() => {
    if (!enrollmentId) { navigate("/player/dashboard"); return; }
    const session = sessionStorage.getItem("playerSession");
    if (!session) { navigate("/player-login"); return; }

    const load = async () => {
      try {
        const [enrRes, progRes] = await Promise.all([
          fetch(`/api/player/enrollment/${enrollmentId}`).then((r) => r.json()),
          fetch(`/api/player/lo-progress/${enrollmentId}`).then((r) => r.json()),
        ]);
        setEnrollment(enrRes);
        setLoProgress(Array.isArray(progRes) ? progRes : []);

        // Load course structure
        const strRes = await fetch(`/api/player/course/${enrRes.courseId}/structure`).then((r) => r.json());
        setStructure(strRes);

        // Start session
        const parsed = JSON.parse(session);
        const sesRes = await fetch("/api/player/session/start", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ enrollmentId, studentId: parsed.user?.id || 0, courseId: enrRes.courseId }),
        }).then((r) => r.json());
        if (sesRes.sessionId) setSessionId(sesRes.sessionId);

        // Expand first module
        if (strRes.modules?.length > 0) {
          setExpandedModules(new Set([strRes.modules[0].id]));
        }
      } catch (e) {
        console.error("Load error:", e);
      }
      setLoading(false);
    };
    load();

    return () => {
      if (timerRef.current) clearInterval(timerRef.current);
    };
  }, [enrollmentId, navigate]);

  // End session on unmount
  useEffect(() => {
    return () => {
      if (sessionId) {
        navigator.sendBeacon("/api/player/session/end", JSON.stringify({ sessionId }));
      }
    };
  }, [sessionId]);

  // Save progress periodically
  const saveProgress = useCallback(async (loId: number, seconds: number, completed: boolean) => {
    try {
      await fetch("/api/player/save-progress", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ enrollmentId, learningObjectId: loId, watchedSeconds: seconds, completed }),
      });
      if (completed) {
        setLoProgress((prev) => {
          const existing = prev.find((p) => p.learningObjectId === loId);
          if (existing) return prev.map((p) => p.learningObjectId === loId ? { ...p, completed: true, watchedSeconds: seconds } : p);
          return [...prev, { learningObjectId: loId, watchedSeconds: seconds, completed: true }];
        });
        // Recalculate overall
        fetch(`/api/player/recalc-progress/${enrollmentId}`, { method: "POST" });
      }
    } catch (e) {
      console.error("Save progress error:", e);
    }
  }, [enrollmentId]);

  // Play/Pause timer
  const startPlayback = (lo: LearningObject) => {
    setCurrentLO(lo);
    setPlaying(true);
    setWatchedSec(loProgress.find((p) => p.learningObjectId === lo.id)?.watchedSeconds || 0);
    if (timerRef.current) clearInterval(timerRef.current);
    if (saveRef.current) clearInterval(saveRef.current);

    timerRef.current = setInterval(() => {
      setWatchedSec((prev) => prev + 1);
    }, 1000);

    // Auto-save every 30s
    saveRef.current = setInterval(() => {
      setWatchedSec((sec) => {
        saveProgress(lo.id, sec, false);
        return sec;
      });
    }, 30000);
  };

  const pausePlayback = () => {
    setPlaying(false);
    if (timerRef.current) { clearInterval(timerRef.current); timerRef.current = null; }
    if (saveRef.current) { clearInterval(saveRef.current); saveRef.current = null; }
    if (currentLO) saveProgress(currentLO.id, watchedSec, false);
  };

  const completeLO = () => {
    if (!currentLO) return;
    pausePlayback();
    saveProgress(currentLO.id, watchedSec, true);
    // Auto-advance to next LO
    const idx = allLOs.findIndex((a) => a.lo.id === currentLO.id);
    if (idx >= 0 && idx < allLOs.length - 1) {
      const next = allLOs[idx + 1];
      setTimeout(() => startPlayback(next.lo), 500);
    } else {
      setCurrentLO(null);
    }
  };

  const toggleModule = (id: number) => {
    setExpandedModules((prev) => {
      const next = new Set(prev);
      next.has(id) ? next.delete(id) : next.add(id);
      return next;
    });
  };

  const formatTime = (s: number) => {
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return `${m}:${sec.toString().padStart(2, "0")}`;
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-[#030712] flex items-center justify-center">
        <div className="animate-spin w-8 h-8 border-4 border-yellow-500 border-t-transparent rounded-full" />
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-[#030712] flex flex-col">
      {/* Top bar */}
      <header className="bg-gray-900/80 backdrop-blur-md border-b border-white/5 sticky top-0 z-20">
        <div className="px-4 h-14 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <button onClick={() => navigate("/player/dashboard")} className="text-gray-400 hover:text-white transition-colors">
              <ArrowLeft size={18} />
            </button>
            <div className="min-w-0">
              <h1 className="text-sm font-bold text-white truncate">{enrollment?.courseTitle || "Corso"}</h1>
              <div className="flex items-center gap-2">
                <span className="text-[11px] text-gray-500">{completedCount}/{totalCount} completati</span>
                <div className="w-20 h-1 bg-gray-800 rounded-full overflow-hidden">
                  <div className="h-full bg-yellow-500 rounded-full transition-all" style={{ width: `${overallProgress}%` }} />
                </div>
                <span className="text-[11px] font-bold text-yellow-500">{overallProgress}%</span>
              </div>
            </div>
          </div>
          <button onClick={() => { pausePlayback(); navigate("/player/dashboard"); }}
            className="text-gray-500 hover:text-red-400 transition-colors" title="Esci">
            <LogOut size={16} />
          </button>
        </div>
      </header>

      <div className="flex-1 flex flex-col lg:flex-row">
        {/* Main content area */}
        <div className="flex-1 flex flex-col">
          {/* Video / Content area */}
          <div className="bg-black aspect-video lg:aspect-auto lg:h-[60vh] relative flex items-center justify-center">
            {currentLO ? (
              <>
                {currentLO.jwplayerCode ? (
                  <div className="w-full h-full flex items-center justify-center bg-gray-950">
                    <div className="text-center">
                      <div className="w-20 h-20 rounded-full bg-yellow-500/20 flex items-center justify-center mx-auto mb-4">
                        <Volume2 size={32} className="text-yellow-500" />
                      </div>
                      <p className="text-white font-semibold">{currentLO.title}</p>
                      <p className="text-gray-400 text-sm mt-1">JWPlayer: {currentLO.jwplayerCode}</p>
                      <p className="text-yellow-500 font-mono text-2xl mt-3">{formatTime(watchedSec)}</p>
                    </div>
                  </div>
                ) : (
                  <div className="text-center">
                    <div className="w-20 h-20 rounded-full bg-yellow-500/20 flex items-center justify-center mx-auto mb-4">
                      <BookOpen size={32} className="text-yellow-500" />
                    </div>
                    <p className="text-white font-semibold text-lg">{currentLO.title}</p>
                    <p className="text-gray-400 text-sm mt-1 capitalize">{currentLO.objectType || "contenuto"}</p>
                    <p className="text-yellow-500 font-mono text-3xl mt-4">{formatTime(watchedSec)}</p>
                  </div>
                )}

                {/* Playback controls */}
                <div className="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/90 to-transparent p-4">
                  <div className="flex items-center justify-center gap-4">
                    {playing ? (
                      <button onClick={pausePlayback} className="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center text-black hover:bg-yellow-600 transition-colors shadow-lg">
                        <Pause size={20} fill="black" />
                      </button>
                    ) : (
                      <button onClick={() => currentLO && startPlayback(currentLO)} className="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center text-black hover:bg-yellow-600 transition-colors shadow-lg">
                        <Play size={20} fill="black" className="ml-0.5" />
                      </button>
                    )}
                    <button onClick={completeLO} className="h-10 px-4 bg-green-500 text-white font-bold text-sm rounded-xl hover:bg-green-600 transition-colors flex items-center gap-2">
                      <CheckCircle size={16} /> Completa
                    </button>
                    {allLOs.findIndex((a) => a.lo.id === currentLO.id) < allLOs.length - 1 && (
                      <button onClick={completeLO} className="h-10 px-4 bg-gray-700 text-white font-semibold text-sm rounded-xl hover:bg-gray-600 transition-colors flex items-center gap-2">
                        <SkipForward size={16} /> Prossimo
                      </button>
                    )}
                  </div>
                </div>
              </>
            ) : (
              <div className="text-center">
                <div className="w-24 h-24 rounded-3xl bg-gradient-to-br from-yellow-400/20 to-yellow-600/20 flex items-center justify-center mx-auto mb-4">
                  <Play size={40} className="text-yellow-500 ml-1" />
                </div>
                <p className="text-gray-300 font-semibold text-lg">
                  {totalCount > 0 ? "Seleziona un contenuto dalla lista" : "Corso senza contenuti strutturati"}
                </p>
                <p className="text-gray-500 text-sm mt-2">
                  {totalCount > 0 ? "Clicca su un argomento nel pannello laterale per iniziare" : "Questo corso non ha ancora moduli e lezioni configurati"}
                </p>
              </div>
            )}
          </div>

          {/* Course info (when no content / no structure) */}
          {totalCount === 0 && (
            <div className="flex-1 p-6">
              <div className="bg-gray-900/60 rounded-2xl border border-white/5 p-6 max-w-2xl mx-auto">
                <h2 className="text-white font-bold text-xl mb-3">{enrollment?.courseTitle}</h2>
                {enrollment?.courseDescription && <p className="text-gray-400 text-sm mb-4">{enrollment.courseDescription}</p>}
                <div className="flex items-center gap-4">
                  {enrollment?.courseHours && (
                    <div className="flex items-center gap-2 text-gray-500 text-sm">
                      <Clock size={14} /> {enrollment.courseHours} ore
                    </div>
                  )}
                  <div className="flex items-center gap-2 text-sm">
                    <span className={`text-[11px] font-bold px-2 py-1 rounded-lg ${enrollment?.status === "completed" ? "bg-green-500/20 text-green-400" : "bg-yellow-500/20 text-yellow-400"}`}>
                      {enrollment?.status === "completed" ? "Completato" : "In Corso"}
                    </span>
                  </div>
                </div>
                <div className="mt-6 bg-blue-500/10 border border-blue-500/20 rounded-xl p-4">
                  <p className="text-blue-300 text-sm">
                    Il contenuto del corso verrà reso disponibile a breve. Contatta il tuo ente formativo per informazioni.
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>

        {/* Sidebar - Course structure */}
        {totalCount > 0 && (
          <aside className="w-full lg:w-80 bg-gray-900/60 border-t lg:border-t-0 lg:border-l border-white/5 overflow-y-auto">
            <div className="p-4 border-b border-white/5">
              <h2 className="text-sm font-bold text-gray-300">Contenuti del Corso</h2>
              <p className="text-xs text-gray-500 mt-1">{completedCount} di {totalCount} completati</p>
            </div>

            {structure?.modules.map((mod) => (
              <div key={mod.id}>
                <button onClick={() => toggleModule(mod.id)}
                  className="w-full flex items-center gap-2 px-4 py-3 hover:bg-white/5 transition-colors text-left border-b border-white/5">
                  {expandedModules.has(mod.id) ? <ChevronDown size={14} className="text-gray-500" /> : <ChevronRight size={14} className="text-gray-500" />}
                  <span className="text-sm font-semibold text-gray-300 truncate">{mod.title}</span>
                </button>

                {expandedModules.has(mod.id) && mod.lessons.map((lesson) => (
                  <div key={lesson.id}>
                    {lesson.learningObjects.length > 0 && (
                      <div className="pl-8 pr-2 py-1">
                        <span className="text-[10px] text-gray-600 uppercase font-semibold tracking-wide">{lesson.title}</span>
                      </div>
                    )}
                    {lesson.learningObjects.map((lo) => {
                      const done = isLoCompleted(lo.id);
                      const isCurrent = currentLO?.id === lo.id;
                      return (
                        <button key={lo.id} onClick={() => { pausePlayback(); startPlayback(lo); }}
                          className={`w-full flex items-center gap-3 pl-10 pr-4 py-2.5 text-left transition-all ${isCurrent ? "bg-yellow-500/10 border-r-2 border-yellow-500" : "hover:bg-white/5"}`}>
                          {done ? (
                            <CheckCircle size={14} className="text-green-500 flex-shrink-0" />
                          ) : isCurrent && playing ? (
                            <div className="w-3.5 h-3.5 rounded-full bg-yellow-500 animate-pulse flex-shrink-0" />
                          ) : (
                            <Circle size={14} className="text-gray-600 flex-shrink-0" />
                          )}
                          <div className="min-w-0">
                            <p className={`text-xs truncate ${isCurrent ? "text-yellow-400 font-semibold" : done ? "text-gray-500" : "text-gray-400"}`}>{lo.title}</p>
                            {lo.duration && <p className="text-[10px] text-gray-600">{lo.duration} min</p>}
                          </div>
                        </button>
                      );
                    })}
                  </div>
                ))}
              </div>
            ))}
          </aside>
        )}
      </div>
    </div>
  );
}
