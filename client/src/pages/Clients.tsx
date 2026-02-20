import { useState, useMemo, useEffect } from "react";
import { useQuery } from "@tanstack/react-query";
import { Link } from "wouter";
import { ChevronDown, ChevronRight, Plus, Search, Download } from "lucide-react";

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

  const filtered = useMemo(() => groups.map((g) => ({
    ...g,
    clients: g.clients.filter((c) => !search || c.businessName.toLowerCase().includes(search.toLowerCase()) || c.contactPerson?.toLowerCase().includes(search.toLowerCase())),
  })).filter((g) => g.clients.length > 0), [groups, search]);

  useEffect(() => {
    if (filtered.length > 0 && expanded.length === 0) setExpanded(filtered.map((g) => g.tutor.id));
  }, [filtered.length]);

  const totalClients = filtered.reduce((sum, g) => sum + g.clients.length, 0);

  return (
    <div>
      {/* Filter bar */}
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[200px]">
          <label className="block text-xs text-gray-300 mb-1">Cerca azienda</label>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
            <input type="text" value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nome azienda o referente..."
              className="w-full h-9 pl-9 pr-3 bg-white border-0 rounded text-sm text-gray-900" />
          </div>
        </div>
        <div className="text-xs text-gray-300 self-center">{totalClients} clienti in {filtered.length} enti</div>
        <Link href="/create-company" className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
          <Plus size={14} />Nuova Azienda
        </Link>
      </div>

      {isLoading ? <div className="text-center py-12 text-gray-500">Caricamento...</div> : filtered.length === 0 ? <div className="text-center py-12 text-gray-500">Nessun cliente trovato</div> : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
          {filtered.map((g) => (
            <div key={g.tutor.id}>
              <button onClick={() => toggle(g.tutor.id)}
                className="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200 hover:bg-gray-100 text-left">
                {expanded.includes(g.tutor.id) ? <ChevronDown size={16} className="text-gray-500" /> : <ChevronRight size={16} className="text-gray-500" />}
                <span className="font-bold text-gray-800 text-sm">{g.tutor.businessName}</span>
                <span className="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">{g.clients.length} clienti</span>
                {g.tutor.subscriptionType && <span className="text-[11px] font-bold px-2 py-0.5 rounded bg-yellow-500 text-black">{g.tutor.subscriptionType}</span>}
              </button>
              {expanded.includes(g.tutor.id) && (
                <table className="w-full text-sm">
                  <thead>
                    <tr className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
                      <th className="p-3 text-left w-12">ID</th>
                      <th className="p-3 text-left">Azienda</th>
                      <th className="p-3 text-left">Referente</th>
                      <th className="p-3 text-left">Email</th>
                      <th className="p-3 text-left">Telefono</th>
                      <th className="p-3 text-left">Città</th>
                    </tr>
                  </thead>
                  <tbody>
                    {g.clients.map((c) => (
                      <tr key={c.id} className="border-b border-gray-100 hover:bg-gray-50">
                        <td className="p-3 text-gray-400 font-mono text-xs">{c.id}</td>
                        <td className="p-3 font-medium text-gray-900 text-xs">{c.businessName}</td>
                        <td className="p-3 text-gray-600 text-xs">{c.contactPerson || "—"}</td>
                        <td className="p-3 text-gray-600 text-xs">{c.email || "—"}</td>
                        <td className="p-3 text-gray-600 text-xs">{c.phone || "—"}</td>
                        <td className="p-3 text-gray-500 text-xs">{c.city || "—"}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              )}
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
