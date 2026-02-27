import { useQuery } from "@tanstack/react-query";
import { FileText } from "lucide-react";

interface Invoice { id: number; invoiceNumber: number; invoiceYear: number; tutorCompanyName: string | null; monthReference: number; yearReference: number; totalAmount: string; status: string | null; createdAt: string | null }

export default function Invoicing() {
  const { data: invoices = [], isLoading } = useQuery<Invoice[]>({
    queryKey: ["invoices"],
    queryFn: () => fetch("/api/invoices", { credentials: "include" }).then((r) => r.json()),
  });

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Fatturazione</h1>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : invoices.length === 0 ? (
        <div className="bg-[#141414] rounded-xl border border-white/5 p-12 text-center">
          <FileText size={48} className="mx-auto text-gray-600 mb-4" />
          <p className="text-gray-500">Nessuna fattura presente</p>
        </div>
      ) : (
        <div className="bg-[#141414] rounded-xl border border-white/5 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                <th className="p-2.5 text-left w-12">ID</th>
                <th className="p-2.5 text-left">N. Fattura</th>
                <th className="p-2.5 text-left">Ente</th>
                <th className="p-2.5 text-left">Periodo</th>
                <th className="p-2.5 text-left">Totale</th>
                <th className="p-2.5 text-left">Stato</th>
              </tr>
            </thead>
            <tbody>
              {invoices.map((inv, i) => (
                <tr key={inv.id} className={`border-b border-white/5 ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                  <td className="p-2.5 text-yellow-500 font-bold text-xs">{inv.id}</td>
                  <td className="p-2.5 font-medium text-gray-200 text-xs">{inv.invoiceNumber}/{inv.invoiceYear}</td>
                  <td className="p-2.5 text-gray-400 text-xs">{inv.tutorCompanyName || "—"}</td>
                  <td className="p-2.5 text-gray-400 text-xs">{inv.monthReference}/{inv.yearReference}</td>
                  <td className="p-2.5 text-green-400 font-bold text-xs">€{inv.totalAmount}</td>
                  <td className="p-2.5">
                    <span className={`text-[11px] font-bold px-2 py-0.5 rounded ${inv.status === "sent" ? "bg-green-500 text-white" : "bg-yellow-500 text-black"}`}>
                      {inv.status === "sent" ? "Inviata" : "Bozza"}
                    </span>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
