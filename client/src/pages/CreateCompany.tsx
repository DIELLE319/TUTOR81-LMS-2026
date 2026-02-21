import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { apiRequest, queryClient } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { useAuth } from "@/hooks/use-auth";
import { useLocation } from "wouter";
import { X, Save } from "lucide-react";

const inputCls = "w-full h-9 bg-[#1a1a1a] border border-white/10 rounded px-3 text-sm text-gray-200 placeholder-gray-600 focus:outline-none focus:border-yellow-500/50";

export default function CreateCompany() {
  const { user } = useAuth();
  const { toast } = useToast();
  const [, navigate] = useLocation();

  const [formData, setFormData] = useState({
    businessName: "", vatNumber: "", fiscalCode: "", address: "", city: "", cap: "", province: "",
    phone: "", email: "", pec: "", contactPerson: "", contactEmail: "", contactPhone: "", notes: "",
  });

  const createMut = useMutation({
    mutationFn: (data: Record<string, any>) => apiRequest("POST", "/api/companies", data),
    onSuccess: () => {
      toast({ title: "Cliente creato con successo" });
      queryClient.invalidateQueries({ queryKey: ["clients"] });
      queryClient.invalidateQueries({ queryKey: ["companies"] });
      navigate("/clients");
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.businessName.trim()) { toast({ title: "Inserisci la ragione sociale", variant: "destructive" }); return; }
    createMut.mutate(formData);
  };

  const update = (field: string, value: string | number) => setFormData((p) => ({ ...p, [field]: value }));

  return (
    <div className="max-w-4xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-xl font-bold text-yellow-500">Crea Cliente</h1>
        <button onClick={() => navigate("/clients")} className="h-8 px-4 border border-red-500/50 text-red-400 rounded-lg text-xs flex items-center gap-1.5 hover:bg-red-500/10">
          <X size={13} />Chiudi
        </button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* DATI AZIENDA */}
        <div className="border border-yellow-500/30 rounded-xl overflow-hidden">
          <div className="bg-yellow-500/10 px-5 py-2">
            <h2 className="text-sm font-bold text-yellow-500 uppercase">Dati Azienda</h2>
          </div>
          <div className="bg-[#141414] p-5 space-y-4">
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs text-gray-400 mb-1">Ragione Sociale *</label>
                <input type="text" value={formData.businessName} onChange={(e) => update("businessName", e.target.value)} placeholder="Nome azienda" className={inputCls} required />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">P.IVA</label>
                <input type="text" value={formData.vatNumber} onChange={(e) => update("vatNumber", e.target.value)} placeholder="12345678901" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">Codice Fiscale</label>
                <input type="text" value={formData.fiscalCode} onChange={(e) => update("fiscalCode", e.target.value)} placeholder="RSSMRA80A01H501U" className={inputCls} />
              </div>
            </div>
            <div className="grid grid-cols-[1fr_1fr_80px_100px] gap-4">
              <div>
                <label className="block text-xs text-gray-400 mb-1">Indirizzo</label>
                <input type="text" value={formData.address} onChange={(e) => update("address", e.target.value)} placeholder="Via Roma, 1" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">Città</label>
                <input type="text" value={formData.city} onChange={(e) => update("city", e.target.value)} placeholder="Milano" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">Prov.</label>
                <input type="text" value={formData.province} onChange={(e) => update("province", e.target.value)} placeholder="MI" className={inputCls} maxLength={2} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">CAP</label>
                <input type="text" value={formData.cap} onChange={(e) => update("cap", e.target.value)} placeholder="20100" className={inputCls} />
              </div>
            </div>
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs text-gray-400 mb-1">Telefono</label>
                <input type="text" value={formData.phone} onChange={(e) => update("phone", e.target.value)} placeholder="02 12345678" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">Email</label>
                <input type="email" value={formData.email} onChange={(e) => update("email", e.target.value)} placeholder="info@azienda.it" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">PEC</label>
                <input type="email" value={formData.pec} onChange={(e) => update("pec", e.target.value)} placeholder="azienda@pec.it" className={inputCls} />
              </div>
            </div>
          </div>
        </div>

        {/* REFERENTE AZIENDALE */}
        <div className="border border-yellow-500/30 rounded-xl overflow-hidden">
          <div className="bg-yellow-500/10 px-5 py-2">
            <h2 className="text-sm font-bold text-yellow-500 uppercase">Referente Aziendale</h2>
          </div>
          <div className="bg-[#141414] p-5">
            <div className="grid grid-cols-3 gap-4">
              <div>
                <label className="block text-xs text-gray-400 mb-1">Nome e Cognome</label>
                <input type="text" value={formData.contactPerson} onChange={(e) => update("contactPerson", e.target.value)} placeholder="Mario Rossi" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">Email</label>
                <input type="email" value={formData.contactEmail} onChange={(e) => update("contactEmail", e.target.value)} placeholder="mario.rossi@azienda.it" className={inputCls} />
              </div>
              <div>
                <label className="block text-xs text-gray-400 mb-1">Telefono</label>
                <input type="text" value={formData.contactPhone} onChange={(e) => update("contactPhone", e.target.value)} placeholder="333 1234567" className={inputCls} />
              </div>
            </div>
          </div>
        </div>

        {/* Submit */}
        <div className="flex justify-end">
          <button type="submit" disabled={createMut.isPending}
            className="h-10 px-6 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg text-sm flex items-center gap-2 disabled:opacity-50">
            <Save size={15} />
            {createMut.isPending ? "Salvataggio..." : "Salva Cliente"}
          </button>
        </div>
      </form>
    </div>
  );
}
