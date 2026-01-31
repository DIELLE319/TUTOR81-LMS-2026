import { useState, useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { X, UserPlus, Users, Plus, Minus, Calendar, Bell, Send } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Company {
  id: number;
  business_name: string;
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

const USER_TYPES = [
  { value: 'dipendente', label: 'Dipendente' },
  { value: 'titolare', label: 'Titolare' },
  { value: 'collaboratore', label: 'Collaboratore' },
  { value: 'socio', label: 'Socio' },
  { value: 'altro', label: 'Altro' },
];

export default function SellCourseModal({ isOpen, onClose, course }: SellCourseModalProps) {
  const [selectedCompanyId, setSelectedCompanyId] = useState<string>('');
  const [userMode, setUserMode] = useState<'new' | 'existing'>('new');
  const [quantity, setQuantity] = useState(1);
  const [email, setEmail] = useState('');
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');
  const [daysToAlert, setDaysToAlert] = useState(15);
  
  const [lastName, setLastName] = useState('');
  const [firstName, setFirstName] = useState('');
  const [fiscalCode, setFiscalCode] = useState('');
  const [userType, setUserType] = useState('');

  const [errors, setErrors] = useState<Record<string, boolean>>({});

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies'],
  });

  useEffect(() => {
    if (isOpen) {
      const today = new Date();
      const endDateDefault = new Date();
      endDateDefault.setDate(today.getDate() + 90);
      
      setStartDate(today.toISOString().split('T')[0]);
      setEndDate(endDateDefault.toISOString().split('T')[0]);
      setQuantity(1);
      setDaysToAlert(15);
      setEmail('');
      setLastName('');
      setFirstName('');
      setFiscalCode('');
      setUserType('');
      setSelectedCompanyId('');
      setUserMode('new');
      setErrors({});
    }
  }, [isOpen]);

  const validateForm = () => {
    const newErrors: Record<string, boolean> = {};
    
    if (!selectedCompanyId) newErrors.company = true;
    if (!email) newErrors.email = true;
    if (!startDate) newErrors.startDate = true;
    if (!endDate) newErrors.endDate = true;
    if (!lastName) newErrors.lastName = true;
    if (!firstName) newErrors.firstName = true;
    if (!fiscalCode) newErrors.fiscalCode = true;
    if (!userType) newErrors.userType = true;
    
    setErrors(newErrors);
    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = () => {
    if (!validateForm()) return;
    
    console.log('Submitting:', {
      courseId: course?.id,
      companyId: selectedCompanyId,
      userMode,
      quantity,
      email,
      startDate,
      endDate,
      daysToAlert,
      corsista: { lastName, firstName, fiscalCode, userType }
    });
    
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
      <DialogContent className="max-w-4xl bg-gradient-to-br from-slate-900 to-slate-800 border-slate-700 text-white p-0 overflow-hidden">
        <DialogHeader className="bg-gradient-to-r from-cyan-700 to-cyan-600 px-6 py-4">
          <DialogTitle className="text-white text-lg font-bold flex items-center gap-2">
            <Send size={20} />
            Invia codici di accesso per il corso
          </DialogTitle>
          <p className="text-cyan-100 font-semibold mt-1">{formatCourseTitle(course.title)}</p>
        </DialogHeader>

        <div className="p-6 space-y-6">
          <div className={`border rounded-lg p-1 ${errors.company ? 'border-red-500' : 'border-slate-600'}`}>
            <Select value={selectedCompanyId} onValueChange={setSelectedCompanyId}>
              <SelectTrigger 
                className="bg-slate-800 border-0 text-white h-12"
                data-testid="select-company"
              >
                <SelectValue placeholder="--- Scegli il cliente ---" />
              </SelectTrigger>
              <SelectContent className="bg-slate-800 border-slate-600">
                {companies
                  .filter(c => !c.business_name?.toLowerCase().includes('tutor'))
                  .sort((a, b) => a.business_name.localeCompare(b.business_name))
                  .map(company => (
                    <SelectItem 
                      key={company.id} 
                      value={company.id.toString()}
                      className="text-white hover:bg-slate-700"
                    >
                      {company.business_name}
                    </SelectItem>
                  ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid grid-cols-2 gap-6">
            <div className="space-y-4">
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

              <div className="bg-slate-800/50 rounded-lg p-4 space-y-4 border border-slate-700">
                <div className="flex items-center gap-4">
                  <Label className="text-slate-300 text-sm w-28">Quantità corsi</Label>
                  <div className="flex items-center gap-2">
                    <Button
                      type="button"
                      size="icon"
                      variant="outline"
                      className="h-8 w-8 border-slate-500"
                      onClick={() => setQuantity(Math.max(1, quantity - 1))}
                      data-testid="btn-qty-minus"
                    >
                      <Minus size={14} />
                    </Button>
                    <span className="w-10 text-center font-bold text-lg">{quantity}</span>
                    <Button
                      type="button"
                      size="icon"
                      variant="outline"
                      className="h-8 w-8 border-slate-500"
                      onClick={() => setQuantity(quantity + 1)}
                      data-testid="btn-qty-plus"
                    >
                      <Plus size={14} />
                    </Button>
                  </div>
                </div>

                <div>
                  <Label className="text-slate-300 text-sm">Dove va spedito il codice di accesso?</Label>
                  <Input
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="E-mail destinatario *"
                    className={`mt-1 bg-slate-700 border-slate-600 text-white ${errors.email ? 'border-red-500' : ''}`}
                    data-testid="input-email"
                  />
                </div>

                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <Label className="text-slate-300 text-sm flex items-center gap-1">
                      <Calendar size={14} />
                      Data inizio corso
                    </Label>
                    <Input
                      type="date"
                      value={startDate}
                      onChange={(e) => setStartDate(e.target.value)}
                      className={`mt-1 bg-slate-700 border-slate-600 text-white ${errors.startDate ? 'border-red-500' : ''}`}
                      data-testid="input-start-date"
                    />
                    <span className="text-xs text-slate-500">(max 90 gg)</span>
                  </div>
                  <div>
                    <Label className="text-slate-300 text-sm flex items-center gap-1">
                      <Calendar size={14} />
                      Data fine corso
                    </Label>
                    <Input
                      type="date"
                      value={endDate}
                      onChange={(e) => setEndDate(e.target.value)}
                      className={`mt-1 bg-slate-700 border-slate-600 text-white ${errors.endDate ? 'border-red-500' : ''}`}
                      data-testid="input-end-date"
                    />
                    <span className="text-xs text-slate-500">(max 90 gg)</span>
                  </div>
                </div>

                <div className="flex items-center gap-4">
                  <Label className="text-slate-300 text-sm flex items-center gap-1">
                    <Bell size={14} />
                    Giorni preavviso
                  </Label>
                  <div className="flex items-center gap-2">
                    <Button
                      type="button"
                      size="icon"
                      variant="outline"
                      className="h-7 w-7 border-slate-500"
                      onClick={() => setDaysToAlert(Math.max(1, daysToAlert - 1))}
                      data-testid="btn-alert-minus"
                    >
                      <Minus size={12} />
                    </Button>
                    <span className="w-8 text-center font-semibold">{daysToAlert}</span>
                    <Button
                      type="button"
                      size="icon"
                      variant="outline"
                      className="h-7 w-7 border-slate-500"
                      onClick={() => setDaysToAlert(daysToAlert + 1)}
                      data-testid="btn-alert-plus"
                    >
                      <Plus size={12} />
                    </Button>
                    <Bell size={16} className="text-yellow-400 ml-1" />
                  </div>
                </div>
              </div>
            </div>

            <div className="space-y-4">
              <div className="bg-cyan-900/30 border border-cyan-700/50 rounded-lg p-4">
                <h3 className="text-cyan-300 font-semibold mb-3 flex items-center gap-2">
                  <UserPlus size={18} />
                  Dati del Corsista
                </h3>
                <div className="grid grid-cols-2 gap-3">
                  <div>
                    <Label className="text-slate-300 text-sm">Cognome *</Label>
                    <Input
                      value={lastName}
                      onChange={(e) => setLastName(e.target.value)}
                      placeholder="Cognome"
                      className={`mt-1 bg-slate-700 border-slate-600 text-white ${errors.lastName ? 'border-red-500' : ''}`}
                      data-testid="input-lastname"
                    />
                  </div>
                  <div>
                    <Label className="text-slate-300 text-sm">Nome *</Label>
                    <Input
                      value={firstName}
                      onChange={(e) => setFirstName(e.target.value)}
                      placeholder="Nome"
                      className={`mt-1 bg-slate-700 border-slate-600 text-white ${errors.firstName ? 'border-red-500' : ''}`}
                      data-testid="input-firstname"
                    />
                  </div>
                  <div className="col-span-2">
                    <Label className="text-slate-300 text-sm">Codice Fiscale *</Label>
                    <Input
                      value={fiscalCode}
                      onChange={(e) => setFiscalCode(e.target.value.toUpperCase())}
                      placeholder="Codice Fiscale"
                      maxLength={16}
                      className={`mt-1 bg-slate-700 border-slate-600 text-white uppercase ${errors.fiscalCode ? 'border-red-500' : ''}`}
                      data-testid="input-fiscal-code"
                    />
                  </div>
                  <div className="col-span-2">
                    <Label className="text-slate-300 text-sm">Tipo Utente *</Label>
                    <Select value={userType} onValueChange={setUserType}>
                      <SelectTrigger 
                        className={`mt-1 bg-slate-700 border-slate-600 text-white ${errors.userType ? 'border-red-500' : ''}`}
                        data-testid="select-user-type"
                      >
                        <SelectValue placeholder="Seleziona tipo utente" />
                      </SelectTrigger>
                      <SelectContent className="bg-slate-800 border-slate-600">
                        {USER_TYPES.map(type => (
                          <SelectItem 
                            key={type.value} 
                            value={type.value}
                            className="text-white hover:bg-slate-700"
                          >
                            {type.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                </div>
              </div>

              <div className="bg-slate-800/30 border border-slate-600 rounded-lg p-4">
                <h4 className="text-slate-400 text-sm font-semibold mb-2">ISTRUZIONI</h4>
                <p className="text-slate-400 text-xs leading-relaxed">
                  Non sei obbligato ad intestare la licenza, il destinatario potrà farlo direttamente inserendo i propri dati al primo accesso.
                </p>
              </div>
            </div>
          </div>
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
