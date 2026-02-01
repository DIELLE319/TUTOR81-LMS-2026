import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Link } from 'wouter';
import { Search, Plus, Building, MapPin, Mail, Phone, Pause, Pencil, Trash2 } from 'lucide-react';
import type { Tutor } from '@shared/schema';
import { Button } from '@/components/ui/button';
import { queryClient, apiRequest } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { TutorAdmins } from '@/components/TutorAdmins';

const SUBSCRIPTION_OPTIONS = [
  { value: 'NESSUNO', label: 'Nessun abbonamento', discount: 0 },
  { value: 'CONSULENTI 500', label: 'Consulenti 500', discount: 60 },
  { value: 'CONSULENTI 1500', label: 'Consulenti 1500', discount: 70 },
  { value: 'ENTI AUTORIZZATI 1500', label: 'Enti Autorizzati 1500', discount: 70 },
];

export default function Tutors() {
  const [searchTerm, setSearchTerm] = useState('');
  const { toast } = useToast();

  const { data: tutors = [], isLoading } = useQuery<Tutor[]>({
    queryKey: ['/api/tutors'],
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

  const handleSuspend = (id: number, name: string) => {
    toast({ title: 'Funzione in sviluppo', description: `Sospensione di "${name}" sarà disponibile presto.` });
  };

  const filteredTutors = tutors.filter(t => 
    t.businessName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    t.city?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    t.email?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-tutors-title">Enti Formativi</h1>
          <p className="text-gray-500 text-sm">{tutors.length} enti formativi registrati</p>
        </div>
        <Link href="/tutors/new">
          <button 
            className="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            data-testid="button-new-tutor"
          >
            <Plus size={18} />
            Nuovo Ente
          </button>
        </Link>
      </div>

      <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca ente formativo..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-zinc-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
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
        <div className="text-center py-12 bg-zinc-900 rounded-xl border border-zinc-800">
          <Building size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun ente formativo</h3>
          <p className="text-gray-500">Aggiungi il primo ente formativo per iniziare</p>
        </div>
      ) : (
        <div className="bg-zinc-900 rounded-xl border border-zinc-800 overflow-hidden">
          <table className="w-full">
            <thead>
              <tr className="border-b border-zinc-800 bg-zinc-800/50">
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">ID</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Ragione Sociale</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Indirizzo</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Telefono</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Email</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Abbonamento</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Inizio</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Scadenza</th>
                <th className="text-right py-3 px-4 text-gray-400 font-medium text-sm">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {filteredTutors.map((tutor) => (
                <tr 
                  key={tutor.id} 
                  className="border-b border-zinc-800 hover:bg-zinc-800/30 transition-colors"
                  data-testid={`row-tutor-${tutor.id}`}
                >
                  <td className="py-3 px-4 text-gray-500 text-sm">{tutor.id}</td>
                  <td className="py-3 px-4">
                    <div className="flex items-center gap-3">
                      <div className="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                        <Building size={18} className="text-yellow-500" />
                      </div>
                      <div>
                        <span className="font-medium text-white block">{tutor.businessName}</span>
                        <div className="mt-1">
                          <TutorAdmins tutorId={tutor.id} />
                        </div>
                      </div>
                    </div>
                  </td>
                  <td className="py-3 px-4 text-gray-400 text-sm">
                    {tutor.address || '-'}
                  </td>
                  <td className="py-3 px-4 text-gray-400 text-sm">
                    {tutor.phone || '-'}
                  </td>
                  <td className="py-3 px-4 text-gray-400 text-sm">
                    {tutor.email || '-'}
                  </td>
                  <td className="py-3 px-4">
                    <Select
                      value={tutor.subscriptionType || 'NESSUNO'}
                      onValueChange={(value) => handleSubscriptionChange(tutor.id, value)}
                    >
                      <SelectTrigger 
                        className="h-8 w-48 bg-zinc-800 border-zinc-700 text-xs"
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
                      className="h-8 px-2 text-xs bg-zinc-800 border border-zinc-700 rounded text-white"
                      data-testid={`input-date-${tutor.id}`}
                    />
                  </td>
                  <td className="py-3 px-4 text-sm">
                    {tutor.subscriptionStart ? (
                      <span className={getExpiryClass(tutor.subscriptionStart)}>
                        {formatExpiry(tutor.subscriptionStart)}
                      </span>
                    ) : (
                      <span className="text-gray-600">-</span>
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
                      >
                        <Pause size={14} />
                      </Button>
                      <Link href={`/tutors/${tutor.id}/edit`}>
                        <Button
                          size="sm"
                          variant="ghost"
                          className="text-gray-400 hover:text-blue-500"
                          data-testid={`button-edit-tutor-${tutor.id}`}
                        >
                          <Pencil size={14} />
                        </Button>
                      </Link>
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
    </div>
  );
}
