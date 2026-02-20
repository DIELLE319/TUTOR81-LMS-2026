import { useState, useRef } from "react";
import { useToast } from "@/hooks/use-toast";

const PLAYER_URL = "https://avviacorso.tutor81.com/prelogin.php";

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
  const [licenseCode, setLicenseCode] = useState("");
  const { toast } = useToast();
  const formRef = useRef<HTMLFormElement>(null);

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
      const r = await fetch("/api/player/verify-identity", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          username: username.trim().toLowerCase(),
          answers: answersArray,
        }),
      });
      const d = await r.json();
      if (r.ok && d.success) {
        setLicenseCode(d.enrollment.licenseCode);
        toast({ title: "Accesso verificato!", description: d.enrollment.courseName });
        setTimeout(() => formRef.current?.submit(), 200);
      } else {
        setErrore(d.error || "Verifica fallita");
        setLoading(false);
      }
    } catch {
      setErrore("Errore di connessione");
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen bg-gray-950 flex items-center justify-center p-4">

      <form ref={formRef} method="POST" action={PLAYER_URL} className="hidden">
        <input name="course_code" defaultValue={licenseCode} />
        <input name="tax_code" defaultValue="" />
      </form>

      <div className="w-full max-w-md bg-white rounded-2xl shadow-2xl overflow-hidden">

        <div className="bg-black text-center py-6 px-4">
          <div className="w-14 h-14 bg-yellow-500 rounded-full mx-auto mb-3 flex items-center justify-center text-2xl font-black text-black">T</div>
          <h1 className="text-yellow-500 text-xl font-bold">Accedi al Corso</h1>
          <p className="text-gray-400 text-sm mt-1">
            {step === 1 ? "Inserisci il tuo nome utente" : "Verifica la tua identità"}
          </p>
        </div>

        <div className="p-6">

          {errore && (
            <div className="bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg p-3 mb-4">
              {errore}
            </div>
          )}

          {step === 1 && (
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-semibold text-gray-700 mb-1">Nome Utente</label>
                <input
                  type="text"
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); verificaUsername(); } }}
                  placeholder="nome.cognome"
                  className="w-full h-11 px-4 border-2 border-gray-300 rounded-lg text-base text-gray-900 placeholder-gray-400 focus:border-yellow-500 focus:outline-none"
                />
                <p className="text-xs text-gray-500 mt-1">
                  Il nome utente ti è stato comunicato nel formato <b>nome.cognome</b>
                </p>
              </div>
              <button
                type="button"
                onClick={verificaUsername}
                disabled={loading}
                className="w-full h-11 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg disabled:opacity-50"
              >
                {loading ? "Verifica..." : "Prosegui"}
              </button>
            </div>
          )}

          {step === 2 && (
            <div className="space-y-4">
              <div className="flex items-center gap-2 mb-1">
                <button
                  type="button"
                  onClick={() => { setStep(1); setQuestions([]); setAnswers({}); setErrore(""); }}
                  className="text-gray-500 hover:text-black text-sm underline"
                >
                  ← Indietro
                </button>
                <span className="text-sm text-gray-600">Utente: <b>{username}</b></span>
              </div>

              <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <p className="text-sm text-blue-800">
                  Per verificare la tua identità, rispondi alle seguenti domande
                </p>
              </div>

              {questions.map((q) => (
                <div key={q.id}>
                  <label className="block text-sm font-semibold text-gray-700 mb-1">{q.label}</label>
                  <select
                    value={answers[q.id] || ""}
                    onChange={(e) => setAnswers((prev) => ({ ...prev, [q.id]: e.target.value }))}
                    className="w-full h-11 px-3 border-2 border-gray-300 rounded-lg text-base text-gray-900 focus:border-yellow-500 focus:outline-none"
                  >
                    <option value="">Seleziona...</option>
                    {q.options.map((opt) => (
                      <option key={opt.value} value={opt.value}>{opt.label}</option>
                    ))}
                  </select>
                </div>
              ))}

              <button
                type="button"
                onClick={verificaIdentita}
                disabled={loading}
                className="w-full h-11 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg disabled:opacity-50"
              >
                {loading ? "Verifica in corso..." : "Avvia Corso"}
              </button>
            </div>
          )}

          <div className="mt-6 text-center">
            <a href="mailto:assistenza@tutor81.com" className="text-sm text-blue-600 hover:underline">
              Non riesci ad accedere? Contatta l'assistenza
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}
