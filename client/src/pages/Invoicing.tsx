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
  orders: Array<{
    orderId: number;
    courseId: number;
    qty: number;
    price: number;
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

          {/* Invoice Content (for print) - White background for print */}
          <div ref={printRef} style={{ background: 'white', padding: '40px', color: 'black', fontFamily: 'Arial, sans-serif' }}>
            {/* Header */}
            <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '40px', borderBottom: '3px solid #EAB308', paddingBottom: '20px' }}>
              <div>
                <h1 style={{ margin: 0, fontSize: '24px', color: '#333' }}>Fattura TUTOR81ONLINE SL</h1>
                <div style={{ marginTop: '15px', fontSize: '13px', color: '#555', lineHeight: '1.6' }}>
                  <p style={{ margin: '3px 0', fontWeight: 'bold' }}>TUTOR81ONLINE SL</p>
                  <p style={{ margin: '3px 0' }}>CIF: B21797709</p>
                  <p style={{ margin: '3px 0' }}>C.C San Agustin - Calle las Dalias, 12</p>
                  <p style={{ margin: '3px 0' }}>Planta 5, Local 340</p>
                  <p style={{ margin: '3px 0' }}>35100 San Bartolomé de Tirajana - Las Palmas</p>
                  <p style={{ margin: '3px 0' }}>assistenza@tutor81.it</p>
                </div>
              </div>
              <div style={{ textAlign: 'right' }}>
                <h2 style={{ margin: 0, fontSize: '20px', color: '#EAB308', fontWeight: 'bold' }}>FACTURA</h2>
                <div style={{ marginTop: '15px', fontSize: '13px', color: '#555' }}>
                  <p style={{ margin: '5px 0' }}><strong>Fecha:</strong> {new Date(invoiceData.generatedAt).toLocaleDateString('es-ES')}</p>
                  <p style={{ margin: '5px 0' }}><strong>Nº Factura:</strong> ___/2025</p>
                </div>
              </div>
            </div>

            {/* Cliente */}
            <div style={{ marginBottom: '40px', padding: '20px', background: '#f9f9f9', borderRadius: '8px', border: '1px solid #eee' }}>
              <h3 style={{ margin: '0 0 10px 0', fontSize: '14px', color: '#888', textTransform: 'uppercase' }}>Cliente</h3>
              <p style={{ margin: '5px 0', fontWeight: 'bold', fontSize: '16px', color: '#333' }}>{invoiceData.tutor.businessName}</p>
              {invoiceData.tutor.vatNumber && <p style={{ margin: '3px 0', fontSize: '13px', color: '#555' }}>P.IVA: {invoiceData.tutor.vatNumber}</p>}
              {invoiceData.tutor.address && <p style={{ margin: '3px 0', fontSize: '13px', color: '#555' }}>{invoiceData.tutor.address}</p>}
              {invoiceData.tutor.city && <p style={{ margin: '3px 0', fontSize: '13px', color: '#555' }}>{invoiceData.tutor.city}</p>}
            </div>

            {/* Concepto Table */}
            <table style={{ width: '100%', borderCollapse: 'collapse', marginBottom: '30px' }}>
              <thead>
                <tr>
                  <th style={{ background: '#EAB308', color: 'black', padding: '15px', textAlign: 'left', fontSize: '14px', fontWeight: 'bold' }}>Concepto</th>
                  <th style={{ background: '#EAB308', color: 'black', padding: '15px', textAlign: 'right', fontSize: '14px', fontWeight: 'bold', width: '150px' }}>Importe</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style={{ padding: '20px 15px', borderBottom: '1px solid #eee', fontSize: '14px', color: '#333' }}>
                    Acquisti e-learning mese di {invoiceData.period.label.toLowerCase()} piattaforma LMS TUTOR81
                  </td>
                  <td style={{ padding: '20px 15px', borderBottom: '1px solid #eee', textAlign: 'right', fontSize: '14px', color: '#333' }}>
                    {formatCurrency(invoiceData.grandTotal)}
                  </td>
                </tr>
              </tbody>
            </table>

            {/* Total */}
            <div style={{ display: 'flex', justifyContent: 'flex-end', marginBottom: '40px' }}>
              <div style={{ background: '#EAB308', padding: '15px 30px', borderRadius: '8px' }}>
                <span style={{ fontSize: '16px', fontWeight: 'bold', color: 'black' }}>TOTAL: </span>
                <span style={{ fontSize: '24px', fontWeight: 'bold', color: 'black' }}>{formatCurrency(invoiceData.grandTotal)}</span>
              </div>
            </div>

            {/* Legal Note - VAT Exemption */}
            <div style={{ marginBottom: '30px', padding: '15px', background: '#f5f5f5', borderRadius: '8px', fontSize: '11px', color: '#666', fontStyle: 'italic', lineHeight: '1.5' }}>
              <p style={{ margin: 0 }}>
                Operación no sujeta al IGIC conforme a la Ley de localización, Artículo 17 de la Ley 20/1991. 
                A efectos de IVA, Inversión de Sujeto Pasivo.
              </p>
            </div>

            {/* Bank Details */}
            <div style={{ borderTop: '2px solid #eee', paddingTop: '20px', fontSize: '12px', color: '#555' }}>
              <p style={{ margin: '5px 0' }}><strong>Pagamento immediato</strong> presso Banca Santander intestato a Tutor81online</p>
              <p style={{ margin: '5px 0' }}><strong>IBAN:</strong> ES76 0049 5875 2821 1631 6735</p>
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
