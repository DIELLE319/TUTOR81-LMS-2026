import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Search, Users as UsersIcon } from "lucide-react";

interface Student { id: number; firstName: string | null; lastName: string | null; email: string; fiscalCode: string | null; phone: string | null; companyName: string; isActive: boolean | null }

export default function Users() {
  const [search, setSearch] = useState("");
  const { data: students = [], isLoading } = useQuery<Student[]>({
    queryKey: ["students"],
    queryFn: () => fetch("/api/students", { credentials: "include" }).then((r) => r.json()),
  });

  const filtered = students.filter((s) => {
    if (!search) return true;
    const q = search.toLowerCase();
    return (s.firstName || "").toLowerCase().includes(q) || (s.lastName || "").toLowerCase().includes(q) || s.email.toLowerCase().includes(q) || (s.fiscalCode || "").toLowerCase().includes(q) || s.companyName.toLowerCase().includes(q);
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-4">Utenti</h1>
      <div className="relative mb-4">
        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cerca utente..." className="w-full h-10 pl-9 pr-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
      </div>
      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun utente trovato</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-x-auto">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 border-b"><tr>
              <th className="p-3 text-left font-medium text-gray-600">Nome</th>
              <th className="p-3 text-left font-medium text-gray-600">Email</th>
              <th className="p-3 text-left font-medium text-gray-600">Codice Fiscale</th>
              <th className="p-3 text-left font-medium text-gray-600">Azienda</th>
              <th className="p-3 text-left font-medium text-gray-600">Stato</th>
            </tr></thead>
            <tbody>
              {filtered.map((s) => (
                <tr key={s.id} className="border-b last:border-0 hover:bg-gray-50">
                  <td className="p-3 font-medium text-gray-900">{s.lastName} {s.firstName}</td>
                  <td className="p-3 text-gray-600">{s.email}</td>
                  <td className="p-3 text-gray-500 font-mono text-xs">{s.fiscalCode || "—"}</td>
                  <td className="p-3 text-gray-600">{s.companyName}</td>
                  <td className="p-3"><span className={`text-xs font-semibold px-2 py-0.5 rounded ${s.isActive ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"}`}>{s.isActive ? "Attivo" : "Disattivo"}</span></td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
