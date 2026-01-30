import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { Calendar, Building, ChevronDown } from 'lucide-react';
import type { Company } from '@shared/schema';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

interface Sale {
  id: number;
  user: string;
  client: string;
  date: string;
  course: string;
  qty: number;
  listPrice: number;
  tutorId: number;
  tutorName: string;
}

interface GroupedSales {
  [monthKey: string]: {
    label: string;
    sales: Sale[];
    total: number;
  };
}

export default function Sales() {
  const [selectedTutorId, setSelectedTutorId] = useState<string>('');
  const [selectedMonth, setSelectedMonth] = useState<string>('all');

  const { data: tutors = [] } = useQuery<Company[]>({
    queryKey: ['/api/tutors'],
  });

  const { data: sales = [], isLoading } = useQuery<Sale[]>({
    queryKey: ['/api/sales', selectedTutorId],
    queryFn: async () => {
      const url = selectedTutorId 
        ? `/api/sales?tutorId=${selectedTutorId}`
        : '/api/sales';
      const res = await fetch(url, { credentials: 'include' });
      return res.json();
    },
    enabled: !!selectedTutorId,
  });

  const selectedTutor = tutors.find(t => t.id.toString() === selectedTutorId);

  const monthNames = ['GENNAIO', 'FEBBRAIO', 'MARZO', 'APRILE', 'MAGGIO', 'GIUGNO', 
                      'LUGLIO', 'AGOSTO', 'SETTEMBRE', 'OTTOBRE', 'NOVEMBRE', 'DICEMBRE'];

  const availableMonths = useMemo(() => {
    const months = new Set<string>();
    sales.forEach(sale => {
      if (!sale.date) return;
      const date = new Date(sale.date);
      const monthKey = `${date.getFullYear()}-${String(date.getMonth()).padStart(2, '0')}`;
      months.add(monthKey);
    });
    return Array.from(months).sort((a, b) => b.localeCompare(a)).map(key => {
      const [year, month] = key.split('-');
      return { key, label: `${monthNames[parseInt(month)]} ${year}` };
    });
  }, [sales]);

  const groupedByMonth = useMemo(() => {
    const groups: GroupedSales = {};
    
    const filteredSales = selectedMonth === 'all' 
      ? sales 
      : sales.filter(sale => {
          if (!sale.date) return false;
          const date = new Date(sale.date);
          const monthKey = `${date.getFullYear()}-${String(date.getMonth()).padStart(2, '0')}`;
          return monthKey === selectedMonth;
        });
    
    filteredSales.forEach(sale => {
      if (!sale.date) return;
      const date = new Date(sale.date);
      const monthKey = `${date.getFullYear()}-${String(date.getMonth()).padStart(2, '0')}`;
      const monthLabel = `${monthNames[date.getMonth()]} ${date.getFullYear()}`;
      
      if (!groups[monthKey]) {
        groups[monthKey] = { label: monthLabel, sales: [], total: 0 };
      }
      groups[monthKey].sales.push(sale);
      groups[monthKey].total += sale.listPrice * (sale.qty || 1);
    });

    return Object.entries(groups)
      .sort(([a], [b]) => b.localeCompare(a))
      .map(([key, value]) => ({ key, ...value }));
  }, [sales, selectedMonth]);

  const totalOrders = sales.length;

  const formatDate = (dateStr: string) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('it-IT');
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(price);
  };

  return (
    <div className="min-h-screen bg-white">
      {/* Header */}
      <div className="bg-white border-b border-gray-200 px-6 py-4">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
              <span className="text-blue-600 font-bold text-lg">
                {selectedTutor?.businessName?.charAt(0) || 'A'}
              </span>
            </div>
            <Select value={selectedTutorId} onValueChange={setSelectedTutorId}>
              <SelectTrigger className="w-[300px] border-0 shadow-none text-xl font-bold text-gray-900 p-0 h-auto" data-testid="select-tutor">
                <SelectValue placeholder="Seleziona Ente Formativo" />
              </SelectTrigger>
              <SelectContent>
                {tutors.map(tutor => (
                  <SelectItem key={tutor.id} value={tutor.id.toString()} data-testid={`option-tutor-${tutor.id}`}>
                    {tutor.businessName}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>
          {selectedTutorId && (
            <div className="flex items-center gap-4">
              <Select value={selectedMonth} onValueChange={setSelectedMonth}>
                <SelectTrigger className="w-[200px] bg-gray-100 border-0" data-testid="select-month">
                  <SelectValue placeholder="Tutti i mesi" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Tutti i mesi</SelectItem>
                  {availableMonths.map(m => (
                    <SelectItem key={m.key} value={m.key} data-testid={`option-month-${m.key}`}>
                      {m.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <div className="bg-gray-100 px-4 py-2 rounded-lg">
                <span className="text-gray-600 text-sm">Totale Ordini: </span>
                <span className="font-bold text-gray-900" data-testid="text-total-orders">{totalOrders}</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Content */}
      <div className="p-6">
        {!selectedTutorId ? (
          <div className="text-center py-20">
            <Building size={64} className="mx-auto text-gray-300 mb-4" />
            <h3 className="text-xl font-medium text-gray-600 mb-2">Seleziona un Ente Formativo</h3>
            <p className="text-gray-400">Scegli un ente formativo dal menu per visualizzare le vendite</p>
          </div>
        ) : isLoading ? (
          <div className="text-center py-12">
            <div className="animate-spin w-8 h-8 border-2 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
            <p className="text-gray-500 mt-4">Caricamento...</p>
          </div>
        ) : sales.length === 0 ? (
          <div className="text-center py-20">
            <Calendar size={64} className="mx-auto text-gray-300 mb-4" />
            <h3 className="text-xl font-medium text-gray-600 mb-2">Nessuna vendita</h3>
            <p className="text-gray-400">Non ci sono vendite per questo ente formativo</p>
          </div>
        ) : (
          <div className="space-y-6">
            {groupedByMonth.map(group => (
              <div key={group.key} className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                {/* Month Header */}
                <div className="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                  <div className="flex items-center gap-2">
                    <Calendar size={16} className="text-blue-500" />
                    <span className="font-bold text-gray-800" data-testid={`text-month-${group.key}`}>{group.label}</span>
                  </div>
                  <div className="text-right">
                    <span className="text-gray-500 text-sm">Tot. Costo: </span>
                    <span className="font-bold text-blue-600" data-testid={`text-month-total-${group.key}`}>{formatPrice(group.total)}</span>
                  </div>
                </div>

                {/* Table */}
                <table className="w-full">
                  <thead className="bg-gray-50 border-b border-gray-100">
                    <tr>
                      <th className="text-left py-2 px-4 text-xs font-medium text-gray-500 uppercase w-16">ORD.</th>
                      <th className="text-left py-2 px-4 text-xs font-medium text-gray-500 uppercase">UTENTE</th>
                      <th className="text-left py-2 px-4 text-xs font-medium text-gray-500 uppercase">CLIENTE</th>
                      <th className="text-left py-2 px-4 text-xs font-medium text-gray-500 uppercase w-28">DATA</th>
                      <th className="text-left py-2 px-4 text-xs font-medium text-gray-500 uppercase">CORSO</th>
                      <th className="text-center py-2 px-4 text-xs font-medium text-gray-500 uppercase w-16">QTA</th>
                      <th className="text-right py-2 px-4 text-xs font-medium text-gray-500 uppercase w-24">COSTO</th>
                    </tr>
                  </thead>
                  <tbody>
                    {group.sales.map((sale, index) => (
                      <tr 
                        key={sale.id} 
                        className={`border-b border-gray-50 hover:bg-blue-50/30 ${index % 2 === 0 ? 'bg-white' : 'bg-gray-50/30'}`}
                        data-testid={`row-sale-${sale.id}`}
                      >
                        <td className="py-3 px-4 text-sm text-gray-500">{sale.id}</td>
                        <td className="py-3 px-4 text-sm text-gray-700">{sale.user}</td>
                        <td className="py-3 px-4 text-sm text-blue-600 font-medium hover:underline cursor-pointer">{sale.client}</td>
                        <td className="py-3 px-4 text-sm text-gray-600">{formatDate(sale.date)}</td>
                        <td className="py-3 px-4 text-sm text-gray-700 max-w-xs truncate">{sale.course}</td>
                        <td className="py-3 px-4 text-sm text-center text-gray-600">{sale.qty}</td>
                        <td className="py-3 px-4 text-sm text-right text-blue-600 font-medium">{formatPrice(sale.listPrice)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
