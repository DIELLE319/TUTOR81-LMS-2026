import { useState, useEffect } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { queryClient, apiRequest } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';

const PROVINCE = [
  'AGRIGENTO', 'ALESSANDRIA', 'ANCONA', 'AOSTA', 'AREZZO', 'ASCOLI PICENO', 'ASTI', 'AVELLINO',
  'BARI', 'BARLETTA-ANDRIA-TRANI', 'BELLUNO', 'BENEVENTO', 'BERGAMO', 'BIELLA', 'BOLOGNA', 'BOLZANO',
  'BRESCIA', 'BRINDISI', 'CAGLIARI', 'CALTANISSETTA', 'CAMPOBASSO', 'CASERTA', 'CATANIA', 'CATANZARO',
  'CHIETI', 'COMO', 'COSENZA', 'CREMONA', 'CROTONE', 'CUNEO', 'ENNA', 'FERMO', 'FERRARA', 'FIRENZE',
  'FOGGIA', 'FORLÌ-CESENA', 'FROSINONE', 'GENOVA', 'GORIZIA', 'GROSSETO', 'IMPERIA', 'ISERNIA',
  'LA SPEZIA', 'LATINA', 'LECCE', 'LECCO', 'LIVORNO', 'LODI', 'LUCCA', 'MACERATA', 'MANTOVA',
  'MASSA-CARRARA', 'MATERA', 'MESSINA', 'MILANO', 'MODENA', 'MONZA E BRIANZA', 'NAPOLI', 'NOVARA',
  'NUORO', 'ORISTANO', 'PADOVA', 'PALERMO', 'PARMA', 'PAVIA', 'PERUGIA', 'PESARO E URBINO', 'PESCARA',
  'PIACENZA', 'PISA', 'PISTOIA', 'PORDENONE', 'POTENZA', 'PRATO', 'RAGUSA', 'RAVENNA', 'REGGIO CALABRIA',
  'REGGIO EMILIA', 'RIETI', 'RIMINI', 'ROMA', 'ROVIGO', 'SALERNO', 'SASSARI', 'SAVONA', 'SIENA',
  'SIRACUSA', 'SONDRIO', 'SUD SARDEGNA', 'TARANTO', 'TERAMO', 'TERNI', 'TORINO', 'TRAPANI', 'TRENTO',
  'TREVISO', 'TRIESTE', 'UDINE', 'VARESE', 'VENEZIA', 'VERBANO-CUSIO-OSSOLA', 'VERCELLI', 'VERONA',
  'VIBO VALENTIA', 'VICENZA', 'VITERBO'
];

const SUBSCRIPTION_OPTIONS = [
  { value: 'NESSUNO', label: 'Nessun abbonamento', discount: 0, price: 0, maxAdmins: 1, ecommerce: false, customCourses: false },
  { value: 'CONSULENTI 500', label: 'Consulenti 500', discount: 60, price: 500, maxAdmins: 3, ecommerce: false, customCourses: false },
  { value: 'CONSULENTI 1500', label: 'Consulenti 1500', discount: 70, price: 1500, maxAdmins: 5, ecommerce: true, customCourses: false },
  { value: 'ENTI AUTORIZZATI 1500', label: 'Enti Autorizzati 1500', discount: 70, price: 1500, maxAdmins: 10, ecommerce: true, customCourses: false },
];

interface TutorModalProps {
  open: boolean;
  onClose: () => void;
  tutor?: any;
}

