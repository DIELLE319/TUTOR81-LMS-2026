import { useState } from "react";
import { useMutation, useQuery } from "@tanstack/react-query";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/hooks/use-auth";
import { useLocation } from "wouter";

export default function CreateCompany() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [, navigate] = useLocation();

  const [formData, setFormData] = useState({
    businessName: "", vatNumber: "", fiscalCode: "", address: "", city: "", cap: "", province: "",
    phone: "", email: "", pec: "", contactPerson: "", notes: "", tutorId: 0,
  });

  const { data: tutors = [] } = useQuery<{ id: number; businessName: string }[]>({
    queryKey: ["tutors-for-companies"],
    queryFn: () => fetch("/api/tutors-for-companies", { credentials: "include" }).then((r) => r.json()),
    enabled: (user?.role ?? 0) >= 1000,
  });

  const createMut = useMutation({
    mutationFn: (data: typeof formData & { isTutor: boolean }) => apiRequest("POST", "/api/companies", data),
    onSuccess: () => {
      toast({ title: "Azienda creata con successo" });
      queryClient.invalidateQueries({ queryKey: ["clients"] });
      queryClient.invalidateQueries({ queryKey: ["companies"] });
      navigate("/clients");
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.businessName.trim()) { toast({ title: "Inserisci la ragione sociale", variant: "destructive" }); return; }
    createMut.mutate({ ...formData, isTutor: false });
  };

  const update = (field: string, value: string | number) => setFormData((p) => ({ ...p, [field]: value }));

  return (
    <div className="max-w-2xl mx-auto">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Nuova Azienda</h1>
      <form onSubmit={handleSubmit} className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
        {(user?.role ?? 0) >= 1000 && tutors.length > 0 && (
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Ente Formativo</label>
            <select value={formData.tutorId} onChange={(e) => update("tutorId", parseInt(e.target.value))} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm">
              <option value={0}>Seleziona ente...</option>
              {tutors.map((t) => <option key={t.id} value={t.id}>{t.businessName}</option>)}
            </select>
          </div>
        )}
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Ragione Sociale *</label>
          <input type="text" value={formData.businessName} onChange={(e) => update("businessName", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" required />
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">P. IVA</label>
            <input type="text" value={formData.vatNumber} onChange={(e) => update("vatNumber", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Codice Fiscale</label>
            <input type="text" value={formData.fiscalCode} onChange={(e) => update("fiscalCode", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Indirizzo</label>
          <input type="text" value={formData.address} onChange={(e) => update("address", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
        </div>
        <div className="grid grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Città</label>
            <input type="text" value={formData.city} onChange={(e) => update("city", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">CAP</label>
            <input type="text" value={formData.cap} onChange={(e) => update("cap", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Provincia</label>
            <input type="text" value={formData.province} onChange={(e) => update("province", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" maxLength={2} />
          </div>
        </div>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Telefono</label>
            <input type="text" value={formData.phone} onChange={(e) => update("phone", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" value={formData.email} onChange={(e) => update("email", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
          </div>
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">PEC</label>
          <input type="email" value={formData.pec} onChange={(e) => update("pec", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Referente</label>
          <input type="text" value={formData.contactPerson} onChange={(e) => update("contactPerson", e.target.value)} className="w-full h-10 border border-gray-300 rounded-lg px-3 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
        </div>
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <textarea value={formData.notes} onChange={(e) => update("notes", e.target.value)} rows={3} className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
        </div>
        <div className="flex gap-3 pt-2">
          <button type="submit" disabled={createMut.isPending} className="h-10 px-6 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg text-sm disabled:opacity-50">
            {createMut.isPending ? "Creazione..." : "Crea Azienda"}
          </button>
          <button type="button" onClick={() => navigate("/clients")} className="h-10 px-6 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Annulla</button>
        </div>
      </form>
    </div>
  );
}
