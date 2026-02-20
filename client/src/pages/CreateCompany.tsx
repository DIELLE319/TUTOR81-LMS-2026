import { useState } from 'react';
import { useLocation } from 'wouter';
import { useMutation } from '@tanstack/react-query';
import { Building, ArrowLeft, Save, User, Mail, Phone, MapPin, FileText } from 'lucide-react';
import { apiRequest, queryClient } from '@/lib/queryClient';
import { useToast } from '@/hooks/use-toast';

export default function CreateCompany() {
  const [, navigate] = useLocation();
  const { toast } = useToast();
  
  const searchParams = new URLSearchParams(window.location.search);
  const companyType = searchParams.get('type') || 'client';
  const isTutor = companyType === 'tutor';

  const [formData, setFormData] = useState({
    businessName: '',
    address: '',
    city: '',
    cap: '',
    province: '',
    vatNumber: '',
    fiscalCode: '',
    phone: '',
    email: '',
    pec: '',
    website: '',
    regionalAuthorization: '',
    licenseType: '',
    contactPerson: '',
    notes: '',
  });

  const createMutation = useMutation({
    mutationFn: async (data: typeof formData & { isTutor: boolean }) => {
      return apiRequest('POST', '/api/companies', data);
    },
    onSuccess: () => {
      toast({ title: 'Azienda creata con successo' });
      queryClient.invalidateQueries({ queryKey: ['/api/tutors'] });
      queryClient.invalidateQueries({ queryKey: ['/api/clients'] });
      navigate(isTutor ? '/tutors' : '/clients');
    },
    onError: (error: Error) => {
      toast({ title: 'Errore', description: error.message, variant: 'destructive' });
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    createMutation.mutate({ ...formData, isTutor });
  };

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    setFormData(prev => ({ ...prev, [e.target.name]: e.target.value }));
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="max-w-4xl mx-auto">
        
        <button 
          onClick={() => navigate(isTutor ? '/tutors' : '/clients')}
          className="flex items-center gap-2 text-gray-400 hover:text-white mb-6 transition-colors"
          data-testid="button-back"
        >
          <ArrowLeft size={18} />
          Indietro
        </button>

        <div className="flex items-center gap-4 mb-8">
          <div className={`w-14 h-14 rounded-xl flex items-center justify-center ${isTutor ? 'bg-yellow-500/20' : 'bg-blue-500/20'}`}>
            <Building size={28} className={isTutor ? 'text-yellow-500' : 'text-blue-500'} />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-white" data-testid="text-create-title">
              {isTutor ? 'Nuovo Ente Formativo' : 'Nuova Azienda Cliente'}
            </h1>
            <p className="text-gray-500">Compila i dati dell'azienda</p>
          </div>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-6 space-y-6">
            
            <div className="border-b border-gray-800 pb-4 mb-4">
              <h2 className="text-lg font-bold text-white flex items-center gap-2">
                <Building size={18} className="text-gray-500" />
                Dati Azienda
              </h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="md:col-span-2">
                <label className="block text-sm text-gray-400 mb-1">Ragione Sociale *</label>
                <input
                  type="text"
                  name="businessName"
                  value={formData.businessName}
                  onChange={handleChange}
                  required
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-business-name"
                />
              </div>

              <div>
                <label className="block text-sm text-gray-400 mb-1">P.IVA</label>
                <input
                  type="text"
                  name="vatNumber"
                  value={formData.vatNumber}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-vat"
                />
              </div>

              <div>
                <label className="block text-sm text-gray-400 mb-1">Codice Fiscale</label>
                <input
                  type="text"
                  name="fiscalCode"
                  value={formData.fiscalCode}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-fiscal-code"
                />
              </div>
            </div>

            <div className="border-b border-gray-800 pb-4 mb-4 mt-8">
              <h2 className="text-lg font-bold text-white flex items-center gap-2">
                <MapPin size={18} className="text-gray-500" />
                Indirizzo
              </h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="md:col-span-2">
                <label className="block text-sm text-gray-400 mb-1">Indirizzo</label>
                <input
                  type="text"
                  name="address"
                  value={formData.address}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-address"
                />
              </div>

              <div>
                <label className="block text-sm text-gray-400 mb-1">Città</label>
                <input
                  type="text"
                  name="city"
                  value={formData.city}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-city"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm text-gray-400 mb-1">CAP</label>
                  <input
                    type="text"
                    name="cap"
                    value={formData.cap}
                    onChange={handleChange}
                    className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                    data-testid="input-cap"
                  />
                </div>
                <div>
                  <label className="block text-sm text-gray-400 mb-1">Provincia</label>
                  <input
                    type="text"
                    name="province"
                    value={formData.province}
                    onChange={handleChange}
                    className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                    data-testid="input-province"
                  />
                </div>
              </div>
            </div>

            <div className="border-b border-gray-800 pb-4 mb-4 mt-8">
              <h2 className="text-lg font-bold text-white flex items-center gap-2">
                <Mail size={18} className="text-gray-500" />
                Contatti
              </h2>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm text-gray-400 mb-1">Email</label>
                <input
                  type="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-email"
                />
              </div>

              <div>
                <label className="block text-sm text-gray-400 mb-1">PEC</label>
                <input
                  type="email"
                  name="pec"
                  value={formData.pec}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-pec"
                />
              </div>

              <div>
                <label className="block text-sm text-gray-400 mb-1">Telefono</label>
                <input
                  type="tel"
                  name="phone"
                  value={formData.phone}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-phone"
                />
              </div>

              <div>
                <label className="block text-sm text-gray-400 mb-1">Sito Web</label>
                <input
                  type="url"
                  name="website"
                  value={formData.website}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-website"
                />
              </div>

              <div className="md:col-span-2">
                <label className="block text-sm text-gray-400 mb-1">Referente</label>
                <input
                  type="text"
                  name="contactPerson"
                  value={formData.contactPerson}
                  onChange={handleChange}
                  className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                  data-testid="input-contact-person"
                />
              </div>
            </div>

            {isTutor && (
              <>
                <div className="border-b border-gray-800 pb-4 mb-4 mt-8">
                  <h2 className="text-lg font-bold text-white flex items-center gap-2">
                    <FileText size={18} className="text-gray-500" />
                    Autorizzazioni
                  </h2>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm text-gray-400 mb-1">Autorizzazione Regionale</label>
                    <input
                      type="text"
                      name="regionalAuthorization"
                      value={formData.regionalAuthorization}
                      onChange={handleChange}
                      className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                      data-testid="input-auth"
                    />
                  </div>

                  <div>
                    <label className="block text-sm text-gray-400 mb-1">Tipo Licenza</label>
                    <select
                      name="licenseType"
                      value={formData.licenseType}
                      onChange={handleChange}
                      className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none"
                      data-testid="select-license-type"
                    >
                      <option value="">Seleziona...</option>
                      <option value="TUTOR BASIC">Tutor Basic</option>
                      <option value="TUTOR PRO">Tutor Pro</option>
                      <option value="TUTOR ENTERPRISE">Tutor Enterprise</option>
                    </select>
                  </div>
                </div>
              </>
            )}

            <div className="mt-8">
              <label className="block text-sm text-gray-400 mb-1">Note</label>
              <textarea
                name="notes"
                value={formData.notes}
                onChange={handleChange}
                rows={3}
                className="w-full bg-black border border-gray-700 rounded-lg py-2.5 px-4 text-white focus:border-yellow-500 focus:outline-none resize-none"
                data-testid="textarea-notes"
              />
            </div>

            <div className="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-800">
              <button
                type="button"
                onClick={() => navigate(isTutor ? '/tutors' : '/clients')}
                className="px-6 py-2.5 text-gray-400 hover:text-white transition-colors"
              >
                Annulla
              </button>
              <button
                type="submit"
                disabled={createMutation.isPending}
                className="bg-yellow-500 hover:bg-yellow-400 text-black font-bold px-6 py-2.5 rounded-lg flex items-center gap-2 transition-colors disabled:opacity-50"
                data-testid="button-save"
              >
                {createMutation.isPending ? (
                  <div className="w-4 h-4 border-2 border-black border-t-transparent rounded-full animate-spin" />
                ) : (
                  <Save size={18} />
                )}
                Salva
              </button>
            </div>

          </div>
        </form>
      </div>
    </div>
  );
}
