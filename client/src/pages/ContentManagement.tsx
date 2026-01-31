import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Search, Book, Film, PlayCircle, ChevronRight, FileText, Settings, List, Eye, Edit } from 'lucide-react';
import type { Course, LearningProject } from '@shared/schema';

type Tab = 'catalogo' | 'lezioni' | 'learningObjects';
type StatusFilter = 'attivi' | 'sospesi' | 'nonPubblicati';
type ViewMode = 'progetti' | 'corsi';

export default function ContentManagement() {
  const [activeTab, setActiveTab] = useState<Tab>('catalogo');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('attivi');
  const [viewMode, setViewMode] = useState<ViewMode>('corsi');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCourseId, setSelectedCourseId] = useState<number | null>(null);
  const [typeFilter, setTypeFilter] = useState<'generico' | 'specifico' | 'demo' | 'test' | null>(null);

  const { data: courses = [], isLoading: loadingCourses } = useQuery<Course[]>({
    queryKey: ['/api/courses'],
  });

  const { data: projects = [] } = useQuery<LearningProject[]>({
    queryKey: ['/api/learning-projects'],
  });

  const getCourseCategory = (title: string) => {
    const t = title.toUpperCase();
    if (t.includes('LAVORATORE') || t.includes('LAVORATORI')) return 'LAVORATORE';
    if (t.includes('PREPOSTO')) return 'PREPOSTO';
    if (t.includes('DIRIGENTE')) return 'DIRIGENTE';
    if (t.includes('CARRELLO') || t.includes('MULETTO')) return 'CARRELLO ELEVATORE';
    if (t.includes('RSPP') || t.includes('ASPP')) return 'RSPP/ASPP';
    if (t.includes('RLS')) return 'RLS';
    if (t.includes('ANTINCENDIO')) return 'ANTINCENDIO';
    if (t.includes('PRIMO SOCCORSO')) return 'PRIMO SOCCORSO';
    if (t.includes('HACCP')) return 'HACCP';
    return 'ALTRI CORSI';
  };

  const getCourseType = (title: string) => {
    const t = title.toLowerCase();
    if (t.includes('aggiornamento') || t.includes('agg.') || t.includes('agg ')) {
      return 'Agg';
    }
    return 'Base';
  };

  const filteredCourses = useMemo(() => {
    const items = viewMode === 'corsi' ? courses : [];
    return items.filter(c => {
      const matchesSearch = c.title.toLowerCase().includes(searchTerm.toLowerCase());
      let matchesStatus = true;
      if (statusFilter === 'attivi') {
        matchesStatus = c.isPublished === true;
      } else if (statusFilter === 'nonPubblicati') {
        matchesStatus = c.isPublished === false;
      } else if (statusFilter === 'sospesi') {
        matchesStatus = false;
      }
      return matchesSearch && matchesStatus;
    });
  }, [courses, searchTerm, statusFilter, viewMode]);

  const groupedCourses = useMemo(() => {
    const groups: { [key: string]: typeof filteredCourses } = {};
    filteredCourses.forEach(course => {
      const category = getCourseCategory(course.title);
      if (!groups[category]) groups[category] = [];
      groups[category].push(course);
    });
    const orderedCategories = ['LAVORATORE', 'PREPOSTO', 'DIRIGENTE', 'CARRELLO ELEVATORE', 'RSPP/ASPP', 'RLS', 'ANTINCENDIO', 'PRIMO SOCCORSO', 'HACCP', 'ALTRI CORSI'];
    return orderedCategories
      .filter(cat => groups[cat]?.length > 0)
      .map(cat => ({ category: cat, items: groups[cat] }));
  }, [filteredCourses]);

  const selectedCourse = useMemo(() => {
    return courses.find(c => c.id === selectedCourseId);
  }, [courses, selectedCourseId]);

  const linkedProject = useMemo(() => {
    if (!selectedCourse?.learningProjectId) return null;
    return projects.find(p => p.id === selectedCourse.learningProjectId);
  }, [selectedCourse, projects]);

  const activeCounts = useMemo(() => {
    const attivi = courses.filter(c => c.isPublished === true).length;
    const nonPubblicati = courses.filter(c => c.isPublished === false).length;
    const sospesi = 0;
    return { attivi, sospesi, nonPubblicati };
  }, [courses]);

  return (
    <div className="min-h-screen bg-gray-100 font-sans">
      <div className="bg-[#4a90a4] text-white">
        <div className="flex items-center justify-between px-6 py-3">
          <div className="flex items-center gap-2">
            <span className="text-2xl font-bold">Tutor</span>
            <span className="text-2xl font-bold text-yellow-300">81</span>
            <span className="text-xs text-white/70 ml-2">advanced elearning application</span>
          </div>
          <div className="flex items-center gap-6">
            <button 
              className={`px-4 py-2 rounded text-sm font-medium transition-colors ${activeTab === 'catalogo' ? 'bg-white/20' : 'hover:bg-white/10'}`}
              onClick={() => setActiveTab('catalogo')}
              data-testid="tab-catalogo"
            >
              Catalogo corsi
            </button>
            <button 
              className={`px-4 py-2 rounded text-sm font-medium transition-colors ${activeTab === 'lezioni' ? 'bg-white/20' : 'hover:bg-white/10'}`}
              onClick={() => setActiveTab('lezioni')}
              data-testid="tab-lezioni"
            >
              Lezioni
            </button>
            <button 
              className={`px-4 py-2 rounded text-sm font-medium transition-colors ${activeTab === 'learningObjects' ? 'bg-white/20' : 'hover:bg-white/10'}`}
              onClick={() => setActiveTab('learningObjects')}
              data-testid="tab-learning-objects"
            >
              Learning Objects
            </button>
          </div>
          <div className="text-sm text-white/80">
            Benvenuto Superadmin | <button className="underline hover:text-white">Logout</button>
          </div>
        </div>
      </div>

      {activeTab === 'catalogo' && (
        <div className="flex">
          <div className="w-[400px] bg-white border-r border-gray-200 min-h-[calc(100vh-60px)]">
            <div className="border-b border-gray-200 p-3">
              <div className="flex gap-4 mb-3">
                <button 
                  className={`px-3 py-1 text-xs font-medium border rounded ${viewMode === 'progetti' ? 'bg-[#4a90a4] text-white border-[#4a90a4]' : 'bg-white text-gray-600 border-gray-300'}`}
                  onClick={() => setViewMode('progetti')}
                  data-testid="btn-view-progetti"
                >
                  PROGETTI
                </button>
                <button 
                  className={`px-3 py-1 text-xs font-medium border rounded ${viewMode === 'corsi' ? 'bg-[#4a90a4] text-white border-[#4a90a4]' : 'bg-white text-gray-600 border-gray-300'}`}
                  onClick={() => setViewMode('corsi')}
                  data-testid="btn-view-corsi"
                >
                  CORSI
                </button>
              </div>

              <div className="flex gap-2 mb-3 text-xs">
                <button 
                  className={`px-2 py-1 rounded ${statusFilter === 'attivi' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-600'}`}
                  onClick={() => setStatusFilter('attivi')}
                  data-testid="filter-attivi"
                >
                  Attivi ({activeCounts.attivi})
                </button>
                <button 
                  className={`px-2 py-1 rounded ${statusFilter === 'sospesi' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-600'}`}
                  onClick={() => setStatusFilter('sospesi')}
                  data-testid="filter-sospesi"
                >
                  Sospesi
                </button>
                <button 
                  className={`px-2 py-1 rounded ${statusFilter === 'nonPubblicati' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-600'}`}
                  onClick={() => setStatusFilter('nonPubblicati')}
                  data-testid="filter-non-pubblicati"
                >
                  Non Pubblicati
                </button>
              </div>

              <div className="flex gap-2 mb-3 text-xs">
                <label className="flex items-center gap-1">
                  <input type="radio" name="type" checked={typeFilter === 'generico'} onChange={() => setTypeFilter('generico')} className="w-3 h-3" />
                  Generico
                </label>
                <label className="flex items-center gap-1">
                  <input type="radio" name="type" checked={typeFilter === 'specifico'} onChange={() => setTypeFilter('specifico')} className="w-3 h-3" />
                  Specifico
                </label>
                <label className="flex items-center gap-1">
                  <input type="radio" name="type" checked={typeFilter === 'demo'} onChange={() => setTypeFilter('demo')} className="w-3 h-3" />
                  Demo
                </label>
                <label className="flex items-center gap-1">
                  <input type="radio" name="type" checked={typeFilter === 'test'} onChange={() => setTypeFilter('test')} className="w-3 h-3" />
                  Test
                </label>
              </div>

              <div className="flex items-center gap-2 text-xs text-gray-600 mb-3">
                <span className="font-medium">FILTRA I CORSI</span>
                <input 
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  placeholder=""
                  className="flex-1 border border-gray-300 rounded px-2 py-1 text-xs"
                  data-testid="input-filter-courses"
                />
              </div>
            </div>

            <div className="overflow-y-auto max-h-[calc(100vh-240px)]">
              <table className="w-full text-xs">
                <thead className="bg-gray-50 sticky top-0">
                  <tr>
                    <th className="px-2 py-2 text-left font-medium text-gray-600">Tipo</th>
                    <th className="px-2 py-2 text-left font-medium text-gray-600">ID</th>
                    <th className="px-2 py-2 text-left font-medium text-gray-600">Nome Corso</th>
                  </tr>
                </thead>
                <tbody>
                  {loadingCourses ? (
                    <tr>
                      <td colSpan={3} className="text-center py-8 text-gray-400">
                        Caricamento...
                      </td>
                    </tr>
                  ) : (
                    groupedCourses.map(group => (
                      <>
                        <tr key={`cat-${group.category}`} className="bg-gray-100">
                          <td colSpan={3} className="px-2 py-2 font-bold text-gray-700 text-xs uppercase">
                            {group.category}
                          </td>
                        </tr>
                        {group.items.map(course => {
                          const type = getCourseType(course.title);
                          return (
                            <tr 
                              key={course.id}
                              className={`border-b border-gray-100 cursor-pointer hover:bg-blue-50 ${selectedCourseId === course.id ? 'bg-blue-100' : ''}`}
                              onClick={() => setSelectedCourseId(course.id)}
                              data-testid={`row-cms-course-${course.id}`}
                            >
                              <td className="px-2 py-1.5">
                                <span className={`inline-block px-1.5 py-0.5 rounded text-[10px] font-medium text-white ${type === 'Base' ? 'bg-blue-500' : 'bg-orange-500'}`}>
                                  {type}
                                </span>
                              </td>
                              <td className="px-2 py-1.5 text-gray-500">{course.id}</td>
                              <td className="px-2 py-1.5 text-gray-800 text-[11px]">{course.title}</td>
                            </tr>
                          );
                        })}
                      </>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </div>

          <div className="flex-1 p-4">
            {selectedCourse ? (
              <div className="bg-white rounded shadow-sm">
                <div className="border-b border-gray-200 px-4 py-2 flex gap-4 text-xs">
                  <button className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded flex items-center gap-1.5" data-testid="btn-dettaglio">
                    <FileText size={14} />
                    Dettaglio corso
                  </button>
                  <button className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded flex items-center gap-1.5" data-testid="btn-modifica">
                    <Edit size={14} />
                    Modifica
                  </button>
                  <button className="px-3 py-1.5 bg-[#4a90a4] text-white rounded flex items-center gap-1.5" data-testid="btn-corso">
                    <PlayCircle size={14} />
                    CORSO
                  </button>
                  <button className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded flex items-center gap-1.5" data-testid="btn-domande">
                    <List size={14} />
                    Visualizza domande
                  </button>
                  <button className="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded flex items-center gap-1.5" data-testid="btn-listino">
                    <Settings size={14} />
                    Modifica listino prezzi
                  </button>
                </div>

                <div className="p-4">
                  <h2 className="text-lg font-bold text-[#4a90a4] mb-4">
                    (ID:{selectedCourse.id}) {selectedCourse.title}
                  </h2>

                  <div className="grid grid-cols-[200px_1fr] gap-y-2 text-sm">
                    <div className="font-medium text-gray-600">Data di creazione</div>
                    <div className="text-gray-800">{selectedCourse.createdAt ? new Date(selectedCourse.createdAt).toLocaleString('it-IT') : '-'}</div>
                    
                    <div className="font-medium text-gray-600">Requisiti minimi per accedere</div>
                    <div className="text-gray-800">nessuno</div>
                    
                    <div className="font-medium text-gray-600">Categoria</div>
                    <div className="text-gray-800">sicurezza</div>
                    
                    <div className="font-medium text-gray-600">Sottocategoria</div>
                    <div className="text-gray-800">lavoratore</div>
                    
                    <div className="font-medium text-gray-600">Tipo</div>
                    <div className="text-gray-800">{getCourseType(selectedCourse.title).toLowerCase()}</div>
                    
                    <div className="font-medium text-gray-600">Test in presenza</div>
                    <div className="text-gray-800">No</div>
                    
                    <div className="font-medium text-gray-600">Rischio Azienda</div>
                    <div className="text-gray-800">medio</div>
                    
                    <div className="font-medium text-gray-600">Destinazione</div>
                    <div className="text-gray-800">Base+Specifico</div>
                    
                    <div className="font-medium text-gray-600">Obiettivi del corso</div>
                    <div className="text-gray-800">{selectedCourse.description || 'Formazione generale e specifica dei lavoratori in Aziende a rischio medio'}</div>
                    
                    {linkedProject && (
                      <>
                        <div className="font-medium text-gray-600">Progetto Formativo</div>
                        <div className="text-gray-800">{linkedProject.title}</div>
                        
                        <div className="font-medium text-gray-600">Durata Totale</div>
                        <div className="text-gray-800">{linkedProject.hours} ore</div>
                        
                        <div className="font-medium text-gray-600">Riferimento normativo</div>
                        <div className="text-gray-800">Decreto 81 art. 37 - Accordo Stato-Regioni</div>
                        
                        <div className="font-medium text-gray-600">Validità</div>
                        <div className="text-gray-800">quinquennale</div>
                      </>
                    )}
                    
                    <div className="font-medium text-gray-600">Stato pubblicazione</div>
                    <div className="text-gray-800">
                      <span className={`inline-block px-2 py-0.5 rounded text-xs ${selectedCourse.isPublished ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                        {selectedCourse.isPublished ? 'Pubblicato' : 'Non pubblicato'}
                      </span>
                    </div>
                  </div>

                  <div className="mt-6 border-t pt-4">
                    <h3 className="font-bold text-gray-700 mb-2">Profili e competenze per la gestione didattica</h3>
                    <p className="text-sm text-gray-600">
                      Il discente ha la disponibilità dei profili di competenza per la gestione didattica e tecnica E-learning quali:
                      • Responsabile scientifico dei corsi • Mentor di contenuto • Sviluppatore della piattaforma • Tutor di processo
                    </p>
                  </div>

                  <div className="mt-4">
                    <h3 className="font-bold text-gray-700 mb-2">Relatori e docenti</h3>
                    <p className="text-sm text-gray-600">
                      Tutte le lezioni sono state progettate e scritte da docenti qualificati con esperienza almeno decennale nel settore di competenza.
                    </p>
                  </div>

                  <div className="mt-4">
                    <h3 className="font-bold text-gray-700 mb-2">Verifica di apprendimento</h3>
                    <p className="text-sm text-gray-600">
                      La verifica di apprendimento principale privilegiata nell'ambiente Tutor81 è la verifica in itinere. 
                      Si tratta di un tempo trasmessi frequentemente e con lo scopo non solo di controllare la presenza del partecipante ma di stimolare l'attenzione.
                    </p>
                  </div>
                </div>
              </div>
            ) : (
              <div className="bg-white rounded shadow-sm p-8 text-center text-gray-500">
                <Book size={48} className="mx-auto mb-4 text-gray-300" />
                <p>Seleziona un corso dalla lista per visualizzarne i dettagli</p>
              </div>
            )}
          </div>
        </div>
      )}

      {activeTab === 'lezioni' && (
        <div className="p-6">
          <div className="bg-white rounded shadow-sm p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <Film size={24} className="text-[#4a90a4]" />
              Gestione Lezioni
            </h2>
            <p className="text-gray-600">Qui puoi gestire le lezioni dei corsi, aggiungere nuovi moduli e contenuti.</p>
            <div className="mt-6 text-center py-12 bg-gray-50 rounded">
              <p className="text-gray-400">Seleziona un corso per visualizzare e modificare le lezioni</p>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'learningObjects' && (
        <div className="p-6">
          <div className="bg-white rounded shadow-sm p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <PlayCircle size={24} className="text-[#4a90a4]" />
              Learning Objects
            </h2>
            <p className="text-gray-600">Gestisci i contenuti multimediali: video, audio, documenti e pacchetti SCORM.</p>
            <div className="mt-6 text-center py-12 bg-gray-50 rounded">
              <p className="text-gray-400">Funzionalità in fase di sviluppo...</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
