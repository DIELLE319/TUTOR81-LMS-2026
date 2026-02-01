import { useState, useEffect, useMemo } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { UserPlus, Users, Plus, Minus, Send, Search, Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import { useToast } from '@/hooks/use-toast';
import { apiRequest, queryClient } from '@/lib/queryClient';
import { useAuth } from '@/hooks/use-auth';

interface Company {
  id: number;
  businessName: string;
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

const validateItalianFiscalCode = (cf: string): boolean => {
  // Accetta qualsiasi codice fiscale non vuoto (validazione permissiva)
  if (!cf || cf.trim().length === 0) return false;
  return true;
};

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
  const { toast } = useToast();
  const { user } = useAuth();
  const [selectedCompanyId, setSelectedCompanyId] = useState<string>('');
  const [companySearchOpen, setCompanySearchOpen] = useState(false);
  const [userMode, setUserMode] = useState<'new' | 'existing'>('new');
  const [rows, setRows] = useState<CorsistaRow[]>([createEmptyRow()]);
  const [selectedExistingUsers, setSelectedExistingUsers] = useState<Set<string>>(new Set());
  const [existingUserSearch, setExistingUserSearch] = useState('');
  const [errors, setErrors] = useState<Record<string, boolean>>({});
  const [successMessage, setSuccessMessage] = useState<string | null>(null);
  
  // Get tutorId from logged-in user (super admins see all companies)
  const isSuperAdmin = user?.role === 1000;
  const tutorId = isSuperAdmin ? null : user?.idcompany;

  const enrollMutation = useMutation({
    mutationFn: async (data: { courseId: number; companyId: number; corsisti: CorsistaRow[] }) => {
      const response = await apiRequest('POST', '/api/enrollments/activate', data);
      return response.json();
    },
    onSuccess: (data) => {
      setSuccessMessage(`${data.created || 0} iscrizioni create con successo!\nLe email sono state inviate.`);
      queryClient.invalidateQueries({ queryKey: ['/api/enrollments'] });
    },
    onError: (error: Error) => {
      toast({
        title: "Errore",
        description: error.message || "Si è verificato un errore nell'invio dei codici",
        variant: "destructive",
      });
    },
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies', { tutorId }],
    queryFn: async () => {
      const url = tutorId ? `/api/companies?tutorId=${tutorId}` : '/api/companies';
      const res = await fetch(url);
      return res.json();
    },
    enabled: isOpen,
  });

  const { data: companyUsers = [] } = useQuery<CompanyUser[]>({
    queryKey: ['/api/companies', selectedCompanyId, 'users'],
    enabled: !!selectedCompanyId && userMode === 'existing',
  });

  const filteredCompanies = useMemo(() => {
    return companies
      .filter(c => c.businessName)
      .sort((a, b) => (a.businessName || '').localeCompare(b.businessName || ''));
  }, [companies]);

  const selectedCompanyName = useMemo(() => {
    const company = companies.find(c => c.id.toString() === selectedCompanyId);
    return company?.businessName || '';
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
      setSuccessMessage(null);
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
    const cfErrors: string[] = [];
    
    if (!selectedCompanyId) {
      newErrors.company = true;
      toast({
        title: "Campo obbligatorio",
        description: "Seleziona un'azienda cliente",
        variant: "destructive",
      });
    }
    
    if (userMode === 'new') {
      const seenCFs = new Set<string>();
      const missingFields: string[] = [];
      
      rows.forEach((row, idx) => {
        const rowNum = idx + 1;
        
        if (!row.email) {
          newErrors[`email_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Email`);
        }
        if (!row.startDate) {
          newErrors[`startDate_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Data inizio`);
        }
        if (!row.endDate) {
          newErrors[`endDate_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Data fine`);
        }
        if (!row.lastName) {
          newErrors[`lastName_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Cognome`);
        }
        if (!row.firstName) {
          newErrors[`firstName_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Nome`);
        }
        if (!row.userType) {
          newErrors[`userType_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Tipo Utente`);
        }
        
        // Validate fiscal code
        if (!row.fiscalCode) {
          newErrors[`fiscalCode_${idx}`] = true;
          missingFields.push(`Riga ${rowNum}: Codice Fiscale`);
        } else {
          const cfUpper = row.fiscalCode.toUpperCase();
          
          // Check format and checksum
          if (!validateItalianFiscalCode(cfUpper)) {
            newErrors[`fiscalCode_${idx}`] = true;
            cfErrors.push(`Riga ${rowNum}: Codice Fiscale non valido`);
          }
          
          // Check for duplicates in the same form
          if (seenCFs.has(cfUpper)) {
            newErrors[`fiscalCode_${idx}`] = true;
            cfErrors.push(`Riga ${rowNum}: Codice Fiscale duplicato`);
          }
          seenCFs.add(cfUpper);
        }
      });
      
      if (missingFields.length > 0) {
        toast({
          title: "Campi obbligatori mancanti",
          description: missingFields.slice(0, 5).join(", ") + (missingFields.length > 5 ? "..." : ""),
          variant: "destructive",
        });
      } else if (cfErrors.length > 0) {
        toast({
          title: "Errore Codice Fiscale",
          description: cfErrors.join(", "),
          variant: "destructive",
        });
      }
    } else {
      if (selectedExistingUsers.size === 0) newErrors.existingUsers = true;
    }
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = () => {
    if (!validateForm()) return;
    
    if (!course) return;
    
    if (userMode === 'new') {
      enrollMutation.mutate({
        courseId: course.id,
        companyId: parseInt(selectedCompanyId),
        corsisti: rows
      });
    } else {
      // Per utenti esistenti, prepara i dati in formato corsista
      const existingUserData = companyUsers
        .filter(u => selectedExistingUsers.has(u.id))
        .map(u => ({
          email: u.email || '',
          startDate: new Date().toISOString().split('T')[0],
          endDate: new Date(Date.now() + 90 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
          daysToAlert: 15,
          lastName: u.lastName || '',
          firstName: u.firstName || '',
          fiscalCode: u.fiscalCode || '',
          userType: 'lavoratore',
        }));
      
      enrollMutation.mutate({
        courseId: course.id,
        companyId: parseInt(selectedCompanyId),
        corsisti: existingUserData
      });
    }
  };

  const formatCourseTitle = (title: string) => {
    const dashIndex = title.indexOf(' - ');
    if (dashIndex > 0 && dashIndex < 30) {
      return title.substring(dashIndex + 3).trim();
    }
    return title.replace(/^EL\d*[a-zA-Z]*\s*-\s*/i, '').trim();
  };

  if (!course) return null;

  if (successMessage) {
    return (
      <Dialog open={isOpen} onOpenChange={onClose}>
        <DialogContent className="max-w-lg bg-white border-yellow-500 border-4 text-black p-0 overflow-hidden">
          <div className="flex flex-col items-center justify-center p-12 text-center">
            <div className="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mb-6">
              <Check size={48} className="text-white" />
            </div>
            <h2 className="text-3xl font-bold text-green-600 mb-4">Invio Completato!</h2>
            <p className="text-xl text-gray-700 whitespace-pre-line mb-8">{successMessage}</p>
            <Button 
              onClick={onClose}
              className="bg-yellow-500 hover:bg-yellow-600 text-black font-bold px-8 py-3 text-lg"
              data-testid="button-close-success"
            >
              Chiudi
            </Button>
          </div>
        </DialogContent>
      </Dialog>
    );
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-[95vw] w-[1400px] bg-white border-yellow-500 border-2 text-black p-0 overflow-hidden max-h-[95vh]">
        <form noValidate onSubmit={(e) => e.preventDefault()}>
        <DialogHeader className="bg-yellow-500 px-6 py-4">
          <DialogTitle className="text-black text-lg font-bold flex items-center gap-2">
            <Send size={20} />
            Invia codici di accesso per il corso
          </DialogTitle>
          <p className="text-black font-semibold mt-1">{formatCourseTitle(course.title)}</p>
        </DialogHeader>

        <div className="p-6 space-y-4 overflow-y-auto max-h-[calc(95vh-180px)]">
          <div className={`border rounded-lg ${errors.company ? 'border-red-500' : 'border-gray-300'}`}>
            <Popover open={companySearchOpen} onOpenChange={setCompanySearchOpen}>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  aria-expanded={companySearchOpen}
                  className="w-full justify-between h-12 bg-white border border-gray-300 text-black hover:bg-yellow-50"
                  data-testid="select-company"
                >
                  {selectedCompanyName || "--- Scegli il cliente ---"}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-[500px] p-0 bg-white border-gray-300" align="start">
                <Command className="bg-white">
                  <CommandInput 
                    placeholder="Cerca azienda..." 
                    className="text-black"
                  />
                  <CommandList className="max-h-60">
                    <CommandEmpty className="text-gray-500 py-4 text-center">Nessuna azienda trovata</CommandEmpty>
                    <CommandGroup>
                      {filteredCompanies.map(company => (
                        <CommandItem
                          key={company.id}
                          value={company.businessName}
                          onSelect={() => {
                            setSelectedCompanyId(company.id.toString());
                            setCompanySearchOpen(false);
                          }}
                          className="text-black hover:bg-yellow-100 cursor-pointer"
                        >
                          <Check
                            className={cn(
                              "mr-2 h-4 w-4",
                              selectedCompanyId === company.id.toString() ? "opacity-100" : "opacity-0"
                            )}
                          />
                          {company.businessName}
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
              <span className="text-black text-sm font-semibold">Chi deve svolgere il corso</span>
              <div className="flex gap-2">
                <Button
                  type="button"
                  variant={userMode === 'new' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setUserMode('new')}
                  className={userMode === 'new' ? 'bg-yellow-500 text-black hover:bg-yellow-400' : 'border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black'}
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
                  className={userMode === 'existing' ? 'bg-yellow-500 text-black hover:bg-yellow-400' : 'border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black'}
                  data-testid="btn-existing-user"
                >
                  <Users size={16} className="mr-1" />
                  Utente esistente
                </Button>
              </div>
            </div>

            {userMode === 'new' && (
              <div className="flex items-center gap-3">
                <Label className="text-black text-sm font-semibold">Quantità corsi</Label>
                <div className="flex items-center gap-2">
                  <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    className="h-8 w-8 border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black"
                    onClick={removeRow}
                    disabled={rows.length <= 1}
                    data-testid="btn-qty-minus"
                  >
                    <Minus size={14} />
                  </Button>
                  <span className="w-10 text-center font-bold text-lg text-black">{rows.length}</span>
                  <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    className="h-8 w-8 border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black"
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
            <div className="bg-gray-50 rounded-lg border border-yellow-500 overflow-hidden">
              <table className="w-full text-sm">
                <thead className="bg-yellow-500">
                  <tr>
                    <th className="px-2 py-2 text-left text-xs font-medium text-black w-[180px]">Email destinatario *</th>
                    <th className="px-2 py-2 text-center text-xs font-medium text-black w-[100px]">Data inizio</th>
                    <th className="px-2 py-2 text-center text-xs font-medium text-black w-[100px]">Fine corso</th>
                    <th className="px-2 py-2 text-center text-xs font-medium text-black w-[70px]">Alert gg</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-black">Cognome *</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-black">Nome *</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-black w-[180px]">Codice Fiscale *</th>
                    <th className="px-2 py-2 text-left text-xs font-medium text-black w-[160px]">Tipo Utente *</th>
                  </tr>
                </thead>
                <tbody>
                  {rows.map((row, idx) => (
                    <tr key={idx} className="border-t border-yellow-300">
                      <td className="px-2 py-2">
                        <Input
                          type="text"
                          value={row.email}
                          onChange={(e) => updateRow(idx, 'email', e.target.value)}
                          placeholder="E-mail *"
                          className={`h-8 text-xs bg-white border-gray-300 text-black ${errors[`email_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-email-${idx}`}
                        />
                      </td>
                      <td className="px-1 py-2">
                        <Input
                          type="date"
                          value={row.startDate}
                          onChange={(e) => updateRow(idx, 'startDate', e.target.value)}
                          className="h-8 text-xs bg-white border-gray-300 text-black w-[120px]"
                          data-testid={`input-start-${idx}`}
                        />
                      </td>
                      <td className="px-1 py-2">
                        <Input
                          type="date"
                          value={row.endDate}
                          onChange={(e) => updateRow(idx, 'endDate', e.target.value)}
                          className="h-8 text-xs bg-white border-gray-300 text-black w-[120px]"
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
                          className={`h-8 text-xs bg-white border-gray-300 text-black ${errors[`lastName_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-lastname-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          value={row.firstName}
                          onChange={(e) => updateRow(idx, 'firstName', e.target.value)}
                          placeholder="Nome"
                          className={`h-8 text-xs bg-white border-gray-300 text-black ${errors[`firstName_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-firstname-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Input
                          value={row.fiscalCode}
                          onChange={(e) => updateRow(idx, 'fiscalCode', e.target.value.toUpperCase())}
                          placeholder="Codice Fiscale"
                          maxLength={16}
                          className={`h-8 text-sm bg-white border-gray-300 text-black uppercase min-w-[180px] font-mono ${errors[`fiscalCode_${idx}`] ? 'border-red-500' : ''}`}
                          data-testid={`input-cf-${idx}`}
                        />
                      </td>
                      <td className="px-2 py-2">
                        <Select value={row.userType} onValueChange={(val) => updateRow(idx, 'userType', val)}>
                          <SelectTrigger 
                            className={`h-9 text-sm bg-white border-gray-300 text-black ${errors[`userType_${idx}`] ? 'border-red-500' : ''}`}
                            data-testid={`select-type-${idx}`}
                          >
                            <SelectValue placeholder="Seleziona tipo..." />
                          </SelectTrigger>
                          <SelectContent className="bg-white border-gray-300">
                            {USER_TYPES.map(type => (
                              <SelectItem 
                                key={type.value} 
                                value={type.value}
                                className="text-black hover:bg-yellow-100 text-sm"
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
                    className="h-8 w-48 text-xs bg-white border-gray-300 text-black"
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
        </form>
      </DialogContent>
    </Dialog>
  );
}
