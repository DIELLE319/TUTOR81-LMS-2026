import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { BookOpen, Clock, Euro, ShoppingCart } from "lucide-react";
import SellCourseModal from "@/components/SellCourseModal";

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
  const [sellCourse, setSellCourse] = useState<Course | null>(null);
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
              <div className="flex items-center justify-between mt-3">
                <div className="flex items-center gap-4 text-xs text-gray-500">
                  {c.hours != null && <span className="flex items-center gap-1"><Clock size={12} />{c.hours}h</span>}
                  {c.listPrice && <span className="flex items-center gap-1"><Euro size={12} />{c.listPrice}</span>}
                </div>
                <button onClick={() => setSellCourse(c)} className="h-8 px-3 bg-yellow-500 hover:bg-yellow-600 text-black text-xs font-bold rounded-lg flex items-center gap-1">
                  <ShoppingCart size={12} />Vendi
                </button>
              </div>
            </div>
          ))}
        </div>
      )}
      {sellCourse && <SellCourseModal course={sellCourse} onClose={() => setSellCourse(null)} />}
    </div>
  );
}
