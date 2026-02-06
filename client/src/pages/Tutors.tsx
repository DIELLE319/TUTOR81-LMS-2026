import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Search, Plus, Building, Pause, Pencil, Trash2, Download } from 'lucide-react';
import type { Tutor } from '@shared/schema';
import { Button } from '@/components/ui/button';
import { queryClient, apiRequest } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TutorAdmins } from '@/components/TutorAdmins';
import { TutorModal } from '@/components/TutorModal';
import { useAuth } from '@/hooks/use-auth';
import { Checkbox } from '@/components/ui/checkbox';

const SUBSCRIPTION_OPTIONS = [
  { value: 'NESSUNO', label: 'Nessun abbonamento', discount: 0 },
  { value: 'CONSULENTI 500', label: 'Consulenti 500', discount: 60 },
  { value: 'CONSULENTI 1500', label: 'Consulenti 1500', discount: 70 },
  { value: 'ENTI AUTORIZZATI 1500', label: 'Enti Autorizzati 1500', discount: 70 },
];

export default function Tutors() {
  const { user } = useAuth();
  const [searchTerm, setSearchTerm] = useState('');
  const [modalOpen, setModalOpen] = useState(false);
  const [editingTutor, setEditingTutor] = useState<Tutor | null>(null);
  const { toast } = useToast();

  const isSuperAdmin = user?.role === 1000;

  const openCreateModal = () => {
    setEditingTutor(null);
    setModalOpen(true);
  };

  const openEditModal = (tutor: Tutor) => {
    setEditingTutor(tutor);
    setModalOpen(true);
  };

  const isPlatformTutor = (t: Pick<Tutor, 'businessName' | 'email'>) => {
    const name = (t.businessName || '').toLowerCase();
    const email = (t.email || '').toLowerCase();
    return name.includes('tutor81') || email.includes('tutor81');
  };

  const { data: tutors = [], isLoading, error: tutorsError } = useQuery<Tutor[]>({
    queryKey: ['/api/tutors'],
    refetchOnMount: 'always',
    queryFn: async () => {
      const res = await fetch('/api/tutors', { credentials: 'include', cache: 'no-store' });
      if (!res.ok) throw new Error('Failed to fetch tutors');
      return res.json();
    },
  });

  const updateSubscriptionMutation = useMutation({
    mutationFn: ({ id, subscriptionType, discountPercentage }: { id: number, subscriptionType: string, discountPercentage: number }) => 
      apiRequest('PUT', `/api/tutors/${id}`, { subscriptionType, discountPercentage }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      toast({ title: 'Salvato', description: 'Abbonamento aggiornato.' });
    },
    onError: () => {
      toast({ title: 'Errore', description: 'Impossibile salvare.', variant: 'destructive' });
    }
  });

  const handleSubscriptionChange = (tutorId: number, value: string) => {
    const option = SUBSCRIPTION_OPTIONS.find(o => o.value === value);
    if (option) {
      updateSubscriptionMutation.mutate({ 
        id: tutorId, 
        subscriptionType: option.value, 
        discountPercentage: option.discount 
      });
    }
  };

  const updateDateMutation = useMutation({
    mutationFn: ({ id, subscriptionStart }: { id: number, subscriptionStart: string | null }) => 
      apiRequest('PUT', `/api/tutors/${id}`, { subscriptionStart }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      toast({ title: 'Salvato', description: 'Data aggiornata.' });
    },
  });

  const handleDateChange = (tutorId: number, value: string) => {
    updateDateMutation.mutate({ id: tutorId, subscriptionStart: value || null });
  };

  const formatExpiry = (startDate: string) => {
    const start = new Date(startDate);
    const expiry = new Date(start);
    expiry.setFullYear(expiry.getFullYear() + 1);
    return expiry.toLocaleDateString('it-IT');
  };

  const getExpiryClass = (startDate: string) => {
    const start = new Date(startDate);
    const expiry = new Date(start);
    expiry.setFullYear(expiry.getFullYear() + 1);
    const now = new Date();
    const daysLeft = Math.ceil((expiry.getTime() - now.getTime()) / (1000 * 60 * 60 * 24));
    
    if (daysLeft < 0) return 'text-red-500 font-medium';
    if (daysLeft < 30) return 'text-orange-500';
    if (daysLeft < 90) return 'text-yellow-500';
    return 'text-green-500';
  };

  const deleteMutation = useMutation({
    mutationFn: (id: number) => apiRequest('DELETE', `/api/tutors/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      toast({ title: 'Ente eliminato', description: 'L\'ente formativo è stato eliminato.' });
    },
    onError: (error: any) => {
      toast({ 
        title: 'Errore', 
        description: error.message || 'Impossibile eliminare l\'ente formativo.',
        variant: 'destructive'
      });
    }
  });

  const handleDelete = (id: number, name: string) => {
    if (confirm(`Sei sicuro di voler eliminare "${name}"?`)) {
      deleteMutation.mutate(id);
    }
  };

  const suspendMutation = useMutation({
    mutationFn: ({ id }: { id: number }) => apiRequest('PUT', `/api/tutors/${id}`, { isActive: false }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      queryClient.invalidateQueries({ queryKey: ['/api/companies/tutors'] });
      queryClient.invalidateQueries({ queryKey: ['/api/companies'] });
      queryClient.invalidateQueries({ queryKey: ['/api/clients'] });
      toast({ title: 'Sospeso', description: 'Ente sospeso e rimosso dalla visualizzazione.' });
    },
    onError: (error: any) => {
      toast({
        title: 'Errore',
        description: error?.message || 'Impossibile sospendere l\'ente.',
        variant: 'destructive'
      });
    }
  });

  const handleSuspend = (id: number, name: string) => {
    const normalized = (name || '').toLowerCase();
    if (normalized.includes('tutor81')) {
      toast({
        title: 'Non consentito',
        description: 'Il venditore piattaforma "TUTOR81" non può essere sospeso da qui.',
        variant: 'destructive',
      });
      return;
    }

    if (confirm(`Sospendere "${name}"?\n\nL'ente non verrà più mostrato tra gli attivi, ma resterà nel database.`)) {
      suspendMutation.mutate({ id });
    }
  };

  // NOTA: l'API /api/tutors già filtra gli "attivi" (da OVH se configurato, altrimenti da subscriptionType/discount su Postgres).
  // Evitiamo filtri client-side aggiuntivi che possono svuotare la lista (es. quando OVH guida gli attivi).
  const activeTutors = tutors;

  const filteredTutors = activeTutors.filter(t =>
    t.businessName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    t.city?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    t.email?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-tutors-title">Enti Formativi</h1>
          <p className="text-gray-500 text-sm">{activeTutors.length} enti formativi attivi</p>
          {tutorsError ? (
            <p className="text-red-400 text-sm mt-1" data-testid="text-tutors-error">
              Errore nel caricamento enti formativi: {String((tutorsError as any)?.message ?? tutorsError)}
            </p>
          ) : null}
        </div>
        <div className="flex gap-2">
          <a 
            href="/api/export/tutor-gerarchia"
            download="tutor_gerarchia.csv"
            className="bg-gray-700 hover:bg-gray-600 text-white font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            data-testid="button-export-csv"
          >
            <Download size={18} />
            Esporta CSV
          </a>
          <button 
            onClick={openCreateModal}
            className="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            data-testid="button-new-tutor"
          >
            <Plus size={18} />
          Nuovo Ente
        </button>
        </div>
      </div>

      <div
        className={
          isSuperAdmin
            ? 'bg-white rounded-xl overflow-hidden border-2 border-black/70 mb-6'
            : 'bg-zinc-900 rounded-xl border border-zinc-800 p-4 mb-6'
        }
      >
        <div className={isSuperAdmin ? 'bg-yellow-400 px-4 py-3 border-b border-black/20' : ''}>
          <div className="relative">
            <Search
              className={
                isSuperAdmin
                  ? 'absolute left-3 top-1/2 -translate-y-1/2 text-black/60'
                  : 'absolute left-3 top-1/2 -translate-y-1/2 text-gray-500'
              }
              size={18}
            />
            <input
              type="text"
              placeholder="Cerca ente formativo..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className={
                isSuperAdmin
                  ? 'w-full bg-white border border-yellow-600/30 rounded-lg py-3 pl-10 pr-4 text-black placeholder-black/50 focus:border-black/40 focus:outline-none'
                  : 'w-full bg-black border border-zinc-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none'
              }
              data-testid="input-search-tutors"
            />
          </div>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredTutors.length === 0 ? (
        <div className="text-center py-12 bg-zinc-900 rounded-xl border border-zinc-800">
          <Building size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun ente formativo attivo</h3>
          <p className="text-gray-500">Sono visibili solo gli enti con un abbonamento attivo.</p>
        </div>
      ) : (
        <div
          className={
            isSuperAdmin
              ? 'bg-white rounded-xl border-2 border-black/70 overflow-hidden'
              : 'bg-zinc-900 rounded-xl border border-zinc-800 overflow-hidden'
          }
        >
          <table className="w-full">
            <thead>
              <tr
                className={
                  isSuperAdmin
                    ? 'bg-yellow-500 text-left text-xs text-black uppercase'
                    : 'border-b border-zinc-800 bg-zinc-800/50'
                }
              >
                <th className={isSuperAdmin ? 'text-left py-3 px-2 font-bold w-10' : 'text-left py-3 px-2 text-gray-400 font-medium text-sm w-10'}></th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>ID</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Ragione Sociale</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Indirizzo</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Telefono</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Email</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Abbonamento</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Inizio</th>
                <th className={isSuperAdmin ? 'text-left py-3 px-4 font-bold' : 'text-left py-3 px-4 text-gray-400 font-medium text-sm'}>Scadenza</th>
                <th className={isSuperAdmin ? 'text-right py-3 px-4 font-bold' : 'text-right py-3 px-4 text-gray-400 font-medium text-sm'}>Azioni</th>
              </tr>
            </thead>
            <tbody>
              {filteredTutors.map((tutor) => (
                <tr 
                  key={tutor.id} 
                  className={
                    isSuperAdmin
                      ? 'border-b border-gray-200 hover:bg-gray-50 transition-colors'
                      : 'border-b border-zinc-800 hover:bg-zinc-800/30 transition-colors'
                  }
                  data-testid={`row-tutor-${tutor.id}`}
                >
                  <td className={isSuperAdmin ? 'py-3 px-2' : 'py-3 px-2'}>
                    <div className="flex items-center justify-center">
                      <Checkbox
                        checked={true}
                        disabled={isPlatformTutor(tutor)}
                        onCheckedChange={(checked) => {
                          // In questa vista mostriamo solo attivi: quindi l'unica azione è sospendere.
                          if (checked === false) {
                            handleSuspend(tutor.id, tutor.businessName);
                          }
                        }}
                        aria-label={isPlatformTutor(tutor) ? 'Venditore piattaforma (non sospendibile)' : 'Sospendi ente'}
                        data-testid={`flag-active-tutor-${tutor.id}`}
                      />
                    </div>
                  </td>
                  <td className={isSuperAdmin ? 'py-3 px-4 text-gray-600 text-sm font-mono' : 'py-3 px-4 text-gray-500 text-sm'}>{tutor.id}</td>
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                        <Building size={18} className="text-yellow-500" />
                      </div>
                      <div>
                        <span className={isSuperAdmin ? 'font-medium text-black block' : 'font-medium text-white block'}>{tutor.businessName}</span>
                        <div className="mt-1">
                          <TutorAdmins
                            tutorId={tutor.id}
                            theme={isSuperAdmin ? 'light' : 'dark'}
                            showManageLink={false}
                            summaryMax={2}
                            label="Admin:"
                          />
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className={isSuperAdmin ? 'py-3 px-4 text-gray-700 text-sm' : 'py-3 px-4 text-gray-400 text-sm'}>
                    {tutor.address || '-'}
                  </td>
                  <td className={isSuperAdmin ? 'py-3 px-4 text-gray-700 text-sm' : 'py-3 px-4 text-gray-400 text-sm'}>
                    {tutor.phone || '-'}
                  </td>
                  <td className={isSuperAdmin ? 'py-3 px-4 text-gray-700 text-sm' : 'py-3 px-4 text-gray-400 text-sm'}>
                    {tutor.email || '-'}
                  </td>
                  <td className="py-3 px-4">
                    <Select
                      value={tutor.subscriptionType || 'NESSUNO'}
                      onValueChange={(value) => handleSubscriptionChange(tutor.id, value)}
                    >
                      <SelectTrigger 
                        className={
                          isSuperAdmin
                            ? 'h-8 w-48 bg-white border-yellow-600/30 text-black text-xs'
                            : 'h-8 w-48 bg-zinc-800 border-zinc-700 text-white text-xs'
                        }
                        data-testid={`select-subscription-${tutor.id}`}
                      >
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {SUBSCRIPTION_OPTIONS.map(option => (
                          <SelectItem key={option.value} value={option.value}>
                            {option.label} ({option.discount}%)
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </td>
                  <td className="py-3 px-4">
                    <input
                      type="date"
                      value={tutor.subscriptionStart || ''}
                      onChange={(e) => handleDateChange(tutor.id, e.target.value)}
                      className={
                        isSuperAdmin
                          ? 'h-8 px-2 text-xs bg-white border border-yellow-600/30 rounded text-black'
                          : 'h-8 px-2 text-xs bg-zinc-800 border border-zinc-700 rounded text-white'
                      }
                      data-testid={`input-date-${tutor.id}`}
                    />
                  </td>
                  <td className="py-3 px-4 text-sm">
                    {tutor.subscriptionStart ? (
                      <span className={getExpiryClass(tutor.subscriptionStart)}>
                        {formatExpiry(tutor.subscriptionStart)}
                      </span>
                    ) : (
                      <span className={isSuperAdmin ? 'text-gray-600' : 'text-gray-500'}>-</span>
                    )}
                  </td>
                  <td className="py-3 px-4">
                    <div className="flex items-center justify-end gap-2">
                      <Button
                        size="sm"
                        variant="ghost"
                        className="text-gray-400 hover:text-yellow-500"
                        onClick={() => handleSuspend(tutor.id, tutor.businessName)}
                        data-testid={`button-suspend-tutor-${tutor.id}`}
                        title="Sospendi"
                      >
                        <Pause size={14} />
                        <span className="sr-only">Sospendi</span>
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="text-gray-400 hover:text-blue-500"
                        onClick={() => openEditModal(tutor)}
                        data-testid={`button-edit-tutor-${tutor.id}`}
                      >
                        <Pencil size={14} />
                      </Button>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="text-gray-400 hover:text-red-500"
                        onClick={() => handleDelete(tutor.id, tutor.businessName)}
                        data-testid={`button-delete-tutor-${tutor.id}`}
                      >
                        <Trash2 size={14} />
                      </Button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
      <TutorModal 
        open={modalOpen} 
        onClose={() => setModalOpen(false)} 
        tutor={editingTutor} 
      />
    </div>
  );
}
