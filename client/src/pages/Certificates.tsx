import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Search, Award, Calendar, User, Download, FileText } from 'lucide-react';
import type { Certificate } from '@shared/schema';

export default function Certificates() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data: certificates = [], isLoading } = useQuery<Certificate[]>({
    queryKey: ['/api/certificates'],
  });

  const filteredCertificates = certificates.filter(c => 
    c.courseTitle?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const formatDate = (dateStr: string | Date | null) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('it-IT');
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-certificates-title">Attestati</h1>
          <p className="text-gray-500 text-sm">Certificati di completamento corsi</p>
        </div>
      </div>

      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca attestato..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
            data-testid="input-search-certificates"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredCertificates.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <Award size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun attestato</h3>
          <p className="text-gray-500">Gli attestati appariranno qui dopo il completamento dei corsi</p>
        </div>
      ) : (
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {filteredCertificates.map((cert, index) => (
            <motion.div
              key={cert.id}
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              transition={{ delay: index * 0.05 }}
              className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-5 hover:border-yellow-500/50 transition-colors"
              data-testid={`card-certificate-${cert.id}`}
            >
              <div className="flex items-start justify-between mb-4">
                <div className="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center">
                  <Award size={24} className="text-yellow-500" />
                </div>
                <button className="text-gray-500 hover:text-yellow-500 transition-colors">
                  <Download size={18} />
                </button>
              </div>
              
              <h3 className="font-bold text-white mb-2">{cert.courseTitle}</h3>
              
              <div className="space-y-2 text-sm text-gray-500">
                <div className="flex items-center gap-2">
                  <Calendar size={14} />
                  <span>Completato il {formatDate(cert.completedAt)}</span>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
}
