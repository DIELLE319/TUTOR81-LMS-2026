import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search, Users as UsersIcon } from 'lucide-react';
import type { User as UserType } from '@shared/schema';

interface UserWithCompany extends UserType {
  companyName?: string | null;
  tutorName?: string | null;
}

export default function Users() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data: users = [], isLoading } = useQuery<UserWithCompany[]>({
    queryKey: ['/api/platform-users'],
  });

  const filteredUsers = users.filter(u => 
    u.id?.toString().includes(searchTerm) ||
    u.firstName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.lastName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.companyName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.tutorName?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-4">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-users-title">Elenco Utenti</h1>
          <p className="text-gray-500 text-sm">{users.length} utenti registrati</p>
        </div>
      </div>

      <div className="bg-[#1e1e1e] rounded-lg border border-gray-800 p-3 mb-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={16} />
          <input
            type="text"
            placeholder="Cerca per ID, nome, email o azienda..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-2 pl-9 pr-4 text-sm text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
            data-testid="input-search-users"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredUsers.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-lg border border-gray-800">
          <UsersIcon size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun utente trovato</h3>
          <p className="text-gray-500">Gli utenti registrati appariranno qui</p>
        </div>
      ) : (
        <div className="bg-white rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-gray-100 text-left text-xs text-gray-600 uppercase">
                <th className="px-3 py-2 font-medium">ID</th>
                <th className="px-3 py-2 font-medium">Ente Formativo</th>
                <th className="px-3 py-2 font-medium">Azienda</th>
                <th className="px-3 py-2 font-medium">Email</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filteredUsers.map((user) => (
                <tr 
                  key={user.id} 
                  className="hover:bg-gray-50 text-gray-800"
                  data-testid={`row-user-${user.id}`}
                >
                  <td className="px-3 py-1.5 font-mono text-xs text-gray-500">
                    {user.id.substring(0, 8)}
                  </td>
                  <td className="px-3 py-1.5 text-xs">
                    {user.tutorName || '-'}
                  </td>
                  <td className="px-3 py-1.5 text-xs">
                    {user.companyName || '-'}
                  </td>
                  <td className="px-3 py-1.5 text-xs text-gray-600">
                    {user.email || '-'}
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
