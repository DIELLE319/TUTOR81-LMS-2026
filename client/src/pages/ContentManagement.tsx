import { useState, useMemo } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Search, Book, Film, PlayCircle, FileText, Settings, List, Edit, LogOut, Upload, XCircle, CheckCircle } from 'lucide-react';
import type { Course, LearningProject } from '@shared/schema';
import { useAuth } from '@/hooks/use-auth';
import { useToast } from '@/hooks/use-toast';
import { apiRequest, queryClient } from '@/lib/queryClient';

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
  const { user, logout } = useAuth();
  const { toast } = useToast();

  const { data: courses = [], isLoading: loadingCourses } = useQuery<Course[]>({
    queryKey: ['/api/courses'],
  });

  const publishMutation = useMutation({
    mutationFn: async (courseId: number) => {
      return apiRequest('POST', `/api/courses/${courseId}/publish`);
    },
    onSuccess: (_, courseId) => {
      queryClient.invalidateQueries({ queryKey: ['/api/courses'] });
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Corso pubblicato!", description: "Il corso è ora disponibile nel catalogo" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile pubblicare il corso", variant: "destructive" });
    },
  });

  const unpublishMutation = useMutation({
    mutationFn: async (courseId: number) => {
      return apiRequest('POST', `/api/courses/${courseId}/unpublish`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/courses'] });
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Corso rimosso", description: "Il corso è tornato in bozza" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile rimuovere dalla pubblicazione", variant: "destructive" });
    },
  });

  const { data: projects = [] } = useQuery<LearningProject[]>({
    queryKey: ['/api/learning-projects'],
  });

  const getCourseCategory = (title: string) => {
    const t = title.toUpperCase();
    if (t.includes('LAVORATORE') || t.includes('LAVORATORI') || t.includes('EL01') || t.includes('EL - ')) return 'LAVORATORE';
    if (t.includes('PREPOSTO') || t.includes('EL02')) return 'PREPOSTO';
    if (t.includes('DIRIGENTE') || t.includes('EL03')) return 'DIRIGENTE';
    if (t.includes('RSPP') || t.includes('ASPP') || t.includes('EL04') || t.includes('EL05')) return 'RSPP/ASPP';
    if (t.includes('RLS') || t.includes('EL07')) return 'RLS';
    if (t.includes('CARRELLO') || t.includes('MULETTO') || t.includes('ELEVATORE')) return 'CARRELLO ELEVATORE';
    if (t.includes('ANTINCENDIO') || t.includes('EL08')) return 'ANTINCENDIO';
    if (t.includes('PRIMO SOCCORSO') || t.includes('SOCCORSO') || t.includes('EL09')) return 'PRIMO SOCCORSO';
    if (t.includes('HACCP') || t.includes('ALIMENTAR')) return 'HACCP';
    if (t.includes('PRIVACY') || t.includes('GDPR')) return 'PRIVACY/GDPR';
    if (t.includes('231') || t.includes('ORGANIZZATIVO')) return 'D.LGS 231';
    if (t.includes('STRESS') || t.includes('MOBBING')) return 'RISCHI PSICOSOCIALI';
    if (t.includes('AMIANTO')) return 'AMIANTO';
    if (t.includes('ELETTRIC')) return 'RISCHIO ELETTRICO';
    if (t.includes('SPAZI CONFINATI')) return 'SPAZI CONFINATI';
    if (t.includes('PONTEGGI') || t.includes('LAVORI IN QUOTA')) return 'LAVORI IN QUOTA';
    if (t.includes('GRU') || t.includes('SOLLEVAMENTO')) return 'APPARECCHI SOLLEVAMENTO';
    if (t.includes('PLE') || t.includes('PIATTAFORM')) return 'PLE';
    if (t.includes('DEMO') || t.includes('TEST')) return 'DEMO/TEST';
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
    const orderedCategories = [
      'LAVORATORE', 'PREPOSTO', 'DIRIGENTE', 'RSPP/ASPP', 'RLS', 
      'CARRELLO ELEVATORE', 'PLE', 'APPARECCHI SOLLEVAMENTO', 'LAVORI IN QUOTA',
      'ANTINCENDIO', 'PRIMO SOCCORSO', 'HACCP', 
      'RISCHIO ELETTRICO', 'SPAZI CONFINATI', 'AMIANTO',
      'PRIVACY/GDPR', 'D.LGS 231', 'RISCHI PSICOSOCIALI',
      'DEMO/TEST', 'ALTRI CORSI'
    ];
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
    <div className="min-h-screen bg-[#f0f0f0] font-sans text-[13px]">
      <header className="bg-gradient-to-b from-[#5ba3b8] to-[#4a90a4] shadow-md">
        <div className="flex items-center justify-between px-4 py-2">
          <div className="flex items-center">
            <div className="flex items-baseline">
              <span className="text-3xl font-black text-white tracking-tight" style={{ fontFamily: 'Arial Black, sans-serif' }}>Tutor</span>
              <span className="text-3xl font-black text-[#ffd700] tracking-tight" style={{ fontFamily: 'Arial Black, sans-serif' }}>81</span>
            </div>
            <span className="text-[10px] text-white/60 ml-2 italic">advanced elearning application</span>
          </div>
          
          <div className="flex items-center gap-1">
            <NavTab active={activeTab === 'catalogo'} onClick={() => setActiveTab('catalogo')}>
              Catalogo corsi
            </NavTab>
            <NavTab active={activeTab === 'lezioni'} onClick={() => setActiveTab('lezioni')}>
              Lezioni
            </NavTab>
            <NavTab active={activeTab === 'learningObjects'} onClick={() => setActiveTab('learningObjects')}>
              Learning Objects
            </NavTab>
          </div>
          
          <div className="flex items-center gap-3 text-white text-xs">
            <span>Benvenuto <strong>Superadmin {user?.firstName}</strong></span>
            <span className="text-white/50">|</span>
            <button onClick={() => logout()} className="hover:underline flex items-center gap-1">
              <LogOut size={12} />
              Logout
            </button>
          </div>
        </div>
      </header>

      {activeTab === 'catalogo' && (
        <div className="flex h-[calc(100vh-52px)]">
          <aside className="w-[380px] bg-white border-r border-gray-300 flex flex-col shadow-sm">
            <div className="p-3 border-b border-gray-200 bg-gray-50">
              <div className="flex gap-3 mb-3">
                <TabButton active={viewMode === 'progetti'} onClick={() => setViewMode('progetti')}>
                  PROGETTI
                </TabButton>
                <TabButton active={viewMode === 'corsi'} onClick={() => setViewMode('corsi')}>
                  CORSI
                </TabButton>
              </div>

              <div className="flex gap-1.5 mb-3">
                <StatusButton 
                  active={statusFilter === 'attivi'} 
                  color="green"
                  onClick={() => setStatusFilter('attivi')}
                >
                  Attivi ({activeCounts.attivi})
                </StatusButton>
                <StatusButton 
                  active={statusFilter === 'sospesi'} 
                  color="orange"
                  onClick={() => setStatusFilter('sospesi')}
                >
                  Sospesi
                </StatusButton>
                <StatusButton 
                  active={statusFilter === 'nonPubblicati'} 
                  color="red"
                  onClick={() => setStatusFilter('nonPubblicati')}
                >
                  Non Pubblicati
                </StatusButton>
              </div>

              <div className="flex flex-wrap gap-x-3 gap-y-1 mb-3 text-[11px] text-gray-600">
                <RadioOption checked={typeFilter === 'generico'} onChange={() => setTypeFilter(typeFilter === 'generico' ? null : 'generico')}>
                  Generico
                </RadioOption>
                <RadioOption checked={typeFilter === 'specifico'} onChange={() => setTypeFilter(typeFilter === 'specifico' ? null : 'specifico')}>
                  Specifico
                </RadioOption>
                <RadioOption checked={typeFilter === 'demo'} onChange={() => setTypeFilter(typeFilter === 'demo' ? null : 'demo')}>
                  Demo
                </RadioOption>
                <RadioOption checked={typeFilter === 'test'} onChange={() => setTypeFilter(typeFilter === 'test' ? null : 'test')}>
                  Test
                </RadioOption>
              </div>

              <div className="flex items-center gap-2">
                <span className="text-[11px] font-semibold text-gray-700 uppercase whitespace-nowrap">Filtra i corsi</span>
                <input 
                  type="text"
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="flex-1 border border-gray-300 rounded px-2 py-1 text-xs focus:outline-none focus:border-[#4a90a4] focus:ring-1 focus:ring-[#4a90a4]/30"
                  data-testid="input-filter-courses"
                />
              </div>
            </div>

            <div className="flex-1 overflow-y-auto">
              <table className="w-full text-[11px]">
                <thead className="bg-[#e8e8e8] sticky top-0 z-10">
                  <tr className="border-b border-gray-300">
                    <th className="px-2 py-1.5 text-left font-semibold text-gray-700 w-12">Tipo</th>
                    <th className="px-2 py-1.5 text-left font-semibold text-gray-700 w-10">ID</th>
                    <th className="px-2 py-1.5 text-left font-semibold text-gray-700">Nome Corso</th>
                  </tr>
                </thead>
                <tbody>
                  {loadingCourses ? (
                    <tr>
                      <td colSpan={3} className="text-center py-12 text-gray-400">
                        <div className="animate-spin w-6 h-6 border-2 border-[#4a90a4] border-t-transparent rounded-full mx-auto mb-2"></div>
                        Caricamento...
                      </td>
                    </tr>
                  ) : groupedCourses.length === 0 ? (
                    <tr>
                      <td colSpan={3} className="text-center py-12 text-gray-400">
                        Nessun corso trovato
                      </td>
                    </tr>
                  ) : (
                    groupedCourses.map(group => (
                      <tbody key={group.category}>
                        <tr className="bg-[#d0d0d0]">
                          <td colSpan={3} className="px-2 py-1.5 font-bold text-gray-800 text-[11px] uppercase tracking-wide">
                            {group.category}
                          </td>
                        </tr>
                        {group.items.map((course, idx) => {
                          const type = getCourseType(course.title);
                          const isSelected = selectedCourseId === course.id;
                          return (
                            <tr 
                              key={course.id}
                              className={`border-b border-gray-100 cursor-pointer transition-colors ${
                                isSelected 
                                  ? 'bg-[#4a90a4] text-white' 
                                  : idx % 2 === 0 
                                    ? 'bg-white hover:bg-[#e6f3f7]' 
                                    : 'bg-[#f8f8f8] hover:bg-[#e6f3f7]'
                              }`}
                              onClick={() => setSelectedCourseId(course.id)}
                              data-testid={`row-cms-course-${course.id}`}
                            >
                              <td className="px-2 py-1">
                                <span className={`inline-block px-1.5 py-0.5 rounded text-[9px] font-bold text-white ${
                                  type === 'Base' ? 'bg-[#3498db]' : 'bg-[#e67e22]'
                                }`}>
                                  {type}
                                </span>
                              </td>
                              <td className={`px-2 py-1 ${isSelected ? 'text-white/80' : 'text-gray-500'}`}>
                                {course.id}
                              </td>
                              <td className={`px-2 py-1 ${isSelected ? 'text-white font-medium' : 'text-gray-800'}`}>
                                {course.title}
                              </td>
                            </tr>
                          );
                        })}
                      </tbody>
                    ))
                  )}
                </tbody>
              </table>
            </div>
          </aside>

          <main className="flex-1 overflow-y-auto bg-[#f5f5f5]">
            {selectedCourse ? (
              <div className="h-full flex flex-col">
                <div className="bg-white border-b border-gray-200 px-3 py-2 flex items-center gap-2 shadow-sm flex-wrap">
                  <ActionButton icon={<FileText size={13} />}>Dettaglio corso</ActionButton>
                  <ActionButton icon={<Edit size={13} />}>Modifica</ActionButton>
                  <ActionButton icon={<PlayCircle size={13} />} primary>CORSO</ActionButton>
                  <ActionButton icon={<List size={13} />}>Visualizza domande</ActionButton>
                  <ActionButton icon={<Settings size={13} />}>Modifica listino prezzi</ActionButton>
                  
                  <div className="flex-1" />
                  
                  {selectedCourse.isPublished ? (
                    <button
                      onClick={() => unpublishMutation.mutate(selectedCourse.id)}
                      disabled={unpublishMutation.isPending}
                      className="px-4 py-1.5 text-[11px] font-medium rounded flex items-center gap-1.5 bg-orange-500 text-white hover:bg-orange-600 disabled:opacity-50 shadow-sm transition-all"
                      data-testid="btn-unpublish"
                    >
                      <XCircle size={13} />
                      {unpublishMutation.isPending ? 'Rimozione...' : 'Rimuovi pubblicazione'}
                    </button>
                  ) : (
                    <button
                      onClick={() => publishMutation.mutate(selectedCourse.id)}
                      disabled={publishMutation.isPending}
                      className="px-4 py-1.5 text-[11px] font-medium rounded flex items-center gap-1.5 bg-green-500 text-white hover:bg-green-600 disabled:opacity-50 shadow-sm transition-all"
                      data-testid="btn-publish"
                    >
                      <Upload size={13} />
                      {publishMutation.isPending ? 'Pubblicazione...' : 'Pubblica nel Catalogo'}
                    </button>
                  )}
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                  <div className="bg-white rounded shadow-sm border border-gray-200">
                    <div className="bg-gradient-to-r from-[#4a90a4] to-[#5ba3b8] px-4 py-2 rounded-t">
                      <h2 className="text-base font-bold text-white">
                        (ID:{selectedCourse.id}) {selectedCourse.title.toUpperCase()}
                      </h2>
                    </div>

                    <div className="p-4">
                      <table className="w-full text-[12px]">
                        <tbody>
                          <DetailRow label="Data di creazione" value={selectedCourse.createdAt ? new Date(selectedCourse.createdAt).toLocaleString('it-IT') : '-'} />
                          <DetailRow label="Creato da" value="Superadmin Tutor81 (ID: 6)" />
                          <DetailRow label="Requisiti minimi per accedere al corso" value="nessuno" />
                          <DetailRow label="Categoria" value="sicurezza" />
                          <DetailRow label="Sottocategoria" value="lavoratore" />
                          <DetailRow label="Tipo" value={getCourseType(selectedCourse.title).toLowerCase()} />
                          <DetailRow label="Test in presenza" value="No" />
                          <DetailRow label="Rischio Azienda" value="medio" />
                          <DetailRow label="Destinazione" value="Base+Specifico" />
                          <DetailRow 
                            label="Obiettivi del corso" 
                            value={selectedCourse.description || 'Formazione generale e specifica dei lavoratori in Aziende a rischio medio'} 
                          />
                          <DetailRow label="Rivolto a" value="" />
                          <DetailRow label="Riferimento normativo" value="Decreto 81 art. 37 - Accordo Stato-Regioni del 17/04/2025" />
                          <DetailRow label="Validità" value="quinquennale" />
                          <DetailRow label="Integrazione in aula" value="non necessaria" />
                          {linkedProject && (
                            <>
                              <DetailRow label="Durata Totale" value={`${linkedProject.hours} ore`} highlight />
                              <DetailRow label="Durata minima del corso in e-learning" value={`${linkedProject.hours} ore`} />
                              <DetailRow label="Tempo massimo per la conclusione" value="60 giorni" />
                            </>
                          )}
                        </tbody>
                      </table>

                      <div className="mt-6 space-y-4">
                        <ContentSection 
                          title="Profili e competenze per la gestione didattica" 
                          content="Il discente ha la disponibilità dei profili di competenza per la gestione didattica e tecnica E-learning quali: • Responsabile scientifico dei corsi • Mentor di contenuto • Sviluppatore della piattaforma • Tutor di processo"
                        />
                        <ContentSection 
                          title="Relatori e docenti" 
                          content="Tutte le lezioni sono state progettate e scritte da docenti qualificati con esperienza almeno decennale nel settore di competenza. In alcuni casi i docenti sono stati affiancati da attori per l'aspetto comunicativo."
                        />
                        <ContentSection 
                          title="Verifica di apprendimento" 
                          content="La verifica di apprendimento principale privilegiata nell'ambiente Tutor81 è la verifica in itinere. Si tratta di un tempo trasmessi frequentemente e con lo scopo non solo di controllare la presenza del partecipante ma di stimolare l'attenzione. Il corsista riceve un feedback immediato alla risposta rilasciata. I test sono trasmessi in modalità random, ciò significa che per la stessa domanda esistono varie alternative. In caso il risultato finale del test, sia inferiore alla soglia minima prevista dai test corretti, l'attestato non viene generato dal sistema."
                        />
                        <ContentSection 
                          title="Caratteristiche tecniche della piattaforma" 
                          content="Il metodo Tutor81 prevede una percentuale minima pari al 60% di filmati e l'integrazione di slide interattive. In ogni oggetto multimediale è inserita uno o più domande (temporizzate) rilasciate in modalità random."
                        />
                        <ContentSection 
                          title="Programma del corso" 
                          content="Concetti di rischio, danno, prevenzione, protezione, organizzazione della prevenzione aziendale, diritti, doveri e sanzioni per i vari soggetti aziendali, organi di vigilanza, controllo e assistenza."
                        />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            ) : (
              <div className="h-full flex items-center justify-center">
                <div className="text-center">
                  <div className="w-20 h-20 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                    <Book size={36} className="text-gray-400" />
                  </div>
                  <p className="text-gray-500 text-sm">Seleziona un corso dalla lista per visualizzarne i dettagli</p>
                </div>
              </div>
            )}
          </main>
        </div>
      )}

      {activeTab === 'lezioni' && (
        <div className="p-6">
          <div className="bg-white rounded shadow-sm border border-gray-200 p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <Film size={24} className="text-[#4a90a4]" />
              Gestione Lezioni
            </h2>
            <p className="text-gray-600">Qui puoi gestire le lezioni dei corsi, aggiungere nuovi moduli e contenuti.</p>
            <div className="mt-6 text-center py-16 bg-gray-50 rounded border border-dashed border-gray-300">
              <p className="text-gray-400">Seleziona un corso dal Catalogo per visualizzare e modificare le lezioni</p>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'learningObjects' && (
        <div className="p-6">
          <div className="bg-white rounded shadow-sm border border-gray-200 p-6">
            <h2 className="text-xl font-bold text-gray-800 mb-4 flex items-center gap-2">
              <PlayCircle size={24} className="text-[#4a90a4]" />
              Learning Objects
            </h2>
            <p className="text-gray-600">Gestisci i contenuti multimediali: video, audio, documenti e pacchetti SCORM.</p>
            <div className="mt-6 text-center py-16 bg-gray-50 rounded border border-dashed border-gray-300">
              <p className="text-gray-400">Funzionalità in fase di sviluppo...</p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function NavTab({ children, active, onClick }: { children: React.ReactNode; active: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={`px-4 py-1.5 text-xs font-medium rounded transition-all ${
        active 
          ? 'bg-white text-[#4a90a4] shadow-sm' 
          : 'text-white hover:bg-white/15'
      }`}
    >
      {children}
    </button>
  );
}

function TabButton({ children, active, onClick }: { children: React.ReactNode; active: boolean; onClick: () => void }) {
  return (
    <button
      onClick={onClick}
      className={`px-4 py-1 text-[11px] font-semibold border rounded transition-all ${
        active 
          ? 'bg-[#4a90a4] text-white border-[#4a90a4] shadow-sm' 
          : 'bg-white text-gray-600 border-gray-300 hover:border-[#4a90a4] hover:text-[#4a90a4]'
      }`}
    >
      {children}
    </button>
  );
}

function StatusButton({ children, active, color, onClick }: { 
  children: React.ReactNode; 
  active: boolean; 
  color: 'green' | 'orange' | 'red';
  onClick: () => void;
}) {
  const colors = {
    green: active ? 'bg-green-500 text-white border-green-500' : 'bg-white text-green-600 border-green-300 hover:bg-green-50',
    orange: active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-orange-600 border-orange-300 hover:bg-orange-50',
    red: active ? 'bg-red-500 text-white border-red-500' : 'bg-white text-red-600 border-red-300 hover:bg-red-50',
  };
  
  return (
    <button
      onClick={onClick}
      className={`px-2 py-0.5 text-[10px] font-medium border rounded transition-all ${colors[color]}`}
    >
      {children}
    </button>
  );
}

function RadioOption({ children, checked, onChange }: { children: React.ReactNode; checked: boolean; onChange: () => void }) {
  return (
    <label className="flex items-center gap-1 cursor-pointer hover:text-[#4a90a4]">
      <input 
        type="checkbox" 
        checked={checked} 
        onChange={onChange}
        className="w-3 h-3 accent-[#4a90a4]" 
      />
      <span>{children}</span>
    </label>
  );
}

function ActionButton({ children, icon, primary }: { children: React.ReactNode; icon: React.ReactNode; primary?: boolean }) {
  return (
    <button
      className={`px-3 py-1.5 text-[11px] font-medium rounded flex items-center gap-1.5 transition-all ${
        primary 
          ? 'bg-[#4a90a4] text-white hover:bg-[#3d7a8c] shadow-sm' 
          : 'bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-200'
      }`}
    >
      {icon}
      {children}
    </button>
  );
}

function DetailRow({ label, value, highlight }: { label: string; value: string; highlight?: boolean }) {
  return (
    <tr className="border-b border-gray-100">
      <td className={`py-1.5 pr-4 font-semibold text-gray-600 align-top w-[220px] ${highlight ? 'text-[#4a90a4]' : ''}`}>
        {label}
      </td>
      <td className={`py-1.5 text-gray-800 ${highlight ? 'font-bold text-[#4a90a4]' : ''}`}>
        {value}
      </td>
    </tr>
  );
}

function ContentSection({ title, content }: { title: string; content: string }) {
  return (
    <div className="border-l-4 border-[#4a90a4] pl-3">
      <h3 className="font-bold text-gray-800 text-[12px] mb-1">{title}</h3>
      <p className="text-[11px] text-gray-600 leading-relaxed">{content}</p>
    </div>
  );
}
