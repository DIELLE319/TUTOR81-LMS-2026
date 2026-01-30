import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Link } from 'wouter';
import { motion } from 'framer-motion';
import { Search, Plus, Building, MapPin, FileText, Pause, Pencil, Trash2, User } from 'lucide-react';
import type { Company } from '@shared/schema';

type TutorWithAdmins = Company & {
  admins: { id: number; firstName: string | null; lastName: string | null; email: string }[];
};
import { Button } from '@/components/ui/button';
import { queryClient, apiRequest } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';

export default function Tutors() {
  const [searchTerm, setSearchTerm] = useState('');
  const { toast } = useToast();

  const { data: tutors = [], isLoading } = useQuery<TutorWithAdmins[]>({
    queryKey: ['/api/tutors'],
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => apiRequest('DELETE', `/api/companies/${id}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      toast({ title: 'Ente eliminato', description: 'L\'ente formativo è stato eliminato.' });
    },
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
    t.city?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-tutors-title">Enti Formativi</h1>
          <p className="text-gray-500 text-sm">Gestisci i tutor autorizzati</p>
        </div>
        <Link href="/companies/new?type=tutor">
          <button 
            className="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
            data-testid="button-new-tutor"
          >
            <Plus size={18} />
            Nuovo Ente
          </button>
        </Link>
      </div>

      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca ente formativo..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
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
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <Building size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun ente formativo</h3>
          <p className="text-gray-500">Aggiungi il primo ente formativo per iniziare</p>
        </div>
      ) : (
        <div className="grid gap-4">
          {filteredTutors.map((tutor, index) => (
            <motion.div
              key={tutor.id}
              initial={{ opacity: 0, y: 20 }}
              animate={{ opacity: 1, y: 0 }}
              transition={{ delay: index * 0.05 }}
              className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 hover:border-yellow-500/50 transition-colors cursor-pointer"
              data-testid={`card-tutor-${tutor.id}`}
            >
              <div className="flex items-center justify-between gap-4">
                <div className="flex items-center gap-4 flex-1 min-w-0">
                  <div className="w-12 h-12 rounded-full bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                    <Building size={24} className="text-yellow-500" />
                  </div>
                  <div className="min-w-0 flex-1">
                    <h3 className="font-bold text-white truncate">
                      <span className="text-gray-500 font-normal mr-2">#{tutor.id}</span>
                      {tutor.businessName}
                    </h3>
                    <div className="flex items-center gap-4 text-sm text-gray-500 mt-1 flex-wrap">
                      {tutor.city && (
                        <span className="flex items-center gap-1">
                          <MapPin size={14} />
                          {tutor.city}
                        </span>
                      )}
                      {tutor.licenseType && (
                        <span className="flex items-center gap-1">
                          <FileText size={14} />
                          {tutor.licenseType}
                        </span>
                      )}
                    </div>
                    {tutor.admins && tutor.admins.length > 0 && (
                      <div className="flex items-center gap-1 text-sm text-yellow-500/80 mt-1">
                        <User size={14} />
                        <span>
                          {tutor.admins.map(a => `${a.firstName || ''} ${a.lastName || ''}`.trim() || a.email).join(', ')}
                        </span>
                      </div>
                    )}
                  </div>
                </div>
                <div className="flex items-center gap-2 flex-shrink-0">
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-gray-700 text-gray-400 hover:text-yellow-500 hover:border-yellow-500"
                    onClick={(e) => { e.stopPropagation(); handleSuspend(tutor.id, tutor.businessName); }}
                    data-testid={`button-suspend-tutor-${tutor.id}`}
                  >
                    <Pause size={14} className="mr-1" />
                    Sospendi
                  </Button>
                  <Link href={`/companies/${tutor.id}/edit`}>
                    <Button
                      size="sm"
                      variant="outline"
                      className="border-gray-700 text-gray-400 hover:text-blue-500 hover:border-blue-500"
                      onClick={(e) => e.stopPropagation()}
                      data-testid={`button-edit-tutor-${tutor.id}`}
                    >
                      <Pencil size={14} className="mr-1" />
                      Modifica
                    </Button>
                  </Link>
                  <Button
                    size="sm"
                    variant="outline"
                    className="border-gray-700 text-gray-400 hover:text-red-500 hover:border-red-500"
                    onClick={(e) => { e.stopPropagation(); handleDelete(tutor.id, tutor.businessName); }}
                    data-testid={`button-delete-tutor-${tutor.id}`}
                  >
                    <Trash2 size={14} className="mr-1" />
                    Elimina
                  </Button>
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      )}
    </div>
  );
}
