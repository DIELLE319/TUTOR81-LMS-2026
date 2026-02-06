import * as Icons from "lucide-react";
import { useLocation } from "wouter";
import { useAuth } from "@/hooks/use-auth";
import { Redirect } from "wouter";

function EntryCard({
  title,
  description,
  to,
  icon: Icon,
  accentClass,
}: {
  title: string;
  description: string;
  to: string;
  icon: React.ComponentType<{ className?: string }>;
  accentClass: string;
}) {
  const [, setLocation] = useLocation();

  return (
    <button
      type="button"
      onClick={() => setLocation(to)}
      className="text-left bg-[#121212] border border-gray-800 rounded-2xl p-6 hover:border-yellow-500/60 hover:bg-[#161616] transition-colors"
      data-testid={`superadmin-entry-${to.replace(/\W+/g, "-")}`}
    >
      <div className="flex items-start gap-4">
        <div className={`h-12 w-12 rounded-xl flex items-center justify-center ${accentClass}`}>
          <Icon className="h-6 w-6" />
        </div>
        <div className="min-w-0">
          <div className="text-lg font-extrabold text-white leading-tight">{title}</div>
          <div className="text-sm text-gray-400 mt-1">{description}</div>
        </div>
      </div>
    </button>
  );
}

export default function SuperAdminEntry() {
  const { user } = useAuth();

  if (user && user.role !== 1000) {
    return <Redirect to="/dashboard" />;
  }

  return (
    <div className="p-6 bg-black min-h-screen font-sans">
      <div className="flex items-end justify-between gap-4 mb-6">
        <div>
          <h1 className="text-3xl font-extrabold text-white">Ingresso Super Admin</h1>
          <p className="text-gray-400 mt-1">
            {user?.firstName || ""} {user?.lastName || ""} — accesso rapido alle sezioni principali
          </p>
        </div>
        <div className="text-sm text-gray-500">
          {new Date().toLocaleDateString("it-IT", {
            weekday: "long",
            day: "numeric",
            month: "long",
            year: "numeric",
          })}
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <EntryCard
          title="Enti Formativi"
          description="Gestisci tutor / enti formativi"
          to="/tutors"
          icon={Icons.Building}
          accentClass="bg-blue-500/15 text-blue-300"
        />
        <EntryCard
          title="Aziende Clienti"
          description="Elenco aziende e schede clienti"
          to="/clients"
          icon={Icons.Users}
          accentClass="bg-emerald-500/15 text-emerald-300"
        />
        <EntryCard
          title="Vendite"
          description="Corsi venduti e fatturazione"
          to="/sales"
          icon={Icons.FileText}
          accentClass="bg-orange-500/15 text-orange-300"
        />
        <EntryCard
          title="Invia Avvio Corso"
          description="Catalogo e invio email di avvio"
          to="/catalog"
          icon={Icons.ShoppingCart}
          accentClass="bg-yellow-500/15 text-yellow-300"
        />
        <EntryCard
          title="Corsi In Attività"
          description="Monitoraggio corsi attivi"
          to="/courses/active"
          icon={Icons.Activity}
          accentClass="bg-fuchsia-500/15 text-fuchsia-300"
        />
        <EntryCard
          title="Attestati"
          description="Ricerca e stampa attestati"
          to="/certificates"
          icon={Icons.CheckCircle}
          accentClass="bg-lime-500/15 text-lime-300"
        />
        <EntryCard
          title="Utenti"
          description="Elenco e import utenti"
          to="/users"
          icon={Icons.User}
          accentClass="bg-cyan-500/15 text-cyan-300"
        />
        <EntryCard
          title="Gestione Contenuti"
          description="Learning objects e contenuti"
          to="/content-management"
          icon={Icons.Film}
          accentClass="bg-purple-500/15 text-purple-300"
        />
      </div>

      <div className="mt-6 rounded-2xl border border-yellow-500/30 bg-yellow-500/10 p-4 text-sm text-yellow-200">
        Suggerimento: questa pagina è visibile solo al ruolo <span className="font-bold">1000</span>.
      </div>
    </div>
  );
}
