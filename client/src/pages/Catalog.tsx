import { useQuery } from "@tanstack/react-query";
import { BookOpen, Clock, Euro } from "lucide-react";

interface Course {
  id: number;
  title: string;
  description: string | null;
  category: string | null;
  hours: number | null;
  listPrice: string | null;
  riskLevel: string | null;
}

export default function Catalog() {
  const { data: courses = [], isLoading } = useQuery<Course[]>({
    queryKey: ["courses"],
    queryFn: () => fetch("/api/courses", { credentials: "include" }).then((r) => r.json()),
  });

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">Catalogo Corsi</h1>
      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Caricamento...</div>
      ) : courses.length === 0 ? (
        <div className="text-center py-12 text-gray-500">Nessun corso disponibile</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {courses.map((c) => (
            <div key={c.id} className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm hover:shadow-md transition-shadow">
              <div className="flex items-start justify-between mb-3">
                <BookOpen size={20} className="text-yellow-500 mt-0.5" />
                {c.riskLevel && <span className="text-xs font-semibold px-2 py-0.5 rounded bg-gray-100 text-gray-600">{c.riskLevel}</span>}
              </div>
              <h3 className="font-bold text-gray-900 mb-1 text-sm leading-tight">{c.title}</h3>
              {c.category && <p className="text-xs text-gray-500 mb-3">{c.category}</p>}
              <div className="flex items-center gap-4 text-xs text-gray-500">
                {c.hours != null && <span className="flex items-center gap-1"><Clock size={12} />{c.hours}h</span>}
                {c.listPrice && <span className="flex items-center gap-1"><Euro size={12} />{c.listPrice}</span>}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
