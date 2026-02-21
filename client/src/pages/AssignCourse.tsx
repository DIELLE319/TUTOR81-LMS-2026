import { useState } from "react";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { useLocation, useSearch } from "wouter";
import { Send, UserPlus, UserCheck, Minus, Plus, AlertTriangle, Check, X } from "lucide-react";

interface Company { id: number; businessName: string }
interface CourseRaw { id: number; title: string }

interface Destinatario {
  email: string; startDate: string; endDate: string; alertDays: number;
  firstName: string; lastName: string; fiscalCode: string; funzione: string;
}

interface LicenzaInviata {
  firstName: string; lastName: string; email: string; username: string; licenseCode: string;
}

const inputCls = "h-8 px-2 bg-[#1a1a1a] border border-white/10 rounded text-xs text-gray-200 placeholder-gray-600 focus:outline-none focus:border-yellow-500/50";

const emptyDest = (): Destinatario => ({
  email: "", firstName: "", lastName: "", fiscalCode: "", funzione: "LAVORATORE",
  startDate: new Date().toISOString().slice(0, 10),
  endDate: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000).toISOString().slice(0, 10),
  alertDays: 30,
});

export default function AssignCourse() {
  const { toast } = useToast();
  const qc = useQueryClient();
  const [, navigate] = useLocation();
  const search = useSearch();
  const params = new URLSearchParams(search);
  const courseId = parseInt(params.get("courseId") || "0");
  const courseTitle = decodeURIComponent(params.get("courseTitle") || "");

  const [companyId, setCompanyId] = useState(0);
  const [destinatari, setDestinatari] = useState<Destinatario[]>([emptyDest()]);
  const [licenzeInviate, setLicenzeInviate] = useState<LicenzaInviata[]>([]);
  const [showConfirm, setShowConfirm] = useState(false);
  const [cfStatus, setCfStatus] = useState<Record<number, { exists: boolean; msg?: string }>>({});

  const checkCF = async (index: number, cf: string) => {
    if (!cf || cf.length < 10) { setCfStatus(p => ({ ...p, [index]: { exists: false } })); return; }
    try {
      const r = await fetch("/api/check-fiscal-code", { method: "POST", headers: { "Content-Type": "application/json" }, credentials: "include", body: JSON.stringify({ fiscalCode: cf }) });
      const d = await r.json();
      if (d.exists) {
        const name = d.user?.firstName || d.student?.firstName || "";
        const last = d.user?.lastName || d.student?.lastName || "";
        const email = d.user?.email || "";
        if (name) updateDest(index, "firstName", name);
        if (last) updateDest(index, "lastName", last);
        if (email) updateDest(index, "email", email);
        setCfStatus(p => ({ ...p, [index]: { exists: true, msg: `Trovato${d.source === "ovh" ? " su OVH" : ""}: ${name} ${last}` } }));
      } else {
        setCfStatus(p => ({ ...p, [index]: { exists: false, msg: "OK" } }));
      }
    } catch { setCfStatus(p => ({ ...p, [index]: { exists: false } })); }
  };

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ["companies-list"],
    queryFn: () => fetch("/api/companies-list", { credentials: "include" }).then((r) => r.json()),
  });

  const activateMut = useMutation({
    mutationFn: (data: any) => apiRequest("POST", "/api/enrollments/activate", data),
    onSuccess: (d: any) => {
      toast({ title: "Licenze inviate con successo!" });
      qc.invalidateQueries({ queryKey: ["enrollments"] });
      qc.invalidateQueries({ queryKey: ["sales"] });
      if (d.results) {
        setLicenzeInviate(d.results.map((r: any) => ({
          firstName: r.firstName || "", lastName: r.lastName || "",
          email: r.email || "", username: r.username || "",
          licenseCode: r.licenseCode || r.code || "",
        })));
      }
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const updateDest = (i: number, field: keyof Destinatario, value: string | number) => {
    setDestinatari((prev) => prev.map((d, idx) => idx === i ? { ...d, [field]: value } : d));
  };

  const setQuantita = (n: number) => {
    if (n < 1) return;
    setDestinatari((prev) => {
      if (n > prev.length) return [...prev, ...Array(n - prev.length).fill(null).map(() => emptyDest())];
      return prev.slice(0, n);
    });
  };

  const addNewUser = () => setDestinatari((prev) => [...prev, emptyDest()]);

  const handleSubmit = () => {
    if (!companyId) { toast({ title: "Seleziona un cliente", variant: "destructive" }); return; }
    if (!courseId) { toast({ title: "Corso non selezionato", variant: "destructive" }); return; }
    const valid = destinatari.filter((d) => d.email.trim() && d.firstName.trim() && d.lastName.trim());
    if (valid.length === 0) { toast({ title: "Inserisci almeno un destinatario", variant: "destructive" }); return; }
    setShowConfirm(true);
  };

  const confirmSend = () => {
    setShowConfirm(false);
    const valid = destinatari.filter((d) => d.email.trim() && d.firstName.trim() && d.lastName.trim());
    activateMut.mutate({
      companyId, courseId,
      corsisti: valid.map((d) => ({
        firstName: d.firstName, lastName: d.lastName, fiscalCode: d.fiscalCode,
        email: d.email, startDate: d.startDate, endDate: d.endDate,
      })),
    });
  };

  return (
    <div className="w-full">
      {/* Corso header */}
      <div className="bg-gradient-to-r from-green-700 to-green-600 rounded-xl px-5 py-3 mb-4">
        <span className="text-sm text-green-200">Corso:</span>{" "}
        <span className="text-sm font-bold text-white">{courseTitle || `Corso #${courseId}`}</span>
      </div>

      {/* Cliente + Quantità bar */}
      <div className="bg-yellow-500 rounded-xl px-5 py-3 mb-6 flex flex-wrap items-center gap-4">
        <div className="flex items-center gap-2">
          <span className="text-sm font-bold text-black">Cliente:</span>
          <select value={companyId} onChange={(e) => setCompanyId(parseInt(e.target.value))}
            className="h-8 px-3 border border-yellow-600 rounded text-sm bg-white text-gray-900 min-w-[250px]">
            <option value={0}>Seleziona azienda...</option>
            {companies.map((c) => <option key={c.id} value={c.id}>{c.businessName}</option>)}
          </select>
        </div>
        <div className="flex items-center gap-2">
          <span className="text-sm font-bold text-black">Quantità:</span>
          <button onClick={() => setQuantita(destinatari.length - 1)}
            className="w-7 h-7 flex items-center justify-center bg-white/80 rounded text-black hover:bg-white">
            <Minus size={14} />
          </button>
          <span className="w-8 h-7 flex items-center justify-center bg-white rounded text-sm font-bold text-black">{destinatari.length}</span>
          <button onClick={() => setQuantita(destinatari.length + 1)}
            className="w-7 h-7 flex items-center justify-center bg-white/80 rounded text-black hover:bg-white">
            <Plus size={14} />
          </button>
        </div>
        <button onClick={addNewUser} className="h-8 px-4 bg-green-600 text-white rounded text-xs font-bold flex items-center gap-1.5 hover:bg-green-700">
          <UserPlus size={13} />Nuovo Utente
        </button>
        <button className="h-8 px-4 bg-white border border-gray-300 text-gray-700 rounded text-xs font-medium flex items-center gap-1.5 hover:bg-gray-50">
          <UserCheck size={13} />Utente Esistente
        </button>
      </div>

      {/* Destinatari table */}
      <div className="bg-[#141414] rounded-xl border border-white/5 overflow-x-auto mb-4">
        <table className="w-full text-sm">
          <thead>
            <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
              <th className="p-2 text-left w-8">N.</th>
              <th className="p-2 text-left">Email Destinatario</th>
              <th className="p-2 text-left">Data Inizio</th>
              <th className="p-2 text-left">Fine Corso</th>
              <th className="p-2 text-center">Alert (GG)</th>
              <th className="p-2 text-left">Nome</th>
              <th className="p-2 text-left">Cognome</th>
              <th className="p-2 text-left">Codice Fiscale</th>
              <th className="p-2 text-left">Funzione</th>
            </tr>
          </thead>
          <tbody>
            {destinatari.map((d, i) => (
              <tr key={i} className={`border-b border-white/5 ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                <td className="p-2 text-gray-500 text-xs">{i + 1}</td>
                <td className="p-2">
                  <input type="email" value={d.email} onChange={(e) => updateDest(i, "email", e.target.value)}
                    placeholder="email@esempio.com" className={`${inputCls} w-full`} />
                </td>
                <td className="p-2">
                  <input type="date" value={d.startDate} onChange={(e) => updateDest(i, "startDate", e.target.value)}
                    className={`${inputCls} w-full`} />
                </td>
                <td className="p-2">
                  <input type="date" value={d.endDate} onChange={(e) => updateDest(i, "endDate", e.target.value)}
                    className={`${inputCls} w-full`} />
                </td>
                <td className="p-2">
                  <select value={d.alertDays} onChange={(e) => updateDest(i, "alertDays", parseInt(e.target.value))}
                    className={`${inputCls} w-16 text-center`}>
                    <option value={7}>7</option>
                    <option value={15}>15</option>
                    <option value={30}>30</option>
                  </select>
                </td>
                <td className="p-2">
                  <input type="text" value={d.firstName} onChange={(e) => updateDest(i, "firstName", e.target.value)}
                    placeholder="Nome" className={`${inputCls} w-full`} />
                </td>
                <td className="p-2">
                  <input type="text" value={d.lastName} onChange={(e) => updateDest(i, "lastName", e.target.value)}
                    placeholder="Cognome" className={`${inputCls} w-full`} />
                </td>
                <td className="p-2">
                  <div className="relative">
                    <input type="text" value={d.fiscalCode}
                      onChange={(e) => updateDest(i, "fiscalCode", e.target.value.toUpperCase())}
                      onBlur={(e) => checkCF(i, e.target.value)}
                      placeholder="CODICE FISCALE" className={`${inputCls} w-full uppercase ${cfStatus[i]?.exists ? "border-yellow-500" : cfStatus[i]?.msg === "OK" ? "border-green-500" : ""}`} />
                    {cfStatus[i]?.exists && <span className="text-[9px] text-yellow-400 mt-0.5 block" title={cfStatus[i]?.msg}><AlertTriangle size={10} className="inline mr-0.5" />{cfStatus[i]?.msg}</span>}
                    {cfStatus[i]?.msg === "OK" && <span className="text-[9px] text-green-400 mt-0.5 block"><Check size={10} className="inline mr-0.5" />Disponibile</span>}
                  </div>
                </td>
                <td className="p-2">
                  <select value={d.funzione} onChange={(e) => updateDest(i, "funzione", e.target.value)}
                    className={`${inputCls} w-full`}>
                    <option value="LAVORATORE">LAVORATORE</option>
                    <option value="PREPOSTO">PREPOSTO</option>
                    <option value="DIRIGENTE">DIRIGENTE</option>
                    <option value="DATORE">DATORE</option>
                    <option value="RLS">RLS</option>
                    <option value="RSPP">RSPP</option>
                  </select>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Invia Licenze button */}
      <div className="flex justify-end mb-8">
        <button onClick={handleSubmit} disabled={activateMut.isPending}
          className="h-10 px-6 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-sm flex items-center gap-2 disabled:opacity-50">
          <Send size={15} />
          {activateMut.isPending ? "Invio in corso..." : "Invia Licenze"}
        </button>
      </div>

      {/* Licenze inviate con successo */}
      {/* Modal conferma */}
      {showConfirm && (
        <div className="fixed inset-0 bg-black/70 z-50 flex items-center justify-center" onClick={() => setShowConfirm(false)}>
          <div className="bg-[#1a1a1a] border border-white/10 rounded-2xl p-6 max-w-md w-full mx-4" onClick={e => e.stopPropagation()}>
            <h3 className="text-lg font-bold text-white mb-3">Conferma Invio Licenze</h3>
            <p className="text-sm text-gray-400 mb-2">Stai per assegnare <span className="text-yellow-500 font-bold">{destinatari.filter(d => d.email && d.firstName && d.lastName).length} licenza/e</span> per il corso:</p>
            <p className="text-sm text-white font-bold mb-4">{courseTitle}</p>
            <p className="text-sm text-gray-400 mb-4">Cliente: <span className="text-white font-bold">{companies.find(c => c.id === companyId)?.businessName || "—"}</span></p>
            <div className="flex justify-end gap-3">
              <button onClick={() => setShowConfirm(false)} className="h-9 px-5 border border-white/20 text-gray-400 rounded-lg text-sm flex items-center gap-1.5 hover:bg-white/5">
                <X size={14} />Annulla
              </button>
              <button onClick={confirmSend} className="h-9 px-5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-sm flex items-center gap-1.5">
                <Send size={14} />Conferma e Invia
              </button>
            </div>
          </div>
        </div>
      )}

      {licenzeInviate.length > 0 && (
        <div className="bg-[#141414] rounded-xl border border-green-500/30 overflow-hidden mb-6">
          <div className="bg-green-600 px-5 py-2">
            <h2 className="text-sm font-bold text-white uppercase">Licenze Inviate con Successo</h2>
          </div>
          <table className="w-full text-sm">
            <thead>
              <tr className="border-b border-white/10 text-gray-400 text-xs">
                <th className="p-2.5 text-left font-semibold">Nome</th>
                <th className="p-2.5 text-left font-semibold">Cognome</th>
                <th className="p-2.5 text-left font-semibold">Email</th>
                <th className="p-2.5 text-left font-semibold">Username</th>
                <th className="p-2.5 text-left font-semibold">Codice Licenza</th>
              </tr>
            </thead>
            <tbody>
              {licenzeInviate.map((l, i) => (
                <tr key={i} className="border-b border-white/5">
                  <td className="p-2.5 text-gray-300 text-xs">{l.firstName}</td>
                  <td className="p-2.5 text-gray-300 text-xs">{l.lastName}</td>
                  <td className="p-2.5 text-gray-400 text-xs">{l.email}</td>
                  <td className="p-2.5 text-gray-400 text-xs font-mono">{l.username}</td>
                  <td className="p-2.5 text-yellow-500 text-xs font-bold">{l.licenseCode}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
