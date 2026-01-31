import { useState, useMemo, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Search, Book, Film, PlayCircle, FileText, Settings, List, Edit, LogOut, Upload, XCircle, CheckCircle, Mail, Printer } from 'lucide-react';
import type { LearningProject, Company, LearningObject } from '@shared/schema';
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
type StatusFilter = 'attivi' | 'sospesi' | 'nonPubblicati' | 'riservati' | 'test';

// Keywords per identificare corsi di test/demo
const TEST_KEYWORDS = ['komplett', 'trops', 'innovyn', 'inovyn', 'prova', 'test', 'italpress'];
const isTestCourse = (title: string) => {
  const lowerTitle = title.toLowerCase();
  return TEST_KEYWORDS.some(keyword => lowerTitle.includes(keyword));
};

export default function ContentManagement() {
  const [activeTab, setActiveTab] = useState<Tab>('catalogo');
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('attivi');
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCourseId, setSelectedCourseId] = useState<number | null>(null);
  const [selectedLOs, setSelectedLOs] = useState<Set<number>>(new Set());
  const [selectedLO, setSelectedLO] = useState<LearningObject | null>(null);
  const [loDetails, setLoDetails] = useState<any>(null);
  const [loadingDetails, setLoadingDetails] = useState(false);
  const { user, logout } = useAuth();
  const { toast } = useToast();

  const { data: projects = [], isLoading: loadingProjects } = useQuery<LearningProject[]>({
    queryKey: ['/api/learning-projects'],
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies'],
  });

  const { data: learningObjects = [], isLoading: loadingLOs } = useQuery<LearningObject[]>({
    queryKey: ['/api/learning-objects'],
  });

  const companyLookup = useMemo(() => {
    const lookup: Record<number, string> = {};
    companies.forEach(c => {
      lookup[c.id] = c.businessName || `Ente #${c.id}`;
    });
    return lookup;
  }, [companies]);

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

  // Load learning object details when selected
  useEffect(() => {
    if (selectedLO) {
      setLoadingDetails(true);
      fetch(`/api/learning-objects/${selectedLO.id}/details`, { credentials: 'include' })
        .then(res => res.json())
        .then(data => {
          setLoDetails(data);
          setLoadingDetails(false);
        })
        .catch(() => setLoadingDetails(false));
    } else {
      setLoDetails(null);
    }
  }, [selectedLO]);

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

  const updateCategoryMutation = useMutation({
    mutationFn: async ({ projectId, category }: { projectId: number; category: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/category`, { category });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Categoria aggiornata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare la categoria", variant: "destructive" });
    },
  });

  const updateSubcategoryMutation = useMutation({
    mutationFn: async ({ projectId, subcategory }: { projectId: number; subcategory: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/subcategory`, { subcategory });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Sottocategoria aggiornata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare la sottocategoria", variant: "destructive" });
    },
  });

  const updateSectorMutation = useMutation({
    mutationFn: async ({ projectId, sector }: { projectId: number; sector: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/sector`, { sector });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Settore aggiornato" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare il settore", variant: "destructive" });
    },
  });

  const updateCourseTypeMutation = useMutation({
    mutationFn: async ({ projectId, courseType }: { projectId: number; courseType: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/course-type`, { courseType });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Tipo corso aggiornato" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare il tipo corso", variant: "destructive" });
    },
  });

  const updateModalityMutation = useMutation({
    mutationFn: async ({ projectId, modality }: { projectId: number; modality: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/modality`, { modality });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Modalità aggiornata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare la modalità", variant: "destructive" });
    },
  });

  const updateRiskLevelMutation = useMutation({
    mutationFn: async ({ projectId, riskLevel }: { projectId: number; riskLevel: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/risk-level`, { riskLevel });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Rischio aggiornato" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare il rischio", variant: "destructive" });
    },
  });

  const updateValidityMutation = useMutation({
    mutationFn: async ({ projectId, validity }: { projectId: number; validity: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/validity`, { validity });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Validità aggiornata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare la validità", variant: "destructive" });
    },
  });

  const updateIntegrationMutation = useMutation({
    mutationFn: async ({ projectId, integration }: { projectId: number; integration: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/integration`, { integration });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Integrazione aggiornata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare l'integrazione", variant: "destructive" });
    },
  });

  const updateDestinationMutation = useMutation({
    mutationFn: async ({ projectId, destination }: { projectId: number; destination: string }) => {
      return apiRequest('PATCH', `/api/learning-projects/${projectId}/destination`, { destination });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/learning-projects'] });
      toast({ title: "Destinazione aggiornata" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare la destinazione", variant: "destructive" });
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
    // Rimuove tutto ciò che c'è prima del primo trattino (codici come el01BALL, st01B, var03S, ecc.)
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
      .replace(/<br\s*\/?>/gi, '\n')
      .replace(/<\/p>/gi, '\n')
      .replace(/<[^>]*>/g, '')
      .replace(/\n{3,}/g, '\n\n')
      .replace(/[ \t]+/g, ' ')
      .trim();
  };

  const filteredProjects = useMemo(() => {
    return projects.filter(p => {
      const matchesSearch = p.title.toLowerCase().includes(searchTerm.toLowerCase());
      let matchesStatus = true;
      const isReserved = !!(p.reservedTo && p.reservedTo > 0);
      const isTest = isTestCourse(p.title);
      
      if (statusFilter === 'attivi') {
        matchesStatus = p.isPublishedInEcommerce === 1 && !isTest && !isReserved;
      } else if (statusFilter === 'nonPubblicati') {
        matchesStatus = p.isPublishedInEcommerce === 0 && !isTest && !isReserved;
      } else if (statusFilter === 'sospesi') {
        matchesStatus = p.isPublishedInEcommerce === 2 && !isTest && !isReserved;
      } else if (statusFilter === 'riservati') {
        matchesStatus = isReserved && !isTest;
      } else if (statusFilter === 'test') {
        matchesStatus = isTest;
      }
      return matchesSearch && matchesStatus;
    });
  }, [projects, searchTerm, statusFilter]);

  const groupedProjects = useMemo(() => {
    const groups: { [key: string]: typeof filteredProjects } = {};
    
    if (statusFilter === 'riservati') {
      // Raggruppa per ente formativo
      filteredProjects.forEach(project => {
        const enteName = project.reservedTo ? (companyLookup[project.reservedTo] || `Ente #${project.reservedTo}`) : 'Senza Ente';
        if (!groups[enteName]) groups[enteName] = [];
        groups[enteName].push(project);
      });
      // Ordina alfabeticamente per nome ente
      const sortedEntes = Object.keys(groups).sort((a, b) => a.localeCompare(b));
      return sortedEntes.map(ente => ({ category: ente, items: groups[ente] }));
    } else {
      // Raggruppa per categoria corso
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
    }
  }, [filteredProjects, statusFilter, companyLookup]);

  const selectedProject = useMemo(() => {
    return projects.find(p => p.id === selectedCourseId);
  }, [projects, selectedCourseId]);

  const activeCounts = useMemo(() => {
    const test = projects.filter(p => isTestCourse(p.title)).length;
    const riservati = projects.filter(p => p.reservedTo && p.reservedTo > 0 && !isTestCourse(p.title)).length;
    const attivi = projects.filter(p => p.isPublishedInEcommerce === 1 && !isTestCourse(p.title) && !(p.reservedTo && p.reservedTo > 0)).length;
    const nonPubblicati = projects.filter(p => p.isPublishedInEcommerce === 0 && !isTestCourse(p.title) && !(p.reservedTo && p.reservedTo > 0)).length;
    const sospesi = projects.filter(p => p.isPublishedInEcommerce === 2 && !isTestCourse(p.title) && !(p.reservedTo && p.reservedTo > 0)).length;
    return { attivi, sospesi, nonPubblicati, riservati, test };
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
            <button className="bg-yellow-500 hover:bg-yellow-600 text-black text-[11px] px-3 py-1.5 rounded flex items-center gap-1 ml-2">
              <Edit size={12} /> Crea Corso
            </button>
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
                <StatusButton 
                  active={statusFilter === 'riservati'} 
                  color="purple"
                  onClick={() => setStatusFilter('riservati')}
                >
                  Riservati ({activeCounts.riservati})
                </StatusButton>
                <StatusButton 
                  active={statusFilter === 'test'} 
                  color="gray"
                  onClick={() => setStatusFilter('test')}
                >
                  Test ({activeCounts.test})
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
                              <div className="flex items-center gap-1.5">
                                <span className={`inline-block w-2 h-2 rounded-full ${project.isPublishedInEcommerce === 1 ? 'bg-green-500' : 'bg-orange-400'}`} title={project.isPublishedInEcommerce === 1 ? 'Pubblicato' : 'Bozza'}></span>
                                {formatCourseTitle(project.title)}
                              </div>
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
                      onClick={() => {
                        if (window.confirm(`Vuoi davvero rimuovere il corso "${formatCourseTitle(selectedProject.title)}" dal catalogo?`)) {
                          unpublishMutation.mutate(selectedProject.id);
                        }
                      }}
                    >
                      {unpublishMutation.isPending ? 'Rimozione...' : 'Rimuovi dal catalogo'}
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
                </div>

                <div className="flex-1 overflow-y-auto p-4">
                  <div className="bg-white rounded shadow-sm border border-gray-200">
                    <div className="bg-gradient-to-r from-[#4a90a4] to-[#5ba3b8] px-4 py-2 rounded-t flex items-center justify-between">
                      <h2 className="text-base font-bold text-white">
                        (ID:{selectedProject.id}) {formatCourseTitle(selectedProject.title).toUpperCase()}
                      </h2>
                      <div className="flex gap-2">
                        <button className="bg-white/20 hover:bg-white/30 text-white text-[11px] px-3 py-1 rounded flex items-center gap-1">
                          <Mail size={12} /> Invia Email
                        </button>
                        <button className="bg-white/20 hover:bg-white/30 text-white text-[11px] px-3 py-1 rounded flex items-center gap-1">
                          <Printer size={12} /> Stampa
                        </button>
                      </div>
                    </div>

                    <div className="p-4">
                      <table className="w-full text-[12px]">
                        <tbody>
                          <tr className="border-b border-gray-100">
                            <td className="py-1.5 pr-4 font-semibold text-gray-600 align-top w-[220px]">Data di creazione</td>
                            <td className="py-1.5 text-gray-800 w-[200px]">{selectedProject.createdAt ? new Date(selectedProject.createdAt).toLocaleString('it-IT') : '-'}</td>
                            <td className="py-1.5 pr-4 font-semibold text-gray-600 align-top w-[100px]">Creato da</td>
                            <td className="py-1.5 text-gray-800">Superadmin Tutor81 (ID: 6)</td>
                          </tr>
                          <DetailRow label="Requisiti minimi per accedere al corso" value={selectedProject.prerequisites || "nessuno"} />
                          <tr className="border-b border-gray-100">
                            <td className="py-2 pr-4 text-gray-600 font-medium w-[200px] align-top">Categoria</td>
                            <td className="py-2">
                              <div className="flex items-center gap-4">
                                <Select
                                  value={selectedProject.category || "SICUREZZA"}
                                  onValueChange={(value) => {
                                    updateCategoryMutation.mutate({
                                      projectId: selectedProject.id,
                                      category: value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[140px] h-8 text-[12px]">
                                    <SelectValue />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="SICUREZZA">SICUREZZA</SelectItem>
                                    <SelectItem value="HACCP">HACCP</SelectItem>
                                    <SelectItem value="INFORMATICA">INFORMATICA</SelectItem>
                                    <SelectItem value="HR">HR</SelectItem>
                                  </SelectContent>
                                </Select>
                                <span className="text-gray-600 font-medium text-[12px]">Sottocategoria</span>
                                <div className="flex flex-col gap-1">
                                  <Select
                                    value={selectedProject.subcategory || "LAVORATORE"}
                                    onValueChange={(value) => {
                                      updateSubcategoryMutation.mutate({
                                        projectId: selectedProject.id,
                                        subcategory: value
                                      });
                                    }}
                                  >
                                    <SelectTrigger className="w-[160px] h-8 text-[12px]">
                                      <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                      <SelectItem value="LAVORATORE">LAVORATORE</SelectItem>
                                      <SelectItem value="PREPOSTO">PREPOSTO</SelectItem>
                                      <SelectItem value="RSPP">RSPP</SelectItem>
                                      <SelectItem value="ASPP">ASPP</SelectItem>
                                      <SelectItem value="RLS">RLS</SelectItem>
                                      <SelectItem value="DIRIGENTE">DIRIGENTE</SelectItem>
                                      <SelectItem value="ANTINCENDIO">ANTINCENDIO</SelectItem>
                                      <SelectItem value="PRIMO SOCCORSO">PRIMO SOCCORSO</SelectItem>
                                      <SelectItem value="ALTRO">ALTRO</SelectItem>
                                    </SelectContent>
                                  </Select>
                                  <button 
                                    className="text-[10px] text-[#4a90a4] hover:underline text-left"
                                    onClick={() => {
                                      const newCat = prompt("Inserisci il nome della nuova categoria:");
                                      if (newCat && newCat.trim()) {
                                        toast({ title: `Categoria "${newCat.trim().toUpperCase()}" da aggiungere`, description: "Funzionalità in sviluppo" });
                                      }
                                    }}
                                  >
                                    + Categoria
                                  </button>
                                </div>
                                <span className="text-gray-600 font-medium text-[12px]">Tipo</span>
                                <Select
                                  value={selectedProject.courseType || "ND"}
                                  onValueChange={(value) => {
                                    updateCourseTypeMutation.mutate({
                                      projectId: selectedProject.id,
                                      courseType: value === "ND" ? "" : value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[140px] h-8 text-[12px]">
                                    <SelectValue placeholder="Non definito" />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="Base">BASE</SelectItem>
                                    <SelectItem value="Aggiornamento">AGGIORNAMENTO</SelectItem>
                                    <SelectItem value="ND">NON DEFINITO</SelectItem>
                                  </SelectContent>
                                </Select>
                                <span className="text-gray-600 font-medium text-[12px]">Settore</span>
                                <Select
                                  value={selectedProject.sector || "TUTTI"}
                                  onValueChange={(value) => {
                                    updateSectorMutation.mutate({
                                      projectId: selectedProject.id,
                                      sector: value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[160px] h-8 text-[12px]">
                                    <SelectValue />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="TUTTI">TUTTI</SelectItem>
                                    <SelectItem value="EDILIZIA">EDILIZIA</SelectItem>
                                    <SelectItem value="INDUSTRIA">INDUSTRIA</SelectItem>
                                    <SelectItem value="COMMERCIO">COMMERCIO</SelectItem>
                                    <SelectItem value="SANITA">SANITA</SelectItem>
                                    <SelectItem value="ALIMENTARE">ALIMENTARE</SelectItem>
                                    <SelectItem value="TRASPORTI">TRASPORTI</SelectItem>
                                    <SelectItem value="UFFICI">UFFICI</SelectItem>
                                  </SelectContent>
                                </Select>
                              </div>
                            </td>
                          </tr>
                          <tr className="border-b border-gray-100">
                            <td className="py-2 pr-4 text-gray-600 font-medium w-[200px] align-top">Modalità di erogazione</td>
                            <td className="py-2">
                              <div className="flex items-center gap-4">
                                <Select
                                  value={selectedProject.modality || "E-LEARNING"}
                                  onValueChange={(value) => {
                                    updateModalityMutation.mutate({
                                      projectId: selectedProject.id,
                                      modality: value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[180px] h-8 text-[12px]">
                                    <SelectValue />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="E-LEARNING">E-LEARNING</SelectItem>
                                    <SelectItem value="E-LEARNING + VD">E-LEARNING + VD</SelectItem>
                                    <SelectItem value="VIDEOCONFERENZA">VIDEOCONFERENZA</SelectItem>
                                  </SelectContent>
                                </Select>
                                <span className="text-gray-600 font-medium text-[12px]">Integrazione in aula</span>
                                <Select
                                  value={selectedProject.externalIntegration === "SI" ? "SI" : "NO"}
                                  onValueChange={(value) => {
                                    updateIntegrationMutation.mutate({
                                      projectId: selectedProject.id,
                                      integration: value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[80px] h-8 text-[12px]">
                                    <SelectValue />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="SI">SI</SelectItem>
                                    <SelectItem value="NO">NO</SelectItem>
                                  </SelectContent>
                                </Select>
                              </div>
                            </td>
                          </tr>
                                                    <tr className="border-b border-gray-100">
                            <td className="py-2 pr-4 text-gray-600 font-medium w-[200px] align-top">Rischio Azienda</td>
                            <td className="py-2">
                              <div className="flex items-center gap-4">
                                <Select
                                  value={selectedProject.riskLevel || "medio"}
                                  onValueChange={(value) => {
                                    updateRiskLevelMutation.mutate({
                                      projectId: selectedProject.id,
                                      riskLevel: value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[100px] h-8 text-[12px]">
                                    <SelectValue />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="basso">basso</SelectItem>
                                    <SelectItem value="medio">medio</SelectItem>
                                    <SelectItem value="alto">alto</SelectItem>
                                    <SelectItem value="nd">nd</SelectItem>
                                  </SelectContent>
                                </Select>
                                <span className="text-gray-600 font-medium text-[12px]">Destinazione</span>
                                <Select
                                  value={selectedProject.destination || "BASE + SPECIFICA"}
                                  onValueChange={(value) => {
                                    updateDestinationMutation.mutate({
                                      projectId: selectedProject.id,
                                      destination: value
                                    });
                                  }}
                                >
                                  <SelectTrigger className="w-[160px] h-8 text-[12px]">
                                    <SelectValue />
                                  </SelectTrigger>
                                  <SelectContent>
                                    <SelectItem value="BASE">BASE</SelectItem>
                                    <SelectItem value="BASE + SPECIFICA">BASE + SPECIFICA</SelectItem>
                                    <SelectItem value="SPECIFICA">SPECIFICA</SelectItem>
                                    <SelectItem value="ND">ND</SelectItem>
                                  </SelectContent>
                                </Select>
                              </div>
                            </td>
                          </tr>
                          <DetailRow 
                            label="Obiettivi del corso" 
                            value={stripHtml(selectedProject.objectives) || 'Formazione generale e specifica dei lavoratori'} 
                          />
                          <DetailRow label="Rivolto a" value={selectedProject.targetAudience || ""} />
                          <DetailRow label="Riferimento normativo" value={selectedProject.lawReference || "Decreto 81 art. 37 - Accordo Stato-Regioni del 17/04/2025"} />
                          <tr className="border-b border-gray-100">
                            <td className="py-2 pr-4 text-gray-600 font-medium w-[200px] align-top">Validità</td>
                            <td className="py-2">
                              <Select
                                value={selectedProject.courseValidity || "quinquennale"}
                                onValueChange={(value) => {
                                  updateValidityMutation.mutate({
                                    projectId: selectedProject.id,
                                    validity: value
                                  });
                                }}
                              >
                                <SelectTrigger className="w-[200px] h-8 text-[12px]">
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="annuale">annuale</SelectItem>
                                  <SelectItem value="quinquennale">quinquennale</SelectItem>
                                </SelectContent>
                              </Select>
                            </td>
                          </tr>
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
                        <div className="border-l-4 border-[#4a90a4] pl-3 bg-blue-50 p-3 rounded-r">
                          <h3 className="font-bold text-gray-800 text-[13px] mb-2">Programma del corso</h3>
                          <div className="max-h-[300px] overflow-y-auto">
                            <p className="text-[12px] text-gray-700 leading-relaxed whitespace-pre-wrap">
                              {stripHtml(selectedProject.courseProgram || selectedProject.description) || 'Concetti di rischio, danno, prevenzione, protezione, organizzazione della prevenzione aziendale, diritti, doveri e sanzioni per i vari soggetti aziendali, organi di vigilanza, controllo e assistenza.'}
                            </p>
                          </div>
                        </div>
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
            <div className="flex items-center justify-between mb-4">
              <h2 className="text-xl font-bold text-gray-800 flex items-center gap-2">
                <PlayCircle size={24} className="text-[#4a90a4]" />
                Learning Objects ({learningObjects.length})
              </h2>
              <div className="flex items-center gap-4">
                <select 
                  className="text-xs border border-gray-300 rounded px-2 py-1 bg-white"
                  defaultValue=""
                >
                  <option value="">Tutte le categorie</option>
                  <option value="sicurezza">SICUREZZA</option>
                  <option value="informatica">INFORMATICA</option>
                  <option value="haccp">HACCP</option>
                  <option value="231">231</option>
                  <option value="hr">HR</option>
                </select>
                <div className="flex gap-2 text-xs">
                  <span className="px-2 py-1 bg-green-100 text-green-700 rounded">
                    In uso: {learningObjects.filter(lo => lo.inUse).length}
                  </span>
                  <span className="px-2 py-1 bg-red-100 text-red-700 rounded">
                    Non in uso: {learningObjects.filter(lo => !lo.inUse).length}
                  </span>
                </div>
              </div>
            </div>
            
            {selectedLOs.size > 0 && (
              <div className="mb-4 p-3 bg-blue-50 rounded flex items-center justify-between">
                <span className="text-sm text-blue-700">
                  {selectedLOs.size} oggetti selezionati
                </span>
                <div className="flex gap-2">
                  <button 
                    className="px-3 py-1 text-xs bg-red-500 text-white rounded hover:bg-red-600"
                    onClick={() => {
                      toast({ title: `Sospesi ${selectedLOs.size} oggetti` });
                      setSelectedLOs(new Set());
                    }}
                  >
                    Sospendi selezionati
                  </button>
                  <button 
                    className="px-3 py-1 text-xs bg-gray-200 text-gray-700 rounded hover:bg-gray-300"
                    onClick={() => setSelectedLOs(new Set())}
                  >
                    Deseleziona
                  </button>
                </div>
              </div>
            )}
            
            {loadingLOs ? (
              <div className="text-center py-8 text-gray-400">Caricamento...</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-xs">
                  <thead>
                    <tr className="border-b border-gray-200 text-left">
                      <th className="py-2 px-2 w-8">
                        <input 
                          type="checkbox"
                          checked={selectedLOs.size === learningObjects.length && learningObjects.length > 0}
                          onChange={(e) => {
                            if (e.target.checked) {
                              setSelectedLOs(new Set(learningObjects.map(lo => lo.id)));
                            } else {
                              setSelectedLOs(new Set());
                            }
                          }}
                          className="w-3 h-3 accent-[#4a90a4]"
                        />
                      </th>
                      <th className="py-2 px-2 font-medium text-gray-500 w-8"></th>
                      <th className="py-2 px-2 font-medium text-gray-500">ID</th>
                      <th className="py-2 px-2 font-medium text-gray-500">Tipo</th>
                      <th className="py-2 px-2 font-medium text-gray-500">Titolo</th>
                      <th className="py-2 px-2 font-medium text-gray-500">Durata</th>
                      <th className="py-2 px-2 font-medium text-gray-500">Stato</th>
                      <th className="py-2 px-2 font-medium text-gray-500">Azione</th>
                    </tr>
                  </thead>
                  <tbody>
                    {learningObjects.slice(0, 100).map(lo => (
                      <tr 
                        key={lo.id} 
                        className={`border-b border-gray-100 hover:bg-gray-50 cursor-pointer ${!lo.inUse ? 'bg-red-50' : ''} ${selectedLOs.has(lo.id) ? 'bg-blue-50' : ''} ${selectedLO?.id === lo.id ? 'ring-2 ring-[#4a90a4]' : ''}`}
                        onClick={() => setSelectedLO(lo)}
                      >
                        <td className="py-2 px-2" onClick={(e) => e.stopPropagation()}>
                          <input 
                            type="checkbox"
                            checked={selectedLOs.has(lo.id)}
                            onChange={(e) => {
                              const newSet = new Set(selectedLOs);
                              if (e.target.checked) {
                                newSet.add(lo.id);
                              } else {
                                newSet.delete(lo.id);
                              }
                              setSelectedLOs(newSet);
                            }}
                            className="w-3 h-3 accent-[#4a90a4]"
                          />
                        </td>
                        <td className="py-2 px-2 text-center">
                          {lo.objectType === 1 && <Film size={14} className="text-blue-500" />}
                          {lo.objectType === 2 && <FileText size={14} className="text-purple-500" />}
                          {lo.objectType === 3 && <Book size={14} className="text-orange-500" />}
                        </td>
                        <td className="py-2 px-2 text-gray-600">{lo.legacyId || lo.id}</td>
                        <td className="py-2 px-2">
                          {lo.objectType === 1 && <span className="text-blue-600">Video</span>}
                          {lo.objectType === 2 && <span className="text-purple-600">Slide</span>}
                          {lo.objectType === 3 && <span className="text-orange-600">Doc</span>}
                        </td>
                        <td className={`py-2 px-2 ${!lo.inUse ? 'text-red-600' : 'text-gray-800'}`}>
                          {lo.title}
                        </td>
                        <td className="py-2 px-2 text-gray-500">{lo.duration} min</td>
                        <td className="py-2 px-2">
                          {lo.suspended ? (
                            <span className="px-2 py-0.5 bg-gray-200 text-gray-600 rounded text-[10px]">Sospeso</span>
                          ) : lo.inUse ? (
                            <span className="px-2 py-0.5 bg-green-100 text-green-700 rounded text-[10px]">Attivo</span>
                          ) : (
                            <span className="px-2 py-0.5 bg-red-100 text-red-700 rounded text-[10px]">Non usato</span>
                          )}
                        </td>
                        <td className="py-2 px-2">
                          <button 
                            className={`px-2 py-0.5 text-[10px] rounded ${
                              lo.suspended 
                                ? 'bg-green-100 text-green-700 hover:bg-green-200' 
                                : 'bg-red-100 text-red-700 hover:bg-red-200'
                            }`}
                          >
                            {lo.suspended ? 'Riattiva' : 'Sospendi'}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {learningObjects.length > 100 && (
                  <p className="text-center text-gray-400 text-xs py-2">Mostrati primi 100 di {learningObjects.length}</p>
                )}
              </div>
            )}
            
          </div>
        </div>
      )}
      {/* Modal Dettaglio Learning Object */}
      {selectedLO && (
        <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50" onClick={() => setSelectedLO(null)}>
          <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between p-4 border-b bg-[#4a90a4] text-white rounded-t-lg">
              <h3 className="text-lg font-bold">Learning Object #{selectedLO.legacyId || selectedLO.id}</h3>
              <button 
                onClick={() => setSelectedLO(null)}
                className="text-white hover:text-gray-200"
              >
                <XCircle size={24} />
              </button>
            </div>
            
            <div className="p-6">
              {/* Info base */}
              <div className="grid grid-cols-3 gap-4 text-sm mb-6">
                <div>
                  <label className="text-xs font-medium text-gray-500">Titolo</label>
                  <p className="text-gray-800 font-semibold">{selectedLO.title}</p>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500">Tipo</label>
                  <p className="text-gray-800">
                    {selectedLO.objectType === 1 ? 'Video' : selectedLO.objectType === 2 ? 'Slide' : 'Documento'}
                  </p>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500">Durata</label>
                  <p className="text-gray-800">{selectedLO.duration} minuti</p>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500">Categoria</label>
                  <select className="w-full text-sm border border-gray-300 rounded px-2 py-1 bg-white">
                    <option value="">Seleziona</option>
                    <option value="sicurezza">SICUREZZA</option>
                    <option value="informatica">INFORMATICA</option>
                    <option value="haccp">HACCP</option>
                    <option value="231">231</option>
                    <option value="hr">HR</option>
                  </select>
                </div>
                <div>
                  <label className="text-xs font-medium text-gray-500">Stato</label>
                  <p className={selectedLO.suspended ? 'text-gray-500' : selectedLO.inUse ? 'text-green-600 font-medium' : 'text-red-600 font-medium'}>
                    {selectedLO.suspended ? 'Sospeso' : selectedLO.inUse ? 'Attivo' : 'Non in uso'}
                  </p>
                </div>
              </div>

              {/* Video Player con domande in anteprima */}
              {selectedLO.jwplayerCode && (
                <div className="mb-6">
                  <label className="text-xs font-medium text-gray-500 mb-2 block">Video Preview</label>
                  <div className="flex gap-4">
                    <div className="flex-1 bg-black rounded overflow-hidden relative" style={{ aspectRatio: '16/9' }}>
                      <iframe
                        src={`https://cdn.jwplayer.com/players/${selectedLO.jwplayerCode}-ZXcv1712.html`}
                        width="100%"
                        height="100%"
                        frameBorder="0"
                        allow="autoplay; fullscreen"
                        allowFullScreen
                      />
                      {/* Anteprima domande sul video */}
                      {loDetails?.interruptionPoints?.length > 0 && (
                        <div className="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent p-3">
                          <div className="flex gap-2 overflow-x-auto flex-wrap">
                            {loDetails.interruptionPoints.map((ip: any, idx: number) => {
                              const totalSeconds = Math.floor(ip.time / 1000);
                              const minutes = Math.floor(totalSeconds / 60);
                              const seconds = totalSeconds % 60;
                              return (
                                <div 
                                  key={ip.id}
                                  className="flex-shrink-0 bg-yellow-500 text-black text-xs font-bold px-2 py-1 rounded cursor-pointer hover:bg-yellow-400"
                                  title={ip.questions?.filter((q: any) => q.id).map((q: any) => q.text).join('\n')}
                                >
                                  {minutes}:{String(seconds).padStart(2, '0')}
                                </div>
                              );
                            })}
                          </div>
                        </div>
                      )}
                    </div>
                  </div>
                </div>
              )}

              {/* Domande associate */}
              <div className="mb-6">
                <label className="text-xs font-medium text-gray-500 mb-2 block">
                  Domande Associate ({loDetails?.interruptionPoints?.length || 0} punti di interruzione)
                </label>
                {loadingDetails ? (
                  <p className="text-gray-400 text-sm">Caricamento domande...</p>
                ) : loDetails?.interruptionPoints?.length > 0 ? (
                  <div className="space-y-4 max-h-60 overflow-y-auto">
                    {loDetails.interruptionPoints.map((ip: any, idx: number) => (
                      <div key={ip.id} className="border border-gray-200 rounded p-3 bg-gray-50">
                        <div className="text-xs text-gray-500 mb-2 flex items-center gap-2">
                          Interruzione #{idx + 1} a 
                          <input 
                            type="number" 
                            defaultValue={Math.floor(Math.floor(ip.time / 1000) / 60)} 
                            className="w-12 px-2 py-0.5 border border-gray-300 rounded text-[#4a90a4] font-mono font-medium text-center"
                            min="0"
                          />
                          <span>:</span>
                          <input 
                            type="number" 
                            defaultValue={Math.floor(ip.time / 1000) % 60} 
                            className="w-12 px-2 py-0.5 border border-gray-300 rounded text-[#4a90a4] font-mono font-medium text-center"
                            min="0"
                            max="59"
                          />
                          <span className="text-gray-400">(mm:ss)</span>
                          <label className="flex items-center gap-1 ml-4 cursor-pointer">
                            <input type="checkbox" className="w-4 h-4 accent-[#4a90a4]" />
                            <span className="text-gray-600">Domanda a fine lezione</span>
                          </label>
                        </div>
                        {ip.questions?.filter((q: any) => q.id).map((q: any) => (
                          <div key={q.id} className="mb-3 last:mb-0">
                            <p className="text-sm font-medium text-gray-800 mb-1">{q.text}</p>
                            <div className="pl-4 space-y-1">
                              {q.answers?.map((a: any) => (
                                <div 
                                  key={a.id} 
                                  className={`text-xs flex items-center gap-2 ${a.isCorrect ? 'text-green-600 font-medium' : 'text-gray-600'}`}
                                >
                                  {a.isCorrect ? '✓' : '○'} {a.text}
                                </div>
                              ))}
                            </div>
                          </div>
                        ))}
                      </div>
                    ))}
                  </div>
                ) : (
                  <p className="text-gray-400 text-sm">Nessuna domanda associata</p>
                )}
              </div>
              
              <div className="pt-4 border-t border-gray-200 flex gap-3">
                <button className="px-4 py-2 text-sm bg-[#4a90a4] text-white rounded hover:bg-[#3a7084]">
                  Modifica
                </button>
                <button className={`px-4 py-2 text-sm rounded ${selectedLO.suspended ? 'bg-green-500 text-white hover:bg-green-600' : 'bg-red-500 text-white hover:bg-red-600'}`}>
                  {selectedLO.suspended ? 'Riattiva' : 'Sospendi'}
                </button>
                <button 
                  onClick={() => setSelectedLO(null)}
                  className="px-4 py-2 text-sm bg-gray-200 text-gray-700 rounded hover:bg-gray-300 ml-auto"
                >
                  Chiudi
                </button>
              </div>
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
  color: 'green' | 'orange' | 'red' | 'purple' | 'gray';
  onClick: () => void;
}) {
  const colors = {
    green: active ? 'bg-green-500 text-white border-green-500' : 'bg-white text-green-600 border-green-300 hover:bg-green-50',
    orange: active ? 'bg-orange-500 text-white border-orange-500' : 'bg-white text-orange-600 border-orange-300 hover:bg-orange-50',
    red: active ? 'bg-red-500 text-white border-red-500' : 'bg-white text-red-600 border-red-300 hover:bg-red-50',
    purple: active ? 'bg-purple-500 text-white border-purple-500' : 'bg-white text-purple-600 border-purple-300 hover:bg-purple-50',
    gray: active ? 'bg-gray-500 text-white border-gray-500' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50',
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
      <p className="text-[11px] text-gray-600 leading-relaxed whitespace-pre-wrap">{content}</p>
    </div>
  );
}
