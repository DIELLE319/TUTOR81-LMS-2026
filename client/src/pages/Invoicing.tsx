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
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Fatturazione</h1>
      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : invoices.length === 0 ? (
        <div className="bg-white rounded-xl border border-gray-200 p-12 text-center">
          <FileText size={48} className="mx-auto text-gray-300 mb-4" />
          <p className="text-gray-500">Nessuna fattura presente</p>
        </div>
      ) : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b"><tr>
              <th className="p-3 text-left font-medium text-gray-600">N. Fattura</th>
              <th className="p-3 text-left font-medium text-gray-600">Ente</th>
              <th className="p-3 text-left font-medium text-gray-600">Periodo</th>
              <th className="p-3 text-left font-medium text-gray-600">Totale</th>
              <th className="p-3 text-left font-medium text-gray-600">Stato</th>
            </tr></thead>
            <tbody>
              {invoices.map((inv) => (
                <tr key={inv.id} className="border-b last:border-0 hover:bg-gray-50">
                  <td className="p-3 font-medium text-gray-900">{inv.invoiceNumber}/{inv.invoiceYear}</td>
                  <td className="p-3 text-gray-700">{inv.tutorCompanyName || "—"}</td>
                  <td className="p-3 text-gray-500">{inv.monthReference}/{inv.yearReference}</td>
                  <td className="p-3 text-gray-900 font-medium">€{inv.totalAmount}</td>
                  <td className="p-3"><span className={`text-xs font-semibold px-2 py-0.5 rounded ${inv.status === "sent" ? "bg-green-100 text-green-700" : "bg-yellow-100 text-yellow-700"}`}>{inv.status === "sent" ? "Inviata" : "Bozza"}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
