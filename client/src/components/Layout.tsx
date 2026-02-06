import { useState, useRef, useEffect } from 'react';
import { Link, useLocation } from 'wouter';
import { useAuth } from '@/hooks/use-auth';
import { useQuery } from '@tanstack/react-query';
import * as Icons from 'lucide-react';
import EnvironmentBanner from '@/components/EnvironmentBanner';

interface LayoutProps {
  children: React.ReactNode;
}

interface User {
  id: number;
  firstName: string | null;
  lastName: string | null;
  email: string | null;
  fiscalCode: string | null;
  companyName: string | null;
}

const getRoleName = (role: number | null | undefined): string => {
  switch (role) {
    case 1000: return 'SUPER ADMIN';
    case 1: return 'VENDITORE';
    case 2: return 'REFERENTE';
    default: return 'UTENTE';
  }
};

const getRoleColor = (role: number | null | undefined): string => {
  switch (role) {
    case 1000: return 'text-red-400';
    case 1: return 'text-yellow-400';
    case 2: return 'text-blue-400';
    default: return 'text-gray-400';
  }
};

export default function Layout({ children }: LayoutProps) {
  const [location, setLocation] = useLocation();
  const { user, logout } = useAuth();
  const userRole = user?.role ?? 0;
  const [searchTerm, setSearchTerm] = useState('');
  const [showResults, setShowResults] = useState(false);
  const searchRef = useRef<HTMLDivElement>(null);

  // Cerca utenti
  const { data: searchResults = [] } = useQuery<User[]>({
    queryKey: ['/api/users/search', searchTerm],
    queryFn: async () => {
      if (searchTerm.length < 2) return [];
      const res = await fetch(`/api/users/search?q=${encodeURIComponent(searchTerm)}`);
      if (!res.ok) return [];
      return res.json();
    },
    enabled: searchTerm.length >= 2,
  });

  // Chiudi dropdown quando si clicca fuori
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (searchRef.current && !searchRef.current.contains(event.target as Node)) {
        setShowResults(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, []);

  const handleUserClick = (userId: string) => {
    setShowResults(false);
    setSearchTerm('');
    setLocation(`/users?userId=${userId}`);
  };

  const getIcon = (name: keyof typeof Icons) => {
    const IconComponent = Icons[name] as React.ComponentType<{ size?: number }>;
    return IconComponent ? <IconComponent size={18} /> : null;
  };

  const MenuItem = ({ to, label, color = 'text-gray-400', badge, iconName }: {
    to: string;
    label: string;
    color?: string;
    badge?: string | number;
    iconName: keyof typeof Icons;
  }) => (
    <li>
      <Link 
        href={to} 
        className={`block py-2 px-4 text-sm hover:text-yellow-500 transition-colors ${location === to ? 'text-yellow-500 font-bold bg-yellow-500/10 border-l-4 border-yellow-500' : color} flex justify-between items-center`}
        data-testid={`nav-${to.replace(/\//g, '-')}`}
      >
        <div className="flex items-center gap-3">
          {getIcon(iconName)}
          {label}
        </div>
        {badge && <span className="bg-red-600 text-white text-xs px-1.5 rounded-full">{badge}</span>}
      </Link>
    </li>
  );

  const SectionHeader = ({ title }: { title: string }) => (
    <div className="px-4 py-2 mt-4 text-gray-500 uppercase text-[10px] font-bold tracking-widest">
      {title}
    </div>
  );

  const isSuperAdmin = userRole === 1000;
  const isVenditore = userRole === 1;
  const isReferente = userRole === 2;

  return (
    <div className='flex h-screen bg-black font-sans text-gray-300'>
      
      <div className={`w-64 bg-black border-r border-gray-800 flex flex-col flex-shrink-0 overflow-hidden`}> 
        
        <div className="p-5 border-b border-gray-800">
          <h1 className="font-bold text-lg text-yellow-500 uppercase tracking-wide">TUTOR 81 LMS</h1>
          <div className="text-sm text-gray-400 mt-2 font-bold uppercase truncate">
            {user?.firstName} {user?.lastName}
          </div>
          {isVenditore && (user as any)?.tutorName && (
            <div className="text-xs text-yellow-400 mt-1 truncate">
              {(user as any).tutorName}
            </div>
          )}
        </div>

        <div className="p-5 flex items-center gap-3 border-b border-gray-800">
          <div className="w-10 h-10 rounded-full bg-yellow-500 flex items-center justify-center text-black font-bold overflow-hidden">
            {user?.profileImageUrl ? (
              <img src={user.profileImageUrl} alt="" className="w-full h-full object-cover" />
            ) : (
              user?.firstName?.charAt(0) || 'U'
            )}
          </div>
          <div className="overflow-hidden">
            <div className="text-[10px] uppercase font-bold tracking-wider text-yellow-500">
              {getRoleName(userRole)}
            </div>
            <div className="text-xs truncate text-gray-400">{user?.email || 'assistenza@tutor81.it'}</div>
            <div className="flex gap-2 mt-1">
              <Icons.LogOut 
                size={12} 
                className="cursor-pointer text-gray-400 hover:text-yellow-500" 
                onClick={() => logout()}
                data-testid="button-logout"
              />
            </div>
          </div>
        </div>
        
        <nav className='flex-1 overflow-y-auto py-2'>
          <ul className='space-y-0.5'>
            
            <SectionHeader title="HOME" />
            {isSuperAdmin && (
              <MenuItem to="/superadmin" label="Ingresso Super Admin" iconName="Sparkles" />
            )}
            <MenuItem to="/dashboard" label="Home Page" iconName="Home" />
            
            {/* Super Admin: vede tutto */}
            {isSuperAdmin && (
              <>
                <MenuItem to="/tutors" label="Enti Formativi" iconName="Building" />
                <MenuItem to="/clients" label="Aziende Clienti" iconName="Users" />
              </>
            )}
            
            {/* Venditore (Admin Tutor): menu completo ente formativo */}
            {isVenditore && (
              <>
                <SectionHeader title="CLIENTI" />
                <MenuItem to="/clients" label="Aziende Clienti" iconName="Building2" />
                <MenuItem to="/companies/new" label="Crea Cliente" iconName="UserPlus" />
                
                <SectionHeader title="VENDITA" />
                <MenuItem to="/catalog" label="Invia avvio corso" iconName="ShoppingCart" />
                <MenuItem to="/sales" label="Corsi Venduti" iconName="FileText" />
                
                <SectionHeader title="CORSI (LMS)" />
                <MenuItem to="/courses/active" label="In attività" iconName="Activity" />
                <MenuItem to="/certificates" label="Attestati" iconName="CheckCircle" />
                
                <SectionHeader title="ARCHIVIO" />
                <MenuItem to="/users" label="Elenco Utenti" iconName="User" />
                <MenuItem to="/users/import" label="Importa Utenti" iconName="Upload" />
                
                <SectionHeader title="STRUMENTI" />
                <MenuItem to="/videoconference" label="Videoconferenza" color="text-red-500" iconName="Video" />
                <MenuItem to="/tracking" label="Tracciamento" iconName="BarChart2" />
                <MenuItem to="/feedback" label="Feedback" iconName="MessageSquare" />
              </>
            )}
            
            {/* Referente: vede la sua azienda */}
            {isReferente && (
              <MenuItem to="/my-company" label="La Mia Azienda" iconName="Building2" />
            )}

            {/* Super Admin: menu completo */}
            {isSuperAdmin && (
              <>
                <SectionHeader title="VENDITA" />
                <MenuItem to="/companies/new" label="Crea Cliente" iconName="UserPlus" />
                <MenuItem to="/catalog" label="Invia avvio corso" iconName="ShoppingCart" />
                <MenuItem to="/sales" label="Corsi Venduti" iconName="FileText" />
                <MenuItem to="/invoicing" label="Fatturazione" iconName="Receipt" />

                <SectionHeader title="CORSI (LMS)" />
                <MenuItem to="/courses/active" label="In attività" iconName="Activity" />
                <MenuItem to="/certificates" label="Attestati" iconName="CheckCircle" />
                <MenuItem to="/courses/expiring" label="Da Ripetere" iconName="Clock" />

                <SectionHeader title="ARCHIVIO" />
                <MenuItem to="/users" label="Elenco Utenti" iconName="User" />
                <MenuItem to="/users/import" label="Importa Utenti" iconName="Upload" />
                
                <SectionHeader title="STRUMENTI" />
                <MenuItem to="/videoconference" label="Videoconferenza" color="text-red-500" iconName="Video" />
                <MenuItem to="/tracking" label="Tracciamento" iconName="BarChart2" />
                <MenuItem to="/feedback" label="Feedback" iconName="MessageSquare" />
                
                <SectionHeader title="CONTENT MANAGEMENT" />
                <MenuItem to="/content-management" label="Gestione Contenuti" iconName="Film" />
              </>
            )}
            
            {/* Referente: gestisce utenti della sua azienda */}
            {isReferente && (
              <>
                <SectionHeader title="UTENTI AZIENDA" />
                <MenuItem to="/company-users" label="Utenti Azienda" iconName="Users" />
              </>
            )}

          </ul>
        </nav>
      </div>

      <div className='flex-1 flex flex-col overflow-hidden bg-black'>

        <EnvironmentBanner />
        
        <header className='h-16 border-b border-gray-800 flex justify-between items-center px-6 bg-black'>
          
          <div className="relative w-[600px]" ref={searchRef}>
            <Icons.Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 z-10" size={16} />
            <input 
              type="text" 
              placeholder="Cerca Utente.." 
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setShowResults(true);
              }}
              onFocus={() => setShowResults(true)}
              className="w-full bg-[#1e1e1e] border border-gray-700 rounded-md py-2 pl-10 pr-4 text-sm text-gray-300 focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500"
              data-testid="input-search"
            />
            {showResults && searchTerm.length >= 2 && (
              <div className="absolute top-full left-0 right-0 mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-80 overflow-y-auto z-50">
                {searchResults.length === 0 ? (
                  <div className="p-3 text-gray-500 text-sm">Nessun utente trovato</div>
                ) : (
                  searchResults.slice(0, 10).map((u) => (
                    <div
                      key={u.id}
                      onClick={() => handleUserClick(String(u.id))}
                      className="p-3 hover:bg-yellow-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                    >
                      <div className="font-medium text-black text-sm">
                        {u.id} - {u.firstName} {u.lastName} {u.companyName ? `- ${u.companyName}` : ''}
                      </div>
                      <div className="text-xs text-gray-500">{u.email}</div>
                    </div>
                  ))
                )}
              </div>
            )}
          </div>

          <div className="flex items-center gap-4 text-sm font-medium text-gray-400">
            <div className="flex items-center gap-2 cursor-pointer hover:text-white">
              <Icons.HelpCircle size={16} />
              <span>Assistenza</span>
            </div>
          </div>
        </header>

        <main className='flex-1 overflow-auto p-0 bg-black'>
          {children}
        </main>
      </div>
    </div>
  );
}
