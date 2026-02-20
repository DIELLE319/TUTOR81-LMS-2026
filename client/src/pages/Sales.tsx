import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import { it } from 'date-fns/locale';
import { Search, ChevronsUpDown, Check, Download, FileSpreadsheet, ArrowUpDown, ArrowUp, ArrowDown } from 'lucide-react';
import { useAuth } from '@/hooks/use-auth';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import PageHeader from '@/components/PageHeader';

interface Sale {
  id: number;
  adminId: number;
  adminName: string;
  client: string;
  clientId: number;
  tutorId: number;
  tutorName: string;
  date: string;
  courseId: number;
  courseName: string;
  qty: number;
  unitPrice: string;
  totalCost: string;
  activatedStudents?: string;
}

interface Tutor {
  id: number;
  businessName: string;
}

interface Company {
  id: number;
  businessName: string;
}

export default function Sales() {
  const { user } = useAuth();
  const isSuperAdmin = user?.role === 1000;
  const [search, setSearch] = useState('');
  const [pageSize, setPageSize] = useState('100');
  const [tutorSearchOpen, setTutorSearchOpen] = useState(false);
  const [companyFilter, setCompanyFilter] = useState<string>('');
  const [companySearchOpen, setCompanySearchOpen] = useState(false);
  const [sortOrder, setSortOrder] = useState<'asc' | 'desc'>('desc');
  
  // Se l'utente è venditore (admin tutor), usa automaticamente il suo tutorId
  const isVenditore = user?.role === 1;
  const userTutorId = (user as any)?.tutorId;
  const [tutorFilter, setTutorFilter] = useState<string>('');
  
  // Imposta il filtro tutor automaticamente per gli admin tutor
  const effectiveTutorFilter = isVenditore && userTutorId ? userTutorId.toString() : tutorFilter;

  const { data: sales = [], isLoading } = useQuery<Sale[]>({
    queryKey: ['/api/sales', effectiveTutorFilter],
    queryFn: async () => {
      const url = effectiveTutorFilter ? `/api/sales?tutorId=${effectiveTutorFilter}` : '/api/sales';
      const res = await fetch(url, { credentials: 'include' });
      if (!res.ok) throw new Error('Failed to fetch');
      return res.json();
    },
  });

  const { data: tutors = [] } = useQuery<Tutor[]>({
    queryKey: ['/api/tutors'],
    refetchOnMount: 'always',
    queryFn: async () => {
      const res = await fetch('/api/tutors', { credentials: 'include', cache: 'no-store' });
      if (!res.ok) throw new Error('Failed to fetch tutors');
      return res.json();
    },
  });

  const isNoneSubscription = (subscriptionType: string | null | undefined) => {
    const normalized = (subscriptionType ?? '').trim().toLowerCase();
    if (!normalized) return true;
    if (normalized === 'nessuno') return true;
    if (normalized.includes('nessun abbonamento')) return true;
    if (normalized.startsWith('nessun')) return true;
    return false;
  };

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies-list'],
  });

  const sortedTutors = useMemo(() => {
    return tutors
      .filter(t => t.businessName && !isNoneSubscription(t.subscriptionType))
      .sort((a, b) => (a.businessName || '').localeCompare(b.businessName || ''));
  }, [tutors]);

  const sortedCompanies = useMemo(() => {
    return companies
      .filter(c => c.businessName)
      .sort((a, b) => (a.businessName || '').localeCompare(b.businessName || ''));
  }, [companies]);

  const selectedTutorName = useMemo(() => {
    if (!tutorFilter) return '';
    const tutor = tutors.find(t => t.id.toString() === tutorFilter);
    return tutor?.businessName || '';
  }, [tutorFilter, tutors]);

  const selectedCompanyName = useMemo(() => {
    if (!companyFilter) return '';
    const company = companies.find(c => c.id.toString() === companyFilter);
    return company?.businessName || '';
  }, [companyFilter, companies]);

  const formatDate = (date: string | null) => {
    if (!date) return '-';
    return format(new Date(date), 'dd/MM/yyyy', { locale: it });
  };

  const filteredSales = useMemo(() => {
    const filtered = sales.filter(sale => {
      if (tutorFilter && sale.tutorId?.toString() !== tutorFilter) return false;
      if (companyFilter && sale.clientId?.toString() !== companyFilter) return false;
      if (search) {
        const s = search.toLowerCase();
        return (
          sale.adminName?.toLowerCase().includes(s) ||
          sale.client?.toLowerCase().includes(s) ||
          sale.courseName?.toLowerCase().includes(s)
        );
      }
      return true;
    });
    return filtered.sort((a, b) => sortOrder === 'desc' ? b.id - a.id : a.id - b.id);
  }, [sales, tutorFilter, companyFilter, selectedCompanyName, search, sortOrder]);

  const displayedSales = filteredSales.slice(0, parseInt(pageSize));

  const exportCSV = () => {
    const headers = ['Numero Ordine', 'ID Admin', 'Utente Admin', 'Cliente', 'Ente Formativo', 'Data Vendita', 'Nome Corso', 'Quantità', 'Tuo Costo'];
    const rows = filteredSales.map(sale => [
      sale.id,
      sale.adminId,
      sale.adminName,
      sale.client,
      sale.tutorName,
      formatDate(sale.date),
      sale.courseName || '-',
      sale.qty,
      parseFloat(sale.totalCost || '0').toFixed(2)
    ]);
    
    const csvContent = [headers, ...rows].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
    const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `vendite_${format(new Date(), 'yyyy-MM-dd')}.csv`;
    link.click();
    URL.revokeObjectURL(url);
  };

  const exportExcel = async () => {
    const headers = ['Numero Ordine', 'ID Admin', 'Utente Admin', 'Cliente', 'Ente Formativo', 'Data Vendita', 'Nome Corso', 'Quantità', 'Tuo Costo'];
    const rows = filteredSales.map(sale => [
      sale.id,
      sale.adminId,
      sale.adminName,
      sale.client,
      sale.tutorName,
      formatDate(sale.date),
      sale.courseName || '-',
      sale.qty,
      parseFloat(sale.totalCost || '0').toFixed(2)
    ]);
    
    const { utils, writeFile } = await import('xlsx');
    const ws = utils.aoa_to_sheet([headers, ...rows]);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Vendite');
    writeFile(wb, `vendite_${format(new Date(), 'yyyy-MM-dd')}.xlsx`);
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <PageHeader
        title="Corsi Venduti"
        description={`Totale: ${filteredSales.length} vendite`}
        actions={
          <>
            <Button
              onClick={exportCSV}
              className="bg-white/10 hover:bg-white/15 text-white border border-gray-700"
              data-testid="button-export-csv"
            >
              <Download className="h-4 w-4 mr-2" />
              CSV
            </Button>
            <Button
              onClick={exportExcel}
              className="bg-green-600 hover:bg-green-700 text-white"
              data-testid="button-export-excel"
            >
              <FileSpreadsheet className="h-4 w-4 mr-2" />
              Excel
            </Button>
          </>
        }
      />

      <div
        className={cn(
          'mt-6 rounded-xl border-2 p-4',
          isSuperAdmin ? 'bg-white border-black/70' : 'bg-[#1e1e1e] border-gray-800'
        )}
      >
        <div className="flex items-center justify-between gap-4 flex-wrap">
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex items-center gap-2">
              <span className={cn('text-sm font-medium', isSuperAdmin ? 'text-black' : 'text-gray-300')}>Mostra</span>
              <Select value={pageSize} onValueChange={setPageSize}>
                <SelectTrigger
                  className={cn(
                    'w-20',
                    isSuperAdmin ? 'bg-white border-black/20 text-black' : 'bg-black border-gray-700 text-white'
                  )}
                  data-testid="select-page-size"
                >
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="10">10</SelectItem>
                  <SelectItem value="25">25</SelectItem>
                  <SelectItem value="50">50</SelectItem>
                  <SelectItem value="100">100</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div className="flex items-center gap-2">
              <span className={cn('text-sm font-medium', isSuperAdmin ? 'text-black' : 'text-gray-300')}>Cerca</span>
              <div className="relative">
                <Input
                  type="text"
                  placeholder="Admin, cliente, corso..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className={cn(
                    'w-56 h-9 text-sm pl-3 pr-9 rounded-md',
                    isSuperAdmin
                      ? 'bg-white text-black border-black/20'
                      : 'bg-black text-white border-gray-700'
                  )}
                  data-testid="input-search"
                />
                <Search
                  className={cn(
                    'absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4',
                    isSuperAdmin ? 'text-black/50' : 'text-gray-500'
                  )}
                />
              </div>
            </div>

            <Popover open={tutorSearchOpen} onOpenChange={setTutorSearchOpen} modal={false}>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  className={cn(
                    'w-64 justify-between',
                    isSuperAdmin
                      ? 'bg-white border-black/20 text-black hover:bg-yellow-50'
                      : 'bg-black border-gray-700 text-white hover:bg-white/5'
                  )}
                  data-testid="select-tutor-filter"
                >
                  {selectedTutorName || '--- Tutti gli Enti ---'}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-64 p-0" align="start">
                <Command>
                  <CommandInput placeholder="Cerca ente..." />
                  <CommandList>
                    <CommandEmpty>Nessun ente trovato</CommandEmpty>
                    <CommandGroup>
                      <CommandItem
                        value="tutti"
                        onSelect={() => {
                          setTutorFilter('');
                          setTutorSearchOpen(false);
                        }}
                        className="cursor-pointer font-bold"
                      >
                        <Check className={cn('mr-2 h-4 w-4', !tutorFilter ? 'opacity-100' : 'opacity-0')} />
                        --- Tutti gli Enti ---
                      </CommandItem>
                      {sortedTutors.map(tutor => (
                        <CommandItem
                          key={tutor.id}
                          value={tutor.businessName}
                          onSelect={() => {
                            setTutorFilter(tutor.id.toString());
                            setTutorSearchOpen(false);
                          }}
                          className="cursor-pointer"
                        >
                          <Check className={cn('mr-2 h-4 w-4', tutorFilter === tutor.id.toString() ? 'opacity-100' : 'opacity-0')} />
                          {tutor.businessName}
                        </CommandItem>
                      ))}
                    </CommandGroup>
                  </CommandList>
                </Command>
              </PopoverContent>
            </Popover>

            <Popover open={companySearchOpen} onOpenChange={setCompanySearchOpen} modal={false}>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  className={cn(
                    'w-64 justify-between',
                    isSuperAdmin
                      ? 'bg-white border-black/20 text-black hover:bg-yellow-50'
                      : 'bg-black border-gray-700 text-white hover:bg-white/5'
                  )}
                  data-testid="select-company-filter"
                >
                  {selectedCompanyName || '--- Tutte le Aziende ---'}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-64 p-0" align="start">
                <Command>
                  <CommandInput placeholder="Cerca azienda..." />
                  <CommandList>
                    <CommandEmpty>Nessuna azienda trovata</CommandEmpty>
                    <CommandGroup>
                      <CommandItem
                        value="tutte"
                        onSelect={() => {
                          setCompanyFilter('');
                          setCompanySearchOpen(false);
                        }}
                        className="cursor-pointer font-bold"
                      >
                        <Check className={cn('mr-2 h-4 w-4', !companyFilter ? 'opacity-100' : 'opacity-0')} />
                        --- Tutte le Aziende ---
                      </CommandItem>
                      {sortedCompanies.map(company => (
                        <CommandItem
                          key={company.id}
                          value={company.businessName}
                          onSelect={() => {
                            setCompanyFilter(company.id.toString());
                            setCompanySearchOpen(false);
                          }}
                          className="cursor-pointer"
                        >
                          <Check className={cn('mr-2 h-4 w-4', companyFilter === company.id.toString() ? 'opacity-100' : 'opacity-0')} />
                          {company.businessName}
                        </CommandItem>
                      ))}
                    </CommandGroup>
                  </CommandList>
                </Command>
              </PopoverContent>
            </Popover>
          </div>
        </div>
      </div>

      <div
        className={cn(
          'mt-4 rounded-xl border-2 overflow-x-auto',
          isSuperAdmin ? 'bg-white border-black/70' : 'bg-[#0f0f0f] border-gray-800'
        )}
      >
          <table className="w-full min-w-[1200px]" data-testid="table-sales">
            <thead className="bg-yellow-500">
              <tr>
                <th className="text-left p-2 text-xs font-bold text-black uppercase w-20">
                  <button
                    onClick={() => setSortOrder(prev => prev === 'desc' ? 'asc' : 'desc')}
                    className="flex items-center gap-1 hover:text-yellow-700 transition-colors"
                    data-testid="button-sort-id"
                  >
                    N. Ordine
                    {sortOrder === 'desc' ? <ArrowDown className="h-3 w-3" /> : <ArrowUp className="h-3 w-3" />}
                  </button>
                </th>
                <th className="text-left p-2 text-xs font-bold text-black uppercase w-24">Data</th>
                <th className="text-left p-2 text-xs font-bold text-black uppercase">Ente Formativo</th>
                <th className="text-left p-2 text-xs font-bold text-black uppercase">Cliente</th>
                <th className="text-left p-2 text-xs font-bold text-black uppercase w-16">ID LP</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Corso</th>
                <th className="text-center p-2 text-xs font-bold text-black uppercase w-12">Qta</th>
                <th className="text-right p-2 text-xs font-bold text-black uppercase w-24">Tuo Costo</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={8} className={cn('text-center py-10', isSuperAdmin ? 'text-gray-600' : 'text-gray-400')}>
                    Caricamento...
                  </td>
                </tr>
              ) : displayedSales.length === 0 ? (
                <tr>
                  <td colSpan={8} className={cn('text-center py-10', isSuperAdmin ? 'text-gray-600' : 'text-gray-400')}>
                    Nessuna vendita trovata
                  </td>
                </tr>
              ) : (
                displayedSales.map((sale) => (
                  <tr
                    key={sale.id}
                    className={cn(
                      'border-b',
                      isSuperAdmin
                        ? 'border-gray-200 hover:bg-gray-50'
                        : 'border-gray-800 hover:bg-white/5'
                    )}
                    data-testid={`row-sale-${sale.id}`}
                  >
                    <td className={cn('p-2 text-sm font-medium', isSuperAdmin ? 'text-black' : 'text-white')}>
                      {sale.id}
                    </td>
                    <td className={cn('p-2 text-sm', isSuperAdmin ? 'text-gray-700' : 'text-gray-200')}>
                      {formatDate(sale.date)}
                    </td>
                    <td className={cn('p-2 text-sm max-w-[150px]', isSuperAdmin ? 'text-gray-700' : 'text-gray-200')}>
                      <div className="truncate" title={sale.tutorName}>
                        {sale.tutorName}
                      </div>
                      <div className={cn('text-xs mt-0.5', isSuperAdmin ? 'text-gray-500' : 'text-gray-500')}>
                        ID {sale.adminId} - {sale.adminName}
                      </div>
                    </td>
                    <td
                      className={cn(
                        'p-2 text-sm max-w-[150px] truncate',
                        isSuperAdmin ? 'text-gray-700' : 'text-gray-200'
                      )}
                      title={sale.client}
                    >
                      {sale.client}
                    </td>
                    <td className={cn('p-2 text-sm', isSuperAdmin ? 'text-gray-700' : 'text-gray-200')}>
                      {sale.courseId}
                    </td>
                    <td className={cn('p-3 text-sm max-w-[280px]', isSuperAdmin ? 'text-gray-700' : 'text-gray-200')}>
                      <div className={cn('font-medium', isSuperAdmin ? 'text-black' : 'text-white')}>
                        {(sale.courseName || '-').replace(/^[a-zA-Z0-9]+\s*-\s*/, '')}
                      </div>
                      {sale.activatedStudents && (
                        <div className={cn('text-xs mt-0.5', isSuperAdmin ? 'text-gray-500' : 'text-gray-500')}>
                          {sale.activatedStudents}
                        </div>
                      )}
                    </td>
                    <td className={cn('p-2 text-sm text-center', isSuperAdmin ? 'text-gray-700' : 'text-gray-200')}>
                      {sale.qty}
                    </td>
                    <td className={cn('p-2 text-sm text-right font-medium', isSuperAdmin ? 'text-black' : 'text-white')}>
                      {parseFloat(sale.totalCost || '0').toFixed(2)} €
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
  );
}
