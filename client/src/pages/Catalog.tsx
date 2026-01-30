import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search, ShoppingCart, ChevronDown, ChevronUp } from 'lucide-react';
import type { LearningProject } from '@shared/schema';

export default function Catalog() {
  const [searchTerm, setSearchTerm] = useState('');
  const [expandedCategories, setExpandedCategories] = useState<Set<string>>(new Set());

  const { data: courses = [], isLoading } = useQuery<LearningProject[]>({
    queryKey: ['/api/catalog'],
  });

  const filteredCourses = courses.filter(c => {
    const matchesSearch = c.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                          c.description?.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesSearch;
  });

  const getCourseType = (title: string) => {
    const t = title.toLowerCase();
    if (t.includes('aggiornamento') || t.includes('agg.') || t.includes('agg ')) {
      return { label: 'Agg.', color: 'bg-orange-500' };
    }
    return { label: 'Base', color: 'bg-blue-500' };
  };

  const getRiskLevel = (title: string) => {
    const t = title.toLowerCase();
    if (t.includes('rischio alto') || t.includes('alto rischio')) {
      return { label: 'Alto', color: 'bg-red-500' };
    }
    if (t.includes('rischio medio') || t.includes('medio rischio')) {
      return { label: 'Medio', color: 'bg-yellow-500' };
    }
    if (t.includes('rischio basso') || t.includes('basso rischio')) {
      return { label: 'Basso', color: 'bg-cyan-500' };
    }
    if (t.includes('tutti')) {
      return { label: 'Tutti', color: 'bg-cyan-400' };
    }
    return { label: 'Non Definito', color: 'bg-gray-400' };
  };

  const getCourseCategory = (title: string) => {
    const t = title.toUpperCase();
    if (t.includes('LAVORATORE') || t.includes('LAVORATORI')) return 'LAVORATORE';
    if (t.includes('PREPOSTO')) return 'PREPOSTO';
    if (t.includes('DIRIGENTE')) return 'DIRIGENTE';
    if (t.includes('RSPP') || t.includes('ASPP')) return 'RSPP/ASPP';
    if (t.includes('RLS')) return 'RLS';
    if (t.includes('ANTINCENDIO')) return 'ANTINCENDIO';
    if (t.includes('PRIMO SOCCORSO')) return 'PRIMO SOCCORSO';
    if (t.includes('HACCP')) return 'HACCP';
    if (t.includes('231')) return 'D.LGS 231';
    if (t.includes('PRIVACY') || t.includes('GDPR')) return 'PRIVACY';
    return 'ALTRI CORSI';
  };

  const groupedCourses = useMemo(() => {
    const groups: { [key: string]: LearningProject[] } = {};
    
    filteredCourses.forEach(course => {
      const category = getCourseCategory(course.title);
      if (!groups[category]) {
        groups[category] = [];
      }
      groups[category].push(course);
    });

    const orderedCategories = [
      'LAVORATORE', 'PREPOSTO', 'DIRIGENTE', 'RSPP/ASPP', 'RLS', 
      'ANTINCENDIO', 'PRIMO SOCCORSO', 'HACCP', 'D.LGS 231', 'PRIVACY', 'ALTRI CORSI'
    ];

    return orderedCategories
      .filter(cat => groups[cat]?.length > 0)
      .map(cat => ({ category: cat, courses: groups[cat] }));
  }, [filteredCourses]);

  const toggleCategory = (category: string) => {
    const newExpanded = new Set(expandedCategories);
    if (newExpanded.has(category)) {
      newExpanded.delete(category);
    } else {
      newExpanded.add(category);
    }
    setExpandedCategories(newExpanded);
  };

  const formatPrice = (price: string | number | null) => {
    const numPrice = typeof price === 'string' ? parseFloat(price) : (price ?? 0);
    if (numPrice === 0) return '---';
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(numPrice);
  };

  const calculateTutorCost = (listPrice: string | number | null) => {
    const numPrice = typeof listPrice === 'string' ? parseFloat(listPrice) : (listPrice ?? 0);
    return numPrice * 0.3;
  };

  return (
    <div className="bg-gray-100 min-h-screen font-sans">
      <div className="bg-white border-b border-gray-200 px-6 py-4">
        <div className="flex items-center justify-between max-w-[1600px] mx-auto">
          <h1 className="text-xl font-bold text-gray-800" data-testid="text-catalog-title">
            Catalogo Corsi
          </h1>
          <div className="relative w-72">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
            <input 
              type="text"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Cerca corso..." 
              className="w-full bg-gray-100 border border-gray-200 rounded-lg py-2 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              data-testid="input-search-catalog"
            />
          </div>
        </div>
      </div>

      <div className="p-6 max-w-[1600px] mx-auto">
        {isLoading ? (
          <div className="text-center py-12 bg-white rounded-lg shadow">
            <div className="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
            <p className="text-gray-500 mt-4">Caricamento catalogo...</p>
          </div>
        ) : filteredCourses.length === 0 ? (
          <div className="text-center py-12 bg-white rounded-lg shadow">
            <h3 className="text-lg font-bold text-gray-700 mb-2">Nessun corso trovato</h3>
            <p className="text-gray-500">Prova a modificare i filtri di ricerca</p>
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full text-sm">
              <thead className="bg-gray-50 border-b border-gray-200">
                <tr>
                  <th className="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                  <th className="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Rischio</th>
                  <th className="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ID</th>
                  <th className="px-3 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Nome Corso</th>
                  <th className="px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Ore</th>
                  <th className="px-3 py-3 text-right text-xs font-semibold text-gray-600 uppercase">
                    <div>Listino</div>
                    <div className="text-[10px] font-normal text-gray-400">prezzo di vendita consigliato</div>
                  </th>
                  <th className="px-3 py-3 text-right text-xs font-semibold text-gray-600 uppercase">Tuo Costo €</th>
                  <th className="px-3 py-3 text-center text-xs font-semibold text-gray-600 uppercase">Vendi</th>
                </tr>
              </thead>
              <tbody>
                {groupedCourses.map(group => (
                  <>
                    <tr 
                      key={`header-${group.category}`}
                      className="bg-blue-600 cursor-pointer hover:bg-blue-700"
                      onClick={() => toggleCategory(group.category)}
                    >
                      <td colSpan={8} className="px-4 py-3">
                        <div className="flex items-center gap-3">
                          {expandedCategories.has(group.category) ? (
                            <ChevronUp size={18} className="text-white" />
                          ) : (
                            <ChevronDown size={18} className="text-white" />
                          )}
                          <span className="font-bold text-white text-sm uppercase tracking-wide">{group.category}</span>
                          <span className="bg-white/20 text-white text-xs px-2 py-0.5 rounded-full">{group.courses.length}</span>
                        </div>
                      </td>
                    </tr>
                    {!expandedCategories.has(group.category) && group.courses.map((course, idx) => {
                      const type = getCourseType(course.title);
                      const risk = getRiskLevel(course.title);
                      return (
                        <tr
                          key={course.id}
                          className={`border-b border-gray-100 hover:bg-blue-50/50 ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50/50'}`}
                          data-testid={`row-course-${course.id}`}
                        >
                          <td className="px-3 py-2">
                            <span className={`inline-block px-2 py-0.5 rounded text-xs font-medium text-white ${type.color}`}>
                              {type.label}
                            </span>
                          </td>
                          <td className="px-3 py-2">
                            <span className={`inline-block px-2 py-0.5 rounded text-xs font-medium text-white ${risk.color}`}>
                              {risk.label}
                            </span>
                          </td>
                          <td className="px-3 py-2 text-gray-500">{course.id}</td>
                          <td className="px-3 py-2 text-gray-800 font-medium">{course.title}</td>
                          <td className="px-3 py-2 text-center text-gray-600">{course.hours || '-'}</td>
                          <td className="px-3 py-2 text-right text-gray-700">{formatPrice(course.listPrice)}</td>
                          <td className="px-3 py-2 text-right text-green-600 font-medium">
                            {formatPrice(calculateTutorCost(course.listPrice))}
                          </td>
                          <td className="px-3 py-2 text-center">
                            <button 
                              className="text-gray-400 hover:text-blue-600 transition-colors"
                              data-testid={`button-sell-${course.id}`}
                            >
                              <ShoppingCart size={18} />
                            </button>
                          </td>
                        </tr>
                      );
                    })}
                  </>
                ))}
              </tbody>
            </table>
          </div>
        )}
        
        <div className="mt-4 text-sm text-gray-500">
          Totale corsi: {filteredCourses.length}
        </div>
      </div>
    </div>
  );
}
