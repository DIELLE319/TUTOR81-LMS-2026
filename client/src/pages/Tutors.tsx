import { useState, useMemo, useRef } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Search, Plus, Edit2, Trash2, Upload, X, Building2, Image } from "lucide-react";

interface Tutor {
  id: number;
  businessName: string;
  vatNumber: string | null;
  fiscalCode: string | null;
  address: string | null;
  city: string | null;
  cap: string | null;
  province: string | null;
  phone: string | null;
  email: string | null;
  pec: string | null;
  website: string | null;
  regionalAuthorization: string | null;
  contactPerson: string | null;
  adminName: string | null;
  subscriptionType: string | null;
  discountPercentage: number | null;
  subscriptionStart: string | null;
  logoUrl: string | null;
  notes: string | null;
  isActive: boolean | null;
}

const SUB_TYPES = ["TUTOR PLUS", "TUTOR BASIC", "CONSULENTI 500", "COMPANY"];
const emptyTutor: Partial<Tutor> = {
  businessName: "", vatNumber: "", fiscalCode: "", address: "", city: "", cap: "", province: "",
  phone: "", email: "", pec: "", website: "", regionalAuthorization: "", contactPerson: "", adminName: "",
  subscriptionType: "CONSULENTI 500", discountPercentage: 60, subscriptionStart: "", notes: "", isActive: true,
};

function subBadge(type: string | null) {
  switch (type) {
    case "TUTOR PLUS": return "bg-purple-500 text-white";
    case "TUTOR BASIC": return "bg-blue-500 text-white";
    case "COMPANY": return "bg-green-500 text-white";
    default: return "bg-yellow-500 text-black";
  }
}

