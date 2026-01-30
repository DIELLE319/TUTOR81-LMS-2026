import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'wouter';
import { motion } from 'framer-motion';
import { Search, Plus, Users, ChevronRight, MapPin, Mail, Phone } from 'lucide-react';
import type { Company } from '@shared/schema';

export default function Clients() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data: clients = [], isLoading } = useQuery<Company[]>({
    queryKey: ['/api/clients'],
  });

  const filteredClients = clients.filter(c => 
    c.businessName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    c.city?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-clients-title">Aziende Clienti</h1>
          <p className="text-gray-500 text-sm">Gestisci le aziende clienti</p>
        </div>
        <Link href="/companies/new?type=client">
          <button 
            className="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            data-testid="button-new-client"
          >
            <Plus size={18} />
            Nuova Azienda
          </button>
        </Link>
      </div>

      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca azienda..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
            data-testid="input-search-clients"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredClients.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <Users size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessuna azienda cliente</h3>
          <p className="text-gray-500">Aggiungi la prima azienda cliente per iniziare</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {filteredClients.map((client, index) => (
            <motion.div
              key={client.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.05 }}
              className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 hover:border-blue-500/50 transition-colors cursor-pointer"
              data-testid={`card-client-${client.id}`}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 rounded-full bg-blue-500/20 flex items-center justify-center">
                    <Users size={24} className="text-blue-500" />
                  </div>
                  <div>
                    <h3 className="font-bold text-white">{client.businessName}</h3>
                    <div className="flex items-center gap-4 text-sm text-gray-500 mt-1">
                      {client.city && (
                        <span className="flex items-center gap-1">
                          <MapPin size={14} />
                          {client.city}
                        </span>
                      )}
                      {client.email && (
                        <span className="flex items-center gap-1">
                          <Mail size={14} />
                          {client.email}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
                <ChevronRight size={20} className="text-gray-600" />
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
}
