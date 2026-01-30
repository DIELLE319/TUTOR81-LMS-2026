import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Search, ShoppingCart, Clock, Globe, ChevronDown, ChevronUp } from 'lucide-react';
import type { LearningProject } from '@shared/schema';

export default function Catalog() {
  const [searchTerm, setSearchTerm] = useState('');
  const [category, setCategory] = useState('TUTTE');
  const [expandedId, setExpandedId] = useState<number | null>(null);

  const { data: courses = [], isLoading } = useQuery<LearningProject[]>({
    queryKey: ['/api/catalog'],
  });

  const filteredCourses = courses.filter(c => {
    const matchesSearch = c.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          c.description?.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesCategory = category === 'TUTTE' || c.category === category;
    return matchesSearch && matchesCategory;
  });

  const getRiskColor = (risk?: string | null) => {
    if (!risk) return 'bg-gray-500';
    const r = risk.toLowerCase();
    if (r.includes('alto')) return 'bg-red-500';
    if (r.includes('medio')) return 'bg-yellow-500';
    if (r.includes('basso')) return 'bg-green-500';
    return 'bg-gray-500';
  };

  const formatPrice = (price: string | number | null) => {
    const numPrice = typeof price === 'string' ? parseFloat(price) : (price ?? 0);
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(numPrice);
  };

  return (
    <div className="bg-black min-h-screen font-sans pb-10">
      
      <div className="bg-yellow-500 text-black p-4 flex justify-between items-center shadow-md relative z-20">
        <div className="w-1/3"></div>
        
        <h1 className="text-xl font-bold text-center w-1/3 uppercase" data-testid="text-catalog-title">Catalogo Corsi</h1>
        
        <div className="relative w-1/3 flex justify-end">
          <div className="relative w-64">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={16} />
            <input 
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Cerca corso..." 
              className="w-full bg-white/90 border border-transparent rounded-full py-1.5 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-black/20"
              data-testid="input-search-catalog"
            />
          </div>
        </div>
      </div>

      <div className="flex justify-center mt-4 mb-6">
        <select 
          value={category}
          onChange={(e) => setCategory(e.target.value)}
          className="appearance-none bg-[#1e1e1e] border border-gray-700 text-gray-300 py-2 px-6 rounded-lg text-sm focus:outline-none focus:border-yellow-500"
          data-testid="select-category"
        >
          <option value="TUTTE">TUTTE LE CATEGORIE</option>
          <option value="SICUREZZA">SICUREZZA</option>
          <option value="HACCP">HACCP</option>
          <option value="INFORMATICA">INFORMATICA</option>
          <option value="HR">HR</option>
        </select>
      </div>

      <div className="p-6 max-w-[1800px] mx-auto">
        
        {isLoading ? (
          <div className="text-center py-12">
            <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
            <p className="text-gray-500 mt-4">Caricamento catalogo...</p>
          </div>
        ) : filteredCourses.length === 0 ? (
          <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
            <Globe size={48} className="mx-auto text-gray-600 mb-4" />
            <h3 className="text-lg font-bold text-white mb-2">Nessun corso trovato</h3>
            <p className="text-gray-500">Prova a modificare i filtri di ricerca</p>
          </div>
        ) : (
          <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 overflow-hidden">
            <table className="w-full text-xs text-left">
              <thead className="text-xs text-gray-400 font-bold uppercase bg-black/50 border-b border-gray-800">
                <tr>
                  <th className="px-3 py-3 w-8"></th>
                  <th className="px-3 py-3 text-center">Rischio</th>
                  <th className="px-3 py-3 text-center text-gray-600">ID</th>
                  <th className="px-3 py-3">Nome Corso</th>
                  <th className="px-3 py-3 text-center">Settore</th>
                  <th className="px-3 py-3 text-center">Ore</th>
                  <th className="px-3 py-3 text-center">Modalità</th>
                  <th className="px-3 py-3 text-right">Listino</th>
                  <th className="px-3 py-3 text-center w-16"></th>
                </tr>
              </thead>
              <tbody>
                {filteredCourses.map((course, index) => (
                  <motion.tr
                    key={course.id}
                    initial={{ opacity: 0 }}
                    animate={{ opacity: 1 }}
                    transition={{ delay: index * 0.02 }}
                    className="border-b border-gray-800/50 hover:bg-gray-800/30"
                    data-testid={`row-course-${course.id}`}
                  >
                    <td className="px-3 py-3">
                      <button 
                        onClick={() => setExpandedId(expandedId === course.id ? null : course.id)}
                        className="text-gray-500 hover:text-white"
                      >
                        {expandedId === course.id ? <ChevronUp size={16} /> : <ChevronDown size={16} />}
                      </button>
                    </td>
                    <td className="px-3 py-3 text-center">
                      <span className={`inline-block w-3 h-3 rounded-full ${getRiskColor(course.riskLevel)}`}></span>
                    </td>
                    <td className="px-3 py-3 text-center text-gray-600">{course.id}</td>
                    <td className="px-3 py-3 text-white font-medium">{course.title}</td>
                    <td className="px-3 py-3 text-center text-gray-400">{course.sector || '-'}</td>
                    <td className="px-3 py-3 text-center text-gray-300">
                      <div className="flex items-center justify-center gap-1">
                        <Clock size={12} className="text-gray-600" />
                        {course.hours || 0}
                      </div>
                    </td>
                    <td className="px-3 py-3 text-center">
                      <span className="px-2 py-1 rounded text-xs bg-blue-500/20 text-blue-400">
                        {course.modality || 'Online'}
                      </span>
                    </td>
                    <td className="px-3 py-3 text-right text-green-500 font-medium">
                      {formatPrice(course.listPrice)}
                    </td>
                    <td className="px-3 py-3 text-center">
                      <button 
                        className="bg-yellow-500 hover:bg-yellow-400 text-black p-2 rounded-lg transition-colors"
                        data-testid={`button-buy-${course.id}`}
                      >
                        <ShoppingCart size={14} />
                      </button>
                    </td>
                  </motion.tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
}
