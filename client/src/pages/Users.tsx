import { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { Search, Users as UsersIcon, X, User } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useToast } from '@/hooks/use-toast';
import { apiRequest, queryClient } from '@/lib/queryClient';
import type { User as UserType } from '@shared/schema';

interface UserWithCompany extends UserType {
  companyName?: string | null;
  tutorName?: string | null;
}

interface Company {
  id: number;
  businessName: string;
  isTutor: boolean;
}

interface Enrollment {
  id: number;
  courseTitle: string;
  startDate: string | null;
  status: string;
  completedAt: string | null;
}

export default function Users() {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedUser, setSelectedUser] = useState<UserWithCompany | null>(null);
  const [editData, setEditData] = useState<Partial<UserWithCompany>>({});
  const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());
  const { toast } = useToast();

  const { data: users = [], isLoading } = useQuery<UserWithCompany[]>({
    queryKey: ['/api/platform-users'],
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies'],
  });

  const { data: userEnrollments = [] } = useQuery<Enrollment[]>({
    queryKey: ['/api/user-enrollments', selectedUser?.id],
    enabled: !!selectedUser,
  });

  const updateUserMutation = useMutation({
    mutationFn: async (data: { id: string; updates: Partial<UserWithCompany> }) => {
      return apiRequest('PATCH', `/api/users/${data.id}`, data.updates);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/platform-users'] });
      toast({ title: "Utente aggiornato", description: "I dati sono stati salvati" });
      setSelectedUser(null);
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare l'utente", variant: "destructive" });
    },
  });

  const filteredUsers = users.filter(u => 
    u.id?.toString().includes(searchTerm) ||
    u.firstName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.lastName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.email?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.companyName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    u.tutorName?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const openUserModal = (user: UserWithCompany) => {
    setSelectedUser(user);
    setEditData({
      firstName: user.firstName,
      lastName: user.lastName,
      email: user.email,
      fiscalCode: user.fiscalCode,
      phone: user.phone,
      role: user.role,
      idcompany: user.idcompany,
    });
  };

  const handleSave = () => {
    if (selectedUser) {
      updateUserMutation.mutate({ id: selectedUser.id, updates: editData });
    }
  };

  const roleLabels: Record<number, string> = {
    0: 'Corsista',
    1: 'Amministratore Ente Formativo',
    2: 'Referente Aziendale',
    1000: 'Superadmin',
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-4">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-users-title">Elenco Utenti</h1>
          <p className="text-gray-500 text-sm">{users.length} utenti registrati</p>
        </div>
      </div>

      <div className="bg-[#1e1e1e] rounded-lg border border-gray-800 p-3 mb-4">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={16} />
          <input
            type="text"
            placeholder="Cerca per ID, nome, email o azienda..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-2 pl-9 pr-4 text-sm text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
            data-testid="input-search-users"
          />
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
        <div className="bg-white rounded-lg overflow-hidden">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-gray-100 text-left text-xs text-gray-600 uppercase">
                <th className="px-2 py-2 w-8">
                  <input 
                    type="checkbox" 
                    className="w-4 h-4 rounded border-gray-300"
                    checked={filteredUsers.length > 0 && selectedIds.size === filteredUsers.length}
                    onChange={(e) => {
                      if (e.target.checked) {
                        setSelectedIds(new Set(filteredUsers.map(u => u.id)));
                      } else {
                        setSelectedIds(new Set());
                      }
                    }}
                    data-testid="checkbox-select-all"
                  />
                </th>
                <th className="px-3 py-2 font-medium">ID</th>
                <th className="px-3 py-2 font-medium">Nome</th>
                <th className="px-3 py-2 font-medium">Cognome</th>
                <th className="px-3 py-2 font-medium">Codice Fiscale</th>
                <th className="px-3 py-2 font-medium">Ente Formativo</th>
                <th className="px-3 py-2 font-medium">Azienda</th>
                <th className="px-3 py-2 font-medium">Email</th>
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
                      checked={selectedIds.has(user.id)}
                      onChange={(e) => {
                        const newSet = new Set(selectedIds);
                        if (e.target.checked) {
                          newSet.add(user.id);
                        } else {
                          newSet.delete(user.id);
                        }
                        setSelectedIds(newSet);
                      }}
                      data-testid={`checkbox-user-${user.id}`}
                    />
                  </td>
                  <td className="px-3 py-1.5 font-mono text-xs text-gray-500">
                    {user.id.substring(0, 8)}
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
                  <td className="px-3 py-1.5 text-xs">
                    {user.tutorName || '-'}
                  </td>
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
          
          {selectedIds.size > 0 && (
            <div className="flex items-center justify-between p-3 bg-gray-50 border-t border-gray-200">
              <span className="text-sm text-gray-600">
                {selectedIds.size} utent{selectedIds.size === 1 ? 'e' : 'i'} selezionat{selectedIds.size === 1 ? 'o' : 'i'}
              </span>
              <div className="flex gap-2">
                <Button 
                  size="sm"
                  variant="outline"
                  className="bg-orange-400 hover:bg-orange-500 text-white border-0"
                  data-testid="button-suspend-selected"
                >
                  Sospendi
                </Button>
                <Button 
                  size="sm"
                  variant="destructive"
                  className="bg-red-500 hover:bg-red-600"
                  data-testid="button-delete-selected"
                >
                  Elimina
                </Button>
              </div>
            </div>
          )}
        </div>
      )}

      {selectedUser && (
        <div className="fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-100 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
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

                    <div className="pt-3 border-t border-gray-200">
                      <p className="text-sm text-gray-600 mb-2">Quale ruolo ha questo utente in piattaforma?</p>
                      <div className="space-y-2">
                        {[
                          { value: 0, label: 'Corsista' },
                          { value: 2, label: 'Referente Aziendale' },
                          { value: 1, label: 'Amministratore Ente Formativo' },
                          { value: 1000, label: 'Superadmin' },
                        ].map((r) => (
                          <label key={r.value} className="flex items-center gap-2 cursor-pointer">
                            <input
                              type="radio"
                              name="role"
                              checked={editData.role === r.value}
                              onChange={() => setEditData({ ...editData, role: r.value })}
                              className="text-blue-600"
                            />
                            <span className="text-sm text-gray-700">{r.label}</span>
                          </label>
                        ))}
                      </div>
                    </div>

                    <div className="flex gap-2 pt-4">
                      <Button 
                        variant="destructive" 
                        size="sm"
                        className="bg-red-400 hover:bg-red-500"
                        data-testid="button-delete-user"
                      >
                        Elimina
                      </Button>
                      <Button 
                        variant="outline" 
                        size="sm"
                        className="bg-orange-400 hover:bg-orange-500 text-white border-0"
                        data-testid="button-suspend-user"
                      >
                        Sospendi
                      </Button>
                    </div>
                  </div>
                </div>

                <div className="space-y-6">
                  <div className="bg-white rounded-lg p-4 border border-gray-200">
                    <h3 className="text-gray-600 font-semibold mb-4 text-center">AZIENDA</h3>
                    <div>
                      <label className="text-sm text-blue-600 mb-1 block">cambia azienda</label>
                      <select
                        value={editData.idcompany || ''}
                        onChange={(e) => setEditData({ ...editData, idcompany: parseInt(e.target.value) || null })}
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
                </div>
              </div>

              <div className="mt-6 bg-white rounded-lg p-4 border border-gray-200">
                <h3 className="text-gray-600 font-semibold mb-4 text-center">Dossier Formativo</h3>
                <table className="w-full text-sm">
                  <thead>
                    <tr className="text-left text-gray-600 border-b">
                      <th className="pb-2 font-medium">Nome Corso</th>
                      <th className="pb-2 font-medium text-center">Programmato</th>
                      <th className="pb-2 font-medium text-center">In attività</th>
                      <th className="pb-2 font-medium text-center">Completato</th>
                    </tr>
                  </thead>
                  <tbody>
                    {userEnrollments.length === 0 ? (
                      <tr>
                        <td colSpan={4} className="py-4 text-center text-gray-400">
                          Nessun corso assegnato
                        </td>
                      </tr>
                    ) : (
                      userEnrollments.map((enr) => (
                        <tr key={enr.id} className="border-b border-gray-100">
                          <td className="py-2 text-gray-700">{enr.courseTitle}</td>
                          <td className="py-2 text-center text-gray-500">
                            {enr.startDate ? new Date(enr.startDate).toLocaleDateString('it-IT') : '-'}
                          </td>
                          <td className="py-2 text-center">
                            {enr.status === 'active' ? '✓' : '-'}
                          </td>
                          <td className="py-2 text-center">
                            {enr.completedAt ? new Date(enr.completedAt).toLocaleDateString('it-IT') : '-'}
                          </td>
                        </tr>
                      ))
                    )}
                  </tbody>
                </table>
              </div>

              <div className="flex justify-end gap-3 mt-6">
                <Button
                  variant="outline"
                  onClick={() => setSelectedUser(null)}
                  data-testid="button-cancel-user"
                >
                  Annulla
                </Button>
                <Button
                  onClick={handleSave}
                  disabled={updateUserMutation.isPending}
                  className="bg-yellow-500 hover:bg-yellow-600 text-black"
                  data-testid="button-save-user"
                >
                  {updateUserMutation.isPending ? 'Salvataggio...' : 'Salva Modifiche'}
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
