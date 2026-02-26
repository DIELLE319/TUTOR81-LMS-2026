import { useState, useEffect, useRef, useCallback } from "react";
import { useLocation, useRoute } from "wouter";
import {
  Play, Pause, CheckCircle, Circle, Clock,
  ChevronDown, ChevronRight, LogOut, MessageCircle, HelpCircle, User,
  Calendar, AlertTriangle, Check, X, SkipForward,
} from "lucide-react";

interface LearningObject {
  id: number; title: string; objectType: string;
  jwplayerCode: string | null; videoFilename: string | null;
  slideFilename: string | null; documentFilename: string | null; webFilename: string | null;
  duration: number | null; questions?: any[];
}
interface Lesson { id: number; title: string; learningObjects: LearningObject[]; }
interface Module { id: number; title: string; lessons: Lesson[]; }
interface CourseStructure {
  course: { id: number; title: string; description: string | null; hours: number | null; percentageToPass: number | null };
  modules: Module[];
}
interface EnrollmentData {
  id: number; courseId: number; courseTitle: string; courseDescription: string | null;
  courseHours: number | null; progress: number | null; status: string;
  startDate?: string; endDate?: string;
}
interface LOProgress { learningObjectId: number; watchedSeconds: number; completed: boolean; }

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
  const [quizOverlay, setQuizOverlay] = useState<any>(null);
  const [quizAnswer, setQuizAnswer] = useState<number | null>(null);
  const [quizResult, setQuizResult] = useState<"correct" | "wrong" | null>(null);
  const [correctCount, setCorrectCount] = useState(0);
  const [wrongCount, setWrongCount] = useState(0);
  const [playerUser, setPlayerUser] = useState<any>(null);
  const [videoUrl, setVideoUrl] = useState<string | null>(null);
  const [quizCountdown, setQuizCountdown] = useState(0);
  const [usedQuestionIds, setUsedQuestionIds] = useState<Set<number>>(new Set());
  const timerRef = useRef<any>(null);
  const saveRef = useRef<any>(null);
  const quizTimerRef = useRef<any>(null);
  const watchedSecRef = useRef(0);
  const currentLORef = useRef<LearningObject | null>(null);
  const videoRef = useRef<HTMLVideoElement | null>(null);

  // Attention-check questions for LOs without CMS questions
  const ATTENTION_QUESTIONS = [
    { id: -1, question_text: "Stai seguendo attentamente questa lezione?", answers: [{ id: -1, answer_text: "Sì", is_correct: true }, { id: -2, answer_text: "No", is_correct: false }] },
    { id: -2, question_text: "Confermi la tua presenza?", answers: [{ id: -3, answer_text: "Confermo", is_correct: true }, { id: -4, answer_text: "Non confermo", is_correct: false }] },
    { id: -3, question_text: "Vedi correttamente questa domanda?", answers: [{ id: -5, answer_text: "Sì", is_correct: true }, { id: -6, answer_text: "No", is_correct: false }] },
    { id: -4, question_text: "Il corso sta procedendo regolarmente?", answers: [{ id: -7, answer_text: "Sì, procede regolarmente", is_correct: true }, { id: -8, answer_text: "No", is_correct: false }] },
    { id: -5, question_text: "Stai ancora seguendo il corso?", answers: [{ id: -9, answer_text: "Sì, sto seguendo", is_correct: true }, { id: -10, answer_text: "No", is_correct: false }] },
    { id: -6, question_text: "Confermi di essere presente davanti allo schermo?", answers: [{ id: -11, answer_text: "Confermo", is_correct: true }, { id: -12, answer_text: "Non confermo", is_correct: false }] },
    { id: -7, question_text: "Stai prestando attenzione ai contenuti del corso?", answers: [{ id: -13, answer_text: "Sì", is_correct: true }, { id: -14, answer_text: "No", is_correct: false }] },
    { id: -8, question_text: "La lezione è visibile correttamente?", answers: [{ id: -15, answer_text: "Sì, tutto ok", is_correct: true }, { id: -16, answer_text: "No", is_correct: false }] },
    { id: -9, question_text: "Sei ancora collegato al corso?", answers: [{ id: -17, answer_text: "Sì, sono collegato", is_correct: true }, { id: -18, answer_text: "No", is_correct: false }] },
    { id: -10, question_text: "Confermi che stai visualizzando il materiale didattico?", answers: [{ id: -19, answer_text: "Confermo", is_correct: true }, { id: -20, answer_text: "Non confermo", is_correct: false }] },
    { id: -11, question_text: "La formazione sta procedendo senza problemi?", answers: [{ id: -21, answer_text: "Sì, senza problemi", is_correct: true }, { id: -22, answer_text: "No", is_correct: false }] },
    { id: -12, question_text: "Stai partecipando attivamente alla lezione?", answers: [{ id: -23, answer_text: "Sì", is_correct: true }, { id: -24, answer_text: "No", is_correct: false }] },
    { id: -13, question_text: "Il contenuto audio/video è fruibile?", answers: [{ id: -25, answer_text: "Sì, è fruibile", is_correct: true }, { id: -26, answer_text: "No", is_correct: false }] },
    { id: -14, question_text: "Confermi la tua partecipazione a questa unità didattica?", answers: [{ id: -27, answer_text: "Confermo", is_correct: true }, { id: -28, answer_text: "Non confermo", is_correct: false }] },
    { id: -15, question_text: "Stai completando questa lezione in prima persona?", answers: [{ id: -29, answer_text: "Sì", is_correct: true }, { id: -30, answer_text: "No", is_correct: false }] },
    { id: -16, question_text: "Il corso è in corso di svolgimento?", answers: [{ id: -31, answer_text: "Sì, è in corso", is_correct: true }, { id: -32, answer_text: "No", is_correct: false }] },
    { id: -17, question_text: "Sei presente e attento durante la lezione?", answers: [{ id: -33, answer_text: "Sì, sono presente", is_correct: true }, { id: -34, answer_text: "No", is_correct: false }] },
    { id: -18, question_text: "Confermi che il corso procede correttamente?", answers: [{ id: -35, answer_text: "Confermo", is_correct: true }, { id: -36, answer_text: "Non confermo", is_correct: false }] },
    { id: -19, question_text: "Stai seguendo questa unità formativa?", answers: [{ id: -37, answer_text: "Sì, sto seguendo", is_correct: true }, { id: -38, answer_text: "No", is_correct: false }] },
    { id: -20, question_text: "Riesci a vedere e sentire il contenuto?", answers: [{ id: -39, answer_text: "Sì", is_correct: true }, { id: -40, answer_text: "No", is_correct: false }] },
  ];

  // Flatten all LOs
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
  const currentIdx = currentLO ? allLOs.findIndex(a => a.lo.id === currentLO.id) : -1;
  const currentInfo = currentIdx >= 0 ? allLOs[currentIdx] : null;

  // Total course duration in seconds
  const totalCourseSec = allLOs.reduce((sum, a) => sum + (a.lo.duration || 2) * 60, 0);
  const totalWatchedSec = loProgress.reduce((sum, p) => sum + (p.watchedSeconds || 0), 0) + (currentLO && !isLoCompleted(currentLO.id) ? watchedSec : 0);
  const remainingSec = Math.max(0, totalCourseSec - totalWatchedSec);

  useEffect(() => {
    try { const s = sessionStorage.getItem("playerSession"); if (s) setPlayerUser(JSON.parse(s)); } catch {}
  }, []);

  // Load enrollment + structure + progress
  useEffect(() => {
    if (!enrollmentId) { navigate("/player/dashboard"); return; }
    const session = sessionStorage.getItem("playerSession");
    if (!session) { navigate("/player-login"); return; }

    const load = async () => {
      try {
        const [enrRes, progRes] = await Promise.all([
          fetch(`/api/player/enrollment/${enrollmentId}`).then(r => r.json()),
          fetch(`/api/player/lo-progress/${enrollmentId}`).then(r => r.json()),
        ]);
        setEnrollment(enrRes);
        setLoProgress(Array.isArray(progRes) ? progRes : []);
        const strRes = await fetch(`/api/player/course/${enrRes.courseId}/structure`).then(r => r.json());
        setStructure(strRes);
        const parsed = JSON.parse(session);
        const sesRes = await fetch("/api/player/session/start", {
          method: "POST", headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ enrollmentId, studentId: parsed.user?.id || 0, courseId: enrRes.courseId }),
        }).then(r => r.json());
        if (sesRes.sessionId) setSessionId(sesRes.sessionId);
        if (strRes.modules?.length > 0) setExpandedModules(new Set(strRes.modules.map((m: any) => m.id)));
      } catch (e) { console.error("Load error:", e); }
      setLoading(false);
    };
    load();
    return () => { if (timerRef.current) clearInterval(timerRef.current); };
  }, [enrollmentId, navigate]);

  // Auto-start first non-completed LO
  useEffect(() => {
    if (structure && allLOs.length > 0 && !currentLO) {
      const first = allLOs.find(a => !loProgress.some(p => p.learningObjectId === a.lo.id && p.completed));
      startPlayback((first || allLOs[0]).lo);
    }
  }, [structure]); // eslint-disable-line

  useEffect(() => { return () => { if (sessionId) navigator.sendBeacon("/api/player/session/end", JSON.stringify({ sessionId })); }; }, [sessionId]);

  const saveProgress = useCallback(async (loId: number, seconds: number, completed: boolean) => {
    try {
      await fetch("/api/player/save-progress", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ enrollmentId, learningObjectId: loId, watchedSeconds: seconds, completed }),
      });
      if (completed) {
        setLoProgress(prev => {
          const ex = prev.find(p => p.learningObjectId === loId);
          if (ex) return prev.map(p => p.learningObjectId === loId ? { ...p, completed: true, watchedSeconds: seconds } : p);
          return [...prev, { learningObjectId: loId, watchedSeconds: seconds, completed: true }];
        });
        fetch(`/api/player/recalc-progress/${enrollmentId}`, { method: "POST" });
      }
    } catch (e) { console.error("Save progress error:", e); }
  }, [enrollmentId]);

  const getNextQuestion = (lo: LearningObject): any => {
    // Try CMS questions first
    if (lo.questions && lo.questions.length > 0) {
      const unused = lo.questions.filter((q: any) => !usedQuestionIds.has(q.id));
      if (unused.length > 0) return unused[Math.floor(Math.random() * unused.length)];
      // All used, reset and pick random
      return lo.questions[Math.floor(Math.random() * lo.questions.length)];
    }
    // Fallback to attention-check questions
    const unused = ATTENTION_QUESTIONS.filter(q => !usedQuestionIds.has(q.id));
    if (unused.length > 0) return unused[Math.floor(Math.random() * unused.length)];
    return ATTENTION_QUESTIONS[Math.floor(Math.random() * ATTENTION_QUESTIONS.length)];
  };

  const clearAllTimers = () => {
    if (timerRef.current) { clearInterval(timerRef.current); timerRef.current = null; }
    if (saveRef.current) { clearInterval(saveRef.current); saveRef.current = null; }
    if (quizTimerRef.current) { clearInterval(quizTimerRef.current); quizTimerRef.current = null; }
  };

  const playDing = () => {
    try {
      const ctx = new AudioContext();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.setValueAtTime(1320, ctx.currentTime + 0.1);
      gain.gain.setValueAtTime(0.8, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.6);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.6);
    } catch {}
  };

  const showQuizQuestion = (lo: LearningObject, isEnd: boolean) => {
    clearAllTimers();
    setPlaying(false);
    if (videoRef.current) try { videoRef.current.pause(); } catch {}
    const question = getNextQuestion(lo);
    if (question) {
      playDing();
      setUsedQuestionIds(prev => { const n = new Set(Array.from(prev)); n.add(question.id); return n; });
      setQuizOverlay({ ...question, _isEnd: isEnd });
      setQuizCountdown(30);
    } else if (isEnd) {
      advanceToNextLO(lo);
    } else {
      resumePlayback(lo);
    }
  };

  const advanceToNextLO = (lo: LearningObject) => {
    const idx = allLOs.findIndex(a => a.lo.id === lo.id);
    if (idx >= 0 && idx < allLOs.length - 1) {
      setTimeout(() => startPlayback(allLOs[idx + 1].lo), 800);
    }
  };

  const resumePlayback = (lo: LearningObject) => {
    setQuizOverlay(null); setQuizAnswer(null); setQuizResult(null);
    setPlaying(true);
    if (videoRef.current) try { videoRef.current.play(); } catch {}
    currentLORef.current = lo;
    const durationSec = (lo.duration || 2) * 60;

    timerRef.current = setInterval(() => {
      watchedSecRef.current += 1;
      const sec = watchedSecRef.current;
      setWatchedSec(sec);

      if (sec >= durationSec) {
        clearAllTimers();
        setPlaying(false);
        saveProgress(lo.id, durationSec, true);
        showQuizQuestion(lo, true);
        return;
      }
    }, 1000);

    saveRef.current = setInterval(() => {
      saveProgress(lo.id, watchedSecRef.current, false);
    }, 15000);
  };

  const fetchVideoUrl = async (jwCode: string) => {
    try {
      const res = await fetch(`https://cdn.jwplayer.com/v2/media/${jwCode}`);
      const data = await res.json();
      const sources = data?.playlist?.[0]?.sources || [];
      const mp4 = sources.find((s: any) => s.type === "video/mp4" && s.width >= 720) || sources.find((s: any) => s.type === "video/mp4");
      if (mp4?.file) { setVideoUrl(mp4.file); return; }
      const hls = sources.find((s: any) => s.file?.endsWith(".m3u8"));
      if (hls?.file) { setVideoUrl(hls.file); return; }
    } catch (e) { console.error("Fetch video URL error:", e); }
    setVideoUrl(null);
  };

  const startPlayback = (lo: LearningObject) => {
    // Save old LO progress and clear timers BEFORE switching
    if (currentLORef.current && currentLORef.current.id !== lo.id) {
      saveProgress(currentLORef.current.id, watchedSecRef.current, false);
    }
    clearAllTimers();
    setCurrentLO(lo);
    currentLORef.current = lo;
    setQuizOverlay(null);
    setQuizAnswer(null);
    setQuizResult(null);
    if (lo.jwplayerCode) fetchVideoUrl(lo.jwplayerCode);
    else setVideoUrl(null);
    const prevSec = loProgress.find(p => p.learningObjectId === lo.id)?.watchedSeconds || 0;
    setWatchedSec(prevSec);
    watchedSecRef.current = prevSec;
    setPlaying(true);
    const durationSec = (lo.duration || 2) * 60;

    timerRef.current = setInterval(() => {
      watchedSecRef.current += 1;
      const sec = watchedSecRef.current;
      setWatchedSec(sec);

      if (sec >= durationSec) {
        clearAllTimers();
        setPlaying(false);
        saveProgress(lo.id, durationSec, true);
        showQuizQuestion(lo, true);
        return;
      }
    }, 1000);

    saveRef.current = setInterval(() => {
      saveProgress(lo.id, watchedSecRef.current, false);
    }, 15000);
  };

  const pausePlayback = () => {
    setPlaying(false);
    clearAllTimers();
    if (currentLORef.current) saveProgress(currentLORef.current.id, watchedSecRef.current, false);
  };

  const handleQuizAnswer = async (answerId: number, isCorrect: boolean) => {
    setQuizAnswer(answerId);
    if (isCorrect) { setQuizResult("correct"); setCorrectCount(c => c + 1); }
    else { setQuizResult("wrong"); setWrongCount(c => c + 1); }
    // Save quiz answer to backend
    try {
      const parsed = playerUser;
      await fetch("/api/player/quiz/answer", {
        method: "POST", headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          enrollmentId, studentId: parsed?.user?.id || 0,
          questionId: quizOverlay?.id > 0 ? quizOverlay.id : null,
          answerId: answerId > 0 ? answerId : null,
          isCorrect, timedOut: false,
          responseTimeSeconds: 30 - quizCountdown,
          learningObjectId: currentLORef.current?.id,
          sessionLogId: sessionId,
        }),
      });
    } catch (e) { console.error("Save quiz answer error:", e); }
  };

  // Quiz countdown timer (30 seconds to answer)
  useEffect(() => {
    if (!quizOverlay || quizAnswer !== null) return;
    const interval = setInterval(() => {
      setQuizCountdown(prev => {
        if (prev <= 1) {
          clearInterval(interval);
          // Time's up - count as wrong
          setQuizResult("wrong");
          setWrongCount(c => c + 1);
          return 0;
        }
        return prev - 1;
      });
    }, 1000);
    return () => clearInterval(interval);
  }, [quizOverlay, quizAnswer]);

  const continueAfterQuiz = () => {
    const isEnd = quizOverlay?._isEnd;
    setQuizOverlay(null); setQuizAnswer(null); setQuizResult(null);
    const lo = currentLORef.current;
    if (!lo) return;
    if (isEnd) {
      advanceToNextLO(lo);
    } else {
      resumePlayback(lo);
    }
  };

  const toggleModule = (id: number) => {
    setExpandedModules(prev => { const n = new Set(prev); n.has(id) ? n.delete(id) : n.add(id); return n; });
  };

  const fmt = (s: number) => { const m = Math.floor(s / 60); return `${String(m).padStart(2, "0")}:${String(s % 60).padStart(2, "0")}`; };
  const fmtCountdown = (s: number) => {
    const h = Math.floor(s / 3600); const m = Math.floor((s % 3600) / 60); const sec = s % 60;
    if (h > 0) return `${h}h ${String(m).padStart(2, "0")}m ${String(sec).padStart(2, "0")}s`;
    return `${String(m).padStart(2, "0")}:${String(sec).padStart(2, "0")}`;
  };

  const currentDurationSec = currentLO ? (currentLO.duration || 2) * 60 : 0;
  const progressPct = currentDurationSec > 0 ? Math.min(100, (watchedSec / currentDurationSec) * 100) : 0;
  const countdownSec = Math.max(0, currentDurationSec - watchedSec);

  if (loading) return (
    <div className="min-h-screen bg-[#030712] flex items-center justify-center">
      <div className="animate-spin w-8 h-8 border-4 border-yellow-500 border-t-transparent rounded-full" />
    </div>
  );

  // 1 question per LO (at completion)
  const totalQuestions = allLOs.length;

  return (
    <div className="h-screen bg-[#030712] flex flex-col overflow-hidden">
      {/* TOP BAR */}
      <header className="bg-[#0a0f1a] border-b border-yellow-500/30 flex items-center h-10 px-3 shrink-0 z-20">
        <div className="flex items-center gap-2 flex-1 min-w-0">
          <span className="text-yellow-500 font-black text-sm tracking-tight">tutor81</span>
          <span className="text-[11px] text-white font-bold truncate ml-2 uppercase">{enrollment?.courseTitle}</span>
        </div>
        <div className="flex items-center gap-3 text-[11px] shrink-0">
          <div className="flex items-center gap-2 bg-[#111827] border border-white/10 rounded-lg px-3 py-1">
            <span className="text-yellow-500 font-bold text-[10px] uppercase">Verifica Apprendimento</span>
            <span className="text-gray-500 text-[10px]">Domande previste: {totalQuestions}</span>
            <span className="bg-green-600 text-white font-bold px-2.5 py-0.5 rounded text-[11px] min-w-[28px] text-center">{correctCount} &#10003;</span>
            <span className="bg-red-600 text-white font-bold px-2.5 py-0.5 rounded text-[11px] min-w-[28px] text-center">{wrongCount} &#10007;</span>
          </div>
          <span className="text-gray-500 ml-2">{playerUser?.user?.firstName} {playerUser?.user?.lastName}</span>
          <button onClick={() => { pausePlayback(); navigate("/player/dashboard"); }}
            className="text-gray-500 hover:text-red-400 flex items-center gap-1 ml-2">
            <LogOut size={12} /> <span>Esci</span>
          </button>
        </div>
      </header>

      {/* BREADCRUMB */}
      {currentInfo && (
        <div className="bg-[#0d1320] border-b border-white/5 px-3 h-7 flex items-center text-[11px] text-gray-500 shrink-0">
          <span className="text-gray-600">&lt;</span>
          <span className="ml-2">{currentInfo.lessonTitle}</span>
          <span className="mx-2 text-gray-700">&gt;</span>
          <span className="text-yellow-400 font-semibold truncate">{currentLO?.title}</span>
          <span className="ml-auto text-gray-600">{fmt(watchedSec)} / {fmt(currentDurationSec)}</span>
        </div>
      )}

      <div className="flex-1 flex overflow-hidden">
        {/* LEFT SIDEBAR */}
        <div className="w-52 shrink-0 bg-[#0a0f1a] border-r border-white/5 flex flex-col overflow-hidden">
          {/* User info */}
          <div className="p-3 border-b border-white/10 space-y-1.5">
            <button onClick={() => { pausePlayback(); navigate("/player/dashboard"); }}
              className="bg-yellow-500 text-black font-bold text-[11px] px-3 py-1 rounded w-full hover:bg-yellow-600">ESCI</button>
            <div className="flex items-center gap-1.5 text-[10px] text-gray-400">
              <User size={10} /> <span>{playerUser?.user?.firstName} {playerUser?.user?.lastName}</span>
            </div>
            <div className="flex items-center gap-1.5 text-[10px] text-gray-500">
              <Calendar size={10} /> <span>{new Date().toLocaleDateString("it-IT")}</span>
            </div>
            {enrollment?.endDate && (
              <div className="flex items-center gap-1.5 text-[10px] text-gray-500">
                <Clock size={10} /> <span>Scadenza: {new Date(enrollment.endDate).toLocaleDateString("it-IT")}</span>
              </div>
            )}
          </div>

          {/* PROGRAMMA */}
          <div className="px-3 py-2 border-b border-white/10 flex items-center justify-between">
            <span className="text-[11px] font-bold text-yellow-500 uppercase">Programma</span>
            <span className="text-[10px] text-gray-500">{currentIdx + 1}/{totalCount}</span>
          </div>

          {/* Course structure list */}
          <div className="flex-1 overflow-y-auto">
            {structure?.modules.map(mod => (
              <div key={mod.id}>
                <div className="w-full flex items-center gap-1.5 px-3 py-2 border-b border-white/5">
                  <ChevronDown size={10} className="text-gray-600" />
                  <span className="text-[10px] font-bold text-gray-400 uppercase truncate">{mod.title}</span>
                </div>
                {mod.lessons.map(lesson => (
                  <div key={lesson.id}>
                    {lesson.learningObjects.map(lo => {
                      const done = isLoCompleted(lo.id);
                      const isCurrent = currentLO?.id === lo.id;
                      const loIdx = allLOs.findIndex(a => a.lo.id === lo.id);
                      const canNavigate = done || isCurrent || loIdx < currentIdx;
                      return (
                        <button key={lo.id}
                          onClick={() => { if (canNavigate) { pausePlayback(); startPlayback(lo); } }}
                          className={`w-full flex items-center gap-2 px-3 py-2 text-left transition-all ${
                            isCurrent ? "bg-yellow-500/25 border-l-3 border-yellow-500"
                            : done ? "hover:bg-white/5 border-l-2 border-green-500/50 cursor-pointer"
                            : "border-l-2 border-transparent opacity-50 cursor-not-allowed"
                          }`}>
                          {done ? <CheckCircle size={12} className="text-green-500 shrink-0" />
                            : isCurrent && playing ? <div className="w-3 h-3 rounded-full bg-yellow-500 animate-pulse shrink-0" />
                            : <Circle size={12} className={`shrink-0 ${canNavigate ? "text-gray-500" : "text-gray-700"}`} />}
                          <span className={`text-[11px] truncate ${
                            isCurrent ? "text-yellow-300 font-bold"
                            : done ? "text-green-500"
                            : canNavigate ? "text-white/80" : "text-gray-600"
                          }`}>
                            {lo.title}
                          </span>
                        </button>
                      );
                    })}
                  </div>
                ))}
              </div>
            ))}
          </div>

          {/* Bottom links */}
          <div className="border-t border-white/10 p-2 space-y-1">
            <div className="flex items-center gap-2 text-[10px] text-gray-500 px-1"><MessageCircle size={10} /> Chat</div>
            <div className="flex items-center gap-2 text-[10px] text-gray-500 px-1"><HelpCircle size={10} /> Assistenza</div>
          </div>
        </div>

        {/* MAIN CONTENT */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {/* Countdown badge */}
          {playing && (
            <div className="absolute top-24 right-4 z-10 bg-[#0a0f1a]/90 border border-yellow-500/30 rounded-lg px-3 py-1.5">
              <span className="text-[10px] text-gray-500">FINE CORSO TRA</span>
              <span className="text-sm font-mono font-bold text-yellow-500 ml-2">{fmtCountdown(remainingSec)}</span>
            </div>
          )}

          {/* VIDEO AREA */}
          <div className="flex-1 bg-black relative flex items-center justify-center">
            {currentLO && videoUrl ? (
              <video
                ref={videoRef}
                key={currentLO.id}
                src={videoUrl}
                className="w-full h-full"
                autoPlay
                playsInline
                onPause={() => { /* controlled by our timer */ }}
              />
            ) : currentLO && (currentLO.objectType === 'slide' || currentLO.objectType === 'document') && (currentLO.slideFilename || currentLO.documentFilename) ? (
              <div className="w-full h-full relative flex flex-col">
                {countdownSec > 0 && (
                  <div className="bg-red-900/95 border-b border-red-500/50 px-4 py-2 flex items-center justify-center gap-4 shrink-0 z-10">
                    <AlertTriangle size={18} className="text-red-400" />
                    <span className="text-red-200 font-bold text-sm uppercase">Lettura obbligatoria — non puoi proseguire</span>
                    <div className="flex items-center gap-2 bg-black/40 rounded-lg px-4 py-1">
                      <Clock size={16} className="text-yellow-500" />
                      <span className="text-2xl font-mono font-bold text-yellow-500">{fmt(countdownSec)}</span>
                    </div>
                  </div>
                )}
                {countdownSec <= 0 && playing && (
                  <div className="bg-green-900/95 border-b border-green-500/50 px-4 py-2 flex items-center justify-center gap-3 shrink-0 z-10">
                    <CheckCircle size={18} className="text-green-400" />
                    <span className="text-green-300 font-bold text-sm">Lettura completata — verifica in corso...</span>
                  </div>
                )}
                <iframe
                  key={currentLO.id}
                  src={`/files/${currentLO.slideFilename || currentLO.documentFilename}`}
                  className="flex-1 w-full border-0"
                  title={currentLO.title}
                />
              </div>
            ) : currentLO && currentLO.objectType === 'web' ? (
              <div className="w-full h-full relative flex flex-col">
                {countdownSec > 0 && (
                  <div className="bg-red-900/95 border-b border-red-500/50 px-4 py-2 flex items-center justify-center gap-4 shrink-0 z-10">
                    <AlertTriangle size={18} className="text-red-400" />
                    <span className="text-red-200 font-bold text-sm uppercase">Lettura obbligatoria — non puoi proseguire</span>
                    <div className="flex items-center gap-2 bg-black/40 rounded-lg px-4 py-1">
                      <Clock size={16} className="text-yellow-500" />
                      <span className="text-2xl font-mono font-bold text-yellow-500">{fmt(countdownSec)}</span>
                    </div>
                  </div>
                )}
                {countdownSec <= 0 && playing && (
                  <div className="bg-green-900/95 border-b border-green-500/50 px-4 py-2 flex items-center justify-center gap-3 shrink-0 z-10">
                    <CheckCircle size={18} className="text-green-400" />
                    <span className="text-green-300 font-bold text-sm">Lettura completata — verifica in corso...</span>
                  </div>
                )}
                {currentLO.webFilename ? (
                  <iframe
                    key={currentLO.id}
                    src={`/web/${currentLO.webFilename}/index.html`}
                    className="flex-1 w-full border-0"
                    title={currentLO.title}
                  />
                ) : (
                  <div className="flex-1 flex items-center justify-center">
                    <div className="text-center">
                      <p className="text-white font-bold text-lg">{currentLO.title}</p>
                      <p className="text-gray-500 text-sm mt-2">Contenuto web in caricamento...</p>
                    </div>
                  </div>
                )}
              </div>
            ) : currentLO ? (
              <div className="text-center">
                <Play size={48} className="text-yellow-500 mx-auto mb-4" />
                <p className="text-white font-bold text-lg">{currentLO.title}</p>
                <p className="text-gray-500 text-sm capitalize">{currentLO.objectType}</p>
              </div>
            ) : (
              <div className="text-center">
                <Play size={48} className="text-yellow-500/30 mx-auto mb-4" />
                <p className="text-gray-500">{totalCount > 0 ? "Seleziona un contenuto" : "Corso senza contenuti"}</p>
              </div>
            )}

            {/* Quiz overlay */}
            {quizOverlay && (
              <div className="absolute inset-0 bg-[#0a0f1a]/95 z-30 flex items-center justify-center">
                <div className="max-w-lg w-full mx-4">
                  <div className="bg-yellow-500/20 border-b border-yellow-500/40 rounded-t-xl px-5 py-2 flex items-center justify-between">
                    <h3 className="text-yellow-400 font-bold text-sm">Verifica Apprendimento</h3>
                    {!quizResult && <span className={`font-mono font-bold text-sm ${quizCountdown <= 10 ? "text-red-400 animate-pulse" : "text-yellow-400"}`}>{quizCountdown}s</span>}
                  </div>
                  <div className="bg-[#111827] rounded-b-xl p-6 border border-white/10 border-t-0">
                    {quizResult ? (
                      <div className="text-center py-6">
                        {quizResult === "correct" ? (
                          <>
                            <div className="w-16 h-16 rounded-full border-2 border-green-500 flex items-center justify-center mx-auto mb-4">
                              <Check size={32} className="text-green-500" />
                            </div>
                            <p className="text-green-400 font-bold text-lg">Risposta Corretta!</p>
                          </>
                        ) : (
                          <>
                            <div className="w-16 h-16 rounded-full border-2 border-red-500 flex items-center justify-center mx-auto mb-4">
                              <X size={32} className="text-red-500" />
                            </div>
                            <p className="text-red-400 font-bold text-lg">Risposta Sbagliata</p>
                          </>
                        )}
                        <button onClick={continueAfterQuiz}
                          className="mt-6 bg-gray-700 hover:bg-gray-600 text-white font-bold px-6 py-2 rounded-lg text-sm flex items-center gap-2 mx-auto">
                          <SkipForward size={14} /> Continua
                        </button>
                      </div>
                    ) : (
                      <>
                        <p className="text-gray-300 text-sm mb-5">{quizOverlay.question_text}</p>
                        <div className="space-y-2">
                          {quizOverlay.answers?.map((a: any) => (
                            <button key={a.id} onClick={() => handleQuizAnswer(a.id, a.is_correct)}
                              disabled={quizAnswer !== null}
                              className={`w-full text-left px-4 py-3 rounded-lg border text-sm transition-all ${
                                quizAnswer === a.id
                                  ? a.is_correct ? "border-green-500 bg-green-500/10 text-green-400" : "border-red-500 bg-red-500/10 text-red-400"
                                  : "border-white/10 hover:border-yellow-500/50 text-gray-400 hover:text-white"
                              }`}>
                              <div className="flex items-center gap-3">
                                <div className={`w-5 h-5 rounded-full border flex items-center justify-center shrink-0 ${
                                  quizAnswer === a.id
                                    ? a.is_correct ? "border-green-500 bg-green-500" : "border-red-500 bg-red-500"
                                    : "border-gray-600"
                                }`}>
                                  {quizAnswer === a.id && (a.is_correct ? <Check size={10} className="text-white" /> : <X size={10} className="text-white" />)}
                                </div>
                                {a.answer_text}
                              </div>
                            </button>
                          ))}
                        </div>
                      </>
                    )}
                  </div>
                </div>
              </div>
            )}
          </div>

          {/* BOTTOM CONTROL BAR */}
          <div className="bg-[#0a0f1a] border-t border-white/10 px-4 py-2 shrink-0">
            <div className="flex items-center gap-3">
              {/* Play/Pause */}
              <button onClick={() => {
                if (!currentLO) return;
                playing ? pausePlayback() : startPlayback(currentLO);
              }} className="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-black hover:bg-yellow-600 shrink-0">
                {playing ? <Pause size={16} fill="black" /> : <Play size={16} fill="black" className="ml-0.5" />}
              </button>

              {/* Progress bar */}
              <div className="flex-1 mx-2">
                <div className="w-full h-2 bg-gray-800 rounded-full overflow-hidden cursor-pointer">
                  <div className="h-full bg-yellow-500 rounded-full transition-all duration-300" style={{ width: `${progressPct}%` }} />
                </div>
              </div>

              {/* Countdown */}
              <span className="text-red-500 font-mono text-sm font-bold shrink-0">
                -{fmt(countdownSec)}
              </span>

              {/* LO counter */}
              <span className="text-gray-500 text-[11px] shrink-0 ml-2">
                LO {currentIdx + 1} / {totalCount}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
