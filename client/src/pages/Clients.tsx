import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link } from "wouter";
import { Building2, ChevronDown, ChevronRight, Plus, Search } from "lucide-react";

interface Client { id: number; businessName: string; city: string | null; phone: string | null; email: string | null; contactPerson: string | null }
interface TutorGroup { tutor: { id: number; businessName: string; subscriptionType: string | null }; clients: Client[] }

export default function Clients() {
  const [search, setSearch] = useState("");
  const [expanded, setExpanded] = useState<number[]>([]);

  const { data: groups = [], isLoading } = useQuery<TutorGroup[]>({
    queryKey: ["clients"],
    queryFn: () => fetch("/api/clients", { credentials: "include" }).then((r) => r.json()),
  });

  const toggle = (id: number) => setExpanded((p) => p.includes(id) ? p.filter((x) => x !== id) : [...p, id]);

  const filtered = groups.map((g) => ({
    ...g,
    clients: g.clients.filter((c) => !search || c.businessName.toLowerCase().includes(search.toLowerCase()) || c.contactPerson?.toLowerCase().includes(search.toLowerCase())),
  })).filter((g) => g.clients.length > 0);

  return (
    <div>
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-2xl font-bold text-gray-900">Elenco Clienti</h1>
        <Link href="/create-company" className="h-10 px-4 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg text-sm flex items-center gap-2">
          <Plus size={16} />Nuova Azienda
        </Link>
      </div>
      <div className="relative mb-4">
        <Search size={16} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
        <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Cerca azienda..." className="w-full h-10 pl-9 pr-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" />
      </div>
      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun cliente trovato</div> : (
        <div className="space-y-3">
          {filtered.map((g) => (
            <div key={g.tutor.id} className="bg-white rounded-xl border border-gray-200 overflow-hidden">
              <button onClick={() => toggle(g.tutor.id)} className="w-full flex items-center justify-between px-5 py-4 hover:bg-gray-50">
                <div className="flex items-center gap-3">
                  <Building2 size={18} className="text-yellow-500" />
                  <span className="font-bold text-gray-900">{g.tutor.businessName}</span>
                  <span className="text-xs text-gray-500">({g.clients.length} clienti)</span>
                </div>
                {expanded.includes(g.tutor.id) ? <ChevronDown size={18} className="text-gray-400" /> : <ChevronRight size={18} className="text-gray-400" />}
              </button>
              {expanded.includes(g.tutor.id) && (
                <div className="border-t">
                  <table className="w-full text-sm">
                    <thead className="bg-gray-50"><tr>
                      <th className="p-3 text-left font-medium text-gray-600">Azienda</th>
                      <th className="p-3 text-left font-medium text-gray-600">Referente</th>
                      <th className="p-3 text-left font-medium text-gray-600">Email</th>
                      <th className="p-3 text-left font-medium text-gray-600">Tel</th>
                    </tr></thead>
                    <tbody>
                      {g.clients.map((c) => (
                        <tr key={c.id} className="border-b last:border-0 hover:bg-gray-50">
                          <td className="p-3 font-medium text-gray-900">{c.businessName}</td>
                          <td className="p-3 text-gray-600">{c.contactPerson || "—"}</td>
                          <td className="p-3 text-gray-600">{c.email || "—"}</td>
                          <td className="p-3 text-gray-600">{c.phone || "—"}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
