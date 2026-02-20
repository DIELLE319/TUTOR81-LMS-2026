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
      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : invoices.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <FileText size={48} className="mx-auto text-gray-300 mb-4" />
          <p className="text-gray-500">Nessuna fattura presente</p>
        </div>
      ) : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                <th className="p-3 text-left w-12">ID</th>
                <th className="p-3 text-left">N. Fattura</th>
                <th className="p-3 text-left">Ente</th>
                <th className="p-3 text-left">Periodo</th>
                <th className="p-3 text-left">Totale</th>
                <th className="p-3 text-left">Stato</th>
              </tr>
            </thead>
            <tbody>
              {invoices.map((inv) => (
                <tr key={inv.id} className="border-b border-gray-100 hover:bg-gray-50">
                  <td className="p-3 text-gray-400 font-mono text-xs">{inv.id}</td>
                  <td className="p-3 font-medium text-gray-900 text-xs">{inv.invoiceNumber}/{inv.invoiceYear}</td>
                  <td className="p-3 text-gray-700 text-xs">{inv.tutorCompanyName || "—"}</td>
                  <td className="p-3 text-gray-500 text-xs">{inv.monthReference}/{inv.yearReference}</td>
                  <td className="p-3 text-green-600 font-bold text-xs">€{inv.totalAmount}</td>
                  <td className="p-3">
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
