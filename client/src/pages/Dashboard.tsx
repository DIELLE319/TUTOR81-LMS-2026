import { Building, Users, BookOpen, GraduationCap, ShoppingCart } from 'lucide-react';
import { useQuery } from '@tanstack/react-query';
import { useAuth } from '@/hooks/use-auth';

interface Stats {
  tutors: number;
  companies: number;
  students: number;
  enrollments: number;
  courses: number;
}

function StatCard({ icon: Icon, label, value, color }: {
  icon: React.ComponentType<{ size?: number | string; className?: string }>;
  label: string;
  value: number;
  color: string;
}) {
  return (
    <div className={`${color} rounded-xl p-6 shadow-lg h-36 flex flex-col justify-between`} >
      <div className="w-9 h-9 rounded-full border-2 border-white/30 flex items-center justify-center text-white">
        <Icon size={18} />
      </div>
      <div>
        <p className="text-[11px] font-bold uppercase tracking-wider text-white/80">{label}</p>
        <h3 className="text-3xl font-bold text-white">{value}</h3>
      </div>
    </div>
  );
}

export default function Dashboard() {
  const { user } = useAuth();
  const { data: stats, isLoading } = useQuery<Stats>({
    queryKey: ['/api/stats'],
  });

  return (
    <div className="p-6 bg-black min-h-screen text-white space-y-8">
      
      <div className="flex justify-between items-end">
        <div>
          <h1 className="text-2xl font-bold" data-testid="text-dashboard-title">
            Ciao, {user?.firstName || 'Utente'}
          </h1>
          <p className="text-gray-500 text-sm mt-1">
            {new Date().toLocaleDateString('it-IT', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })}
          </p>
        </div>
      </div>

      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          {[...Array(5)].map((_, i) => (
            <div key={i} className="bg-zinc-900 rounded-xl h-36 animate-pulse" />
          ))}
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
          <StatCard icon={Building} label="Enti Formativi" value={stats?.tutors ?? 0} color="bg-emerald-600" />
          <StatCard icon={Users} label="Aziende Clienti" value={stats?.companies ?? 0} color="bg-blue-600" />
          <StatCard icon={GraduationCap} label="Corsisti" value={stats?.students ?? 0} color="bg-violet-600" />
          <StatCard icon={ShoppingCart} label="Iscrizioni" value={stats?.enrollments ?? 0} color="bg-orange-600" />
          <StatCard icon={BookOpen} label="Corsi Catalogo" value={stats?.courses ?? 0} color="bg-pink-600" />
        </div>
      )}

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-6">
          <h2 className="text-sm font-bold text-gray-300 uppercase mb-4">Azioni rapide</h2>
          <div className="grid grid-cols-2 gap-3">
            <a href="/catalog" className="flex items-center gap-3 bg-zinc-800 hover:bg-yellow-500/10 border border-zinc-700 hover:border-yellow-500/50 rounded-lg p-4 transition-colors">
              <ShoppingCart size={20} className="text-yellow-500" />
              <span className="text-sm font-medium">Vendi Corso</span>
            </a>
            <a href="/clients" className="flex items-center gap-3 bg-zinc-800 hover:bg-blue-500/10 border border-zinc-700 hover:border-blue-500/50 rounded-lg p-4 transition-colors">
              <Users size={20} className="text-blue-400" />
              <span className="text-sm font-medium">Aziende</span>
            </a>
            <a href="/courses/active" className="flex items-center gap-3 bg-zinc-800 hover:bg-emerald-500/10 border border-zinc-700 hover:border-emerald-500/50 rounded-lg p-4 transition-colors">
              <BookOpen size={20} className="text-emerald-400" />
              <span className="text-sm font-medium">Corsi Attivi</span>
            </a>
            <a href="/certificates" className="flex items-center gap-3 bg-zinc-800 hover:bg-violet-500/10 border border-zinc-700 hover:border-violet-500/50 rounded-lg p-4 transition-colors">
              <GraduationCap size={20} className="text-violet-400" />
              <span className="text-sm font-medium">Attestati</span>
            </a>
          </div>
        </div>

        <div className="bg-zinc-900 rounded-xl border border-zinc-800 p-6">
          <h2 className="text-sm font-bold text-gray-300 uppercase mb-4">Informazioni piattaforma</h2>
          <div className="space-y-3 text-sm">
            <div className="flex justify-between text-gray-400">
              <span>Piattaforma</span>
              <span className="text-white font-medium">Tutor81 LMS v2</span>
            </div>
            <div className="flex justify-between text-gray-400">
              <span>Email supporto</span>
              <span className="text-yellow-400">assistenza@tutor81.it</span>
            </div>
            <div className="flex justify-between text-gray-400">
              <span>Ruolo</span>
              <span className="text-white font-medium">
                {user?.role === 1000 ? 'Super Admin' : user?.role === 1 ? 'Venditore' : user?.role === 2 ? 'Referente' : 'Utente'}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
