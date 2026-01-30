import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Search, User, Mail, Building, ChevronRight, Shield, Ban } from 'lucide-react';
import type { User as UserType } from '@shared/schema';

interface UserWithCompany extends UserType {
  companyName?: string;
}

export default function Users() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data: users = [], isLoading } = useQuery<UserWithCompany[]>({
    queryKey: ['/api/platform-users'],
  });

  const filteredUsers = users.filter(u => 
    u.firstName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.lastName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.email?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-users-title">Elenco Utenti</h1>
          <p className="text-gray-500 text-sm">Gestisci gli utenti della piattaforma</p>
        </div>
      </div>

      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca utente per nome o email..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
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
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <User size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun utente trovato</h3>
          <p className="text-gray-500">Gli utenti registrati appariranno qui</p>
        </div>
      ) : (
        <div className="grid gap-3">
          {filteredUsers.map((user, index) => (
            <motion.div
              key={user.id}
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.03 }}
              className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 hover:border-gray-700 transition-colors cursor-pointer"
              data-testid={`card-user-${user.id}`}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-10 h-10 rounded-full bg-gray-700 flex items-center justify-center overflow-hidden">
                    {user.profileImageUrl ? (
                      <img src={user.profileImageUrl} alt="" className="w-full h-full object-cover" />
                    ) : (
                      <User size={20} className="text-gray-400" />
                    )}
                  </div>
                  <div>
                    <h3 className="font-bold text-white">
                      {user.firstName} {user.lastName}
                    </h3>
                    <div className="flex items-center gap-3 text-sm text-gray-500 mt-0.5">
                      <span className="flex items-center gap-1">
                        <Mail size={12} />
                        {user.email}
                      </span>
                      {user.companyName && (
                        <span className="flex items-center gap-1">
                          <Building size={12} />
                          {user.companyName}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
                <div className="flex items-center gap-3">
                  <ChevronRight size={18} className="text-gray-600" />
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
}
