import { useState, useMemo, useRef } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Search, Plus, Edit2, Trash2, Upload, X, Building2, Image, ChevronRight, ChevronDown, UserPlus, User, Mail } from "lucide-react";

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
  entityType: string | null;
  hasRegionalAuth: boolean | null;
  authorizedTrainer: string | null;
  atecoCode: string | null;
  subscriptionType: string | null;
  discountPercentage: number | null;
  subscriptionStart: string | null;
  subscriptionEnd: string | null;
  annualFee: number | null;
  certificateType: number | null;
  ecommerce: boolean | null;
  logoUrl: string | null;
  notes: string | null;
  isActive: boolean | null;
}

interface TutorAdmin {
  id: number;
  tutorId: number;
  name: string;
  email: string | null;
  phone: string | null;
  username: string | null;
  fiscalCode: string | null;
}

const SUB_TYPES = ["CONSULENTI 500", "CONSULENTI 1500", "ENTI AUTORIZZATI 1500", "COMPANY"];
const PROVINCE = [
  "AG","AL","AN","AO","AP","AQ","AR","AT","AV","BA","BG","BI","BL","BN","BO","BR","BS","BT","BZ",
  "CA","CB","CE","CH","CL","CN","CO","CR","CS","CT","CZ","EN","FC","FE","FG","FI","FM","FR","GE","GO","GR",
  "IM","IS","KR","LC","LE","LI","LO","LT","LU","MB","MC","ME","MI","MN","MO","MS","MT","NA","NO","NU",
  "OR","PA","PC","PD","PE","PG","PI","PN","PO","PR","PT","PU","PV","PZ","RA","RC","RE","RG","RI","RM","RN","RO",
  "SA","SI","SO","SP","SR","SS","SU","SV","TA","TE","TN","TO","TP","TR","TS","TV","UD","VA","VB","VC","VE","VI","VR","VT","VV"
];
const emptyTutor: Partial<Tutor> = {
  businessName: "", vatNumber: "", fiscalCode: "", address: "", city: "", cap: "", province: "",
  phone: "", email: "", pec: "", website: "", regionalAuthorization: "", contactPerson: "", adminName: "",
  entityType: "ENTE FORMATIVO", hasRegionalAuth: false, authorizedTrainer: "", atecoCode: "",
  subscriptionType: "CONSULENTI 500", discountPercentage: 60, subscriptionStart: "", subscriptionEnd: "",
  annualFee: 0, ecommerce: false, notes: "", isActive: true,
};

function subBadge(type: string | null) {
  switch (type) {
    case "CONSULENTI 500": return "bg-yellow-500 text-black";
    case "CONSULENTI 1500": return "bg-blue-500 text-white";
    case "ENTI AUTORIZZATI 1500": return "bg-purple-500 text-white";
    case "COMPANY": return "bg-green-500 text-white";
    default: return "bg-gray-400 text-white";
  }
}

