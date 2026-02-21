import { ReactNode, useState } from "react";
import { Link, useLocation } from "wouter";
import { useAuth } from "@/hooks/use-auth";
import {
  LayoutDashboard, BookOpen, Users, Building2, GraduationCap, Award,
  FileText, BarChart3, MessageSquare, Video, LogOut, Menu, X,
  Receipt, Shield, ShoppingCart, PlusCircle, Key, Search,
  Settings, Headphones,
} from "lucide-react";

const NAV_SECTIONS = [
  {
    title: "Home",
    items: [
      { href: "/", label: "Home Page", icon: LayoutDashboard },
      { href: "/tutors", label: "Enti Formativi", icon: Shield },
    ],
  },
  {
    title: "Vendita",
    items: [
      { href: "/create-company", label: "Crea Cliente", icon: PlusCircle },
      { href: "/catalog", label: "Vendi Corsi", icon: ShoppingCart },
      { href: "/sales", label: "Corsi Venduti", icon: FileText },
    ],
  },
  {
    title: "Corsi (LMS)",
    items: [
      { href: "/courses-online", label: "Online", icon: BookOpen },
      { href: "/courses-active", label: "Attivati", icon: GraduationCap },
      { href: "/courses-completed", label: "Completati", icon: Award },
    ],
  },
  {
    title: "Archivio",
    items: [
      { href: "/clients", label: "Elenco Clienti", icon: Building2 },
      { href: "/users", label: "Elenco Utenti", icon: Users },
      { href: "/certificates", label: "Attestati", icon: Award },
      { href: "/feedback", label: "Feedback", icon: MessageSquare },
      { href: "/tracking", label: "Tracciamento", icon: BarChart3 },
      { href: "/invoicing", label: "Fatturazione", icon: Receipt },
      { href: "/videoconference", label: "Videoconferenza", icon: Video },
    ],
  },
];

export default function Layout({ children }: { children: ReactNode }) {
  const { user } = useAuth();
  const [location] = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="min-h-screen flex bg-[#0a0a0a]">
      {/* Sidebar */}
      <aside className={`fixed inset-y-0 left-0 z-40 w-[240px] bg-black text-white transform transition-transform lg:translate-x-0 lg:static flex flex-col border-r border-white/5 ${sidebarOpen ? "translate-x-0" : "-translate-x-full"}`}>
        {/* Logo */}
        <div className="px-4 py-4 border-b border-white/5">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2.5">
              <div className="w-9 h-9 bg-yellow-500 rounded-lg flex items-center justify-center text-black font-black text-sm">T</div>
              <div>
                <div className="font-bold text-yellow-500 text-sm tracking-wide">TUTOR 81 LMS</div>
                <div className="text-[9px] text-gray-500 font-mono">v2.0.0-staging</div>
              </div>
            </div>
            <button onClick={() => setSidebarOpen(false)} className="lg:hidden text-gray-400 hover:text-white"><X size={18} /></button>
          </div>
        </div>

        {/* User info */}
        <div className="px-4 py-3 border-b border-white/5">
          {user?.tutorName && (
            <div className="text-[10px] font-bold text-yellow-500 uppercase truncate mb-2">{user.tutorName}</div>
          )}
          <div className="flex items-center gap-2.5">
            <div className="w-8 h-8 bg-yellow-500/20 rounded-full flex items-center justify-center text-xs font-bold text-yellow-500">
              {(user?.firstName?.[0] || "")}{(user?.lastName?.[0] || "")}
            </div>
            <div className="min-w-0">
              <div className="text-xs font-bold text-white truncate">{user?.firstName} {user?.lastName}</div>
              <div className="text-[10px] text-gray-500 truncate">{user?.role === 1000 ? "Super Admin" : "Amministratore"}</div>
            </div>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-2 py-3 space-y-4 overflow-y-auto">
          {NAV_SECTIONS.map((section) => {
            const items = section.items.filter((item) => {
              if (item.href === "/tutors" && (user?.role ?? 0) < 1000) return false;
              if (item.href === "/clients" && user?.role === 2) return false;
              return true;
            });
            if (items.length === 0) return null;
            return (
              <div key={section.title}>
                <div className="text-[9px] font-bold text-gray-600 uppercase tracking-widest px-3 mb-1">{section.title}</div>
                <div className="space-y-px">
                  {items.map((item) => {
                    const Icon = item.icon;
                    const active = location === item.href || (item.href !== "/" && location.startsWith(item.href));
                    return (
                      <Link key={item.href} href={item.href} onClick={() => setSidebarOpen(false)}
                        className={`flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] transition-all ${
                          active
                            ? "bg-yellow-500/10 text-yellow-500 font-semibold"
                            : "text-gray-400 hover:bg-white/5 hover:text-gray-200"
                        }`}>
                        <Icon size={15} strokeWidth={active ? 2.5 : 1.5} />
                        {item.label}
                      </Link>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </nav>

        {/* Logout */}
        <div className="px-2 py-3 border-t border-white/5">
          <a href="/api/admin-logout" className="flex items-center gap-2.5 px-3 py-2 rounded-lg text-[12px] text-gray-500 hover:bg-white/5 hover:text-red-400 transition-all">
            <LogOut size={15} />
            Esci
          </a>
        </div>
      </aside>

      {/* Overlay */}
      {sidebarOpen && <div className="fixed inset-0 bg-black/70 z-30 lg:hidden" onClick={() => setSidebarOpen(false)} />}

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-12 bg-[#111] border-b border-white/5 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20">
          <div className="flex items-center gap-3">
            <button onClick={() => setSidebarOpen(true)} className="lg:hidden text-gray-400 hover:text-white"><Menu size={20} /></button>
            <div className="hidden lg:flex items-center gap-2 bg-white/5 rounded-lg px-3 py-1.5">
              <Search size={13} className="text-gray-500" />
              <input type="text" placeholder="Cerca Utente..." className="bg-transparent border-none outline-none text-xs text-gray-300 placeholder-gray-600 w-48" />
            </div>
          </div>
          <div className="flex items-center gap-3">
            <button className="flex items-center gap-1.5 text-xs text-gray-400 hover:text-yellow-500 transition-colors">
              <Headphones size={14} />
              <span className="hidden sm:inline">Assistenza</span>
            </button>
          </div>
        </header>
        <main className="flex-1 p-4 lg:p-6 overflow-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
