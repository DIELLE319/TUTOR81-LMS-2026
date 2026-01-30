import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Link } from 'wouter';
import { motion } from 'framer-motion';
import { Search, Plus, Building, ChevronRight, Users, MapPin, FileText } from 'lucide-react';
import type { Company } from '@shared/schema';

export default function Tutors() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data: tutors = [], isLoading } = useQuery<Company[]>({
    queryKey: ['/api/tutors'],
  });

  const filteredTutors = tutors.filter(t => 
    t.businessName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    t.city?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-tutors-title">Enti Formativi</h1>
          <p className="text-gray-500 text-sm">Gestisci i tutor autorizzati</p>
        </div>
        <Link href="/companies/new?type=tutor">
          <button 
            className="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            data-testid="button-new-tutor"
          >
            <Plus size={18} />
            Nuovo Ente
          </button>
        </Link>
      </div>

      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca ente formativo..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
            data-testid="input-search-tutors"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredTutors.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <Building size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun ente formativo</h3>
          <p className="text-gray-500">Aggiungi il primo ente formativo per iniziare</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {filteredTutors.map((tutor, index) => (
            <motion.div
              key={tutor.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.05 }}
              className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 hover:border-yellow-500/50 transition-colors cursor-pointer"
              data-testid={`card-tutor-${tutor.id}`}
            >
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <div className="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center">
                    <Building size={24} className="text-yellow-500" />
                  </div>
                  <div>
                    <h3 className="font-bold text-white">{tutor.businessName}</h3>
                    <div className="flex items-center gap-4 text-sm text-gray-500 mt-1">
                      {tutor.city && (
                        <span className="flex items-center gap-1">
                          <MapPin size={14} />
                          {tutor.city}
                        </span>
                      )}
                      {tutor.licenseType && (
                        <span className="flex items-center gap-1">
                          <FileText size={14} />
                          {tutor.licenseType}
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