export default function Tutors() {
  const [search, setSearch] = useState("");
  const [subFilter, setSubFilter] = useState("all");
  const [editTutor, setEditTutor] = useState<Partial<Tutor> | null>(null);
  const [isNew, setIsNew] = useState(false);
  const fileRef = useRef<HTMLInputElement>(null);
  const [uploadingId, setUploadingId] = useState<number | null>(null);
  const [expandedId, setExpandedId] = useState<number | null>(null);
  const [newAdmin, setNewAdmin] = useState<{ name: string; email: string; username: string; fiscalCode: string } | null>(null);
  const [editingAdmin, setEditingAdmin] = useState<TutorAdmin | null>(null);
  const [modalAdmin, setModalAdmin] = useState({ firstName: "", lastName: "", fiscalCode: "", email: "", sendEmail: true });
  const [adminMode, setAdminMode] = useState<"new" | "existing">("new");
  const qc = useQueryClient();
  const { toast } = useToast();

  const { data: admins = [] } = useQuery<TutorAdmin[]>({
    queryKey: ["tutor-admins", expandedId],
    queryFn: async () => {
      if (!expandedId) return [];
      const r = await fetch(`/api/tutors/${expandedId}/admins`, { credentials: "include" });
      if (!r.ok) return [];
      const data = await r.json();
      return Array.isArray(data) ? data : [];
    },
    enabled: !!expandedId,
  });

  const addAdminMut = useMutation({
    mutationFn: (data: { tutorId: number; name: string; email: string; username: string; fiscalCode: string }) =>
      apiRequest("POST", `/api/tutors/${data.tutorId}/admins`, data),
    onSuccess: () => {
      toast({ title: "Admin aggiunto" });
      qc.invalidateQueries({ queryKey: ["tutor-admins", expandedId] });
      setNewAdmin(null);
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const deleteAdminMut = useMutation({
    mutationFn: (id: number) => apiRequest("DELETE", `/api/tutor-admins/${id}`),
    onSuccess: () => {
      toast({ title: "Admin rimosso" });
      qc.invalidateQueries({ queryKey: ["tutor-admins", expandedId] });
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const updateAdminMut = useMutation({
    mutationFn: (data: { id: number; name?: string; email?: string; username?: string; fiscalCode?: string }) =>
      apiRequest("PATCH", `/api/tutor-admins/${data.id}`, data),
    onSuccess: () => {
      toast({ title: "Admin aggiornato" });
      qc.invalidateQueries({ queryKey: ["tutor-admins", expandedId] });
      setEditingAdmin(null);
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const sendAdminEmailMut = useMutation({
    mutationFn: (data: { adminId: number; email: string; name: string; username: string; tutorName: string }) =>
      apiRequest("POST", "/api/tutors/send-admin-email", data),
    onSuccess: () => toast({ title: "Email inviata!" }),
    onError: (e: Error) => toast({ title: "Errore invio email", description: e.message, variant: "destructive" }),
  });

  const { data: tutors = [], isLoading } = useQuery<Tutor[]>({
    queryKey: ["tutors"],
    queryFn: async () => {
      const r = await fetch("/api/tutors", { credentials: "include" });
      if (!r.ok) throw new Error("Failed to fetch tutors");
      const data = await r.json();
      return Array.isArray(data) ? data : [];
    },
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
    if (subFilter !== "all" && t.subscriptionType !== subFilter) return false;
    if (!search) return true;
    const q = search.toLowerCase();
    return t.businessName.toLowerCase().includes(q) || (t.email || "").toLowerCase().includes(q) || (t.city || "").toLowerCase().includes(q);
  }), [tutors, search, subFilter]);

  const openNew = () => { setEditTutor({ ...emptyTutor }); setIsNew(true); setModalAdmin({ firstName: "", lastName: "", fiscalCode: "", email: "", sendEmail: true }); setAdminMode("new"); };
  const openEdit = (t: Tutor) => { setEditTutor({ ...t }); setIsNew(false); setExpandedId(t.id); };
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
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Enti Formativi</h1>
        <button onClick={openNew} className="h-8 px-4 bg-yellow-500 text-black rounded-lg text-xs font-bold flex items-center gap-1.5 hover:bg-yellow-600">
          <Plus size={13} />Nuovo Ente
        </button>
      </div>

      {/* Yellow toolbar */}
      <div className="bg-yellow-500 rounded-t-xl px-4 py-2.5 flex flex-wrap items-center gap-3">
        <div className="flex items-center gap-1.5">
          <span className="text-xs font-medium text-black">Cerca:</span>
          <div className="relative">
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome, email, città..."
              className="h-7 w-48 px-2.5 pr-7 border border-yellow-600 rounded text-xs bg-white text-gray-900 placeholder-gray-400" />
            <Search size={12} className="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400" />
          </div>
        </div>
        <div className="flex items-center gap-1.5">
          <span className="text-xs font-medium text-black">Abbonamento:</span>
          <select value={subFilter} onChange={(e) => setSubFilter(e.target.value)}
            className="h-7 px-2 border border-yellow-600 rounded text-xs bg-white text-gray-900">
            <option value="all">--- Tutti ---</option>
            {SUB_TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
          </select>
        </div>
        <span className="ml-auto text-sm font-bold text-black">Totale: {filtered.length} enti</span>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? (
        <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 p-12 text-center">
          <Building2 size={48} className="mx-auto text-gray-600 mb-4" />
          <p className="text-gray-500">Nessun ente formativo trovato</p>
        </div>
      ) : (
        <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                <th className="p-2.5 text-left w-14">Logo</th>
                <th className="p-2.5 text-left">Ragione Sociale</th>
                <th className="p-2.5 text-left">Abbonamento</th>
                <th className="p-2.5 text-left">Sconto</th>
                <th className="p-2.5 text-left">Email</th>
                <th className="p-2.5 text-left">Attestato</th>
                <th className="p-2.5 text-left">Telefono</th>
                <th className="p-2.5 text-left">Città</th>
                <th className="p-2.5 text-left">Stato</th>
                <th className="p-2.5 text-left w-24">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {filtered.map((t, i) => {
                const isExpanded = expandedId === t.id;
                return (
                  <>
                    <tr key={t.id} className={`border-b border-white/5 cursor-pointer hover:bg-white/5 ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}
                      onClick={() => { setExpandedId(isExpanded ? null : t.id); setNewAdmin(null); }}>
                      <td className="p-2.5">
                        <div className="relative group">
                          {t.logoUrl ? (
                            <img src={t.logoUrl} alt="" className="w-10 h-10 rounded-lg object-contain bg-white/5 border border-white/10" />
                          ) : (
                            <div className="w-10 h-10 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-gray-600">
                              <Image size={16} />
                            </div>
                          )}
                          <button
                            onClick={(e) => { e.stopPropagation(); setUploadingId(t.id); fileRef.current?.click(); }}
                            className="absolute inset-0 bg-black/50 rounded-lg opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                            <Upload size={12} className="text-white" />
                          </button>
                        </div>
                      </td>
                      <td className="p-2.5 font-medium text-gray-200 text-xs">
                        <div className="flex items-center gap-2">
                          {isExpanded ? <ChevronDown size={14} className="text-yellow-500" /> : <ChevronRight size={14} className="text-gray-500" />}
                          <span className="text-[10px] text-gray-600 font-mono">#{t.id}</span>
                          {t.businessName}
                        </div>
                      </td>
                      <td className="p-2.5" onClick={(e) => e.stopPropagation()}>
                        <select value={t.subscriptionType || ""}
                          onChange={(e) => {
                            const st = e.target.value;
                            const disc = (st === "CONSULENTI 1500" || st === "ENTI AUTORIZZATI 1500") ? 70 : st === "CONSULENTI 500" ? 60 : 0;
                            apiRequest("PATCH", `/api/tutors/${t.id}`, { subscriptionType: st, discountPercentage: disc }).then(() => qc.invalidateQueries({ queryKey: ["tutors"] }));
                          }}
                          className={`text-[10px] font-bold px-1.5 py-1 rounded cursor-pointer border-0 appearance-none ${subBadge(t.subscriptionType)}`}>
                          {SUB_TYPES.map((st) => <option key={st} value={st} className="bg-[#1a1a1a] text-gray-200">{st}</option>)}
                        </select>
                      </td>
                      <td className="p-2.5 text-green-400 font-bold text-xs">{t.discountPercentage != null ? `${t.discountPercentage}%` : "—"}</td>
                      <td className="p-2.5 text-gray-400 text-xs">{t.email || "—"}</td>
                      <td className="p-2.5" onClick={(e) => e.stopPropagation()}>
                        <select value={t.certificateType ?? 1}
                          onChange={(e) => {
                            apiRequest("PATCH", `/api/tutors/${t.id}`, { certificateType: parseInt(e.target.value) }).then(() => qc.invalidateQueries({ queryKey: ["tutors"] }));
                          }}
                          className={`text-[10px] font-bold px-1.5 py-1 rounded cursor-pointer border-0 appearance-none ${(t.certificateType ?? 1) === 1 ? "bg-cyan-600 text-white" : (t.certificateType ?? 1) === 2 ? "bg-orange-500 text-white" : "bg-pink-500 text-white"}`}>
                          <option value={1} className="bg-[#1a1a1a] text-gray-200">Tipo 1</option>
                          <option value={2} className="bg-[#1a1a1a] text-gray-200">Tipo 2</option>
                          <option value={3} className="bg-[#1a1a1a] text-gray-200">Tipo 3</option>
                        </select>
                      </td>
                      <td className="p-2.5 text-gray-400 text-xs">{t.phone || "—"}</td>
                      <td className="p-2.5 text-gray-400 text-xs">{t.city || "—"}</td>
                      <td className="p-2.5">
                        <span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${t.isActive ? "bg-green-500 text-white" : "bg-red-500 text-white"}`}>
                          {t.isActive ? "Attivo" : "Off"}
                        </span>
                      </td>
                      <td className="p-2.5">
                        <div className="flex items-center gap-1">
                          <button onClick={(e) => { e.stopPropagation(); openEdit(t); }} className="w-7 h-7 rounded bg-white/5 hover:bg-white/10 flex items-center justify-center text-blue-400">
                            <Edit2 size={13} />
                          </button>
                          <button onClick={(e) => { e.stopPropagation(); if (confirm(`Eliminare ${t.businessName}?`)) deleteMut.mutate(t.id); }}
                            className="w-7 h-7 rounded bg-white/5 hover:bg-red-500/20 flex items-center justify-center text-red-400">
                            <Trash2 size={13} />
                          </button>
                        </div>
                      </td>
                    </tr>
                    {isExpanded && (
                      <tr key={`admins-${t.id}`}>
                        <td colSpan={10} className="p-0">
                          <div className="bg-[#0d0d0d] border-t border-b border-yellow-500/30 px-6 py-4">
                            <div className="flex items-center justify-between mb-3">
                              <h4 className="text-xs font-bold text-yellow-500 flex items-center gap-2">
                                <User size={13} /> Amministratori di {t.businessName}
                              </h4>
                              <button onClick={() => setNewAdmin(newAdmin ? null : { name: "", email: "", username: "", fiscalCode: "" })}
                                className="h-6 px-3 bg-yellow-500 text-black rounded text-[10px] font-bold flex items-center gap-1 hover:bg-yellow-600">
                                <UserPlus size={11} /> Aggiungi
                              </button>
                            </div>
                            {admins.length === 0 && !newAdmin ? (
                              <p className="text-xs text-gray-600 italic">Nessun amministratore configurato</p>
                            ) : (
                              <table className="w-full text-xs">
                                <thead>
                                  <tr className="text-[10px] text-gray-500 uppercase">
                                    <th className="text-left pb-2 pr-4">Nome</th>
                                    <th className="text-left pb-2 pr-4">Username</th>
                                    <th className="text-left pb-2 pr-4">Email</th>
                                    <th className="text-left pb-2 pr-4">Codice Fiscale</th>
                                    <th className="text-left pb-2 w-16">Azioni</th>
                                  </tr>
                                </thead>
                                <tbody>
                                  {admins.map((a) => {
                                    const isEditing = editingAdmin?.id === a.id;
                                    return (
                                    <tr key={a.id} className="border-t border-white/5">
                                      {isEditing ? (<>
                                      <td className="py-2 pr-2"><input type="text" value={editingAdmin.name} onChange={(e) => setEditingAdmin({ ...editingAdmin, name: e.target.value })} className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200" /></td>
                                      <td className="py-2 pr-2"><input type="text" value={editingAdmin.username || ""} onChange={(e) => setEditingAdmin({ ...editingAdmin, username: e.target.value })} className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200" /></td>
                                      <td className="py-2 pr-2"><input type="email" value={editingAdmin.email || ""} onChange={(e) => setEditingAdmin({ ...editingAdmin, email: e.target.value })} className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200" /></td>
                                      <td className="py-2 pr-2"><input type="text" value={editingAdmin.fiscalCode || ""} onChange={(e) => setEditingAdmin({ ...editingAdmin, fiscalCode: e.target.value.toUpperCase() })} className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200 uppercase" /></td>
                                      <td className="py-2">
                                        <div className="flex gap-1">
                                          <button onClick={() => updateAdminMut.mutate({ id: editingAdmin.id, name: editingAdmin.name, email: editingAdmin.email || undefined, username: editingAdmin.username || undefined, fiscalCode: editingAdmin.fiscalCode || undefined })}
                                            className="w-6 h-6 rounded bg-green-500/20 hover:bg-green-500/40 flex items-center justify-center text-green-400"><Plus size={11} /></button>
                                          <button onClick={() => setEditingAdmin(null)}
                                            className="w-6 h-6 rounded bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400"><X size={11} /></button>
                                        </div>
                                      </td>
                                      </>) : (<>
                                      <td className="py-2 pr-4 text-gray-200 font-medium">{a.name}</td>
                                      <td className="py-2 pr-4 text-yellow-500 font-mono">{a.username || "—"}</td>
                                      <td className="py-2 pr-4 text-gray-400">{a.email || "—"}</td>
                                      <td className="py-2 pr-4 text-gray-400 font-mono">{a.fiscalCode || <span className="text-red-400">MANCANTE</span>}</td>
                                      <td className="py-2">
                                        <div className="flex gap-1">
                                          <button onClick={() => setEditingAdmin({ ...a })} title="Modifica"
                                            className="w-6 h-6 rounded bg-white/5 hover:bg-white/10 flex items-center justify-center text-yellow-400">
                                            <Edit2 size={11} />
                                          </button>
                                          {a.email && (
                                            <button onClick={() => sendAdminEmailMut.mutate({ adminId: a.id, email: a.email!, name: a.name, username: a.username || "", tutorName: t.businessName })}
                                              disabled={sendAdminEmailMut.isPending}
                                              title="Invia email credenziali"
                                              className="w-6 h-6 rounded bg-blue-500/20 hover:bg-blue-500/40 flex items-center justify-center text-blue-400 disabled:opacity-30">
                                              <Mail size={11} />
                                            </button>
                                          )}
                                          <button onClick={() => { if (confirm(`Rimuovere ${a.name}?`)) deleteAdminMut.mutate(a.id); }}
                                            className="w-6 h-6 rounded bg-white/5 hover:bg-red-500/20 flex items-center justify-center text-red-400">
                                            <Trash2 size={11} />
                                          </button>
                                        </div>
                                      </td>
                                      </>)}
                                    </tr>
                                    );
                                  })}
                                  {newAdmin && (
                                    <tr className="border-t border-yellow-500/20">
                                      <td className="py-2 pr-2">
                                        <input type="text" placeholder="Nome Cognome" value={newAdmin.name} onChange={(e) => { const v=e.target.value; const u=v.trim().toLowerCase().replace(/\s+/g,'.'); setNewAdmin({ ...newAdmin, name: v, username: u }); }}
                                          className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200" />
                                      </td>
                                      <td className="py-2 pr-2">
                                        <input type="text" placeholder="nome.cognome" value={newAdmin.username} onChange={(e) => setNewAdmin({ ...newAdmin, username: e.target.value })}
                                          className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200" />
                                      </td>
                                      <td className="py-2 pr-2">
                                        <input type="email" placeholder="email" value={newAdmin.email} onChange={(e) => setNewAdmin({ ...newAdmin, email: e.target.value })}
                                          className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200" />
                                      </td>
                                      <td className="py-2 pr-2">
                                        <input type="text" placeholder="CODICE FISCALE" value={newAdmin.fiscalCode} onChange={(e) => setNewAdmin({ ...newAdmin, fiscalCode: e.target.value.toUpperCase() })}
                                          className="w-full h-7 px-2 bg-[#141414] border border-white/10 rounded text-xs text-gray-200 uppercase" />
                                      </td>
                                      <td className="py-2">
                                        <div className="flex gap-1">
                                          <button onClick={() => { if (newAdmin.name && newAdmin.fiscalCode) addAdminMut.mutate({ tutorId: t.id, ...newAdmin }); }}
                                            disabled={!newAdmin.name || !newAdmin.fiscalCode}
                                            className="w-6 h-6 rounded bg-green-500/20 hover:bg-green-500/40 flex items-center justify-center text-green-400 disabled:opacity-30">
                                            <Plus size={11} />
                                          </button>
                                          <button onClick={() => setNewAdmin(null)}
                                            className="w-6 h-6 rounded bg-white/5 hover:bg-white/10 flex items-center justify-center text-gray-400">
                                            <X size={11} />
                                          </button>
                                        </div>
                                      </td>
                                    </tr>
                                  )}
                                </tbody>
                              </table>
                            )}
                          </div>
                        </td>
                      </tr>
                    )}
                  </>
                );
              })}
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
          <div className="fixed inset-0 bg-black/70" />
          <div className="relative bg-[#1a1a1a] border border-white/10 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between p-5 border-b border-white/10">
              <h2 className="text-lg font-bold text-yellow-500">{isNew ? "Nuovo Ente Formativo / Company" : "Modifica Ente"}</h2>
              <button onClick={() => setEditTutor(null)} className="text-gray-500 hover:text-gray-300"><X size={20} /></button>
            </div>

            <div className="p-5 space-y-5">
              {/* TIPO ENTE */}
              <div>
                <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-2">Tipo Ente</label>
                <div className="flex gap-4">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="radio" checked={editTutor.entityType === "ENTE FORMATIVO"} onChange={() => updateField("entityType", "ENTE FORMATIVO")}
                      className="w-4 h-4 text-yellow-500" />
                    <span className="text-sm text-gray-200 font-bold">ENTE FORMATIVO</span>
                  </label>
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="radio" checked={editTutor.entityType === "COMPANY"} onChange={() => updateField("entityType", "COMPANY")}
                      className="w-4 h-4 text-yellow-500" />
                    <span className="text-sm text-gray-200 font-bold">COMPANY</span>
                  </label>
                </div>
              </div>

              {/* DATI AZIENDALI */}
              <div>
                <h3 className="text-sm font-bold text-gray-300 mb-3 flex items-center gap-2">
                  <Building2 size={14} className="text-yellow-500" /> Dati Aziendali
                </h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div className="md:col-span-2">
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Ragione Sociale *</label>
                    <input type="text" value={editTutor.businessName || ""} onChange={(e) => updateField("businessName", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">P.IVA *</label>
                    <input type="text" value={editTutor.vatNumber || ""} onChange={(e) => updateField("vatNumber", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Codice Fiscale</label>
                    <input type="text" value={editTutor.fiscalCode || ""} onChange={(e) => updateField("fiscalCode", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div className="md:col-span-2">
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Indirizzo *</label>
                    <input type="text" value={editTutor.address || ""} onChange={(e) => updateField("address", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">CAP *</label>
                    <input type="text" value={editTutor.cap || ""} onChange={(e) => updateField("cap", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Città *</label>
                    <input type="text" value={editTutor.city || ""} onChange={(e) => updateField("city", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Provincia *</label>
                    <select value={editTutor.province || ""} onChange={(e) => updateField("province", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50">
                      <option value="">Seleziona una provincia</option>
                      {PROVINCE.map((p) => <option key={p} value={p}>{p}</option>)}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Telefono</label>
                    <input type="tel" value={editTutor.phone || ""} onChange={(e) => updateField("phone", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">E-mail *</label>
                    <input type="email" value={editTutor.email || ""} onChange={(e) => updateField("email", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                </div>
              </div>

              {/* AUTORIZZAZIONE REGIONALE */}
              <div className="bg-purple-500/10 border border-purple-500/30 rounded-xl p-4">
                <div className="flex items-center gap-4 mb-3">
                  <label className="text-sm font-bold text-purple-400">Autorizzazione Regionale</label>
                  <div className="flex gap-3">
                    <label className="flex items-center gap-1.5 cursor-pointer">
                      <input type="radio" checked={!editTutor.hasRegionalAuth} onChange={() => updateField("hasRegionalAuth", false)}
                        className="w-3.5 h-3.5 text-purple-500" />
                      <span className="text-xs text-gray-300">No</span>
                    </label>
                    <label className="flex items-center gap-1.5 cursor-pointer">
                      <input type="radio" checked={!!editTutor.hasRegionalAuth} onChange={() => updateField("hasRegionalAuth", true)}
                        className="w-3.5 h-3.5 text-purple-500" />
                      <span className="text-xs text-gray-300">Si</span>
                    </label>
                  </div>
                  {editTutor.hasRegionalAuth && (
                    <input type="text" placeholder="Numero autorizzazione" value={editTutor.regionalAuthorization || ""} onChange={(e) => updateField("regionalAuthorization", e.target.value)}
                      className="flex-1 h-8 px-3 bg-[#141414] border border-white/10 rounded text-xs text-gray-200 focus:outline-none focus:border-purple-500/50" />
                  )}
                </div>
                <div>
                  <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Soggetto Formatore Autorizzato</label>
                  <textarea value={editTutor.authorizedTrainer || ""} onChange={(e) => updateField("authorizedTrainer", e.target.value)} rows={2}
                    className="w-full px-3 py-2 bg-[#141414] border border-white/10 rounded-lg text-xs text-gray-200 focus:outline-none focus:border-purple-500/50 resize-none" />
                </div>
                <div className="mt-3">
                  <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Codice Ateco</label>
                  <input type="text" value={editTutor.atecoCode || ""} onChange={(e) => updateField("atecoCode", e.target.value)}
                    className="w-full h-8 px-3 bg-[#141414] border border-white/10 rounded-lg text-xs text-gray-200 focus:outline-none focus:border-purple-500/50" />
                </div>
              </div>

              {/* AMMINISTRATORE */}
              <div className="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                  <h3 className="text-sm font-bold text-blue-400 mb-3 flex items-center gap-2">
                    <User size={14} /> Amministratore Ente Formativo
                  </h3>
                  <div className="flex gap-3 mb-3">
                    <label className="flex items-center gap-1.5 cursor-pointer">
                      <input type="radio" checked={adminMode === "new"} onChange={() => setAdminMode("new")}
                        className="w-3.5 h-3.5 text-blue-500" />
                      <span className="text-xs text-gray-300">Crea nuovo utente</span>
                    </label>
                    <label className="flex items-center gap-1.5 cursor-pointer">
                      <input type="radio" checked={adminMode === "existing"} onChange={() => setAdminMode("existing")}
                        className="w-3.5 h-3.5 text-blue-500" />
                      <span className="text-xs text-gray-300">Usa utente esistente</span>
                    </label>
                  </div>
                  {adminMode === "new" && (
                    <>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                          <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Nome *</label>
                          <input type="text" value={modalAdmin.firstName} onChange={(e) => setModalAdmin({ ...modalAdmin, firstName: e.target.value })}
                            className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-blue-500/50" />
                        </div>
                        <div>
                          <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Cognome *</label>
                          <input type="text" value={modalAdmin.lastName} onChange={(e) => setModalAdmin({ ...modalAdmin, lastName: e.target.value })}
                            className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-blue-500/50" />
                        </div>
                        <div>
                          <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Codice Fiscale *</label>
                          <input type="text" value={modalAdmin.fiscalCode} onChange={(e) => setModalAdmin({ ...modalAdmin, fiscalCode: e.target.value.toUpperCase() })}
                            className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-blue-500/50 uppercase" />
                        </div>
                        <div>
                          <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Email *</label>
                          <input type="email" value={modalAdmin.email} onChange={(e) => setModalAdmin({ ...modalAdmin, email: e.target.value })}
                            className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-blue-500/50" />
                        </div>
                      </div>
                      <label className="flex items-center gap-2 cursor-pointer mt-3">
                        <input type="checkbox" checked={modalAdmin.sendEmail} onChange={(e) => setModalAdmin({ ...modalAdmin, sendEmail: e.target.checked })}
                          className="w-4 h-4 rounded border-gray-600 text-blue-500 focus:ring-blue-400" />
                        <span className="text-xs text-gray-300">Invia mail di registrazione all'utente</span>
                      </label>
                    </>
                  )}
                </div>

              {/* PIANO DI ABBONAMENTO */}
              <div className="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
                <h3 className="text-sm font-bold text-yellow-500 mb-3">Piano di Abbonamento</h3>
                <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Piano Tipo</label>
                    <select value={editTutor.subscriptionType || ""} onChange={(e) => {
                      const st = e.target.value;
                      updateField("subscriptionType", st);
                      const autoDiscount = (st === "CONSULENTI 1500" || st === "ENTI AUTORIZZATI 1500") ? 70 : st === "CONSULENTI 500" ? 60 : 0;
                      updateField("discountPercentage", autoDiscount);
                    }}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50">
                      {SUB_TYPES.map((t) => <option key={t} value={t}>{t}</option>)}
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Inizio Validità</label>
                    <input type="date" value={editTutor.subscriptionStart || ""} onChange={(e) => updateField("subscriptionStart", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Fine Validità</label>
                    <input type="date" value={editTutor.subscriptionEnd || ""} onChange={(e) => updateField("subscriptionEnd", e.target.value)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Canone Annuo (€)</label>
                    <input type="number" min={0} value={editTutor.annualFee ?? 0} onChange={(e) => updateField("annualFee", parseInt(e.target.value) || 0)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Sconto Corsi (%)</label>
                    <input type="number" min={0} max={100} value={editTutor.discountPercentage ?? 60} onChange={(e) => updateField("discountPercentage", parseInt(e.target.value) || 0)}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">Tipo Attestato</label>
                    <select value={editTutor.certificateType ?? 1} onChange={(e) => updateField("certificateType", parseInt(e.target.value))}
                      className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50">
                      <option value={1}>Tipo 1</option>
                      <option value={2}>Tipo 2</option>
                      <option value={3}>Tipo 3</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-[11px] font-semibold text-gray-400 uppercase mb-1">E-commerce</label>
                    <div className="flex gap-3 h-9 items-center">
                      <label className="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" checked={!editTutor.ecommerce} onChange={() => updateField("ecommerce", false)}
                          className="w-3.5 h-3.5 text-yellow-500" />
                        <span className="text-xs text-gray-300">NO</span>
                      </label>
                      <label className="flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" checked={!!editTutor.ecommerce} onChange={() => updateField("ecommerce", true)}
                          className="w-3.5 h-3.5 text-yellow-500" />
                        <span className="text-xs text-gray-300">SI</span>
                      </label>
                    </div>
                  </div>
                </div>
              </div>

              {/* STATO + NOTE */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Note</label>
                  <textarea value={editTutor.notes || ""} onChange={(e) => updateField("notes", e.target.value)} rows={2}
                    className="w-full px-3 py-2 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50 resize-none" />
                </div>
                <div className="flex items-end">
                  <label className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={editTutor.isActive ?? true} onChange={(e) => updateField("isActive", e.target.checked)}
                      className="w-4 h-4 rounded border-gray-600 text-yellow-500 focus:ring-yellow-400" />
                    <span className="text-sm text-gray-300 font-medium">Ente Attivo</span>
                  </label>
                </div>
              </div>
            </div>

            <div className="flex items-center justify-end gap-3 p-5 border-t border-white/10">
              <button onClick={() => setEditTutor(null)} className="h-10 px-5 border border-white/10 rounded-lg text-sm text-gray-400 hover:bg-white/5">Annulla</button>
              <button onClick={async () => {
                const result = await saveMut.mutateAsync(editTutor);
                if (isNew && adminMode === "new" && modalAdmin.firstName && modalAdmin.lastName && modalAdmin.fiscalCode) {
                  const tutorId = (result as any)?.id;
                  if (tutorId) {
                    const username = `${modalAdmin.firstName}.${modalAdmin.lastName}`.toLowerCase().replace(/\s+/g, "");
                    await addAdminMut.mutateAsync({ tutorId, name: `${modalAdmin.firstName} ${modalAdmin.lastName}`, email: modalAdmin.email, username, fiscalCode: modalAdmin.fiscalCode });
                    if (modalAdmin.sendEmail && modalAdmin.email) {
                      await sendAdminEmailMut.mutateAsync({ adminId: 0, email: modalAdmin.email, name: `${modalAdmin.firstName} ${modalAdmin.lastName}`, username, tutorName: editTutor.businessName || "" });
                    }
                  }
                }
              }} disabled={saveMut.isPending || !editTutor.businessName?.trim()}
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
