import { ReactNode, useState } from "react";
import { Link, useLocation } from "wouter";
import { useAuth } from "@/hooks/use-auth";
import {
  LayoutDashboard, BookOpen, Users, Building2, GraduationCap, Award,
  FileText, BarChart3, MessageSquare, Video, LogOut, Menu, X,
  Receipt, Bell,
} from "lucide-react";

const NAV_SECTIONS = [
  {
    title: "Principale",
    items: [
      { href: "/", label: "Dashboard", icon: LayoutDashboard },
      { href: "/catalog", label: "Catalogo Corsi", icon: BookOpen },
      { href: "/activated-courses", label: "Corsi Attivi", icon: GraduationCap },
    ],
  },
  {
    title: "Gestione",
    items: [
      { href: "/clients", label: "Elenco Clienti", icon: Building2 },
      { href: "/users", label: "Utenti", icon: Users },
      { href: "/certificates", label: "Attestati", icon: Award },
    ],
  },
  {
    title: "Amministrazione",
    items: [
      { href: "/sales", label: "Vendite", icon: FileText },
      { href: "/tracking", label: "Tracciamento", icon: BarChart3 },
      { href: "/feedback", label: "Feedback", icon: MessageSquare },
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
    <div className="min-h-screen flex bg-gray-100">
      {/* Sidebar */}
      <aside className={`fixed inset-y-0 left-0 z-40 w-[260px] bg-gradient-to-b from-gray-900 via-gray-900 to-gray-950 text-white transform transition-transform lg:translate-x-0 lg:static flex flex-col ${sidebarOpen ? "translate-x-0" : "-translate-x-full"}`}>
        {/* Logo */}
        <div className="px-5 py-5 border-b border-white/10">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-xl flex items-center justify-center text-black font-black text-lg shadow-lg shadow-yellow-500/30">T</div>
              <div>
                <div className="font-bold text-white text-sm tracking-wide">TUTOR 81</div>
                <div className="text-[10px] text-yellow-500/80 font-medium tracking-widest">LMS PLATFORM</div>
              </div>
            </div>
            <button onClick={() => setSidebarOpen(false)} className="lg:hidden text-gray-400 hover:text-white"><X size={20} /></button>
          </div>
        </div>

        {/* Navigation */}
        <nav className="flex-1 px-3 py-4 space-y-5 overflow-y-auto">
          {NAV_SECTIONS.map((section) => {
            const items = section.items.filter((item) => {
              if (item.href === "/clients" && user?.role === 2) return false;
              return true;
            });
            if (items.length === 0) return null;
            return (
              <div key={section.title}>
                <div className="text-[10px] font-bold text-gray-500 uppercase tracking-widest px-3 mb-2">{section.title}</div>
                <div className="space-y-0.5">
                  {items.map((item) => {
                    const Icon = item.icon;
                    const active = location === item.href || (item.href !== "/" && location.startsWith(item.href));
                    return (
                      <Link key={item.href} href={item.href} onClick={() => setSidebarOpen(false)}
                        className={`flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] font-medium transition-all ${
                          active
                            ? "bg-yellow-500 text-black shadow-lg shadow-yellow-500/20"
                            : "text-gray-400 hover:bg-white/5 hover:text-white"
                        }`}>
                        <Icon size={17} strokeWidth={active ? 2.5 : 2} />
                        {item.label}
                      </Link>
                    );
                  })}
                </div>
              </div>
            );
          })}
        </nav>

        {/* User + Logout */}
        <div className="px-3 py-4 border-t border-white/10">
          <div className="flex items-center gap-3 px-3 py-2 mb-2">
            <div className="w-9 h-9 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-full flex items-center justify-center text-xs font-black text-black shadow-md">
              {(user?.firstName?.[0] || "")}{(user?.lastName?.[0] || "")}
            </div>
            <div className="min-w-0">
              <div className="text-sm font-semibold text-white truncate">{user?.firstName} {user?.lastName}</div>
              <div className="text-[11px] text-gray-500 truncate">{user?.email}</div>
            </div>
          </div>
          <a href="/api/admin-logout" className="flex items-center gap-3 px-3 py-2.5 rounded-xl text-[13px] text-gray-500 hover:bg-white/5 hover:text-red-400 transition-all">
            <LogOut size={17} />
            Esci
          </a>
        </div>
      </aside>

      {/* Overlay */}
      {sidebarOpen && <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-30 lg:hidden" onClick={() => setSidebarOpen(false)} />}

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-16 bg-white/80 backdrop-blur-md border-b border-gray-200/80 flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
          <div className="flex items-center gap-4">
            <button onClick={() => setSidebarOpen(true)} className="lg:hidden text-gray-600 hover:text-gray-900"><Menu size={22} /></button>
            <div className="hidden lg:block">
              {user?.tutorName && (
                <div className="flex items-center gap-2">
                  <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
                  <span className="text-sm font-semibold text-gray-700">{user.tutorName}</span>
                </div>
              )}
            </div>
          </div>
          <div className="flex items-center gap-4">
            <button className="relative w-9 h-9 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
              <Bell size={16} className="text-gray-500" />
              <span className="absolute -top-0.5 -right-0.5 w-3.5 h-3.5 bg-red-500 rounded-full text-[8px] font-bold text-white flex items-center justify-center">3</span>
            </button>
            <div className="hidden sm:flex items-center gap-3 pl-4 border-l border-gray-200">
              <div className="text-right">
                <div className="text-sm font-semibold text-gray-800">{user?.firstName} {user?.lastName}</div>
                <div className="text-[11px] text-gray-400">{user?.role === 1000 ? "Super Admin" : "Admin"}</div>
              </div>
              <div className="w-9 h-9 bg-gradient-to-br from-gray-700 to-gray-900 rounded-xl flex items-center justify-center text-xs font-bold text-yellow-400 shadow-sm">
                {(user?.firstName?.[0] || "")}{(user?.lastName?.[0] || "")}
              </div>
            </div>
          </div>
        </header>
        <main className="flex-1 p-4 lg:p-8 overflow-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
