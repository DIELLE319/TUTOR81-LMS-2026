import { useQuery } from "@tanstack/react-query";
import { FileText } from "lucide-react";

interface Sale { id: number; tutorName: string | null; companyName: string | null; courseTitle: string | null; qta: number; price: string; creationDate: string | null; code: string | null; invoiced: boolean | null }

export default function Sales() {
  const { data: sales = [], isLoading } = useQuery<Sale[]>({
    queryKey: ["sales"],
    queryFn: () => fetch("/api/sales", { credentials: "include" }).then((r) => r.json()),
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Vendite</h1>
      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : sales.length === 0 ? <div className="text-center py-12 text-gray-500">Nessuna vendita</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b"><tr>
              <th className="p-3 text-left font-medium text-gray-600">Data</th>
              <th className="p-3 text-left font-medium text-gray-600">Ente</th>
              <th className="p-3 text-left font-medium text-gray-600">Azienda</th>
              <th className="p-3 text-left font-medium text-gray-600">Corso</th>
              <th className="p-3 text-left font-medium text-gray-600">Qtà</th>
              <th className="p-3 text-left font-medium text-gray-600">Prezzo</th>
              <th className="p-3 text-left font-medium text-gray-600">Fatt.</th>
            </tr></thead>
            <tbody>
              {sales.map((s) => (
                <tr key={s.id} className="border-b last:border-0 hover:bg-gray-50">
                  <td className="p-3 text-gray-500 text-xs">{s.creationDate ? new Date(s.creationDate).toLocaleDateString("it-IT") : "—"}</td>
                  <td className="p-3 text-gray-700">{s.tutorName || "—"}</td>
                  <td className="p-3 text-gray-700">{s.companyName || "—"}</td>
                  <td className="p-3 text-gray-900 font-medium">{s.courseTitle || "—"}</td>
                  <td className="p-3 text-gray-600">{s.qta}</td>
                  <td className="p-3 text-gray-600">€{s.price}</td>
                  <td className="p-3"><span className={`text-xs font-semibold px-2 py-0.5 rounded ${s.invoiced ? "bg-green-100 text-green-700" : "bg-gray-100 text-gray-500"}`}>{s.invoiced ? "Sì" : "No"}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
