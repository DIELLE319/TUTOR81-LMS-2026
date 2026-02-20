import { useMemo, useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { useSearch } from 'wouter';
import { Search, Users as UsersIcon, X, User } from 'lucide-react';
import { useToast } from '@/hooks/use-toast';
import { useAuth } from '@/hooks/use-auth';
import { apiRequest, queryClient } from '@/lib/queryClient';

interface StudentRow {
  id: number;
  companyId: number;
  email: string;
  firstName: string | null;
  lastName: string | null;
  fiscalCode: string | null;
  phone: string | null;
  isActive: boolean;
  companyName: string | null;
  tutorId: number | null;
  tutorName: string | null;
}

interface Company {
  id: number;
  businessName: string;
  isTutor: boolean;
}

interface Enrollment {
  id: number;
  learningProjectId: number | null;
  courseTitle: string;
  startDate: string | null;
  activeDate: string | null;
  status: string;
  progress: number;
  completedAt: string | null;
}

export default function Users() {
  const { user } = useAuth();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedTutorId, setSelectedTutorId] = useState<number | 'all'>('all');
  const [selectedUser, setSelectedUser] = useState<StudentRow | null>(null);
  const [editData, setEditData] = useState<Partial<StudentRow>>({});
  const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
  const { toast } = useToast();
  const searchString = useSearch();
  
  // Se l'utente è venditore (admin tutor), filtra per il suo tutorId
  const isVenditore = user?.role === 1;
  const isSuperAdmin = user?.role === 1000;
  const userTutorId = (user as any)?.tutorId;

  const { data: users = [], isLoading } = useQuery<StudentRow[]>({
    queryKey: ['/api/students', userTutorId],
    queryFn: async () => {
      const url = userTutorId ? `/api/students?tutorId=${userTutorId}` : '/api/students';
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) throw new Error('Failed to fetch');
      return res.json();
    },
  });

  useEffect(() => {
    if (users.length > 0 && searchString) {
      const params = new URLSearchParams(searchString);
      const userId = params.get('userId');
      if (userId) {
        const user = users.find(u => u.id === parseInt(userId));
        if (user && !selectedUser) {
          setSelectedUser(user);
          setEditData({
            firstName: user.firstName,
            lastName: user.lastName,
            email: user.email,
            fiscalCode: user.fiscalCode,
            phone: user.phone,
            companyId: user.companyId,
          });
        }
      }
    }
  }, [users, searchString]);

  const { data: companiesResponse } = useQuery<{ data: Company[], pagination: { total: number } }>({
    queryKey: ['/api/companies'],
  });
  const companies = companiesResponse?.data || [];

  const tutorCompanies = useMemo(() => companies.filter((c) => c.isTutor), [companies]);
  const selectedTutorName = useMemo(() => {
    if (selectedTutorId === 'all') return null;
    return tutorCompanies.find((c) => c.id === selectedTutorId)?.businessName ?? null;
  }, [selectedTutorId, tutorCompanies]);

  const { data: userEnrollments = [] } = useQuery<Enrollment[]>({
    queryKey: [`/api/user-enrollments?userId=${selectedUser?.id}`],
    enabled: !!selectedUser,
  });

  const updateUserMutation = useMutation({
    mutationFn: async (data: { id: number; updates: Partial<StudentRow> }) => {
      return apiRequest('PATCH', `/api/students/${data.id}`, data.updates);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/students'] });
      toast({ title: "Utente aggiornato", description: "I dati sono stati salvati" });
      setSelectedUser(null);
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare l'utente", variant: "destructive" });
    },
  });

  const filteredUsers = useMemo(() => {
    const normalizedSearch = searchTerm.trim().toLowerCase();

    return users.filter((u) => {
      if (isSuperAdmin && selectedTutorId !== 'all' && u.tutorId !== selectedTutorId) {
        return false;
      }

      if (!normalizedSearch) return true;

      return (
        u.id?.toString().includes(normalizedSearch) ||
        u.firstName?.toLowerCase().includes(normalizedSearch) ||
        u.lastName?.toLowerCase().includes(normalizedSearch) ||
        u.email?.toLowerCase().includes(normalizedSearch) ||
        u.fiscalCode?.toLowerCase().includes(normalizedSearch) ||
        u.companyName?.toLowerCase().includes(normalizedSearch) ||
        u.tutorName?.toLowerCase().includes(normalizedSearch)
      );
    });
  }, [users, searchTerm, isSuperAdmin, selectedTutorId]);

  const openUserModal = (user: StudentRow) => {
    setSelectedUser(user);
    setEditData({
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      fiscalCode: user.fiscalCode,
      phone: user.phone,
      companyId: user.companyId,
    });
  };

  const handleSave = () => {
    if (selectedUser) {
      updateUserMutation.mutate({ id: selectedUser.id, updates: editData });
    }
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-4">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-users-title">
            Elenco Utenti
            {selectedTutorName ? (
              <>
                {' '}di <span className="text-yellow-400">{selectedTutorName}</span>
              </>
            ) : null}
          </h1>
          <p className="text-gray-500 text-sm">
            {isSuperAdmin && selectedTutorId === 'all'
              ? `${users.length} utenti registrati (tutti gli enti)`
              : `${filteredUsers.length} utenti`}
          </p>
        </div>
      </div>

      <div className="bg-white rounded-xl overflow-hidden border-2 border-black/70 mb-4">
        <div className="bg-yellow-400 px-4 py-3 border-b border-black/20">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:gap-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-black/60" size={16} />
              <input
                type="text"
                placeholder="ID, nome, email, azienda..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full bg-white border border-yellow-600/30 rounded-lg py-2 pl-9 pr-4 text-sm text-black placeholder-black/50 focus:border-black/40 focus:outline-none"
                data-testid="input-search-users"
              />
            </div>

            {isSuperAdmin && !isVenditore && (
              <select
                value={selectedTutorId}
                onChange={(e) => {
                  const value = e.target.value;
                  setSelectedTutorId(value === 'all' ? 'all' : parseInt(value));
                  setSelectedIds(new Set());
                }}
                className="h-10 bg-white border border-yellow-600/30 rounded-lg px-3 text-sm text-black focus:border-black/40 focus:outline-none"
                data-testid="select-tutor-filter"
              >
                <option value="all">--- Tutti gli Enti ---</option>
                {tutorCompanies.map((c) => (
                  <option key={c.id} value={c.id}>
                    {c.businessName}
                  </option>
                ))}
              </select>
            )}

            <div className="flex flex-wrap items-center gap-2 justify-end">
              <div
                className="h-10 px-3 inline-flex items-center rounded-lg bg-green-600 text-white text-sm font-semibold"
                data-testid="badge-total-users"
              >
                Totale: {filteredUsers.length} utenti
              </div>

              {selectedIds.size > 0 && (
                <span className="text-sm text-black/70">
                  {selectedIds.size} selezionati
                </span>
              )}

              <button
                disabled={selectedIds.size === 0}
                className="h-10 px-3 text-sm rounded-lg bg-yellow-200 hover:bg-yellow-100 text-black border border-black/10 disabled:opacity-50"
                data-testid="button-suspend-selected"
              >
                Sospendi
              </button>
              <button
                disabled={selectedIds.size === 0}
                className="h-10 px-3 text-sm rounded-lg bg-orange-500 hover:bg-orange-600 text-black border border-black/10 disabled:opacity-50"
                data-testid="button-delete-selected"
              >
                Elimina
              </button>
            </div>
          </div>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredUsers.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-lg border border-gray-800">
          <UsersIcon size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessun utente trovato</h3>
          <p className="text-gray-500">Gli utenti registrati appariranno qui</p>
        </div>
      ) : (
        <div className="bg-white rounded-xl overflow-hidden border-2 border-black/70">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-left text-xs text-black uppercase">
                <th className="px-2 py-2 w-8">
                  <input 
                    type="checkbox" 
                    className="w-4 h-4 rounded border-gray-300"
                    checked={filteredUsers.length > 0 && selectedIds.size === filteredUsers.length}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedIds(new Set(filteredUsers.map(u => u.id as number)));
                      } else {
                        setSelectedIds(new Set());
                      }
                    }}
                    data-testid="checkbox-select-all"
                  />
                </th>
                <th className="px-3 py-2 font-bold">ID</th>
                <th className="px-3 py-2 font-bold">Nome</th>
                <th className="px-3 py-2 font-bold">Cognome</th>
                <th className="px-3 py-2 font-bold">Codice Fiscale</th>
                {isSuperAdmin && selectedTutorId === 'all' ? (
                  <th className="px-3 py-2 font-bold">Ente</th>
                ) : null}
                <th className="px-3 py-2 font-bold">Azienda</th>
                <th className="px-3 py-2 font-bold">Email</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {filteredUsers.map((user) => (
                <tr 
                  key={user.id} 
                  className="hover:bg-gray-50 text-gray-800 cursor-pointer"
                  onClick={() => openUserModal(user)}
                  data-testid={`row-user-${user.id}`}
                >
                  <td className="px-2 py-1.5" onClick={(e) => e.stopPropagation()}>
                    <input 
                      type="checkbox" 
                      className="w-4 h-4 rounded border-gray-300"
                      checked={selectedIds.has(user.id as number)}
                      onChange={(e) => {
                        const newSet = new Set(selectedIds);
                        if (e.target.checked) {
                          newSet.add(user.id as number);
                        } else {
                          newSet.delete(user.id as number);
                        }
                        setSelectedIds(newSet);
                      }}
                      data-testid={`checkbox-user-${user.id}`}
                    />
                  </td>
                  <td className="px-3 py-1.5 font-mono text-xs text-gray-500">
                    {user.id}
                  </td>
                  <td className="px-3 py-1.5 text-xs">
                    {user.firstName || '-'}
                  </td>
                  <td className="px-3 py-1.5 text-xs">
                    {user.lastName || '-'}
                  </td>
                  <td className="px-3 py-1.5 font-mono text-xs text-gray-600">
                    {user.fiscalCode || '-'}
                  </td>
                  {isSuperAdmin && selectedTutorId === 'all' ? (
                    <td className="px-3 py-1.5 text-xs">
                      {user.tutorName || '-'}
                    </td>
                  ) : null}
                  <td className="px-3 py-1.5 text-xs">
                    {user.companyName || '-'}
                  </td>
                  <td className="px-3 py-1.5 text-xs text-gray-600">
                    {user.email || '-'}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {selectedUser && (
        <div className="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-100 rounded-lg max-w-4xl w-full max-h-[95vh] overflow-y-auto">
            <div className="flex justify-between items-center p-4 border-b border-gray-300">
              <h2 className="text-xl font-semibold text-gray-800">
                <User className="inline mr-2" size={20} />
                Dettaglio Utente
              </h2>
              <button 
                onClick={() => setSelectedUser(null)}
                className="text-gray-500 hover:text-gray-700"
                data-testid="button-close-user-modal"
              >
                <X size={24} />
              </button>
            </div>

            <div className="p-6">
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg p-4 border border-gray-200">
                  <div className="flex justify-between items-center mb-4">
                    <h3 className="text-blue-600 font-semibold flex items-center gap-2">
                      <User size={16} />
                      DATI UTENTE
                    </h3>
                    <span className="text-xs text-gray-400 uppercase">Obbligatori</span>
                  </div>

                  <div className="space-y-3">
                    <input
                      type="text"
                      value={editData.firstName || ''}
                      onChange={(e) => setEditData({ ...editData, firstName: e.target.value })}
                      placeholder="Nome"
                      className="w-full border border-gray-300 rounded px-3 py-2 text-gray-800"
                      data-testid="input-user-firstname"
                    />
                    <input
                      type="text"
                      value={editData.lastName || ''}
                      onChange={(e) => setEditData({ ...editData, lastName: e.target.value })}
                      placeholder="Cognome"
                      className="w-full border border-gray-300 rounded px-3 py-2 text-gray-800"
                      data-testid="input-user-lastname"
                    />
                    <input
                      type="text"
                      value={editData.fiscalCode || ''}
                      onChange={(e) => setEditData({ ...editData, fiscalCode: e.target.value })}
                      placeholder="Codice Fiscale"
                      className="w-full border border-gray-300 rounded px-3 py-2 text-gray-800"
                      data-testid="input-user-fiscalcode"
                    />
                    <input
                      type="email"
                      value={editData.email || ''}
                      onChange={(e) => setEditData({ ...editData, email: e.target.value })}
                      placeholder="Email"
                      className="w-full border border-gray-300 rounded px-3 py-2 text-gray-800"
                      data-testid="input-user-email"
                    />
                    <input
                      type="text"
                      value={editData.phone || ''}
                      onChange={(e) => setEditData({ ...editData, phone: e.target.value })}
                      placeholder="Telefono"
                      className="w-full border border-gray-300 rounded px-3 py-2 text-gray-800"
                      data-testid="input-user-phone"
                    />

                  </div>
                </div>

                <div className="space-y-6">
                  <div className="bg-white rounded-lg p-4 border border-gray-200">
                    <h3 className="text-gray-600 font-semibold mb-4 text-center">AZIENDA</h3>
                    <div>
                      <label className="text-sm text-blue-600 mb-1 block">cambia azienda</label>
                      <select
                        value={editData.companyId || ''}
                        onChange={(e) => setEditData({ ...editData, companyId: parseInt(e.target.value) || undefined })}
                        className="w-full border border-gray-300 rounded px-3 py-2 text-gray-800"
                        data-testid="select-user-company"
                      >
                        <option value="">-- Seleziona azienda --</option>
                        {companies.filter(c => !c.isTutor).map((c) => (
                          <option key={c.id} value={c.id}>{c.businessName}</option>
                        ))}
                      </select>
                    </div>
                  </div>

                  <div className="bg-white rounded-lg p-4 border border-gray-200">
                    <div className="flex gap-2">
                      <button 
                        className="px-3 py-1.5 text-sm rounded bg-red-400 hover:bg-red-500 text-white"
                        data-testid="button-delete-user"
                      >
                        Elimina
                      </button>
                      <button 
                        className="px-3 py-1.5 text-sm rounded bg-orange-400 hover:bg-orange-500 text-white"
                        data-testid="button-suspend-user"
                      >
                        Sospendi
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <div className="mt-6 bg-white rounded-lg p-4 border border-gray-200">
                <h3 className="text-gray-600 font-semibold mb-4 text-center">Dossier Formativo</h3>
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-gray-600 border-b">
                      <th className="pb-2 font-medium w-16">ID</th>
                      <th className="pb-2 font-medium">Nome Corso</th>
                      <th className="pb-2 font-medium text-center">Data attivazione</th>
                      <th className="pb-2 font-medium text-center">In attività</th>
                      <th className="pb-2 font-medium text-center">Completato</th>
                    </tr>
                  </thead>
                  <tbody>
                    {userEnrollments.length === 0 ? (
                      <tr>
                        <td colSpan={5} className="py-4 text-center text-gray-400">
                          Nessun corso assegnato
                        </td>
                      </tr>
                    ) : (
                      userEnrollments.map((enr) => (
                        <tr key={enr.id} className="border-b border-gray-100">
                          <td className="py-2 text-gray-500 font-mono text-xs">{enr.learningProjectId || '-'}</td>
                          <td className="py-2 text-gray-700">{enr.courseTitle?.replace(/^[A-Za-z0-9]+\s*-\s*/, '')}</td>
                          <td className="py-2 text-center text-gray-700">
                            {enr.startDate ? new Date(enr.startDate).toLocaleDateString('it-IT') : '-'}
                          </td>
                          <td className="py-2 text-center text-gray-700">
                            {enr.activeDate ? new Date(enr.activeDate).toLocaleDateString('it-IT') : '-'}
                          </td>
                          <td className="py-2 text-center text-gray-700 font-medium">
                            {enr.completedAt ? new Date(enr.completedAt).toLocaleDateString('it-IT') : '-'}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>

              <div className="flex justify-end gap-3 mt-6">
                <button
                  onClick={() => setSelectedUser(null)}
                  className="px-4 py-2 text-sm rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100"
                  data-testid="button-cancel-user"
                >
                  Annulla
                </button>
                <button
                  onClick={handleSave}
                  disabled={updateUserMutation.isPending}
                  className="px-4 py-2 text-sm rounded-lg bg-yellow-500 hover:bg-yellow-600 text-black font-bold disabled:opacity-50"
                  data-testid="button-save-user"
                >
                  {updateUserMutation.isPending ? 'Salvataggio...' : 'Salva Modifiche'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
