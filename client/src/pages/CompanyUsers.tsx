import { useQuery } from "@tanstack/react-query";

type CompanyUser = {
  id: number;
  firstName: string;
  lastName: string;
  email: string | null;
  fiscalCode: string | null;
  isActive: boolean | null;
};

export default function CompanyUsers() {
  const { data: users = [], isLoading, error } = useQuery<CompanyUser[]>({
    queryKey: ["/api/me/company/users"],
  });

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex items-end justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-yellow-400" data-testid="text-page-title">
            Utenti Azienda
          </h1>
          <div className="text-gray-500 text-sm mt-1">
            {isLoading ? "Caricamento..." : `${users.length} utenti`}
          </div>
        </div>
      </div>

      {error && (
        <div className="mt-4 text-red-400">Errore nel caricamento.</div>
      )}

      <div className="mt-6 rounded-xl border border-gray-800 bg-[#111] overflow-hidden">
        <div className="grid grid-cols-12 gap-3 px-4 py-3 border-b border-gray-800 text-xs uppercase tracking-wider text-gray-500 font-bold">
          <div className="col-span-4">Nome</div>
          <div className="col-span-4">Email</div>
          <div className="col-span-3">Codice Fiscale</div>
          <div className="col-span-1 text-right">Stato</div>
        </div>

        {isLoading ? (
          <div className="px-4 py-6 text-gray-400">Caricamento...</div>
        ) : users.length === 0 ? (
          <div className="px-4 py-6 text-gray-400">Nessun utente.</div>
        ) : (
          users.map((u) => (
            <div
              key={u.id}
              className="grid grid-cols-12 gap-3 px-4 py-3 border-b border-gray-800 last:border-b-0 hover:bg-white/5"
            >
              <div className="col-span-4 text-gray-200 text-sm font-medium">
                {u.lastName} {u.firstName}
              </div>
              <div className="col-span-4 text-gray-300 text-sm truncate">
                {u.email || <span className="text-gray-500">-</span>}
              </div>
              <div className="col-span-3 text-gray-300 text-sm truncate">
                {u.fiscalCode || <span className="text-gray-500">-</span>}
              </div>
              <div className="col-span-1 text-right text-sm">
                {u.isActive ? (
                  <span className="text-green-400">●</span>
                ) : (
                  <span className="text-gray-500">●</span>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}
