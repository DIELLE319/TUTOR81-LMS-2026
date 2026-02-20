import { useQuery } from "@tanstack/react-query";
import { useAuth } from "@/hooks/use-auth";
import { Building2, Users, GraduationCap, FileText } from "lucide-react";
import type { ComponentType } from "react";

interface StatCard { label: string; value: number; icon: ComponentType<any> }

export default function Dashboard() {
  const { user } = useAuth();
  const { data: stats } = useQuery<{ tutors: number; clients: number; sales: number; users: number }>({
    queryKey: ["stats"],
    queryFn: () => fetch("/api/stats", { credentials: "include" }).then((r) => r.json()),
  });

  const cards: StatCard[] = [
    { label: "Clienti", value: stats?.clients ?? 0, icon: Building2 },
    { label: "Utenti", value: stats?.users ?? 0, icon: Users },
    { label: "Vendite", value: stats?.sales ?? 0, icon: FileText },
    { label: "Enti", value: stats?.tutors ?? 0, icon: GraduationCap },
  ];

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">
        Benvenuto, {user?.firstName || "Admin"}
      </h1>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {cards.map((card) => {
          const Icon = card.icon;
          return (
            <div key={card.label} className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
              <div className="flex items-center justify-between mb-3">
                <span className="text-sm font-medium text-gray-500">{card.label}</span>
                <Icon size={20} className="text-yellow-500" />
              </div>
              <div className="text-3xl font-bold text-gray-900">{card.value}</div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
