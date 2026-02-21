import { useState } from "react";
import { useLocation } from "wouter";

interface Question {
  id: string;
  label: string;
  type: "select";
  options: { value: string; label: string }[];
}

export default function PlayerLogin() {
  const [step, setStep] = useState(1);
  const [username, setUsername] = useState("");
  const [questions, setQuestions] = useState<Question[]>([]);
  const [answers, setAnswers] = useState<Record<string, string>>({});
  const [loading, setLoading] = useState(false);
  const [errore, setErrore] = useState("");
  const [, navigate] = useLocation();

  const verificaUsername = async () => {
    setErrore("");
    const val = username.trim().toLowerCase();
    if (!val || !val.includes(".")) {
      setErrore("Inserisci il nome utente nel formato nome.cognome");
      return;
    }
    setLoading(true);
    try {
      const r = await fetch("/api/player/check-username", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: val }),
      });
      const d = await r.json();
      if (r.ok && d.success && d.questions) {
        setQuestions(d.questions);
        setAnswers({});
        setStep(2);
      } else {
        setErrore(d.error || "Utente non trovato");
      }
    } catch {
      setErrore("Errore di connessione");
    }
    setLoading(false);
  };

  const verificaIdentita = async () => {
    setErrore("");
    const unanswered = questions.filter((q) => !answers[q.id]);
    if (unanswered.length > 0) {
      setErrore("Rispondi a tutte le domande");
      return;
    }
    setLoading(true);
    try {
      const answersArray = questions.map((q) => ({ questionId: q.id, value: answers[q.id] }));
      // First verify identity
      const r = await fetch("/api/player/verify-identity", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username: username.trim().toLowerCase(), answers: answersArray }),
      });
      const d = await r.json();
      if (!r.ok || !d.success) {
        setErrore(d.error || "Verifica fallita");
        setLoading(false);
        return;
      }
      // Store player session using student data from verify-identity
      const playerData = {
        success: true,
        user: d.student || { firstName: username.split(".")[0], lastName: username.split(".").slice(1).join(".") },
        enrollment: d.enrollment,
      };
      sessionStorage.setItem("playerSession", JSON.stringify(playerData));
      navigate("/player/dashboard");
    } catch {
      setErrore("Errore di connessione");
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-[#030712] flex items-center justify-center p-4 relative overflow-hidden">
      <div className="absolute top-[-50%] right-[-30%] w-[80vw] h-[80vw] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,0.08)_0%,transparent_70%)]" />
      <div className="absolute bottom-[-40%] left-[-20%] w-[60vw] h-[60vw] rounded-full bg-[radial-gradient(circle,rgba(251,191,36,0.04)_0%,transparent_70%)]" />

      <div className="relative z-10 w-full max-w-md">
        <div className="bg-gradient-to-b from-gray-800/80 to-gray-900/90 backdrop-blur-xl rounded-3xl shadow-2xl border border-white/5 overflow-hidden">

          <div className="text-center py-8 px-6">
            <div className="w-16 h-16 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-2xl font-black text-black shadow-lg shadow-yellow-500/30">T</div>
            <h1 className="text-white text-xl font-bold">Accedi al Corso</h1>
            <p className="text-gray-400 text-sm mt-1">
              {step === 1 ? "Inserisci il tuo nome utente" : "Verifica la tua identità"}
            </p>
            <div className="flex items-center justify-center gap-3 mt-4">
              <div className={`w-8 h-1 rounded-full ${step >= 1 ? "bg-yellow-500" : "bg-gray-700"}`} />
              <div className={`w-8 h-1 rounded-full ${step >= 2 ? "bg-yellow-500" : "bg-gray-700"}`} />
            </div>
          </div>

          <div className="px-6 pb-8">
            {errore && (
              <div className="bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-xl p-3 mb-4">
                {errore}
              </div>
            )}

            {step === 1 && (
              <div className="space-y-4">
                <div>
                  <label className="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Nome Utente</label>
                  <input type="text" value={username} onChange={(e) => setUsername(e.target.value)}
                    onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); verificaUsername(); } }}
                    placeholder="nome.cognome" autoFocus
                    className="w-full h-12 px-4 bg-gray-900/60 border-2 border-gray-700 rounded-xl text-white text-base placeholder-gray-500 focus:border-yellow-500 focus:outline-none transition-colors" />
                  <p className="text-xs text-gray-500 mt-2">Il nome utente ti è stato comunicato nel formato <b className="text-gray-400">nome.cognome</b></p>
                </div>
                <button type="button" onClick={verificaUsername} disabled={loading}
                  className="w-full h-12 bg-gradient-to-r from-yellow-400 to-yellow-600 hover:from-yellow-500 hover:to-yellow-700 text-black font-bold rounded-xl disabled:opacity-50 shadow-lg shadow-yellow-500/20 transition-all">
                  {loading ? "Verifica..." : "Prosegui"}
                </button>
              </div>
            )}

            {step === 2 && (
              <div className="space-y-4">
                <div className="flex items-center gap-2 mb-1">
                  <button type="button" onClick={() => { setStep(1); setQuestions([]); setAnswers({}); setErrore(""); }}
                    className="text-gray-400 hover:text-white text-sm transition-colors">← Indietro</button>
                  <span className="text-sm text-gray-500">Utente: <b className="text-yellow-500">{username}</b></span>
                </div>

                <div className="bg-blue-500/10 border border-blue-500/20 rounded-xl p-3">
                  <p className="text-sm text-blue-300">Per verificare la tua identità, rispondi alle seguenti domande</p>
                </div>

                {questions.map((q) => (
                  <div key={q.id}>
                    <label className="block text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">{q.label}</label>
                    <select value={answers[q.id] || ""} onChange={(e) => setAnswers((prev) => ({ ...prev, [q.id]: e.target.value }))}
                      className="w-full h-12 px-3 bg-gray-900/60 border-2 border-gray-700 rounded-xl text-white text-base focus:border-yellow-500 focus:outline-none transition-colors">
                      <option value="">Seleziona...</option>
                      {q.options.map((opt) => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
                    </select>
                  </div>
                ))}

                <button type="button" onClick={verificaIdentita} disabled={loading}
                  className="w-full h-12 bg-gradient-to-r from-yellow-400 to-yellow-600 hover:from-yellow-500 hover:to-yellow-700 text-black font-bold rounded-xl disabled:opacity-50 shadow-lg shadow-yellow-500/20 transition-all">
                  {loading ? "Verifica in corso..." : "Avvia Corso"}
                </button>
              </div>
            )}

            <div className="mt-6 text-center">
              <a href="mailto:assistenza@tutor81.com" className="text-sm text-gray-500 hover:text-yellow-500 transition-colors">
                Non riesci ad accedere? Contatta l'assistenza
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
