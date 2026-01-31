import { useState, useMemo } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Search, Book, Film, PlayCircle, FileText, Settings, List, Edit, LogOut, Upload, XCircle, CheckCircle } from 'lucide-react';
import type { LearningProject, Company } from '@shared/schema';
import { useAuth } from '@/hooks/use-auth';
import { useToast } from '@/hooks/use-toast';
import { apiRequest, queryClient } from '@/lib/queryClient';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

type Tab = 'catalogo' | 'lezioni' | 'learningObjects';
type StatusFilter = 'attivi' | 'sospesi' | 'nonPubblicati';

export default function ContentManagement() {
  const [activeTab, setActiveTab] = useState<Tab>('catalogo');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('attivi');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCourseId, setSelectedCourseId] = useState<number | null>(null);
  const { user, logout } = useAuth();
  const { toast } = useToast();

  const { data: projects = [], isLoading: loadingProjects } = useQuery<LearningProject[]>({
    queryKey: ['/api/learning-projects'],
  });

  const publishMutation = useMutation({
    mutationFn: async (projectId: number) => {
      return apiRequest('POST', `/api/learning-projects/${projectId}/publish`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Corso pubblicato!", description: "Il corso è ora disponibile nel catalogo" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile pubblicare il corso", variant: "destructive" });
    },
  });

  const unpublishMutation = useMutation({
    mutationFn: async (projectId: number) => {
      return apiRequest('POST', `/api/learning-projects/${projectId}/unpublish`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Corso rimosso", description: "Il corso è tornato in bozza" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile rimuovere dalla pubblicazione", variant: "destructive" });
    },
  });

  const { data: tutors = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies/tutors'],
  });

  const reserveMutation = useMutation({
    mutationFn: async ({ learningProjectId, reservedTo }: { learningProjectId: number; reservedTo: number | null }) => {
      return apiRequest('PATCH', `/api/learning-projects/${learningProjectId}/reserve`, { reservedTo });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Corso aggiornato", description: "La reservation è stata salvata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare la reservation", variant: "destructive" });
    },
  });

  const getCourseCategory = (title: string) => {
    const t = title.toUpperCase();
    // Controlla prima i ruoli specifici (DIRIGENTE, PREPOSTO, RSPP) PRIMA di LAVORATORE
    if (t.includes('DIRIGENTE') || t.includes('EL03')) return 'DIRIGENTE';
    if (t.includes('PREPOSTO') || t.includes('EL02')) return 'PREPOSTO';
    if (t.includes('RSPP') || t.includes('ASPP') || t.includes('DATORE DI LAVORO') || t.includes('EL04') || t.includes('EL05')) return 'RSPP/ASPP';
    if (t.includes('RLS') || t.includes('EL07')) return 'RLS';
    // Attrezzature
    if (t.includes('CARRELLO') || t.includes('MULETTO') || t.includes('ELEVATORE')) return 'CARRELLO ELEVATORE';
    if (t.includes('PLE') || t.includes('PIATTAFORM')) return 'PLE';
    if (t.includes('GRU') || t.includes('SOLLEVAMENTO')) return 'APPARECCHI SOLLEVAMENTO';
    if (t.includes('PONTEGGI') || t.includes('LAVORI IN QUOTA')) return 'LAVORI IN QUOTA';
    // Emergenze
    if (t.includes('ANTINCENDIO') || t.includes('EL08')) return 'ANTINCENDIO';
    if (t.includes('PRIMO SOCCORSO') || t.includes('SOCCORSO') || t.includes('EL09')) return 'PRIMO SOCCORSO';
    // Altri corsi specifici
    if (t.includes('HACCP') || t.includes('ALIMENTAR')) return 'HACCP';
    if (t.includes('PRIVACY') || t.includes('GDPR')) return 'PRIVACY/GDPR';
    if (t.includes('231') || t.includes('ORGANIZZATIVO') || t.includes('MOG')) return 'D.LGS 231';
    if (t.includes('STRESS') || t.includes('MOBBING')) return 'RISCHI PSICOSOCIALI';
    if (t.includes('AMIANTO')) return 'AMIANTO';
    if (t.includes('ELETTRIC')) return 'RISCHIO ELETTRICO';
    if (t.includes('SPAZI CONFINATI')) return 'SPAZI CONFINATI';
    if (t.includes('PARITA') || t.includes('GENERE') || t.includes('LIBELLULA')) return 'PARITÀ DI GENERE';
    if (t.includes('DEMO') || t.includes('TEST')) return 'DEMO/TEST';
    // LAVORATORE va controllato per ultimo (è il più generico)
    if (t.includes('LAVORATORE') || t.includes('LAVORATORI') || t.includes('EL01') || t.includes('EL - ')) return 'LAVORATORE';
    return 'ALTRI CORSI';
  };

  const getCourseType = (title: string): { label: string; color: string } => {
    const t = title.toUpperCase();
    // Controlla prima i ruoli specifici PRIMA di LAVORATORE
    if (t.includes('DIRIGENTE')) return { label: 'DIR', color: 'bg-indigo-600' };
    if (t.includes('PREPOSTO')) return { label: 'PRE', color: 'bg-purple-600' };
    if (t.includes('RSPP') || t.includes('ASPP') || t.includes('DATORE DI LAVORO')) return { label: 'RSPP', color: 'bg-red-600' };
    if (t.includes('RLS')) return { label: 'RLS', color: 'bg-orange-600' };
    if (t.includes('CARRELLO') || t.includes('MULETTO')) return { label: 'CAR', color: 'bg-amber-600' };
    if (t.includes('PLE')) return { label: 'PLE', color: 'bg-yellow-600' };
    if (t.includes('ANTINCENDIO')) return { label: 'ANT', color: 'bg-red-500' };
    if (t.includes('PRIMO SOCCORSO') || t.includes('SOCCORSO')) return { label: 'PS', color: 'bg-green-600' };
    if (t.includes('HACCP')) return { label: 'HAC', color: 'bg-teal-600' };
    if (t.includes('PRIVACY') || t.includes('GDPR')) return { label: 'PRV', color: 'bg-gray-600' };
    if (t.includes('231') || t.includes('MOG')) return { label: '231', color: 'bg-slate-600' };
    if (t.includes('PARITA') || t.includes('GENERE') || t.includes('LIBELLULA')) return { label: 'PAR', color: 'bg-pink-600' };
    // LAVORATORE per ultimo
    if (t.includes('LAVORATORE') || t.includes('LAVORATORI')) return { label: 'LAV', color: 'bg-blue-600' };
    return { label: 'GEN', color: 'bg-gray-500' };
  };

  const formatCourseTitle = (title: string) => {
    return title
      .replace(/^EL\d*[a-zA-Z]?\s*-\s*/i, '')
      .replace(/^EL\s*-\s*/i, '')
      .trim();
  };

  const getCourseDuration = (title: string, hours: number | null) => {
    if (hours && hours > 0) return `${hours}`;
    const match = title.match(/(\d+)\s*ore/i);
    if (match) return match[1];
    return '-';
  };

  const stripHtml = (html: string | null) => {
    if (!html) return '';
    return html
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&amp;/g, '&')
      .replace(/&quot;/g, '"')
      .replace(/&#39;/g, "'")
      .replace(/&nbsp;/g, ' ')
      .replace(/&rsquo;/g, "'")
      .replace(/&lsquo;/g, "'")
      .replace(/&rdquo;/g, '"')
      .replace(/&ldquo;/g, '"')
      .replace(/&agrave;/g, 'à')
      .replace(/&egrave;/g, 'è')
      .replace(/&igrave;/g, 'ì')
      .replace(/&ograve;/g, 'ò')
      .replace(/&ugrave;/g, 'ù')
      .replace(/<[^>]*>/g, '')
      .trim();
  };

  const filteredProjects = useMemo(() => {
    return projects.filter(p => {
      const matchesSearch = p.title.toLowerCase().includes(searchTerm.toLowerCase());
      let matchesStatus = true;
      if (statusFilter === 'attivi') {
        matchesStatus = p.isPublishedInEcommerce === 1;
      } else if (statusFilter === 'nonPubblicati') {
        matchesStatus = p.isPublishedInEcommerce === 0;
      } else if (statusFilter === 'sospesi') {
        matchesStatus = p.isPublishedInEcommerce === 2;
      }
      return matchesSearch && matchesStatus;
    });
  }, [projects, searchTerm, statusFilter]);

  const groupedProjects = useMemo(() => {
    const groups: { [key: string]: typeof filteredProjects } = {};
    filteredProjects.forEach(project => {
      const category = getCourseCategory(project.title);
      if (!groups[category]) groups[category] = [];
      groups[category].push(project);
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
  }, [filteredProjects]);

  const selectedProject = useMemo(() => {
    return projects.find(p => p.id === selectedCourseId);
  }, [projects, selectedCourseId]);

  const activeCounts = useMemo(() => {
    const attivi = projects.filter(p => p.isPublishedInEcommerce === 1).length;
    const nonPubblicati = projects.filter(p => p.isPublishedInEcommerce === 0).length;
    const sospesi = projects.filter(p => p.isPublishedInEcommerce === 2).length;
    return { attivi, sospesi, nonPubblicati };
  }, [projects]);

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
          <aside className="w-[480px] bg-white border-r border-gray-300 flex flex-col shadow-sm">
            <div className="p-3 border-b border-gray-200 bg-gray-50">
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
                    <th className="px-2 py-1.5 text-center font-semibold text-gray-700 w-14">Ore</th>
                  </tr>
                </thead>
                {loadingProjects ? (
                  <tbody>
                    <tr>
                      <td colSpan={4} className="text-center py-12 text-gray-400">
                        <div className="animate-spin w-6 h-6 border-2 border-[#4a90a4] border-t-transparent rounded-full mx-auto mb-2"></div>
                        Caricamento...
                      </td>
                    </tr>
                  </tbody>
                ) : groupedProjects.length === 0 ? (
                  <tbody>
                    <tr>
                      <td colSpan={4} className="text-center py-12 text-gray-400">
                        Nessun corso trovato
                      </td>
                    </tr>
                  </tbody>
                ) : (
                  groupedProjects.map(group => (
                    <tbody key={group.category}>
                      <tr className="bg-[#d0d0d0]">
                        <td colSpan={4} className="px-2 py-1.5 font-bold text-gray-800 text-[11px] uppercase tracking-wide">
                          {group.category}
                        </td>
                      </tr>
                      {group.items.map((project, idx) => {
                        const type = getCourseType(project.title);
                        const isSelected = selectedCourseId === project.id;
                        return (
                          <tr 
                            key={project.id}
                            className={`border-b border-gray-100 cursor-pointer transition-colors ${
                              isSelected 
                                ? 'bg-[#4a90a4] text-white' 
                                : idx % 2 === 0 
                                  ? 'bg-white hover:bg-[#e6f3f7]' 
                                  : 'bg-[#f8f8f8] hover:bg-[#e6f3f7]'
                            }`}
                            onClick={() => setSelectedCourseId(project.id)}
                            data-testid={`row-cms-course-${project.id}`}
                          >
                            <td className="px-2 py-1">
                              <span className={`inline-block px-1.5 py-0.5 rounded text-[9px] font-bold text-white ${type.color}`}>
                                {type.label}
                              </span>
                            </td>
                            <td className={`px-2 py-1 ${isSelected ? 'text-white/80' : 'text-gray-500'}`}>
                              {project.id}
                            </td>
                            <td className={`px-2 py-1 ${isSelected ? 'text-white font-medium' : 'text-gray-800'}`}>
                              {formatCourseTitle(project.title)}
                            </td>
                            <td className="px-2 py-1 text-center text-red-600 font-bold">
                              {getCourseDuration(project.title, project.hours)}
                            </td>
                          </tr>
                        );
                      })}
                    </tbody>
                  ))
                )}
              </table>
            </div>
          </aside>

          <main className="flex-1 overflow-y-auto bg-[#f5f5f5]">
            {selectedProject ? (
              <div className="h-full flex flex-col">
                <div className="bg-white border-b border-gray-200 px-3 py-2 flex items-center gap-1 shadow-sm flex-wrap">
                  <ActionButton icon={<FileText size={13} />}>Dettaglio corso</ActionButton>
                  <ActionButton icon={<Edit size={13} />}>Modifica</ActionButton>
                  <ActionButton icon={<List size={13} />}>Aggiungi modulo</ActionButton>
                  <ActionButton icon={<List size={13} />}>Visualizza domande</ActionButton>
                  <ActionButton icon={<Settings size={13} />}>Modifica listino prezzi</ActionButton>
                  
                  {selectedProject.isPublishedInEcommerce === 1 ? (
                    <ActionButton 
                      icon={<XCircle size={13} />}
                      onClick={() => unpublishMutation.mutate(selectedProject.id)}
                    >
                      {unpublishMutation.isPending ? 'Rimozione...' : 'Rimuovi'}
                    </ActionButton>
                  ) : (
                    <ActionButton 
                      icon={<Upload size={13} />} 
                      primary
                      onClick={() => publishMutation.mutate(selectedProject.id)}
                    >
                      {publishMutation.isPending ? 'Pubblicazione...' : 'Pubblica'}
                    </ActionButton>
                  )}
                  
                  <ActionButton icon={<XCircle size={13} />}>Chiudi corso</ActionButton>
                  <ActionButton icon={<XCircle size={13} />}>Rimuovi</ActionButton>
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                  <div className="bg-white rounded shadow-sm border border-gray-200">
                    <div className="bg-gradient-to-r from-[#4a90a4] to-[#5ba3b8] px-4 py-2 rounded-t">
                      <h2 className="text-base font-bold text-white">
                        (ID:{selectedProject.id}) {formatCourseTitle(selectedProject.title).toUpperCase()}
                      </h2>
                    </div>

                    <div className="p-4">
                      <table className="w-full text-[12px]">
                        <tbody>
                          <DetailRow label="Data di creazione" value={selectedProject.createdAt ? new Date(selectedProject.createdAt).toLocaleString('it-IT') : '-'} />
                          <DetailRow label="Creato da" value="Superadmin Tutor81 (ID: 6)" />
                          <DetailRow label="Requisiti minimi per accedere al corso" value={selectedProject.prerequisites || "nessuno"} />
                          <DetailRow label="Categoria" value={selectedProject.category || "sicurezza"} />
                          <DetailRow label="Sottocategoria" value={selectedProject.subcategory || "lavoratore"} />
                          <DetailRow label="Tipo" value={selectedProject.courseType || getCourseType(selectedProject.title).label} />
                          <DetailRow label="Test in presenza" value="No" />
                          <DetailRow label="Rischio Azienda" value={selectedProject.riskLevel || "medio"} />
                          <DetailRow label="Destinazione" value={selectedProject.destination || "Base+Specifico"} />
                          <DetailRow 
                            label="Obiettivi del corso" 
                            value={stripHtml(selectedProject.objectives) || 'Formazione generale e specifica dei lavoratori'} 
                          />
                          <DetailRow 
                            label="Programma del corso" 
                            value={stripHtml(selectedProject.courseProgram || selectedProject.description) || ''} 
                          />
                          <DetailRow label="Rivolto a" value={selectedProject.targetAudience || ""} />
                          <DetailRow label="Riferimento normativo" value={selectedProject.lawReference || "Decreto 81 art. 37 - Accordo Stato-Regioni del 17/04/2025"} />
                          <DetailRow label="Validità" value={selectedProject.courseValidity || "quinquennale"} />
                          <DetailRow label="Integrazione in aula" value={selectedProject.externalIntegration || "non necessaria"} />
                          <DetailRow label="Durata Totale" value={`${selectedProject.hours || 0} ore`} highlight />
                          <DetailRow label="Durata minima del corso in e-learning" value={`${selectedProject.totalElearning || selectedProject.hours || 0} ore`} />
                          <DetailRow label="Tempo massimo per la conclusione" value={`${selectedProject.maxExecutionTime || 60} giorni`} />
                          <DetailRow 
                            label="Prezzo di listino" 
                            value={selectedProject.listPrice && parseFloat(selectedProject.listPrice) > 0 ? `€ ${parseFloat(selectedProject.listPrice).toFixed(2)}` : 'Non definito'} 
                            highlight 
                          />
                          <tr className="border-b border-gray-100">
                            <td className="py-2 pr-4 text-gray-600 font-medium w-[200px] align-top">Riservato a</td>
                            <td className="py-2">
                              <Select
                                value={selectedProject.reservedTo?.toString() || "none"}
                                onValueChange={(value) => {
                                  reserveMutation.mutate({
                                    learningProjectId: selectedProject.id,
                                    reservedTo: value === "none" ? null : parseInt(value)
                                  });
                                }}
                              >
                                <SelectTrigger className="w-[300px] h-8 text-xs" data-testid="select-reserved-to">
                                  <SelectValue placeholder="Seleziona ente formativo..." />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="none">Nessuna reservation</SelectItem>
                                  {tutors.map(tutor => (
                                    <SelectItem key={tutor.id} value={tutor.id.toString()}>
                                      {tutor.businessName}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                            </td>
                          </tr>
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

function ActionButton({ children, icon, primary, onClick }: { children: React.ReactNode; icon: React.ReactNode; primary?: boolean; onClick?: () => void }) {
  return (
    <button
      onClick={onClick}
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
      <td className={`py-1.5 pr-4 font-semibold text-gray-600 align-top w-[220px] ${highlight ? 'text-red-600' : ''}`}>
        {label}
      </td>
      <td className={`py-1.5 text-gray-800 ${highlight ? 'font-bold text-red-600' : ''}`}>
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
