import { useState, useRef } from 'react';
import { useQuery } from '@tanstack/react-query';
import { FileText, Printer, Building, Calendar, Euro } from 'lucide-react';
import { Button } from '@/components/ui/button';

type Tutor = {
  id: number;
  businessName: string;
};

type InvoiceData = {
  tutor: {
    id: number;
    businessName: string;
    address: string | null;
    city: string | null;
    vatNumber: string | null;
    email: string | null;
  };
  period: {
    month: number;
    year: number;
    monthName: string;
    label: string;
  };
  courses: Array<{
    courseId: number;
    qty: number;
    unitPrice: number;
    total: number;
  }>;
  totalSales: number;
  grandTotal: number;
  generatedAt: string;
};

export default function Invoicing() {
  const currentDate = new Date();
  const [selectedTutor, setSelectedTutor] = useState<number | null>(null);
  const [selectedMonth, setSelectedMonth] = useState(currentDate.getMonth()); // 0-11 for UI, will be 1-12 for API
  const [selectedYear, setSelectedYear] = useState(currentDate.getFullYear());
  const printRef = useRef<HTMLDivElement>(null);

  const { data: tutors = [] } = useQuery<Tutor[]>({
    queryKey: ['/api/tutors'],
    select: (data: any[]) => data.map((t: any) => ({ id: t.id, businessName: t.businessName })),
  });

  const { data: invoiceData, isLoading: isLoadingInvoice, refetch } = useQuery<InvoiceData>({
    queryKey: ['/api/invoice', selectedTutor, selectedMonth + 1, selectedYear],
    enabled: selectedTutor !== null,
  });

  const months = [
    'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
    'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
  ];

  const years = Array.from({ length: 5 }, (_, i) => currentDate.getFullYear() - i);

  const handlePrint = () => {
    if (printRef.current) {
      const printContent = printRef.current.innerHTML;
      const printWindow = window.open('', '_blank');
      if (printWindow) {
        printWindow.document.write(`
          <html>
            <head>
              <title>Fattura ${invoiceData?.tutor.businessName} - ${invoiceData?.period.label}</title>
              <style>
                body { font-family: Arial, sans-serif; padding: 40px; max-width: 800px; margin: 0 auto; }
                .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #EAB308; padding-bottom: 20px; }
                .header h1 { color: #EAB308; margin: 0; }
                .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; }
                .info-box { background: #f5f5f5; padding: 15px; border-radius: 8px; width: 45%; }
                .info-box h3 { margin: 0 0 10px 0; color: #333; font-size: 14px; }
                .info-box p { margin: 5px 0; font-size: 13px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
                th { background: #EAB308; color: black; }
                tr:nth-child(even) { background: #f9f9f9; }
                .total-row { font-weight: bold; background: #FEF3C7 !important; }
                .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
                @media print { body { padding: 20px; } }
              </style>
            </head>
            <body>${printContent}</body>
          </html>
        `);
        printWindow.document.close();
        printWindow.print();
      }
    }
  };

  const formatCurrency = (value: number) => {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(value);
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-invoicing-title">Fatturazione</h1>
          <p className="text-gray-500 text-sm">Genera fatture mensili per gli enti formativi</p>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label className="block text-gray-400 text-sm mb-2">Ente Formativo</label>
            <select
              value={selectedTutor || ''}
              onChange={(e) => setSelectedTutor(e.target.value ? parseInt(e.target.value) : null)}
              className="w-full bg-black border border-gray-700 rounded-lg py-2 px-3 text-white focus:border-yellow-500 focus:outline-none"
              data-testid="select-tutor"
            >
              <option value="">Seleziona ente...</option>
              {tutors.map(t => (
                <option key={t.id} value={t.id}>#{t.id} {t.businessName}</option>
              ))}
            </select>
          </div>
          
          <div>
            <label className="block text-gray-400 text-sm mb-2">Mese</label>
            <select
              value={selectedMonth}
              onChange={(e) => setSelectedMonth(parseInt(e.target.value))}
              className="w-full bg-black border border-gray-700 rounded-lg py-2 px-3 text-white focus:border-yellow-500 focus:outline-none"
              data-testid="select-month"
            >
              {months.map((m, i) => (
                <option key={i} value={i}>{m}</option>
              ))}
            </select>
          </div>
          
          <div>
            <label className="block text-gray-400 text-sm mb-2">Anno</label>
            <select
              value={selectedYear}
              onChange={(e) => setSelectedYear(parseInt(e.target.value))}
              className="w-full bg-black border border-gray-700 rounded-lg py-2 px-3 text-white focus:border-yellow-500 focus:outline-none"
              data-testid="select-year"
            >
              {years.map(y => (
                <option key={y} value={y}>{y}</option>
              ))}
            </select>
          </div>
          
          <div className="flex items-end">
            <Button
              onClick={() => refetch()}
              disabled={!selectedTutor}
              className="w-full bg-yellow-500 hover:bg-yellow-400 text-black"
              data-testid="button-generate-invoice"
            >
              <FileText size={18} className="mr-2" />
              Genera Fattura
            </Button>
          </div>
        </div>
      </div>

      {/* Invoice Preview */}
      {!selectedTutor ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <FileText size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Seleziona un ente formativo</h3>
          <p className="text-gray-500">Scegli l'ente e il periodo per generare la fattura</p>
        </div>
      ) : isLoadingInvoice ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Generazione fattura...</p>
        </div>
      ) : invoiceData ? (
        <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 overflow-hidden">
          {/* Print Button */}
          <div className="p-4 border-b border-gray-800 flex justify-between items-center">
            <div className="flex items-center gap-2 text-gray-400">
              <Calendar size={16} />
              <span>Periodo: {invoiceData.period.label}</span>
            </div>
            <Button
              onClick={handlePrint}
              variant="outline"
              className="border-yellow-500 text-yellow-500 hover:bg-yellow-500 hover:text-black"
              data-testid="button-print-invoice"
            >
              <Printer size={18} className="mr-2" />
              Stampa / PDF
            </Button>
          </div>

          {/* Invoice Content (for print) */}
          <div ref={printRef} className="p-6">
            <div className="header">
              <h1>TUTOR81 LMS</h1>
              <p style={{ margin: '10px 0 0 0', color: '#666' }}>Riepilogo Corsi Venduti</p>
            </div>

            <div className="info-section" style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '30px' }}>
              <div className="info-box" style={{ background: '#2a2a2a', padding: '15px', borderRadius: '8px', width: '45%' }}>
                <h3 style={{ color: '#EAB308', marginBottom: '10px' }}>Destinatario</h3>
                <p style={{ color: 'white', fontWeight: 'bold', margin: '5px 0' }}>{invoiceData.tutor.businessName}</p>
                {invoiceData.tutor.address && <p style={{ color: '#aaa', margin: '3px 0', fontSize: '13px' }}>{invoiceData.tutor.address}</p>}
                {invoiceData.tutor.city && <p style={{ color: '#aaa', margin: '3px 0', fontSize: '13px' }}>{invoiceData.tutor.city}</p>}
                {invoiceData.tutor.vatNumber && <p style={{ color: '#aaa', margin: '3px 0', fontSize: '13px' }}>P.IVA: {invoiceData.tutor.vatNumber}</p>}
              </div>
              <div className="info-box" style={{ background: '#2a2a2a', padding: '15px', borderRadius: '8px', width: '45%' }}>
                <h3 style={{ color: '#EAB308', marginBottom: '10px' }}>Periodo</h3>
                <p style={{ color: 'white', fontWeight: 'bold', margin: '5px 0' }}>{invoiceData.period.label}</p>
                <p style={{ color: '#aaa', margin: '3px 0', fontSize: '13px' }}>Vendite totali: {invoiceData.totalSales}</p>
                <p style={{ color: '#aaa', margin: '3px 0', fontSize: '13px' }}>Generata il: {new Date(invoiceData.generatedAt).toLocaleDateString('it-IT')}</p>
              </div>
            </div>

            {invoiceData.courses.length > 0 ? (
              <>
                <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '20px' }}>
                  <thead>
                    <tr>
                      <th style={{ background: '#EAB308', color: 'black', padding: '12px', textAlign: 'left', border: '1px solid #444', width: '100px' }}>ID Corso</th>
                      <th style={{ background: '#EAB308', color: 'black', padding: '12px', textAlign: 'center', border: '1px solid #444', width: '80px' }}>Qtà</th>
                      <th style={{ background: '#EAB308', color: 'black', padding: '12px', textAlign: 'right', border: '1px solid #444', width: '120px' }}>Prezzo Unit.</th>
                      <th style={{ background: '#EAB308', color: 'black', padding: '12px', textAlign: 'right', border: '1px solid #444', width: '120px' }}>Totale</th>
                    </tr>
                  </thead>
                  <tbody>
                    {invoiceData.courses.map((course, idx) => (
                      <tr key={idx} style={{ background: idx % 2 === 0 ? '#1e1e1e' : '#252525' }}>
                        <td style={{ padding: '12px', border: '1px solid #444', color: 'white', fontFamily: 'monospace' }}>#{course.courseId}</td>
                        <td style={{ padding: '12px', border: '1px solid #444', color: 'white', textAlign: 'center' }}>{course.qty}</td>
                        <td style={{ padding: '12px', border: '1px solid #444', color: 'white', textAlign: 'right' }}>{formatCurrency(course.unitPrice)}</td>
                        <td style={{ padding: '12px', border: '1px solid #444', color: 'white', textAlign: 'right' }}>{formatCurrency(course.total)}</td>
                      </tr>
                    ))}
                    <tr style={{ background: '#3d3400' }}>
                      <td colSpan={3} style={{ padding: '12px', border: '1px solid #444', color: '#EAB308', fontWeight: 'bold', textAlign: 'right' }}>TOTALE</td>
                      <td style={{ padding: '12px', border: '1px solid #444', color: '#EAB308', fontWeight: 'bold', textAlign: 'right', fontSize: '18px' }}>{formatCurrency(invoiceData.grandTotal)}</td>
                    </tr>
                  </tbody>
                </table>
              </>
            ) : (
              <div style={{ textAlign: 'center', padding: '40px', color: '#666' }}>
                <p>Nessuna vendita in questo periodo</p>
              </div>
            )}

            <div className="footer" style={{ textAlign: 'center', marginTop: '40px', paddingTop: '20px', borderTop: '1px solid #444', fontSize: '12px', color: '#666' }}>
              <p>TUTOR81 LMS - Piattaforma E-Learning</p>
              <p>Documento generato automaticamente</p>
            </div>
          </div>

          {/* Summary Cards */}
          <div className="p-4 border-t border-gray-800 grid grid-cols-3 gap-4">
            <div className="bg-black rounded-lg p-4 text-center">
              <Building size={24} className="mx-auto text-yellow-500 mb-2" />
              <p className="text-gray-500 text-sm">Ente</p>
              <p className="text-white font-bold truncate">{invoiceData.tutor.businessName}</p>
            </div>
            <div className="bg-black rounded-lg p-4 text-center">
              <FileText size={24} className="mx-auto text-blue-500 mb-2" />
              <p className="text-gray-500 text-sm">Vendite</p>
              <p className="text-white font-bold">{invoiceData.totalSales}</p>
            </div>
            <div className="bg-black rounded-lg p-4 text-center">
              <Euro size={24} className="mx-auto text-green-500 mb-2" />
              <p className="text-gray-500 text-sm">Totale</p>
              <p className="text-white font-bold">{formatCurrency(invoiceData.grandTotal)}</p>
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