export default function Tutors() {
  const [search, setSearch] = useState("");
  const [editTutor, setEditTutor] = useState<Partial<Tutor> | null>(null);
  const [isNew, setIsNew] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);
  const [uploadingId, setUploadingId] = useState<number | null>(null);
  const qc = useQueryClient();
  const { toast } = useToast();

  const { data: tutors = [], isLoading } = useQuery<Tutor[]>({
    queryKey: ["tutors"],
    queryFn: () => fetch("/api/tutors", { credentials: "include" }).then((r) => r.json()),
  });

  const saveMut = useMutation({
    mutationFn: (data: Partial<Tutor>) => {
      if (data.id) return apiRequest("PATCH", `/api/tutors/${data.id}`, data);
      return apiRequest("POST", "/api/tutors", data);
    },
    onSuccess: () => {
      toast({ title: isNew ? "Ente creato" : "Ente aggiornato" });
      qc.invalidateQueries({ queryKey: ["tutors"] });
      setEditTutor(null);
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const deleteMut = useMutation({
    mutationFn: (id: number) => apiRequest("DELETE", `/api/tutors/${id}`),
    onSuccess: () => {
      toast({ title: "Ente eliminato" });
      qc.invalidateQueries({ queryKey: ["tutors"] });
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const filtered = useMemo(() => tutors.filter((t) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return t.businessName.toLowerCase().includes(q) || (t.email || "").toLowerCase().includes(q) || (t.city || "").toLowerCase().includes(q);
  }), [tutors, search]);

  const openNew = () => { setEditTutor({ ...emptyTutor }); setIsNew(true); };
  const openEdit = (t: Tutor) => { setEditTutor({ ...t }); setIsNew(false); };
  const updateField = (field: string, value: any) => setEditTutor((prev) => prev ? { ...prev, [field]: value } : prev);

  const handleLogoUpload = async (tutorId: number, file: File) => {
    setUploadingId(tutorId);
    const fd = new FormData();
    fd.append("logo", file);
    try {
      const r = await fetch(`/api/tutors/${tutorId}/logo`, { method: "POST", credentials: "include", body: fd });
      const d = await r.json();
      if (r.ok) {
        toast({ title: "Logo caricato" });
        qc.invalidateQueries({ queryKey: ["tutors"] });
      } else {
        toast({ title: "Errore", description: d.error, variant: "destructive" });
      }
    } catch {
      toast({ title: "Errore upload", variant: "destructive" });
    }
    setUploadingId(null);
  };

  return (
    <div>
      {/* Filter bar */}
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-gray-300 mb-1">Cerca ente formativo</label>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, email, città..."
              className="w-full h-9 pl-9 pr-3 bg-white border-0 rounded text-sm text-gray-900" />
          </div>
        </div>
        <div className="text-xs text-gray-300 self-center">{filtered.length} enti</div>
        <button onClick={openNew} className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
          <Plus size={14} />Nuovo Ente
        </button>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <Building2 size={48} className="mx-auto text-gray-300 mb-4" />
          <p className="text-gray-500">Nessun ente formativo trovato</p>
        </div>
      ) : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                <th className="p-3 text-left w-14">Logo</th>
                <th className="p-3 text-left">Ragione Sociale</th>
                <th className="p-3 text-left">Abbonamento</th>
                <th className="p-3 text-left">Sconto</th>
                <th className="p-3 text-left">Email</th>
                <th className="p-3 text-left">Telefono</th>
                <th className="p-3 text-left">Città</th>
                <th className="p-3 text-left">Stato</th>
                <th className="p-3 text-left w-24">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((t) => (
                <tr key={t.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="p-3">
                    <div className="relative group">
                      {t.logoUrl ? (
                        <img src={t.logoUrl} alt="" className="w-10 h-10 rounded-lg object-contain bg-gray-50 border border-gray-200" />
                      ) : (
                        <div className="w-10 h-10 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                          <Image size={16} />
                        </div>
                      )}
                      <button
                        onClick={() => { setUploadingId(t.id); fileRef.current?.click(); }}
                        className="absolute inset-0 bg-black/50 rounded-lg opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                        <Upload size={12} className="text-white" />
                      </button>
                    </div>
                  </td>
                  <td className="p-3 font-medium text-gray-900 text-xs">{t.businessName}</td>
                  <td className="p-3">
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${subBadge(t.subscriptionType)}`}>
                      {t.subscriptionType || "—"}
                    </span>
                  </td>
                  <td className="p-3 text-green-600 font-bold text-xs">{t.discountPercentage != null ? `${t.discountPercentage}%` : "—"}</td>
                  <td className="p-3 text-gray-600 text-xs">{t.email || "—"}</td>
                  <td className="p-3 text-gray-600 text-xs">{t.phone || "—"}</td>
                  <td className="p-3 text-gray-500 text-xs">{t.city || "—"}</td>
                  <td className="p-3">
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${t.isActive ? "bg-green-500 text-white" : "bg-red-500 text-white"}`}>
                      {t.isActive ? "Attivo" : "Disattivo"}
                    </span>
                  </td>
                  <td className="p-3">
                    <div className="flex items-center gap-1">
                      <button onClick={() => openEdit(t)} className="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 flex items-center justify-center text-blue-600">
                        <Edit2 size={13} />
                      </button>
                      <button onClick={() => { if (confirm(`Eliminare ${t.businessName}?`)) deleteMut.mutate(t.id); }}
                        className="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 flex items-center justify-center text-red-600">
                        <Trash2 size={13} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Hidden file input for logo upload */}
      <input ref={fileRef} type="file" accept="image/*" className="hidden"
        onChange={(e) => {
          const file = e.target.files?.[0];
          if (file && uploadingId) handleLogoUpload(uploadingId, file);
          e.target.value = "";
        }} />

      {/* Edit/Create Modal */}
      {editTutor && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" onClick={() => setEditTutor(null)}>
          <div className="fixed inset-0 bg-black/50" />
          <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between p-5 border-b">
              <h2 className="text-lg font-bold text-gray-900">{isNew ? "Nuovo Ente Formativo" : "Modifica Ente"}</h2>
              <button onClick={() => setEditTutor(null)} className="text-gray-400 hover:text-gray-600"><X size={20} /></button>
            </div>

            <div className="p-5 space-y-5">
              {/* Info base */}
              <div>
                <h3 className="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
                  <Building2 size={14} className="text-yellow-500" /> Dati Aziendali
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div className="md:col-span-2">
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Ragione Sociale *</label>
                    <input type="text" value={editTutor.businessName || ""} onChange={(e) => updateField("businessName", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">P.IVA</label>
                    <input type="text" value={editTutor.vatNumber || ""} onChange={(e) => updateField("vatNumber", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Codice Fiscale</label>
                    <input type="text" value={editTutor.fiscalCode || ""} onChange={(e) => updateField("fiscalCode", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Indirizzo</label>
                    <input type="text" value={editTutor.address || ""} onChange={(e) => updateField("address", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Città</label>
                    <input type="text" value={editTutor.city || ""} onChange={(e) => updateField("city", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div className="grid grid-cols-2 gap-3">
                    <div>
                      <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">CAP</label>
                      <input type="text" value={editTutor.cap || ""} onChange={(e) => updateField("cap", e.target.value)}
                        className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                    <div>
                      <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Provincia</label>
                      <input type="text" value={editTutor.province || ""} onChange={(e) => updateField("province", e.target.value)}
                        className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                    </div>
                  </div>
                </div>
              </div>

              {/* Contatti */}
              <div>
                <h3 className="text-sm font-bold text-gray-700 mb-3">Contatti</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Email</label>
                    <input type="email" value={editTutor.email || ""} onChange={(e) => updateField("email", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">PEC</label>
                    <input type="email" value={editTutor.pec || ""} onChange={(e) => updateField("pec", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Telefono</label>
                    <input type="tel" value={editTutor.phone || ""} onChange={(e) => updateField("phone", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Sito Web</label>
                    <input type="url" value={editTutor.website || ""} onChange={(e) => updateField("website", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Referente</label>
                    <input type="text" value={editTutor.contactPerson || ""} onChange={(e) => updateField("contactPerson", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Admin Name</label>
                    <input type="text" value={editTutor.adminName || ""} onChange={(e) => updateField("adminName", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                </div>
              </div>

              {/* Abbonamento */}
              <div className="bg-gray-50 rounded-xl p-4">
                <h3 className="text-sm font-bold text-gray-700 mb-3">Abbonamento</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Tipo Abbonamento</label>
                    <select value={editTutor.subscriptionType || ""} onChange={(e) => updateField("subscriptionType", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400">
                      {SUB_TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Sconto %</label>
                    <input type="number" min={0} max={100} value={editTutor.discountPercentage ?? 60} onChange={(e) => updateField("discountPercentage", parseInt(e.target.value) || 0)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Inizio Abbonamento</label>
                    <input type="date" value={editTutor.subscriptionStart || ""} onChange={(e) => updateField("subscriptionStart", e.target.value)}
                      className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                  </div>
                </div>
              </div>

              {/* Altro */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Autorizzazione Regionale</label>
                  <input type="text" value={editTutor.regionalAuthorization || ""} onChange={(e) => updateField("regionalAuthorization", e.target.value)}
                    className="w-full h-9 px-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
                </div>
                <div className="flex items-end">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={editTutor.isActive ?? true} onChange={(e) => updateField("isActive", e.target.checked)}
                      className="w-4 h-4 rounded border-gray-300 text-yellow-500 focus:ring-yellow-400" />
                    <span className="text-sm text-gray-700 font-medium">Ente Attivo</span>
                  </label>
                </div>
              </div>
              <div>
                <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Note</label>
                <textarea value={editTutor.notes || ""} onChange={(e) => updateField("notes", e.target.value)} rows={3}
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 resize-none" />
              </div>
            </div>

            <div className="flex items-center justify-end gap-3 p-5 border-t bg-gray-50">
              <button onClick={() => setEditTutor(null)} className="h-10 px-5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-100">Annulla</button>
              <button onClick={() => saveMut.mutate(editTutor)} disabled={saveMut.isPending || !editTutor.businessName?.trim()}
                className="h-10 px-6 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg text-sm disabled:opacity-50">
                {saveMut.isPending ? "Salvataggio..." : isNew ? "Crea Ente" : "Salva Modifiche"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
