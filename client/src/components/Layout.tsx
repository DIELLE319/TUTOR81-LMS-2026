import { Link, useLocation } from 'wouter';
import { useAuth } from '@/hooks/use-auth';
import * as Icons from 'lucide-react';

interface LayoutProps {
  children: React.ReactNode;
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
  const [location] = useLocation();
  const { user, logout } = useAuth();
  const userRole = user?.role ?? 0;

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
        className={`block py-2 px-4 text-sm hover:text-white transition-colors ${color} ${location === to ? 'text-white font-bold bg-[#1e1e1e] border-l-4 border-yellow-500' : ''} flex justify-between items-center`}
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
    <div className="px-4 py-2 mt-4 text-gray-600 uppercase text-[10px] font-bold tracking-widest">
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
          <div className="text-sm text-white mt-2 font-bold uppercase truncate">
            {user?.firstName} {user?.lastName}
          </div>
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
            <div className={`text-[10px] uppercase font-bold tracking-wider ${getRoleColor(userRole)}`}>
              {getRoleName(userRole)}
            </div>
            <div className="text-xs truncate text-gray-400">{user?.email || 'assistenza@tutor81.it'}</div>
            <div className="flex gap-2 mt-1">
              <Icons.LogOut 
                size={12} 
                className="cursor-pointer hover:text-white" 
                onClick={() => logout()}
                data-testid="button-logout"
              />
            </div>
          </div>
        </div>
        
        <nav className='flex-1 overflow-y-auto py-2'>
          <ul className='space-y-0.5'>
            
            <SectionHeader title="HOME" />
            <MenuItem to="/dashboard" label="Home Page" iconName="Home" />
            
            {/* Super Admin: vede tutto */}
            {isSuperAdmin && (
              <>
                <MenuItem to="/tutors" label="Enti Formativi" iconName="Building" />
                <MenuItem to="/clients" label="Aziende Clienti" iconName="Users" />
              </>
            )}
            
            {/* Venditore: vede solo le sue aziende clienti */}
            {isVenditore && (
              <MenuItem to="/clients" label="Le Mie Aziende" iconName="Users" />
            )}
            
            {/* Referente: vede la sua azienda */}
            {isReferente && (
              <MenuItem to="/my-company" label="La Mia Azienda" iconName="Building2" />
            )}

            {/* VENDITA - Solo Super Admin e Venditore */}
            {(isSuperAdmin || isVenditore) && (
              <>
                <SectionHeader title="VENDITA" />
                <MenuItem to="/companies/new" label="Crea Cliente" color="text-gray-300" iconName="UserPlus" />
                <MenuItem to="/catalog" label="Iscrivi ai Corsi" color="text-gray-300" iconName="ShoppingCart" />
                <MenuItem to="/sales" label="Corsi Venduti" iconName="FileText" />
                <MenuItem to="/invoicing" label="Fatturazione" iconName="Receipt" />
              </>
            )}

            {/* CORSI LMS - Tutti vedono */}
            <SectionHeader title="CORSI (LMS)" />
            <MenuItem to="/courses/active" label="Attivati" iconName="Activity" />
            <MenuItem to="/certificates" label="Attestati" iconName="CheckCircle" />
            <MenuItem to="/courses/expiring" label="Da Ripetere" iconName="Clock" />

            {/* ARCHIVIO - Solo Super Admin e Venditore */}
            {(isSuperAdmin || isVenditore) && (
              <>
                <SectionHeader title="ARCHIVIO" />
                <MenuItem to="/users" label="Elenco Utenti" iconName="User" />
                <MenuItem to="/users/import" label="Importa Utenti" iconName="Upload" />
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
        
        <header className='h-16 border-b border-gray-800 flex justify-between items-center px-6 bg-black'>
          
          <div className="relative w-96">
            <Icons.Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-600" size={16} />
            <input 
              type="text" 
              placeholder="Cerca Utente.." 
              className="w-full bg-[#1e1e1e] border border-gray-700 rounded-md py-2 pl-10 pr-4 text-sm text-gray-300 focus:outline-none focus:border-gray-500"
              data-testid="input-search"
            />
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
