import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'wouter';
import { Search, Plus, Users, ChevronRight, MapPin, Mail, ChevronDown, ChevronUp, Building } from 'lucide-react';
import { useAuth } from '@/hooks/use-auth';
import { TutorAdmins } from '@/components/TutorAdmins';

type Client = {
  id: number;
  businessName: string;
  city: string | null;
  email: string | null;
  phone: string | null;
  address: string | null;
  vatNumber: string | null;
};

type TutorGroup = {
  tutorId: number | null;
  tutorName: string;
  clients: Client[];
};

export default function Clients() {
  const { user } = useAuth();
  const [searchTerm, setSearchTerm] = useState('');
  const [expandedTutors, setExpandedTutors] = useState<Set<string>>(new Set());
  
  // Se l'utente è venditore (admin tutor), filtra per il suo tutorId
  const isVenditore = user?.role === 1;
  const tutorId = (user as any)?.tutorId;

  const { data: tutorGroups = [], isLoading } = useQuery<TutorGroup[]>({
    queryKey: ['/api/clients', tutorId],
    queryFn: async () => {
      const url = tutorId ? `/api/clients?tutorId=${tutorId}` : '/api/clients';
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) throw new Error('Failed to fetch');
      return res.json();
    },
  });

  const toggleTutor = (tutorKey: string) => {
    setExpandedTutors(prev => {
      const newSet = new Set(prev);
      if (newSet.has(tutorKey)) {
        newSet.delete(tutorKey);
      } else {
        newSet.add(tutorKey);
      }
      return newSet;
    });
  };

  // Filter clients by search term
  const filteredGroups = tutorGroups.map(group => ({
    ...group,
    clients: group.clients.filter(c =>
      c.businessName.toLowerCase().includes(searchTerm.toLowerCase()) ||
      c.city?.toLowerCase().includes(searchTerm.toLowerCase()) ||
      group.tutorName.toLowerCase().includes(searchTerm.toLowerCase())
    )
  })).filter(group => group.clients.length > 0);

  const totalClients = filteredGroups.reduce((acc, g) => acc + g.clients.length, 0);

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-clients-title">Aziende Clienti</h1>
          <p className="text-gray-500 text-sm">
            {totalClients} aziende in {filteredGroups.length} enti formativi
          </p>
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
            placeholder="Cerca azienda o ente formativo..."
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
      ) : filteredGroups.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <Users size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessuna azienda cliente</h3>
          <p className="text-gray-500">Aggiungi la prima azienda cliente per iniziare</p>
        </div>
      ) : (
        <div className="space-y-4">
          {filteredGroups.map((group) => {
            const tutorKey = group.tutorId ? String(group.tutorId) : 'none';
            const isExpanded = expandedTutors.has(tutorKey);
            
            return (
              <div 
                key={tutorKey} 
                className="bg-[#1e1e1e] rounded-xl border border-gray-800 overflow-hidden"
                data-testid={`group-tutor-${tutorKey}`}
              >
                {/* Tutor Header */}
                <div 
                  className="bg-yellow-600 px-4 py-3 cursor-pointer hover:bg-yellow-700 transition-colors flex items-center justify-between"
                  onClick={() => toggleTutor(tutorKey)}
                >
                  <div className="flex items-center gap-3">
                    <Building size={20} className="text-white" />
                    <div>
                      <span className="font-bold text-white">
                        {group.tutorId && <span className="opacity-70 mr-2">#{group.tutorId}</span>}
                        {group.tutorName}
                      </span>
                      {group.tutorId ? (
                        <div className="text-[11px] text-white/80 mt-0.5">
                          <span className="opacity-80">Admin:</span>{' '}
                          <span className="inline-block">
                            <TutorAdmins tutorId={group.tutorId} theme="dark" showManageLink={false} />
                          </span>
                        </div>
                      ) : null}
                      <span className="ml-3 bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">
                        {group.clients.length} aziende
                      </span>
                    </div>
                  </div>
                  {isExpanded ? (
                    <ChevronUp size={20} className="text-white" />
                  ) : (
                    <ChevronDown size={20} className="text-white" />
                  )}
                </div>
                
                {/* Clients List */}
                {!isExpanded && (
                  <div className="divide-y divide-gray-800">
                    {group.clients.map((client) => (
                      <div
                        key={client.id}
                        className="px-4 py-3 hover:bg-gray-800/50 transition-colors cursor-pointer"
                        data-testid={`card-client-${client.id}`}
                      >
                        <div className="flex items-center justify-between">
                          <div className="flex items-center gap-4">
                            <div className="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                              <Users size={18} className="text-blue-500" />
                            </div>
                            <div>
                              <h3 className="font-medium text-white text-sm">
                                <span className="text-gray-500 mr-2">#{client.id}</span>
                                {client.businessName}
                              </h3>
                              <div className="flex items-center gap-4 text-xs text-gray-500 mt-0.5">
                                {client.city && (
                                  <span className="flex items-center gap-1">
                                    <MapPin size={12} />
                                    {client.city}
                                  </span>
                                )}
                                {client.email && (
                                  <span className="flex items-center gap-1">
                                    <Mail size={12} />
                                    {client.email}
                                  </span>
                                )}
                              </div>
                            </div>
                          </div>
                          <ChevronRight size={16} className="text-gray-600" />
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
