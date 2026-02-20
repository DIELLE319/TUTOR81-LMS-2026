import { ReactNode, useState } from "react";
import { Link, useLocation } from "wouter";
import { useAuth } from "@/hooks/use-auth";
import { LayoutDashboard, BookOpen, Users, Building2, GraduationCap, Award, FileText, BarChart3, MessageSquare, Video, LogOut, Menu, X, ChevronDown } from "lucide-react";

const NAV_ITEMS = [
  { href: "/", label: "Dashboard", icon: LayoutDashboard },
  { href: "/catalog", label: "Catalogo", icon: BookOpen },
  { href: "/activated-courses", label: "Corsi Attivi", icon: GraduationCap },
  { href: "/clients", label: "Elenco Clienti", icon: Building2 },
  { href: "/users", label: "Utenti", icon: Users },
  { href: "/certificates", label: "Attestati", icon: Award },
  { href: "/sales", label: "Vendite", icon: FileText },
  { href: "/tracking", label: "Tracciamento", icon: BarChart3 },
  { href: "/feedback", label: "Feedback", icon: MessageSquare },
  { href: "/invoicing", label: "Fatturazione", icon: FileText },
  { href: "/videoconference", label: "Videoconferenza", icon: Video },
];

export default function Layout({ children }: { children: ReactNode }) {
  const { user } = useAuth();
  const [location] = useLocation();
  const [sidebarOpen, setSidebarOpen] = useState(false);

  const filteredNav = NAV_ITEMS.filter((item) => {
    if (item.href === "/clients" && user?.role === 2) return false;
    return true;
  });

  return (
    <div className="min-h-screen flex bg-gray-50">
      {/* Sidebar */}
      <aside className={`fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-white transform transition-transform lg:translate-x-0 lg:static ${sidebarOpen ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="flex items-center justify-between h-16 px-4 border-b border-gray-800">
          <div className="flex items-center gap-2">
            <div className="w-8 h-8 bg-yellow-500 rounded flex items-center justify-center text-black font-black text-sm">T</div>
            <span className="font-bold text-yellow-500">TUTOR 81</span>
          </div>
          <button onClick={() => setSidebarOpen(false)} className="lg:hidden text-gray-400 hover:text-white"><X size={20} /></button>
        </div>
        <nav className="p-3 space-y-1 overflow-y-auto" style={{ maxHeight: "calc(100vh - 8rem)" }}>
          {filteredNav.map((item) => {
            const Icon = item.icon;
            const active = location === item.href || (item.href !== "/" && location.startsWith(item.href));
            return (
              <Link key={item.href} href={item.href} onClick={() => setSidebarOpen(false)}
                className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors ${active ? "bg-yellow-500/20 text-yellow-400" : "text-gray-300 hover:bg-gray-800 hover:text-white"}`}>
                <Icon size={18} />
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="absolute bottom-0 left-0 right-0 p-3 border-t border-gray-800">
          <a href="/api/admin-logout" className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-400 hover:bg-gray-800 hover:text-white">
            <LogOut size={18} />
            Esci
          </a>
        </div>
      </aside>

      {/* Overlay */}
      {sidebarOpen && <div className="fixed inset-0 bg-black/50 z-30 lg:hidden" onClick={() => setSidebarOpen(false)} />}

      {/* Main */}
      <div className="flex-1 flex flex-col min-w-0">
        <header className="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20">
          <button onClick={() => setSidebarOpen(true)} className="lg:hidden text-gray-600 hover:text-gray-900"><Menu size={24} /></button>
          <div className="text-sm text-gray-500 hidden lg:block">
            {user?.tutorName && <span className="font-semibold text-gray-700">{user.tutorName}</span>}
          </div>
          <div className="flex items-center gap-3">
            <span className="text-sm text-gray-600">{user?.firstName} {user?.lastName}</span>
            <div className="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
              {(user?.firstName?.[0] || "")}{(user?.lastName?.[0] || "")}
            </div>
          </div>
        </header>
        <main className="flex-1 p-4 lg:p-6 overflow-auto">
          {children}
        </main>
      </div>
    </div>
  );
}
