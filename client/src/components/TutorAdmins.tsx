import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Plus, X, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { queryClient, apiRequest } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';

interface TutorAdmin {
  id: number;
  tutorId: number;
  name: string;
  email: string | null;
  phone: string | null;
}

interface Props {
  tutorId: number;
}

export function TutorAdmins({ tutorId }: Props) {
  const [isAdding, setIsAdding] = useState(false);
  const [newName, setNewName] = useState('');
  const { toast } = useToast();

  const { data: admins = [] } = useQuery<TutorAdmin[]>({
    queryKey: ['/api/tutors', tutorId, 'admins'],
    queryFn: () => fetch(`/api/tutors/${tutorId}/admins`, { credentials: 'include' }).then(r => r.json()),
  });

  const addMutation = useMutation({
    mutationFn: (name: string) => apiRequest('POST', `/api/tutors/${tutorId}/admins`, { name }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors', tutorId, 'admins'] });
      setNewName('');
      setIsAdding(false);
      toast({ title: 'Admin aggiunto' });
    },
    onError: () => {
      toast({ title: 'Errore', variant: 'destructive' });
    }
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => apiRequest('DELETE', `/api/tutor-admins/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors', tutorId, 'admins'] });
      toast({ title: 'Admin rimosso' });
    },
  });

  const handleAdd = () => {
    if (newName.trim()) {
      addMutation.mutate(newName.trim());
    }
  };

  return (
    <div className="flex flex-wrap items-center gap-1">
      {admins.map(admin => (
        <span 
          key={admin.id} 
          className="inline-flex items-center gap-1 bg-zinc-800 text-yellow-500 text-xs px-2 py-1 rounded"
        >
          <User size={10} className="text-gray-500" />
          <span className="text-gray-500">{admin.id}</span>
          {admin.name}
          <button 
            onClick={() => deleteMutation.mutate(admin.id)}
            className="text-gray-500 hover:text-red-500 ml-1"
            data-testid={`button-remove-admin-${admin.id}`}
          >
            <X size={12} />
          </button>
        </span>
      ))}
      
      {isAdding ? (
        <div className="inline-flex items-center gap-1">
          <Input
            value={newName}
            onChange={(e) => setNewName(e.target.value)}
            placeholder="Nome amm.re"
            className="h-6 w-28 text-xs bg-zinc-800 border-zinc-700"
            autoFocus
            onKeyDown={(e) => {
              if (e.key === 'Enter') handleAdd();
              if (e.key === 'Escape') { setIsAdding(false); setNewName(''); }
            }}
            data-testid={`input-new-admin-${tutorId}`}
          />
          <Button
            size="sm"
            variant="ghost"
            className="h-6 w-6 p-0 text-green-500"
            onClick={handleAdd}
            disabled={addMutation.isPending}
          >
            <Plus size={12} />
          </Button>
        </div>
      ) : (
        <button
          className="inline-flex items-center gap-1 text-gray-500 hover:text-yellow-500 text-xs"
          onClick={() => setIsAdding(true)}
          data-testid={`button-add-admin-${tutorId}`}
        >
          <Plus size={12} />
          <span>{admins.length === 0 ? 'Aggiungi amm.re' : ''}</span>
        </button>
      )}
    </div>
  );
}
