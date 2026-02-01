import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Link } from 'wouter';
import { Search, Plus, Building, MapPin, Mail, Phone, Pause, Pencil, Trash2, Check } from 'lucide-react';
import type { Tutor } from '@shared/schema';
import { Button } from '@/components/ui/button';
import { queryClient, apiRequest } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';
import { Input } from '@/components/ui/input';

export default function Tutors() {
  const [searchTerm, setSearchTerm] = useState('');
  const [editingAdmin, setEditingAdmin] = useState<{id: number, value: string} | null>(null);
  const { toast } = useToast();

  const { data: tutors = [], isLoading } = useQuery<Tutor[]>({
    queryKey: ['/api/tutors'],
  });

  const updateAdminMutation = useMutation({
    mutationFn: ({ id, adminName }: { id: number, adminName: string }) => 
      apiRequest('PUT', `/api/tutors/${id}`, { adminName }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      setEditingAdmin(null);
      toast({ title: 'Salvato', description: 'Amministratore aggiornato.' });
    },
    onError: () => {
      toast({ title: 'Errore', description: 'Impossibile salvare.', variant: 'destructive' });
    }
  });

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
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Amm.re Ente</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Indirizzo</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Telefono</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Email</th>
                <th className="text-left py-3 px-4 text-gray-400 font-medium text-sm">Abbonamento</th>
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
                      <span className="font-medium text-white">{tutor.businessName}</span>
                    </div>
                  </td>
                  <td className="py-3 px-4">
                    {editingAdmin?.id === tutor.id ? (
                      <div className="flex items-center gap-2">
                        <Input
                          value={editingAdmin.value}
                          onChange={(e) => setEditingAdmin({ id: tutor.id, value: e.target.value })}
                          className="h-8 bg-zinc-800 border-zinc-700 text-white text-sm w-40"
                          placeholder="Nome admin..."
                          autoFocus
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                              updateAdminMutation.mutate({ id: tutor.id, adminName: editingAdmin.value });
                            }
                            if (e.key === 'Escape') {
                              setEditingAdmin(null);
                            }
                          }}
                          data-testid={`input-admin-${tutor.id}`}
                        />
                        <Button
                          size="sm"
                          variant="ghost"
                          className="text-green-500 hover:text-green-400 h-8 w-8 p-0"
                          onClick={() => updateAdminMutation.mutate({ id: tutor.id, adminName: editingAdmin.value })}
                          data-testid={`button-save-admin-${tutor.id}`}
                        >
                          <Check size={14} />
                        </Button>
                      </div>
                    ) : (
                      <button
                        onClick={() => setEditingAdmin({ id: tutor.id, value: tutor.adminName || '' })}
                        className="text-gray-400 text-sm hover:text-yellow-500 cursor-pointer text-left"
                        data-testid={`button-edit-admin-${tutor.id}`}
                      >
                        {tutor.adminName || <span className="text-gray-600 italic">+ Aggiungi admin</span>}
                      </button>
                    )}
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
                    <span className="text-xs bg-yellow-500/20 text-yellow-500 px-2 py-1 rounded">
                      {tutor.notes || 'Standard'}
                    </span>
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
