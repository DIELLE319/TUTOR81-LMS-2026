import { useQuery } from "@tanstack/react-query";
import type { ReactNode } from "react";

type Company = {
  id: number;
  tutorId: number | null;
  businessName: string;
  vatNumber: string | null;
  address: string | null;
  city: string | null;
  email: string | null;
  phone: string | null;
  isActive: boolean | null;
  createdAt: string | Date | null;
};

export default function MyCompany() {
  const { data: company, isLoading, error } = useQuery<Company>({
    queryKey: ["/api/me/company"],
  });

  if (isLoading) {
    return (
      <div className="p-6 bg-black min-h-screen">
        <div className="text-gray-400">Caricamento...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 bg-black min-h-screen">
        <h1 className="text-2xl font-bold text-yellow-400">La Mia Azienda</h1>
        <div className="mt-4 text-red-400">Errore nel caricamento.</div>
      </div>
    );
  }

  if (!company) {
    return (
      <div className="p-6 bg-black min-h-screen">
        <h1 className="text-2xl font-bold text-yellow-400">La Mia Azienda</h1>
        <div className="mt-4 text-gray-400">Nessuna azienda associata.</div>
      </div>
    );
  }

  const InfoRow = ({ label, value }: { label: string; value: ReactNode }) => (
    <div className="flex items-start gap-3 py-2 border-b border-gray-800">
      <div className="w-40 text-xs uppercase tracking-wider text-gray-500 font-bold">{label}</div>
      <div className="text-gray-200 text-sm break-words">{value || <span className="text-gray-500">-</span>}</div>
    </div>
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <h1 className="text-2xl font-bold text-yellow-400" data-testid="text-page-title">
        La Mia Azienda
      </h1>

      <div className="mt-6 max-w-3xl rounded-xl border border-gray-800 bg-[#111] overflow-hidden">
        <div className="p-4 border-b border-gray-800">
          <div className="text-lg font-bold text-white">{company.businessName}</div>
          <div className="text-xs text-gray-500">ID: {company.id}</div>
        </div>

        <div className="p-4">
          <InfoRow label="P. IVA" value={company.vatNumber} />
          <InfoRow label="Email" value={company.email} />
          <InfoRow label="Telefono" value={company.phone} />
          <InfoRow label="Città" value={company.city} />
          <InfoRow label="Indirizzo" value={company.address} />
          <InfoRow label="Stato" value={company.isActive ? "Attiva" : "Non attiva"} />
        </div>
      </div>
    </div>
  );
}
