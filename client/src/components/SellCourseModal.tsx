import { useState, useEffect, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { UserPlus, Users, Plus, Minus, Send, Search, Check, ChevronsUpDown } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';

interface Company {
  id: number;
  business_name: string;
}

interface CompanyUser {
  id: string;
  firstName: string | null;
  lastName: string | null;
  fiscalCode: string | null;
  email: string | null;
  role: number | null;
}

interface SellCourseModalProps {
  isOpen: boolean;
  onClose: () => void;
  course: {
    id: number;
    title: string;
    hours: number | null;
    listPrice: string | number | null;
  } | null;
}

interface CorsistaRow {
  email: string;
  startDate: string;
  endDate: string;
  daysToAlert: number;
  lastName: string;
  firstName: string;
  fiscalCode: string;
  userType: string;
}

const USER_TYPES = [
  { value: 'lavoratore', label: 'Lavoratore' },
  { value: 'preposto', label: 'Preposto' },
  { value: 'dirigente', label: 'Dirigente' },
  { value: 'rspp', label: 'RSPP' },
  { value: 'aspp', label: 'ASPP' },
  { value: 'datore', label: 'Datore di Lavoro' },
];

const getRoleLabel = (role: number | null) => {
  switch (role) {
    case 0: return 'Lavoratore';
    case 1: return 'Tutor';
    case 2: return 'Company';
    default: return 'Lavoratore';
  }
};

const createEmptyRow = (): CorsistaRow => {
  const today = new Date();
  const endDateDefault = new Date();
  endDateDefault.setDate(today.getDate() + 90);
  
  return {
    email: '',
    startDate: today.toISOString().split('T')[0],
    endDate: endDateDefault.toISOString().split('T')[0],
    daysToAlert: 15,
    lastName: '',
    firstName: '',
    fiscalCode: '',
    userType: '',
  };
};

export default function SellCourseModal({ isOpen, onClose, course }: SellCourseModalProps) {
  const [selectedCompanyId, setSelectedCompanyId] = useState<string>('');
  const [companySearchOpen, setCompanySearchOpen] = useState(false);
  const [userMode, setUserMode] = useState<'new' | 'existing'>('new');
  const [rows, setRows] = useState<CorsistaRow[]>([createEmptyRow()]);
  const [selectedExistingUsers, setSelectedExistingUsers] = useState<Set<string>>(new Set());
  const [existingUserSearch, setExistingUserSearch] = useState('');
  const [errors, setErrors] = useState<Record<string, boolean>>({});

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies'],
  });

  const { data: companyUsers = [] } = useQuery<CompanyUser[]>({
    queryKey: ['/api/companies', selectedCompanyId, 'users'],
    enabled: !!selectedCompanyId && userMode === 'existing',
  });

  const filteredCompanies = useMemo(() => {
    return companies
      .filter(c => !c.business_name?.toLowerCase().includes('tutor'))
      .sort((a, b) => a.business_name.localeCompare(b.business_name));
  }, [companies]);

  const selectedCompanyName = useMemo(() => {
    const company = companies.find(c => c.id.toString() === selectedCompanyId);
    return company?.business_name || '';
  }, [companies, selectedCompanyId]);

  const filteredExistingUsers = useMemo(() => {
    if (!existingUserSearch) return companyUsers;
    const search = existingUserSearch.toLowerCase();
    return companyUsers.filter(u => 
      u.lastName?.toLowerCase().includes(search) ||
      u.firstName?.toLowerCase().includes(search) ||
      u.fiscalCode?.toLowerCase().includes(search) ||
      u.email?.toLowerCase().includes(search)
    );
  }, [companyUsers, existingUserSearch]);

  useEffect(() => {
    if (isOpen) {
      setRows([createEmptyRow()]);
      setSelectedCompanyId('');
      setUserMode('new');
      setErrors({});
      setSelectedExistingUsers(new Set());
      setExistingUserSearch('');
    }
  }, [isOpen]);

  useEffect(() => {
    setSelectedExistingUsers(new Set());
    setExistingUserSearch('');
  }, [selectedCompanyId]);

  const updateRow = (index: number, field: keyof CorsistaRow, value: string | number) => {
    setRows(prev => {
      const newRows = [...prev];
      newRows[index] = { ...newRows[index], [field]: value };
      return newRows;
    });
  };

  const addRow = () => {
    setRows(prev => [...prev, createEmptyRow()]);
  };

  const removeRow = () => {
    if (rows.length > 1) {
      setRows(prev => prev.slice(0, -1));
    }
  };

  const toggleExistingUser = (userId: string) => {
    setSelectedExistingUsers(prev => {
      const newSet = new Set(prev);
      if (newSet.has(userId)) {
        newSet.delete(userId);
      } else {
        newSet.add(userId);
      }
      return newSet;
    });
  };

  const validateForm = () => {
    const newErrors: Record<string, boolean> = {};
    
    if (!selectedCompanyId) newErrors.company = true;
    
    if (userMode === 'new') {
      rows.forEach((row, idx) => {
        if (!row.email) newErrors[`email_${idx}`] = true;
        if (!row.lastName) newErrors[`lastName_${idx}`] = true;
        if (!row.firstName) newErrors[`firstName_${idx}`] = true;
        if (!row.fiscalCode) newErrors[`fiscalCode_${idx}`] = true;
        if (!row.userType) newErrors[`userType_${idx}`] = true;
      });
    } else {
      if (selectedExistingUsers.size === 0) newErrors.existingUsers = true;
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = () => {
    if (!validateForm()) return;
    
    if (userMode === 'new') {
      console.log('Submitting new users:', {
        courseId: course?.id,
        companyId: selectedCompanyId,
        corsisti: rows
      });
    } else {
      console.log('Submitting existing users:', {
        courseId: course?.id,
        companyId: selectedCompanyId,
        userIds: Array.from(selectedExistingUsers)
      });
    }
    
    onClose();
  };

  const formatCourseTitle = (title: string) => {
    const dashIndex = title.indexOf(' - ');
    if (dashIndex > 0 && dashIndex < 30) {
      return title.substring(dashIndex + 3).trim();
    }
    return title.replace(/^EL\d*[a-zA-Z]*\s*-\s*/i, '').trim();
  };

  if (!course) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-6xl bg-gradient-to-br from-slate-900 to-slate-800 border-slate-700 text-white p-0 overflow-hidden max-h-[90vh]">
        <DialogHeader className="bg-gradient-to-r from-cyan-700 to-cyan-600 px-6 py-4">
          <DialogTitle className="text-white text-lg font-bold flex items-center gap-2">
            <Send size={20} />
            Invia codici di accesso per il corso
          </DialogTitle>
          <p className="text-cyan-100 font-semibold mt-1">{formatCourseTitle(course.title)}</p>
        </DialogHeader>

        <div className="p-6 space-y-4 overflow-y-auto max-h-[calc(90vh-180px)]">
          <div className={`border rounded-lg ${errors.company ? 'border-red-500' : 'border-slate-600'}`}>
            <Popover open={companySearchOpen} onOpenChange={setCompanySearchOpen}>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  aria-expanded={companySearchOpen}
                  className="w-full justify-between h-12 bg-slate-800 border-0 text-white hover:bg-slate-700"
                  data-testid="select-company"
                >
                  {selectedCompanyName || "--- Scegli il cliente ---"}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-[500px] p-0 bg-slate-800 border-slate-600" align="start">
                <Command className="bg-slate-800">
                  <CommandInput 
                    placeholder="Cerca azienda..." 
                    className="text-white"
                  />
                  <CommandList className="max-h-60">
                    <CommandEmpty className="text-slate-400 py-4 text-center">Nessuna azienda trovata</CommandEmpty>
                    <CommandGroup>
                      {filteredCompanies.map(company => (
                        <CommandItem
                          key={company.id}
                          value={company.business_name}
                          onSelect={() => {
                            setSelectedCompanyId(company.id.toString());
                            setCompanySearchOpen(false);
                          }}
                          className="text-white hover:bg-slate-700 cursor-pointer"
                        >
                          <Check
                            className={cn(
                              "mr-2 h-4 w-4",
                              selectedCompanyId === company.id.toString() ? "opacity-100" : "opacity-0"
                            )}
                          />
                          {company.business_name}
                        </CommandItem>
                      ))}
                    </CommandGroup>
                  </CommandList>
                </Command>
              </PopoverContent>
            </Popover>
          </div>

          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <span className="text-slate-300 text-sm">Chi deve svolgere il corso</span>
              <div className="flex gap-2">
                <Button
                  type="button"
                  variant={userMode === 'new' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setUserMode('new')}
                  className={userMode === 'new' ? 'bg-emerald-500 hover:bg-emerald-600' : 'border-slate-500 text-slate-300'}
                  data-testid="btn-new-user"
                >
                  <UserPlus size={16} className="mr-1" />
                  Nuovo utente
                </Button>
                <Button
                  type="button"
                  variant={userMode === 'existing' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setUserMode('existing')}
                  className={userMode === 'existing' ? 'bg-slate-600 hover:bg-slate-500' : 'border-slate-500 text-slate-300'}
                  data-testid="btn-existing-user"
                >
                  <Users size={16} className="mr-1" />
                  Utente esistente
                </Button>
              </div>
            </div>

            {userMode === 'new' && (
              <div className="flex items-center gap-3">
                <Label className="text-slate-300 text-sm">Quantità corsi</Label>
                <div className="flex items-center gap-2">
                  <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    className="h-8 w-8 border-slate-500"
                    onClick={removeRow}
                    disabled={rows.length <= 1}
                    data-testid="btn-qty-minus"
                  >
                    <Minus size={14} />
                  </Button>
                  <span className="w-10 text-center font-bold text-lg">{rows.length}</span>
                  <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    className="h-8 w-8 border-slate-500"
                    onClick={addRow}
                    data-testid="btn-qty-plus"
                  >
                    <Plus size={14} />
                  </Button>
                </div>
              </div>
            )}
          </div>

          {userMode === 'new' ? (
            <div className="bg-slate-800/50 rounded-lg border border-slate-700 overflow-hidden">
              <table className="w-full text-sm">
                <thead className="bg-slate-700/50">
                  <tr>
                    <th className="px-2 py-2 text-left text-xs font-medium text-slate-300 w-[180px]">Email destinatario *</th>
                    <th className="px-2 py-2 text-center text-xs font-medium text-slate-300 w-[100px]">Data inizio</th>
                    <th className="px-2 py-2 text-center text-xs font-medium text-slate-300 w-[100px]">Fine corso</th>
                    <th className="px-2 py-2 text-center text-xs font-medium text-slate-300 w-[70px]">Alert gg</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-slate-300">Cognome *</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-slate-300">Nome *</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-slate-300 w-[140px]">Codice Fiscale *</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-slate-300 w-[130px]">Tipo Utente *</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row, idx) => (
                    <tr key={idx} className="border-t border-slate-700">
                      <td className="px-2 py-2">
                        <Input
                          type="email"
                          value={row.email}
                          onChange={(e) => updateRow(idx, 'email', e.target.value)}
                          placeholder="E-mail *"
                          className={`h-8 text-xs bg-slate-700 border-slate-600 text-white ${errors[`email_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-email-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          type="date"
                          value={row.startDate}
                          onChange={(e) => updateRow(idx, 'startDate', e.target.value)}
                          className="h-8 text-xs bg-slate-700 border-slate-600 text-white"
                          data-testid={`input-start-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          type="date"
                          value={row.endDate}
                          onChange={(e) => updateRow(idx, 'endDate', e.target.value)}
                          className="h-8 text-xs bg-slate-700 border-slate-600 text-white"
                          data-testid={`input-end-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <div className="flex items-center gap-1">
                          <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            className="h-6 w-6"
                            onClick={() => updateRow(idx, 'daysToAlert', Math.max(1, row.daysToAlert - 1))}
                          >
                            <Minus size={10} />
                          </Button>
                          <span className="w-6 text-center text-xs">{row.daysToAlert}</span>
                          <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            className="h-6 w-6"
                            onClick={() => updateRow(idx, 'daysToAlert', row.daysToAlert + 1)}
                          >
                            <Plus size={10} />
                          </Button>
                        </div>
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          value={row.lastName}
                          onChange={(e) => updateRow(idx, 'lastName', e.target.value)}
                          placeholder="Cognome"
                          className={`h-8 text-xs bg-slate-700 border-slate-600 text-white ${errors[`lastName_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-lastname-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          value={row.firstName}
                          onChange={(e) => updateRow(idx, 'firstName', e.target.value)}
                          placeholder="Nome"
                          className={`h-8 text-xs bg-slate-700 border-slate-600 text-white ${errors[`firstName_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-firstname-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          value={row.fiscalCode}
                          onChange={(e) => updateRow(idx, 'fiscalCode', e.target.value.toUpperCase())}
                          placeholder="CF"
                          maxLength={16}
                          className={`h-8 text-xs bg-slate-700 border-slate-600 text-white uppercase ${errors[`fiscalCode_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-cf-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Select value={row.userType} onValueChange={(val) => updateRow(idx, 'userType', val)}>
                          <SelectTrigger 
                            className={`h-8 text-xs bg-slate-700 border-slate-600 text-white ${errors[`userType_${idx}`] ? 'border-red-500' : ''}`}
                            data-testid={`select-type-${idx}`}
                          >
                            <SelectValue placeholder="Tipo" />
                          </SelectTrigger>
                          <SelectContent className="bg-slate-800 border-slate-600">
                            {USER_TYPES.map(type => (
                              <SelectItem 
                                key={type.value} 
                                value={type.value}
                                className="text-white hover:bg-slate-700 text-xs"
                              >
                                {type.label}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : (
            <div className="bg-slate-800/50 rounded-lg border border-slate-700 overflow-hidden">
              <div className="p-3 border-b border-slate-700 flex items-center justify-between">
                <h3 className="text-cyan-300 font-semibold flex items-center gap-2">
                  <Users size={18} />
                  Gestione Utenti Piattaforma
                </h3>
                <div className="flex items-center gap-2">
                  <Search size={16} className="text-slate-400" />
                  <Input
                    placeholder="Cerca..."
                    value={existingUserSearch}
                    onChange={(e) => setExistingUserSearch(e.target.value)}
                    className="h-8 w-48 text-xs bg-slate-700 border-slate-600 text-white"
                    data-testid="input-search-users"
                  />
                </div>
              </div>
              
              {!selectedCompanyId ? (
                <div className="p-8 text-center text-slate-400">
                  Seleziona prima un'azienda per vedere gli utenti
                </div>
              ) : companyUsers.length === 0 ? (
                <div className="p-8 text-center text-slate-400">
                  Nessun utente trovato per questa azienda
                </div>
              ) : (
                <div className="max-h-64 overflow-y-auto">
                  <table className="w-full text-sm">
                    <thead className="bg-slate-700/50 sticky top-0">
                      <tr>
                        <th className="px-3 py-2 text-center text-xs font-medium text-slate-300 w-10"></th>
                        <th className="px-3 py-2 text-left text-xs font-medium text-slate-300">Cognome</th>
                        <th className="px-3 py-2 text-left text-xs font-medium text-slate-300">Nome</th>
                        <th className="px-3 py-2 text-left text-xs font-medium text-slate-300">Codice Fiscale</th>
                        <th className="px-3 py-2 text-left text-xs font-medium text-slate-300">Funzione</th>
                        <th className="px-3 py-2 text-left text-xs font-medium text-slate-300">Email</th>
                        <th className="px-3 py-2 text-center text-xs font-medium text-slate-300">User ID</th>
                      </tr>
                    </thead>
                    <tbody>
                      {filteredExistingUsers.map((user, idx) => (
                        <tr 
                          key={user.id} 
                          className={`border-t border-slate-700 cursor-pointer ${selectedExistingUsers.has(user.id) ? 'bg-cyan-900/30' : 'hover:bg-slate-700/50'}`}
                          onClick={() => toggleExistingUser(user.id)}
                        >
                          <td className="px-3 py-2 text-center">
                            <Checkbox
                              checked={selectedExistingUsers.has(user.id)}
                              onCheckedChange={() => toggleExistingUser(user.id)}
                              className="border-slate-500"
                              data-testid={`checkbox-user-${user.id}`}
                            />
                          </td>
                          <td className="px-3 py-2 text-white">{user.lastName || '-'}</td>
                          <td className="px-3 py-2 text-white">{user.firstName || '-'}</td>
                          <td className="px-3 py-2 text-slate-300 font-mono text-xs">{user.fiscalCode || '-'}</td>
                          <td className="px-3 py-2 text-slate-300">{getRoleLabel(user.role)}</td>
                          <td className="px-3 py-2 text-slate-300">{user.email || '-'}</td>
                          <td className="px-3 py-2 text-center text-slate-400">{user.id}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
              
              {selectedExistingUsers.size > 0 && (
                <div className="p-3 border-t border-slate-700 text-sm text-cyan-300">
                  {selectedExistingUsers.size} utent{selectedExistingUsers.size === 1 ? 'e' : 'i'} selezionat{selectedExistingUsers.size === 1 ? 'o' : 'i'}
                </div>
              )}
              
              {errors.existingUsers && (
                <div className="p-3 text-red-400 text-sm">
                  Seleziona almeno un utente
                </div>
              )}
            </div>
          )}
        </div>

        <div className="bg-slate-900 border-t border-slate-700 px-6 py-4 flex justify-end gap-3">
          <Button
            type="button"
            variant="outline"
            onClick={onClose}
            className="border-slate-500 text-slate-300 hover:bg-slate-700"
            data-testid="btn-close-modal"
          >
            Chiudi
          </Button>
          <Button
            type="button"
            onClick={handleSubmit}
            className="bg-cyan-600 hover:bg-cyan-700 text-white gap-2"
            data-testid="btn-submit-codes"
          >
            <Send size={16} />
            Invia Codici
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
