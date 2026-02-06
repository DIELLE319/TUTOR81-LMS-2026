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
  theme?: 'light' | 'dark';
  defaultExpanded?: boolean;
  showManageLink?: boolean;
  summaryMax?: number;
  label?: string;
}

export function TutorAdmins({ tutorId, theme = 'dark', defaultExpanded = false, showManageLink = true, summaryMax = 2, label }: Props) {
  const [expanded, setExpanded] = useState(defaultExpanded);
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

  const sortedAdmins = [...admins].sort((a, b) => a.id - b.id);
  const normalizedSummaryMax = Math.max(1, Math.min(5, Math.floor(summaryMax)));
  const summaryAdmins = sortedAdmins.slice(0, normalizedSummaryMax);
  const extraAdminsCount = Math.max(0, sortedAdmins.length - summaryAdmins.length);
  const fullAdminsLabel = sortedAdmins.map((a) => a.name).join(', ');

  const summaryTextClass = theme === 'light' ? 'text-gray-600' : 'text-gray-400';
  const manageLinkClass = theme === 'light' ? 'text-gray-600 hover:text-black' : 'text-gray-500 hover:text-yellow-500';
  const chipClass =
    theme === 'light'
      ? 'inline-flex items-center gap-1 bg-yellow-50 border border-yellow-200 text-xs px-2 py-1 rounded text-black'
      : 'inline-flex items-center gap-1 bg-zinc-800 text-yellow-500 text-xs px-2 py-1 rounded';

  return (
    <div className="space-y-1">
      <div className={`text-xs ${summaryTextClass}`} title={fullAdminsLabel || undefined}>
        {label ? <span className="mr-1">{label}</span> : null}
        {summaryAdmins.length > 0 ? (
          <>
            {summaryAdmins.map((a) => a.name).join(', ')}
            {extraAdminsCount > 0 ? <span className="ml-1">(+{extraAdminsCount})</span> : null}
          </>
        ) : (
          <span>-</span>
        )}
        {showManageLink ? (
          <button
            type="button"
            className={`ml-2 underline underline-offset-2 ${manageLinkClass}`}
            onClick={() => setExpanded((v) => !v)}
            data-testid={`button-toggle-admins-${tutorId}`}
          >
            {expanded ? 'Nascondi' : 'Gestisci'}
          </button>
        ) : null}
      </div>

      {showManageLink && expanded ? (
        <div className="flex flex-wrap items-center gap-1">
          {sortedAdmins.map((admin) => (
            <span key={admin.id} className={chipClass}>
              <User size={10} className={theme === 'light' ? 'text-gray-500' : 'text-gray-500'} />
              {admin.name}
              <button
                onClick={() => deleteMutation.mutate(admin.id)}
                className="text-gray-500 hover:text-red-500 ml-1"
                data-testid={`button-remove-admin-${admin.id}`}
                title="Rimuovi admin"
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
                placeholder="Nome e cognome"
                className={
                  theme === 'light'
                    ? 'h-6 w-40 text-xs bg-white border-yellow-600/30 text-black'
                    : 'h-6 w-40 text-xs bg-zinc-800 border-zinc-700 text-white'
                }
                autoFocus
                onKeyDown={(e) => {
                  if (e.key === 'Enter') handleAdd();
                  if (e.key === 'Escape') {
                    setIsAdding(false);
                    setNewName('');
                  }
                }}
                data-testid={`input-new-admin-${tutorId}`}
              />
              <Button
                size="sm"
                variant="ghost"
                className="h-6 w-6 p-0 text-green-500"
                onClick={handleAdd}
                disabled={addMutation.isPending}
                title="Aggiungi admin"
              >
                <Plus size={12} />
              </Button>
            </div>
          ) : (
            <button
              className="inline-flex items-center gap-1 text-gray-500 hover:text-yellow-500 text-xs"
              onClick={() => setIsAdding(true)}
              data-testid={`button-add-admin-${tutorId}`}
              title="Aggiungi admin"
              type="button"
            >
              <Plus size={12} />
              <span>{sortedAdmins.length === 0 ? 'Aggiungi admin' : ''}</span>
            </button>
          )}
        </div>
      ) : null}
    </div>
  );
}
