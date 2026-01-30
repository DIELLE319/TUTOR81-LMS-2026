import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { motion } from 'framer-motion';
import { Search, FileText, Calendar, User, Building, ShoppingCart } from 'lucide-react';

interface Sale {
  id: number;
  user: string;
  client: string;
  date: string;
  course: string;
  qty: number;
  listPrice: number;
  tutorName: string;
}

export default function Sales() {
  const [searchTerm, setSearchTerm] = useState('');

  const { data: sales = [], isLoading } = useQuery<Sale[]>({
    queryKey: ['/api/sales'],
  });

  const filteredSales = sales.filter(s => 
    s.client?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    s.course?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    s.user?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const formatDate = (dateStr: string) => {
    if (!dateStr) return 'N/A';
    const date = new Date(dateStr);
    return date.toLocaleDateString('it-IT');
  };

  const formatPrice = (price: number) => {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR' }).format(price);
  };

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white" data-testid="text-sales-title">Corsi Venduti</h1>
          <p className="text-gray-500 text-sm">Storico delle vendite corsi</p>
        </div>
      </div>

      <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-4 mb-6">
        <div className="relative">
          <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={18} />
          <input
            type="text"
            placeholder="Cerca per cliente, corso o utente..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="w-full bg-black border border-gray-700 rounded-lg py-3 pl-10 pr-4 text-white placeholder-gray-600 focus:border-yellow-500 focus:outline-none"
            data-testid="input-search-sales"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto"></div>
          <p className="text-gray-500 mt-4">Caricamento...</p>
        </div>
      ) : filteredSales.length === 0 ? (
        <div className="text-center py-12 bg-[#1e1e1e] rounded-xl border border-gray-800">
          <ShoppingCart size={48} className="mx-auto text-gray-600 mb-4" />
          <h3 className="text-lg font-bold text-white mb-2">Nessuna vendita</h3>
          <p className="text-gray-500">Le vendite appariranno qui</p>
        </div>
      ) : (
        <div className="bg-[#1e1e1e] rounded-xl border border-gray-800 overflow-hidden">
          <table className="w-full">
            <thead className="bg-black/50 border-b border-gray-800">
              <tr>
                <th className="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">ID</th>
                <th className="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Data</th>
                <th className="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Cliente</th>
                <th className="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Corso</th>
                <th className="text-left py-3 px-4 text-xs font-bold text-gray-500 uppercase">Utente</th>
                <th className="text-center py-3 px-4 text-xs font-bold text-gray-500 uppercase">Qty</th>
                <th className="text-right py-3 px-4 text-xs font-bold text-gray-500 uppercase">Prezzo</th>
              </tr>
            </thead>
            <tbody>
              {filteredSales.map((sale, index) => (
                <motion.tr
                  key={sale.id}
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: index * 0.02 }}
                  className="border-b border-gray-800/50 hover:bg-gray-800/30"
                  data-testid={`row-sale-${sale.id}`}
                >
                  <td className="py-3 px-4 text-sm text-gray-400">#{sale.id}</td>
                  <td className="py-3 px-4 text-sm text-gray-300">
                    <div className="flex items-center gap-2">
                      <Calendar size={14} className="text-gray-600" />
                      {formatDate(sale.date)}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-sm text-white font-medium">
                    <div className="flex items-center gap-2">
                      <Building size={14} className="text-blue-500" />
                      {sale.client}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-sm text-gray-300">
                    <div className="flex items-center gap-2">
                      <FileText size={14} className="text-yellow-500" />
                      {sale.course}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-sm text-gray-400">
                    <div className="flex items-center gap-2">
                      <User size={14} className="text-gray-600" />
                      {sale.user}
                    </div>
                  </td>
                  <td className="py-3 px-4 text-sm text-center text-gray-300">{sale.qty}</td>
                  <td className="py-3 px-4 text-sm text-right text-green-500 font-medium">{formatPrice(sale.listPrice)}</td>
                </motion.tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
