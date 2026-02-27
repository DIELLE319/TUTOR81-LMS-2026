import { useState, useEffect } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { X, Plus, Trash2, Search } from "lucide-react";

interface Company { id: number; businessName: string }
interface Course { id: number; title: string }
interface Corsista { firstName: string; lastName: string; fiscalCode: string; email: string; startDate: string; endDate: string }

interface Props {
  course: Course;
  onClose: () => void;
}

const emptyCorsista = (): Corsista => ({
  firstName: "", lastName: "", fiscalCode: "", email: "",
  startDate: new Date().toISOString().slice(0, 10),
  endDate: new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
});

export default function SellCourseModal({ course, onClose }: Props) {
  const { toast } = useToast();
  const qc = useQueryClient();
  const [companyId, setCompanyId] = useState(0);
  const [corsisti, setCorsisti] = useState<Corsista[]>([emptyCorsista()]);
  const [cfSearch, setCfSearch] = useState("");
  const [cfResult, setCfResult] = useState<{ exists: boolean; student: any } | null>(null);
  const [searchingCf, setSearchingCf] = useState(false);

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ["companies-list"],
    queryFn: () => fetch("/api/companies-list", { credentials: "include" }).then((r) => r.json()),
  });

  const activateMut = useMutation({
    mutationFn: (data: any) => apiRequest("POST", "/api/enrollments/activate", data),
    onSuccess: (d) => {
      toast({ title: "Corso venduto!", description: d.message });
      qc.invalidateQueries({ queryKey: ["enrollments"] });
      qc.invalidateQueries({ queryKey: ["sales"] });
      onClose();
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const updateCorsista = (index: number, field: keyof Corsista, value: string) => {
    setCorsisti((prev) => prev.map((c, i) => i === index ? { ...c, [field]: value } : c));
  };

  const addCorsista = () => setCorsisti((prev) => [...prev, emptyCorsista()]);

  const removeCorsista = (index: number) => {
    if (corsisti.length <= 1) return;
    setCorsisti((prev) => prev.filter((_, i) => i !== index));
  };

  const searchCF = async () => {
    if (!cfSearch.trim()) return;
    setSearchingCf(true);
    try {
      const r = await fetch("/api/check-fiscal-code", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ fiscalCode: cfSearch.trim().toUpperCase() }),
      });
      const d = await r.json();
      setCfResult(d);
      if (d.exists && d.student) {
        setCorsisti((prev) => {
          const updated = [...prev];
          updated[0] = {
            ...updated[0],
            firstName: d.student.firstName || "",
            lastName: d.student.lastName || "",
            fiscalCode: cfSearch.trim().toUpperCase(),
            email: d.student.email || "",
          };
          return updated;
        });
      }
    } catch {
      toast({ title: "Errore ricerca CF", variant: "destructive" });
    }
    setSearchingCf(false);
  };

  const handleSubmit = () => {
    if (!companyId) { toast({ title: "Seleziona un'azienda", variant: "destructive" }); return; }
    const valid = corsisti.filter((c) => c.firstName.trim() && c.lastName.trim() && c.fiscalCode.trim());
    if (valid.length === 0) { toast({ title: "Inserisci almeno un corsista", variant: "destructive" }); return; }
    activateMut.mutate({ companyId, courseId: course.id, corsisti: valid });
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4" onClick={onClose}>
      <div className="fixed inset-0 bg-black/50" />
      <div className="relative bg-[#1a1a1a] border border-white/10 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>

        {/* Header */}
        <div className="flex items-center justify-between p-5 border-b border-white/10">
          <div>
            <h2 className="text-lg font-bold text-white">Vendi Corso</h2>
            <p className="text-sm text-yellow-500 mt-0.5">{course.title}</p>
          </div>
          <button onClick={onClose} className="text-gray-500 hover:text-white"><X size={20} /></button>
        </div>

        {/* Body */}
        <div className="p-5 space-y-5">

          {/* Azienda */}
          <div>
            <label className="block text-sm font-semibold text-gray-400 mb-1">Azienda cliente</label>
            <select value={companyId} onChange={(e) => setCompanyId(parseInt(e.target.value))}
              className="w-full h-10 bg-[#141414] border border-white/10 rounded-lg px-3 text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50">
              <option value={0}>Seleziona azienda...</option>
              {companies.map((c) => <option key={c.id} value={c.id}>{c.businessName}</option>)}
            </select>
          </div>

          {/* Ricerca CF */}
          <div className="bg-white/5 rounded-lg p-4 border border-white/5">
            <label className="block text-sm font-semibold text-gray-400 mb-2">Cerca per Codice Fiscale</label>
            <div className="flex gap-2">
              <input type="text" value={cfSearch} onChange={(e) => setCfSearch(e.target.value.toUpperCase())}
                onKeyDown={(e) => { if (e.key === "Enter") { e.preventDefault(); searchCF(); } }}
                placeholder="RSSMRA80A01H501U"
                className="flex-1 h-10 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 uppercase placeholder-gray-600 focus:outline-none focus:border-yellow-500/50" />
              <button type="button" onClick={searchCF} disabled={searchingCf}
                className="h-10 px-4 bg-yellow-500 text-black rounded-lg text-sm font-bold flex items-center gap-2 hover:bg-yellow-600 disabled:opacity-50">
                <Search size={14} />{searchingCf ? "..." : "Cerca"}
              </button>
            </div>
            {cfResult && (
              <p className={`text-xs mt-2 ${cfResult.exists ? "text-green-400" : "text-orange-400"}`}>
                {cfResult.exists ? `Trovato: ${cfResult.student.firstName} ${cfResult.student.lastName}` : "Utente non trovato — verrà creato"}
              </p>
            )}
          </div>

          {/* Corsisti */}
          <div>
            <div className="flex items-center justify-between mb-2">
              <label className="text-sm font-semibold text-gray-400">Corsisti ({corsisti.length})</label>
              <button type="button" onClick={addCorsista} className="text-sm text-yellow-500 hover:text-yellow-400 flex items-center gap-1">
                <Plus size={14} />Aggiungi
              </button>
            </div>

            <div className="space-y-3">
              {corsisti.map((c, i) => (
                <div key={i} className="border border-white/10 rounded-lg p-3 space-y-2 bg-[#141414]">
                  <div className="flex items-center justify-between">
                    <span className="text-xs font-semibold text-gray-500">Corsista {i + 1}</span>
                    {corsisti.length > 1 && (
                      <button type="button" onClick={() => removeCorsista(i)} className="text-red-400 hover:text-red-300"><Trash2 size={14} /></button>
                    )}
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <input type="text" value={c.firstName} onChange={(e) => updateCorsista(i, "firstName", e.target.value)}
                      placeholder="Nome" className="h-9 px-3 bg-[#1a1a1a] border border-white/10 rounded-md text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-yellow-500/50" />
                    <input type="text" value={c.lastName} onChange={(e) => updateCorsista(i, "lastName", e.target.value)}
                      placeholder="Cognome" className="h-9 px-3 bg-[#1a1a1a] border border-white/10 rounded-md text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <input type="text" value={c.fiscalCode} onChange={(e) => updateCorsista(i, "fiscalCode", e.target.value.toUpperCase())}
                      placeholder="Codice Fiscale" className="h-9 px-3 bg-[#1a1a1a] border border-white/10 rounded-md text-sm text-gray-200 uppercase placeholder-gray-600 focus:outline-none focus:border-yellow-500/50" />
                    <input type="email" value={c.email} onChange={(e) => updateCorsista(i, "email", e.target.value)}
                      placeholder="Email (opzionale)" className="h-9 px-3 bg-[#1a1a1a] border border-white/10 rounded-md text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div className="grid grid-cols-2 gap-2">
                    <div>
                      <label className="text-[11px] text-gray-500">Inizio</label>
                      <input type="date" value={c.startDate} onChange={(e) => updateCorsista(i, "startDate", e.target.value)}
                        className="w-full h-9 px-3 bg-[#1a1a1a] border border-white/10 rounded-md text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                    </div>
                    <div>
                      <label className="text-[11px] text-gray-500">Scadenza</label>
                      <input type="date" value={c.endDate} onChange={(e) => updateCorsista(i, "endDate", e.target.value)}
                        className="w-full h-9 px-3 bg-[#1a1a1a] border border-white/10 rounded-md text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        {/* Footer */}
        <div className="flex items-center justify-end gap-3 p-5 border-t border-white/10">
          <button type="button" onClick={onClose} className="h-10 px-5 border border-white/20 rounded-lg text-sm text-gray-400 hover:bg-white/5">Annulla</button>
          <button type="button" onClick={handleSubmit} disabled={activateMut.isPending}
            className="h-10 px-6 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg text-sm disabled:opacity-50">
            {activateMut.isPending ? "Vendita in corso..." : "Conferma Vendita"}
          </button>
        </div>
      </div>
    </div>
  );
}
