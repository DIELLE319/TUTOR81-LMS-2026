import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search, ShoppingCart, ChevronDown, ChevronUp } from 'lucide-react';
import type { LearningProject } from '@shared/schema';

// Keywords per identificare corsi di test/demo
const TEST_KEYWORDS = ['komplett', 'trops', 'innovyn', 'inovyn', 'prova', 'test', 'italpress'];
const isTestCourse = (title: string) => {
  const lowerTitle = title.toLowerCase();
  return TEST_KEYWORDS.some(keyword => lowerTitle.includes(keyword));
};

const CATEGORIES = [
  { key: 'TUTTI', label: 'Tutti' },
  { key: 'LAVORATORE', label: 'Lavoratore' },
  { key: 'PREPOSTO', label: 'Preposto' },
  { key: 'DIRIGENTE', label: 'Dirigente' },
  { key: 'RSPP/ASPP', label: 'RSPP/ASPP' },
  { key: 'RLS', label: 'RLS' },
  { key: 'CARRELLO ELEVATORE', label: 'Carrello' },
  { key: 'ANTINCENDIO', label: 'Antincendio' },
  { key: 'PRIMO SOCCORSO', label: 'Primo Soccorso' },
  { key: 'HACCP', label: 'HACCP' },
  { key: 'PRIVACY/GDPR', label: 'Privacy' },
  { key: 'D.LGS 231', label: '231' },
];

const getCourseCategory = (title: string) => {
  const t = title.toUpperCase();
  if (t.includes('DIRIGENTE') || t.includes('EL03')) return 'DIRIGENTE';
  if (t.includes('PREPOSTO') || t.includes('EL02')) return 'PREPOSTO';
  if (t.includes('RSPP') || t.includes('ASPP') || t.includes('DATORE DI LAVORO') || t.includes('EL04') || t.includes('EL05')) return 'RSPP/ASPP';
  if (t.includes('RLS') || t.includes('EL07')) return 'RLS';
  if (t.includes('CARRELLO') || t.includes('MULETTO') || t.includes('ELEVATORE')) return 'CARRELLO ELEVATORE';
  if (t.includes('PLE') || t.includes('PIATTAFORM')) return 'PLE';
  if (t.includes('GRU') || t.includes('SOLLEVAMENTO')) return 'APPARECCHI SOLLEVAMENTO';
  if (t.includes('PONTEGGI') || t.includes('LAVORI IN QUOTA')) return 'LAVORI IN QUOTA';
  if (t.includes('ANTINCENDIO') || t.includes('EL08')) return 'ANTINCENDIO';
  if (t.includes('PRIMO SOCCORSO') || t.includes('SOCCORSO') || t.includes('EL09')) return 'PRIMO SOCCORSO';
  if (t.includes('HACCP') || t.includes('ALIMENTAR')) return 'HACCP';
  if (t.includes('PRIVACY') || t.includes('GDPR')) return 'PRIVACY/GDPR';
  if (t.includes('231') || t.includes('ORGANIZZATIVO') || t.includes('MOG')) return 'D.LGS 231';
  if (t.includes('STRESS') || t.includes('MOBBING')) return 'RISCHI PSICOSOCIALI';
  if (t.includes('AMIANTO')) return 'AMIANTO';
  if (t.includes('ELETTRIC')) return 'RISCHIO ELETTRICO';
  if (t.includes('SPAZI CONFINATI')) return 'SPAZI CONFINATI';
  if (t.includes('PARITA') || t.includes('GENERE')) return 'PARITÀ DI GENERE';
  if (t.includes('LAVORATORE') || t.includes('LAVORATORI') || t.includes('EL01')) return 'LAVORATORE';
  return 'ALTRI CORSI';
};