export function TutorModal({ open, onClose, tutor }: TutorModalProps) {
  const { toast } = useToast();
  const isEdit = !!tutor;

  const [form, setForm] = useState({
    businessName: '',
    vatNumber: '',
    city: '',
    address: '',
    province: '',
    phone: '',
    email: '',
    hasRegionalAuth: false,
    regionalAuthorization: '',
    authorizedTrainer: '',
    atecoCode: '',
    subscriptionType: 'NESSUNO',
    subscriptionStart: '',
  });

  useEffect(() => {
    if (tutor) {
      setForm({
        businessName: tutor.businessName || '',
        vatNumber: tutor.vatNumber || '',
        city: tutor.city || '',
        address: tutor.address || '',
        province: tutor.province || '',
        phone: tutor.phone || '',
        email: tutor.email || '',
        hasRegionalAuth: !!tutor.regionalAuthorization,
        regionalAuthorization: tutor.regionalAuthorization || '',
        authorizedTrainer: tutor.notes || '',
        atecoCode: '',
        subscriptionType: tutor.subscriptionType || 'NESSUNO',
        subscriptionStart: tutor.subscriptionStart || '',
      });
    } else {
      setForm({
        businessName: '',
        vatNumber: '',
        city: '',
        address: '',
        province: '',
        phone: '',
        email: '',
        hasRegionalAuth: false,
        regionalAuthorization: '',
        authorizedTrainer: '',
        atecoCode: '',
        subscriptionType: 'NESSUNO',
        subscriptionStart: '',
      });
    }
  }, [tutor, open]);

  const saveMutation = useMutation({
    mutationFn: (data: any) => {
      if (isEdit) {
        return apiRequest('PUT', `/api/tutors/${tutor.id}`, data);
      } else {
        return apiRequest('POST', '/api/tutors', data);
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      toast({ title: isEdit ? 'Ente aggiornato' : 'Ente creato' });
      onClose();
    },
    onError: () => {
      toast({ title: 'Errore', description: 'Impossibile salvare.', variant: 'destructive' });
    }
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const subscriptionOption = SUBSCRIPTION_OPTIONS.find(o => o.value === form.subscriptionType);
    saveMutation.mutate({
      businessName: form.businessName,
      vatNumber: form.vatNumber,
      city: form.city,
      address: form.address,
      province: form.province,
      phone: form.phone,
      email: form.email,
      regionalAuthorization: form.hasRegionalAuth ? form.regionalAuthorization : null,
      notes: form.authorizedTrainer,
      subscriptionType: form.subscriptionType,
      discountPercentage: subscriptionOption?.discount || 0,
      subscriptionStart: form.subscriptionStart || null,
    });
  };

  const selectedPlan = SUBSCRIPTION_OPTIONS.find(o => o.value === form.subscriptionType);

  return (
    <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
      <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto bg-zinc-900 border-zinc-800">
        <DialogHeader>
          <DialogTitle className="text-white text-xl">
            {isEdit ? 'Modifica Ente Formativo' : 'Nuovo Ente Formativo'}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit} className="space-y-4 mt-4">
          <div className="grid grid-cols-2 gap-4">
            <div className="col-span-2">
              <Label className="text-gray-400">Ragione Sociale *</Label>
              <Input
                value={form.businessName}
                onChange={(e) => setForm({ ...form, businessName: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1"
                required
                data-testid="input-business-name"
              />
            </div>

            <div>
              <Label className="text-gray-400">P.IVA *</Label>
              <Input
                value={form.vatNumber}
                onChange={(e) => setForm({ ...form, vatNumber: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1"
                required
                data-testid="input-vat"
              />
            </div>

            <div>
              <Label className="text-gray-400">Località *</Label>
              <Input
                value={form.city}
                onChange={(e) => setForm({ ...form, city: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1"
                required
                data-testid="input-city"
              />
            </div>

            <div className="col-span-2">
              <Label className="text-gray-400">Indirizzo *</Label>
              <Input
                value={form.address}
                onChange={(e) => setForm({ ...form, address: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1"
                required
                data-testid="input-address"
              />
            </div>

            <div>
              <Label className="text-gray-400">Provincia *</Label>
              <Select value={form.province} onValueChange={(v) => setForm({ ...form, province: v })}>
                <SelectTrigger className="bg-zinc-800 border-zinc-700 text-white mt-1" data-testid="select-province">
                  <SelectValue placeholder="Seleziona provincia" />
                </SelectTrigger>
                <SelectContent>
                  {PROVINCE.map(p => (
                    <SelectItem key={p} value={p}>{p}</SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label className="text-gray-400">Telefono</Label>
              <Input
                value={form.phone}
                onChange={(e) => setForm({ ...form, phone: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1"
                data-testid="input-phone"
              />
            </div>

            <div className="col-span-2">
              <Label className="text-gray-400">E-mail *</Label>
              <Input
                type="email"
                value={form.email}
                onChange={(e) => setForm({ ...form, email: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1"
                required
                data-testid="input-email"
              />
            </div>
          </div>

          <div className="border-t border-zinc-800 pt-4 mt-4">
            <div className="flex items-center gap-4 mb-3">
              <Label className="text-gray-400">Autorizzazione regionale</Label>
              <div className="flex items-center gap-4">
                <label className="flex items-center gap-2 text-gray-300">
                  <input
                    type="radio"
                    checked={!form.hasRegionalAuth}
                    onChange={() => setForm({ ...form, hasRegionalAuth: false })}
                    className="accent-yellow-500"
                  />
                  No
                </label>
                <label className="flex items-center gap-2 text-gray-300">
                  <input
                    type="radio"
                    checked={form.hasRegionalAuth}
                    onChange={() => setForm({ ...form, hasRegionalAuth: true })}
                    className="accent-yellow-500"
                  />
                  Sì
                </label>
              </div>
              {form.hasRegionalAuth && (
                <Input
                  value={form.regionalAuthorization}
                  onChange={(e) => setForm({ ...form, regionalAuthorization: e.target.value })}
                  placeholder="Numero autorizzazione"
                  className="bg-zinc-800 border-zinc-700 text-white flex-1"
                  data-testid="input-auth-number"
                />
              )}
            </div>

            <div>
              <Label className="text-gray-400">Soggetto formatore autorizzato</Label>
              <Textarea
                value={form.authorizedTrainer}
                onChange={(e) => setForm({ ...form, authorizedTrainer: e.target.value })}
                className="bg-zinc-800 border-zinc-700 text-white mt-1 min-h-20"
                data-testid="input-authorized-trainer"
              />
            </div>
          </div>

          <div className="border-t border-zinc-800 pt-4 mt-4">
            <h3 className="text-lg font-bold text-yellow-500 mb-4">PIANO DI ABBONAMENTO</h3>
            
            <div className="grid grid-cols-2 gap-4 mb-4">
              <div>
                <Label className="text-gray-400">Tipo abbonamento</Label>
                <Select value={form.subscriptionType} onValueChange={(v) => setForm({ ...form, subscriptionType: v })}>
                  <SelectTrigger className="bg-zinc-800 border-zinc-700 text-white mt-1" data-testid="select-subscription">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {SUBSCRIPTION_OPTIONS.map(o => (
                      <SelectItem key={o.value} value={o.value}>{o.label}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>

              <div>
                <Label className="text-gray-400">Data inizio abbonamento</Label>
                <Input
                  type="date"
                  value={form.subscriptionStart}
                  onChange={(e) => setForm({ ...form, subscriptionStart: e.target.value })}
                  className="bg-zinc-800 border-zinc-700 text-white mt-1"
                  data-testid="input-subscription-start"
                />
              </div>
            </div>

            {selectedPlan && selectedPlan.value !== 'NESSUNO' && (
              <div className="bg-zinc-800 rounded-lg p-4">
                <table className="w-full text-sm">
                  <tbody>
                    <tr className="border-b border-zinc-700">
                      <td className="py-2 text-gray-400">Piano</td>
                      <td className="py-2 text-white text-right font-medium">{selectedPlan.label}</td>
                    </tr>
                    <tr className="border-b border-zinc-700">
                      <td className="py-2 text-gray-400">Sconto su listino (%)</td>
                      <td className="py-2 text-yellow-500 text-right font-medium">{selectedPlan.discount}%</td>
                    </tr>
                    <tr className="border-b border-zinc-700">
                      <td className="py-2 text-gray-400">Corsi personalizzati</td>
                      <td className="py-2 text-white text-right">{selectedPlan.customCourses ? 'SI' : 'NO'}</td>
                    </tr>
                    <tr className="border-b border-zinc-700">
                      <td className="py-2 text-gray-400">Sito Ecommerce</td>
                      <td className="py-2 text-white text-right">{selectedPlan.ecommerce ? 'SI' : 'NO'}</td>
                    </tr>
                    <tr className="border-b border-zinc-700">
                      <td className="py-2 text-gray-400">Max numero amministratori</td>
                      <td className="py-2 text-white text-right">{selectedPlan.maxAdmins}</td>
                    </tr>
                    <tr>
                      <td className="py-2 text-gray-400">Prezzo</td>
                      <td className="py-2 text-green-500 text-right font-bold">Euro {selectedPlan.price}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            )}
          </div>

          <div className="flex justify-end gap-3 pt-4 border-t border-zinc-800">
            <Button type="button" variant="outline" onClick={onClose} data-testid="button-cancel">
              Annulla
            </Button>
            <Button type="submit" className="bg-yellow-500 hover:bg-yellow-600 text-black" disabled={saveMutation.isPending} data-testid="button-save">
              {saveMutation.isPending ? 'Salvataggio...' : (isEdit ? 'Salva modifiche' : 'Crea ente')}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}
