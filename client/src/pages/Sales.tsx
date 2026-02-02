import { useState, useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { format } from 'date-fns';
import { it } from 'date-fns/locale';
import { Search, ChevronsUpDown, Check, Download, FileSpreadsheet } from 'lucide-react';
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

interface Sale {
  id: number;
  adminId: number;
  adminName: string;
  client: string;
  tutorId: number;
  tutorName: string;
  date: string;
  courseId: number;
  courseName: string;
  qty: number;
  listPrice: string;
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
  const [search, setSearch] = useState('');
  const [pageSize, setPageSize] = useState('100');
  const [tutorFilter, setTutorFilter] = useState<string>('');
  const [tutorSearchOpen, setTutorSearchOpen] = useState(false);
  const [companyFilter, setCompanyFilter] = useState<string>('');
  const [companySearchOpen, setCompanySearchOpen] = useState(false);

  const { data: sales = [], isLoading } = useQuery<Sale[]>({
    queryKey: ['/api/sales'],
  });

  const { data: tutors = [] } = useQuery<Tutor[]>({
    queryKey: ['/api/tutors'],
  });

  const { data: companies = [] } = useQuery<Company[]>({
    queryKey: ['/api/companies-list'],
  });

  const sortedTutors = useMemo(() => {
    return tutors
      .filter(t => t.businessName)
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
    return sales.filter(sale => {
      if (tutorFilter && sale.tutorId?.toString() !== tutorFilter) return false;
      if (companyFilter && !sale.client?.toLowerCase().includes(selectedCompanyName.toLowerCase())) return false;
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
  }, [sales, tutorFilter, companyFilter, selectedCompanyName, search]);

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
      parseFloat(sale.listPrice || '0').toFixed(2)
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
      parseFloat(sale.listPrice || '0').toFixed(2)
    ]);
    
    const { utils, writeFile } = await import('xlsx');
    const ws = utils.aoa_to_sheet([headers, ...rows]);
    const wb = utils.book_new();
    utils.book_append_sheet(wb, ws, 'Vendite');
    writeFile(wb, `vendite_${format(new Date(), 'yyyy-MM-dd')}.xlsx`);
  };

  return (
    <div className="min-h-screen bg-gray-100">
      <div className="bg-black py-4 px-6">
        <h1 className="text-xl font-bold text-yellow-400" data-testid="text-page-title">
          Corsi Venduti
        </h1>
      </div>

      <div className="p-6">
        <div className="flex items-center justify-between mb-4 bg-yellow-400 p-3 rounded-lg gap-4 flex-wrap">
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex items-center gap-2">
              <span className="text-sm text-black font-medium">Show</span>
              <Select value={pageSize} onValueChange={setPageSize}>
                <SelectTrigger className="w-20 bg-white border-black" data-testid="select-page-size">
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
              <span className="text-sm text-black font-medium">Cerca:</span>
              <div className="relative">
                <Input
                  type="text"
                  placeholder="Admin, cliente, corso..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  className="w-48 h-8 text-sm bg-white text-black border-black pl-2 pr-8 rounded"
                  data-testid="input-search"
                />
                <Search className="absolute right-2 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-500" />
              </div>
            </div>
            <Popover open={tutorSearchOpen} onOpenChange={setTutorSearchOpen}>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  className="w-64 justify-between bg-white border-black text-black hover:bg-yellow-50"
                  data-testid="select-tutor-filter"
                >
                  {selectedTutorName || '--- Tutti gli Enti ---'}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-64 p-0 bg-white" align="start">
                <Command>
                  <CommandInput placeholder="Cerca ente..." className="text-black" />
                  <CommandList>
                    <CommandEmpty>Nessun ente trovato</CommandEmpty>
                    <CommandGroup>
                      <CommandItem
                        value="tutti"
                        onSelect={() => {
                          setTutorFilter('');
                          setTutorSearchOpen(false);
                        }}
                        className="text-black hover:bg-yellow-100 cursor-pointer font-bold"
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
                          className="text-black hover:bg-yellow-100 cursor-pointer"
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
            <Popover open={companySearchOpen} onOpenChange={setCompanySearchOpen}>
              <PopoverTrigger asChild>
                <Button
                  variant="outline"
                  role="combobox"
                  className="w-64 justify-between bg-white border-black text-black hover:bg-yellow-50"
                  data-testid="select-company-filter"
                >
                  {selectedCompanyName || '--- Tutte le Aziende ---'}
                  <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
              </PopoverTrigger>
              <PopoverContent className="w-64 p-0 bg-white" align="start">
                <Command>
                  <CommandInput placeholder="Cerca azienda..." className="text-black" />
                  <CommandList>
                    <CommandEmpty>Nessuna azienda trovata</CommandEmpty>
                    <CommandGroup>
                      <CommandItem
                        value="tutte"
                        onSelect={() => {
                          setCompanyFilter('');
                          setCompanySearchOpen(false);
                        }}
                        className="text-black hover:bg-yellow-100 cursor-pointer font-bold"
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
                          className="text-black hover:bg-yellow-100 cursor-pointer"
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
          <div className="flex items-center gap-3">
            <span className="text-black font-bold">
              Totale: {filteredSales.length} vendite
            </span>
            <Button 
              onClick={exportCSV}
              className="bg-white hover:bg-gray-100 text-black border border-black"
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
          </div>
        </div>

        <div className="bg-white rounded-lg shadow-lg border-2 border-black overflow-x-auto">
          <table className="w-full min-w-[1200px]" data-testid="table-sales">
            <thead className="bg-yellow-400">
              <tr>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">N. Ordine</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Cliente</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Ente Formativo</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Data Vendita</th>
                <th className="text-left p-3 text-xs font-bold text-black uppercase">Corso</th>
                <th className="text-center p-3 text-xs font-bold text-black uppercase">Qta</th>
                <th className="text-right p-3 text-xs font-bold text-black uppercase">Tuo Costo</th>
              </tr>
            </thead>
            <tbody>
              {isLoading ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-black">
                    Caricamento...
                  </td>
                </tr>
              ) : displayedSales.length === 0 ? (
                <tr>
                  <td colSpan={7} className="text-center py-8 text-black">
                    Nessuna vendita trovata
                  </td>
                </tr>
              ) : (
                displayedSales.map((sale) => (
                  <tr
                    key={sale.id}
                    className="bg-white border-b border-gray-200 hover:bg-yellow-50"
                    data-testid={`row-sale-${sale.id}`}
                  >
                    <td className="p-3 text-sm text-black font-medium">{sale.id}</td>
                    <td className="p-3 text-sm text-black max-w-[180px] truncate" title={sale.client}>
                      {sale.client}
                    </td>
                    <td className="p-3 text-sm text-black max-w-[180px]">
                      <div className="truncate" title={sale.tutorName}>
                        {sale.tutorName}
                      </div>
                      <div className="text-xs text-gray-500 mt-0.5">
                        ID {sale.adminId} - {sale.adminName}
                      </div>
                    </td>
                    <td className="p-3 text-sm text-black">{formatDate(sale.date)}</td>
                    <td className="p-3 text-sm text-black max-w-[250px]">
                      <div className="font-medium">
                        ID {sale.courseId} - {sale.courseName || '-'}
                      </div>
                      {sale.activatedStudents && (
                        <div className="text-xs text-gray-500 mt-0.5">
                          {sale.activatedStudents}
                        </div>
                      )}
                    </td>
                    <td className="p-3 text-sm text-center text-black">{sale.qty}</td>
                    <td className="p-3 text-sm text-right text-black font-medium">
                      {parseFloat(sale.listPrice || '0').toFixed(2)} €
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