export default function Catalog() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCategory, setSelectedCategory] = useState('TUTTI');
  const [expandedCategories, setExpandedCategories] = useState<Set<string>>(new Set(['LAVORATORE']));

  const { data: projects = [], isLoading } = useQuery<LearningProject[]>({
    queryKey: ['/api/learning-projects'],
  });

  // Filtra solo corsi pubblicati, non test, non riservati
  const publishedCourses = useMemo(() => {
    return projects.filter(c => 
      c.isPublishedInEcommerce === 1 && 
      !isTestCourse(c.title) && 
      !(c.reservedTo && c.reservedTo > 0)
    );
  }, [projects]);

  const filteredCourses = useMemo(() => {
    return publishedCourses.filter(c => {
      const matchesSearch = c.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                            c.description?.toLowerCase().includes(searchTerm.toLowerCase());
      const matchesCategory = selectedCategory === 'TUTTI' || getCourseCategory(c.title) === selectedCategory;
      return matchesSearch && matchesCategory;
    });
  }, [publishedCourses, searchTerm, selectedCategory]);

  const formatCourseTitle = (title: string) => {
    const dashIndex = title.indexOf(' - ');
    if (dashIndex > 0 && dashIndex < 30) {
      return title.substring(dashIndex + 3).trim();
    }
    return title
      .replace(/^EL\d*[a-zA-Z]*\s*-\s*/i, '')
      .replace(/^EL\s*-\s*/i, '')
      .replace(/^st\d*[a-zA-Z]*\s*-\s*/i, '')
      .replace(/^var\d*[a-zA-Z]*\s*-\s*/i, '')
      .trim();
  };

  const getSubcategoryLabel = (subcategory: string | null) => {
    if (!subcategory) return { label: '-', color: 'bg-gray-400' };
    const labels: Record<string, { label: string; color: string }> = {
      'LAVORATORE': { label: 'LAV', color: 'bg-blue-600' },
      'PREPOSTO': { label: 'PRE', color: 'bg-purple-600' },
      'DIRIGENTE': { label: 'DIR', color: 'bg-indigo-600' },
      'RSPP': { label: 'RSPP', color: 'bg-red-600' },
      'ASPP': { label: 'ASPP', color: 'bg-red-500' },
      'RLS': { label: 'RLS', color: 'bg-orange-600' },
      'ANTINCENDIO': { label: 'ANT', color: 'bg-red-500' },
      'PRIMO SOCCORSO': { label: 'PS', color: 'bg-green-600' },
      'ALTRO': { label: 'ALT', color: 'bg-gray-500' },
    };
    return labels[subcategory] || { label: subcategory.substring(0, 3).toUpperCase(), color: 'bg-gray-500' };
  };

  const getCourseTypeLabel = (courseType: string | null) => {
    if (!courseType) return { label: '-', color: 'bg-gray-400' };
    if (courseType.toLowerCase() === 'aggiornamento') {
      return { label: 'Agg.', color: 'bg-orange-500' };
    }
    return { label: 'Base', color: 'bg-blue-500' };
  };

  const getRiskLabel = (riskLevel: string | null) => {
    if (!riskLevel) return { label: '-', color: 'bg-gray-400' };
    const risk = riskLevel.toLowerCase();
    if (risk === 'alto') return { label: 'Alto', color: 'bg-red-500' };
    if (risk === 'medio') return { label: 'Medio', color: 'bg-yellow-500' };
    if (risk === 'basso') return { label: 'Basso', color: 'bg-cyan-500' };
    if (risk === 'tutti') return { label: 'Tutti', color: 'bg-teal-500' };
    if (risk === 'nd') return { label: 'N/D', color: 'bg-gray-400' };
    return { label: riskLevel, color: 'bg-gray-400' };
  };

  const getCourseDuration = (hours: number | null) => {
    if (hours && hours > 0) return `${hours}`;
    return '-';
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
      'CARRELLO ELEVATORE', 'PLE', 'APPARECCHI SOLLEVAMENTO', 'LAVORI IN QUOTA',
      'ANTINCENDIO', 'PRIMO SOCCORSO', 'HACCP', 
      'RISCHIO ELETTRICO', 'SPAZI CONFINATI', 'AMIANTO',
      'PRIVACY/GDPR', 'D.LGS 231', 'RISCHI PSICOSOCIALI', 'PARITÀ DI GENERE',
      'ALTRI CORSI'
    ];

    return orderedCategories
      .filter(cat => groups[cat]?.length > 0)
      .map(cat => ({ 
        category: cat, 
        courses: groups[cat].sort((a, b) => {
          const typeOrder = { 'Base': 0, 'Aggiornamento': 1 };
          const typeA = typeOrder[a.courseType as keyof typeof typeOrder] ?? 2;
          const typeB = typeOrder[b.courseType as keyof typeof typeOrder] ?? 2;
          if (typeA !== typeB) return typeA - typeB;
          
          const riskOrder = { 'Basso': 0, 'Medio': 1, 'Alto': 2 };
          const riskA = riskOrder[a.riskLevel as keyof typeof riskOrder] ?? 3;
          const riskB = riskOrder[b.riskLevel as keyof typeof riskOrder] ?? 3;
          return riskA - riskB;
        })
      }));
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
      <div className="bg-gradient-to-r from-[#1a1a1a] to-[#2d2d2d] border-b border-gray-700 px-6 py-4">
        <div className="max-w-[1600px] mx-auto">
          <div className="flex items-center justify-between mb-4">
            <div>
              <h1 className="text-xl font-bold text-white" data-testid="text-catalog-title">
                Catalogo Corsi E-Learning e Videoconferenza
              </h1>
              <p className="text-yellow-400/80 text-sm mt-1">Seleziona i corsi da vendere ai tuoi clienti</p>
            </div>
            <div className="relative w-80">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" size={18} />
              <input 
                type="text"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                placeholder="Cerca corso per nome..." 
                className="w-full bg-white border border-gray-300 rounded-lg py-2.5 pl-10 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent shadow-sm"
                data-testid="input-search-catalog"
              />
            </div>
          </div>
          
          <div className="flex items-center gap-2 flex-wrap">
            <span className="text-white/70 text-xs font-medium uppercase mr-2">Categoria:</span>
            {CATEGORIES.map(cat => (
              <button
                key={cat.key}
                onClick={() => {
                  setSelectedCategory(cat.key);
                  if (cat.key !== 'TUTTI') {
                    setExpandedCategories(new Set([cat.key]));
                  }
                }}
                className={`px-3 py-1.5 rounded text-xs font-medium transition-all ${
                  selectedCategory === cat.key
                    ? 'bg-yellow-500 text-gray-900'
                    : 'bg-white/10 text-white hover:bg-white/20'
                }`}
                data-testid={`btn-category-${cat.key}`}
              >
                {cat.label}
              </button>
            ))}
          </div>
        </div>
      </div>

      <div className="p-6">
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
          <div className="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
            <table className="w-full text-sm table-fixed">
              <colgroup>
                <col style={{ width: '10%' }} />
                <col style={{ width: '7%' }} />
                <col style={{ width: '6%' }} />
                <col style={{ width: '32%' }} />
                <col style={{ width: '5%' }} />
                <col style={{ width: '12%' }} />
                <col style={{ width: '9%' }} />
                <col style={{ width: '9%' }} />
                <col style={{ width: '10%' }} />
              </colgroup>
              <thead className="bg-[#1a1a1a] text-white">
                <tr>
                  <th className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide">Sottocategoria</th>
                  <th className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide">Tipo</th>
                  <th className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide">Rischio</th>
                  <th className="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wide">Nome Corso</th>
                  <th className="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide">Ore</th>
                  <th className="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide">Modalità</th>
                  <th className="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide">Listino €</th>
                  <th className="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wide">Tuo Costo €</th>
                  <th className="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wide">Azione</th>
                </tr>
              </thead>
              <tbody>
                {groupedCourses.map(group => (
                  <>
                    <tr 
                      key={`cat-${group.category}`}
                      className="bg-[#2d2d2d] cursor-pointer hover:bg-[#3d3d3d] transition-colors"
                      onClick={() => toggleCategory(group.category)}
                    >
                      <td colSpan={9} className="px-4 py-2.5">
                        <div className="flex items-center gap-3">
                          {expandedCategories.has(group.category) ? (
                            <ChevronUp size={18} className="text-yellow-400" />
                          ) : (
                            <ChevronDown size={18} className="text-yellow-400" />
                          )}
                          <span className="font-bold text-yellow-400 text-sm uppercase tracking-wide">{group.category}</span>
                          <span className="bg-yellow-400/20 text-yellow-400 text-xs px-2.5 py-0.5 rounded-full font-medium">{group.courses.length} corsi</span>
                        </div>
                      </td>
                    </tr>
                    {expandedCategories.has(group.category) && group.courses.map((course, idx) => {
                      const subcategory = getSubcategoryLabel(course.subcategory);
                      const courseType = getCourseTypeLabel(course.courseType);
                      const risk = getRiskLabel(course.riskLevel);
                      
                      return (
                        <tr
                          key={course.id}
                          className={`border-b border-gray-100 hover:bg-yellow-50 transition-colors ${idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}`}
                          data-testid={`row-course-${course.id}`}
                        >
                          <td className="px-3 py-2.5">
                            <span className={`inline-block w-[90px] text-center px-2 py-0.5 rounded text-[11px] font-semibold text-white ${subcategory.color}`}>
                              {subcategory.label}
                            </span>
                          </td>
                          <td className="px-3 py-2.5">
                            <span className={`inline-block w-[70px] text-center px-2 py-0.5 rounded text-[11px] font-semibold text-white ${courseType.color}`}>
                              {courseType.label}
                            </span>
                          </td>
                          <td className="px-3 py-2.5">
                            <span className={`inline-block w-[50px] text-center px-2 py-0.5 rounded text-[11px] font-semibold text-white ${risk.color}`}>
                              {risk.label}
                            </span>
                          </td>
                          <td className="px-3 py-2.5 text-gray-800 font-medium">
                            {formatCourseTitle(course.title)}
                          </td>
                          <td className="px-3 py-2.5 text-center">
                            <span className="text-red-600 font-bold">{getCourseDuration(course.hours)}</span>
                          </td>
                          <td className="px-3 py-2.5 text-center">
                            <span className="text-[11px] font-medium text-gray-700">{course.modality || 'E-LEARNING'}</span>
                          </td>
                          <td className="px-3 py-2.5 text-right text-gray-700 font-medium">{formatPrice(course.listPrice)}</td>
                          <td className="px-3 py-2.5 text-right">
                            <span className="text-green-600 font-bold">{formatPrice(calculateTutorCost(course.listPrice))}</span>
                          </td>
                          <td className="px-3 py-2.5 text-center">
                            <button 
                              className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 px-3 py-1.5 rounded text-xs font-bold transition-colors flex items-center gap-1.5 mx-auto"
                              data-testid={`button-sell-${course.id}`}
                            >
                              <ShoppingCart size={14} />
                              Vendi
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
        
        <div className="mt-4 flex items-center justify-between">
          <div className="text-sm text-gray-600">
            <span className="font-semibold text-gray-800">{filteredCourses.length}</span> corsi disponibili nel catalogo
          </div>
          <div className="flex items-center gap-4 text-xs text-gray-500">
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 bg-blue-500 rounded"></span> Base
            </div>
            <div className="flex items-center gap-1.5">
              <span className="w-3 h-3 bg-orange-500 rounded"></span> Aggiornamento
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
