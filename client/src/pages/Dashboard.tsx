import { motion } from 'framer-motion';
import * as Icons from 'lucide-react';
import { useQuery } from '@tanstack/react-query';

export default function Dashboard() {
  const { data: stats } = useQuery<{ tutors: number; clients: number; sales: number; users: number }>({
    queryKey: ['/api/stats'],
  });

  return (
    <div className="space-y-6 font-sans p-4 bg-black min-h-screen text-white">
      
      <div className="flex justify-between items-end mb-8">
        <div>
          <h1 className="text-3xl font-bold text-white mb-1" data-testid="text-dashboard-title">Dashboard LMS</h1>
          <p className="text-gray-400">Benvenuto nella piattaforma Tutor81</p>
        </div>
        <div className="text-right text-gray-500 text-sm">
          {new Date().toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.1 }}
          className="bg-[#00b894] rounded-xl p-6 shadow-lg h-40 relative overflow-hidden flex flex-col justify-between"
          data-testid="card-stat-tutors"
        >
          <div className="w-10 h-10 rounded-full border-2 border-white/30 flex items-center justify-center text-white mb-2">
            <Icons.Building size={20} />
          </div>
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-white/90">ENTI FORMATIVI</p>
            <h3 className="text-4xl font-bold text-white">{stats?.tutors ?? 0}</h3>
          </div>
        </motion.div>

        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.2 }}
          className="bg-[#0984e3] rounded-xl p-6 shadow-lg h-40 relative overflow-hidden flex flex-col justify-between"
          data-testid="card-stat-clients"
        >
          <div className="w-10 h-10 rounded-full border-2 border-white/30 flex items-center justify-center text-white mb-2">
            <Icons.Users size={20} />
          </div>
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-white/90">AZIENDE CLIENTI</p>
            <h3 className="text-4xl font-bold text-white">{stats?.clients ?? 0}</h3>
          </div>
        </motion.div>

        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.3 }}
          className="bg-[#e17055] rounded-xl p-6 shadow-lg h-40 relative overflow-hidden flex flex-col justify-between"
          data-testid="card-stat-sales"
        >
          <div className="w-10 h-10 rounded-full border-2 border-white/30 flex items-center justify-center text-white mb-2">
            <Icons.ShoppingCart size={20} />
          </div>
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-white/90">CORSI VENDUTI</p>
            <h3 className="text-4xl font-bold text-white">{stats?.sales ?? 0}</h3>
          </div>
        </motion.div>

        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.4 }}
          className="bg-[#6c5ce7] rounded-xl p-6 shadow-lg h-40 relative overflow-hidden flex flex-col justify-between"
          data-testid="card-stat-users"
        >
          <div className="w-10 h-10 rounded-full border-2 border-white/30 flex items-center justify-center text-white mb-2">
            <Icons.User size={20} />
          </div>
          <div>
            <p className="text-xs font-bold uppercase tracking-wider text-white/90">UTENTI TOTALI</p>
            <h3 className="text-4xl font-bold text-white">{stats?.users ?? 0}</h3>
          </div>
        </motion.div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
        
        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.5 }}
          className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-6 min-h-[400px]"
        >
          <div className="flex justify-between items-center mb-6">
            <h2 className="text-lg font-bold text-white">Andamento Attivazioni</h2>
            <button className="bg-gray-700 text-xs px-3 py-1 rounded text-gray-300">Ultimi 30 giorni</button>
          </div>
          
          <div className="w-full h-[300px] border border-dashed border-gray-700 rounded flex items-center justify-center text-gray-600">
            Grafico attivazioni in arrivo...
          </div>
        </motion.div>

        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.6 }}
          className="bg-white rounded-xl p-6 text-center text-gray-800 shadow-lg flex flex-col justify-center"
        >
          <h3 className="text-xs font-bold uppercase text-gray-400 mb-4 tracking-widest">I TUOI DATI</h3>
          
          <h2 className="text-xl font-bold text-gray-900 mb-1">TUTOR 81 LMS</h2>
          <p className="text-xs text-blue-600 font-medium mb-4">assistenza@tutor81.it</p>
          
          <div className="text-xs text-gray-500 space-y-1 mb-6">
            <p>Via Mazzolari, 45 - Gussago</p>
          </div>

          <div className="border-t border-gray-100 pt-4">
            <h3 className="text-xs font-bold uppercase text-gray-400 mb-3 tracking-widest">LA TUA LICENZA</h3>
            
            <div className="text-sm font-bold text-gray-800 mb-2">ENTI AUTORIZZATI</div>
            
            <div className="space-y-1 text-xs">
              <div className="flex justify-between px-4">
                <span className="text-gray-500">Scade il:</span>
                <span className="font-bold text-red-500">28/09/2030</span>
              </div>
              <div className="flex justify-between px-4 mt-2">
                <span className="text-gray-500">Sconto:</span>
                <span className="bg-yellow-100 text-yellow-800 px-2 rounded font-bold">70%</span>
              </div>
            </div>
          </div>
        </motion.div>

        <motion.div 
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ delay: 0.7 }}
          className="bg-[#1e1e1e] rounded-xl border border-gray-800 p-6 flex flex-col justify-center"
        >
          <h2 className="text-sm font-bold text-gray-300 uppercase mb-6 flex items-center gap-2">
            <Icons.BarChart2 size={16} className="text-yellow-500" /> STATO RISORSE
          </h2>
          
          <div className="space-y-6">
            <div>
              <div className="flex justify-between text-xs mb-1 text-gray-400">
                <span>Licenze Utilizzate</span>
                <span className="font-bold text-white">75%</span>
              </div>
              <div className="w-full bg-gray-700 rounded-full h-1.5">
                <div className="bg-green-500 h-1.5 rounded-full" style={{ width: '75%' }}></div>
              </div>
            </div>

            <div>
              <div className="flex justify-between text-xs mb-1 text-gray-400">
                <span>Spazio Archiviazione</span>
                <span className="font-bold text-white">45%</span>
              </div>
              <div className="w-full bg-gray-700 rounded-full h-1.5">
                <div className="bg-yellow-500 h-1.5 rounded-full" style={{ width: '45%' }}></div>
              </div>
            </div>

            <div>
              <div className="flex justify-between text-xs mb-1 text-gray-400">
                <span>Utenti Attivi</span>
                <span className="font-bold text-white">90%</span>
              </div>
              <div className="w-full bg-gray-700 rounded-full h-1.5">
                <div className="bg-red-500 h-1.5 rounded-full" style={{ width: '90%' }}></div>
              </div>
            </div>
          </div>
        </motion.div>

      </div>
    </div>
  );
}
