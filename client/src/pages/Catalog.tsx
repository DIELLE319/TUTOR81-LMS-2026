import { useState, useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { ChevronDown, ChevronRight, ShoppingCart } from "lucide-react";
import { useLocation } from "wouter";
import { useAuth } from "@/hooks/use-auth";

interface CourseRaw {
  id: number;
  title: string;
  description: string | null;
  categoria: string | null;
  sottocategoria: string | null;
  tipo: string | null;
  settore: string | null;
  rischio_azienda: string | null;
  durata_totale: string | null;
  price: string | null;
  rivolto_a: string | null;
  validita: string | null;
}

interface CourseRow extends CourseRaw {
  parsedType: string;
  parsedRisk: string;
  parsedDuration: string;
  parsedModality: string;
}

const CATEGORY_ORDER = ["LAVORATORE", "PREPOSTO", "DIRIGENTE", "RLS", "DATORE DI LAVORO", "INFORMATICA", "PARITÀ DI GENERE", "PESPAV", "TEST", "ALTRO"];

function parseCategory(title: string, subcategory: string | null): string {
  const sub = (subcategory || "").toUpperCase();
  for (const cat of CATEGORY_ORDER) {
    if (sub.includes(cat)) return cat;
  }
  const t = title.toUpperCase();
  if (t.includes("PREPOSTO")) return "PREPOSTO";
  if (t.includes("DIRIGENT")) return "DIRIGENTE";
  if (t.includes("DATORE")) return "DATORE DI LAVORO";
  if (t.includes("RLS")) return "RLS";
  if (t.includes("LAVORATOR")) return "LAVORATORE";
  if (t.includes("CYBER") || t.includes("INFORMAT")) return "INFORMATICA";
  if (t.includes("GENERE") || t.includes("PARIT")) return "PARITÀ DI GENERE";
  if (t.includes("PESPAV")) return "PESPAV";
  if (t.includes("TEST") || t.includes("DIMOSTR")) return "TEST";
  return "ALTRO";
}

function parseType(title: string): string {
  return title.toUpperCase().includes("AGGIORNAMENTO") ? "Agg." : "Base";
}

function parseRisk(title: string, riskLevel: string | null): string {
  if (riskLevel) return riskLevel;
  const t = title.toUpperCase();
  if (t.includes("RISCHIO ALTO")) return "Alto";
  if (t.includes("RISCHIO MEDIO")) return "Medio";
  if (t.includes("RISCHIO BASSO")) return "Basso";
  return "N/D";
}

function parseDuration(title: string, durata: string | null): string {
  if (durata) return durata.replace(/\s+/g, " ").trim();
  const m = title.match(/(\d+)\s*ore/i);
  return m ? `${m[1]} ore` : "";
}

function riskBadge(risk: string) {
  switch (risk.toLowerCase()) {
    case "basso": return "bg-green-500 text-white";
    case "medio": return "bg-yellow-500 text-black";
    case "alto": return "bg-red-500 text-white";
    default: return "bg-gray-600 text-gray-300";
  }
}

function typeBadge(type: string) {
  return type === "Base" ? "bg-green-600 text-white" : "bg-blue-500 text-white";
}

export default function Catalog() {
  const { user } = useAuth();
  const [, navigate] = useLocation();
  const [expandedGroups, setExpandedGroups] = useState<string[]>([]);

  const { data: tutorData } = useQuery<any>({
    queryKey: ["my-tutor", user?.tutorId],
    queryFn: () => fetch(`/api/tutors/${user!.tutorId}`, { credentials: "include" }).then((r) => r.json()),
    enabled: !!user?.tutorId,
  });
  const discount = tutorData?.discountPercentage ?? 0;

  const { data: rawCourses = [], isLoading } = useQuery<CourseRaw[]>({
    queryKey: ["courses"],
    queryFn: () => fetch("/api/courses", { credentials: "include" }).then((r) => r.json()),
  });

  const courses: CourseRow[] = useMemo(() =>
    rawCourses.map((c) => ({
      ...c,
      parsedType: c.tipo || parseType(c.title),
      parsedRisk: parseRisk(c.title, c.rischio_azienda || null),
      parsedDuration: parseDuration(c.title, c.durata_totale),
      parsedModality: "E-LEARNING",
    })),
    [rawCourses]
  );

  const grouped = useMemo(() => {
    const map: Record<string, CourseRow[]> = {};
    for (const c of courses) {
      const key = parseCategory(c.title, c.sottocategoria);
      if (!map[key]) map[key] = [];
      map[key].push(c);
    }
    const riskOrder: Record<string, number> = { "Basso": 0, "Medio": 1, "Alto": 2, "N/D": 3 };
    for (const [, arr] of Object.entries(map)) {
      arr.sort((a, b) => {
        const typeA = a.parsedType === "Base" ? 0 : 1;
        const typeB = b.parsedType === "Base" ? 0 : 1;
        if (typeA !== typeB) return typeA - typeB;
        return (riskOrder[a.parsedRisk] ?? 9) - (riskOrder[b.parsedRisk] ?? 9);
      });
    }
    return Object.entries(map).sort(([a], [b]) => {
      const ia = CATEGORY_ORDER.indexOf(a);
      const ib = CATEGORY_ORDER.indexOf(b);
      return (ia === -1 ? 99 : ia) - (ib === -1 ? 99 : ib);
    });
  }, [courses]);

  const toggleGroup = (key: string) => {
    setExpandedGroups((prev) => prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]);
  };

  if (grouped.length > 0 && expandedGroups.length === 0) {
    setExpandedGroups(grouped.map(([k]) => k));
  }

  return (
    <div>
      {/* Table */}
      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Caricamento...</div>
      ) : courses.length === 0 ? (
        <div className="text-center py-12 text-gray-500">Nessun corso nel catalogo</div>
      ) : (
        <div className="bg-[#141414] rounded-xl border border-white/5 overflow-hidden">
          {/* Header */}
          <div className="border-b border-white/10">
            <div className="grid grid-cols-[60px_60px_1fr_70px_90px_90px_70px] items-center px-3 py-2.5 gap-2 text-gray-400 text-[11px] font-semibold uppercase tracking-wide">
              <span>Tipo</span>
              <span>Rischio</span>
              <span>Nome Corso</span>
              <span>Durata</span>
              <span className="text-right">Listino €</span>
              <span className="text-right">Tuo Costo €</span>
              <span>Azione</span>
            </div>
          </div>

          {/* Groups */}
          {grouped.map(([groupName, groupCourses]) => (
            <div key={groupName}>
              <button onClick={() => toggleGroup(groupName)}
                className="w-full flex items-center gap-2 px-3 py-2 bg-white/[0.03] border-b border-white/5 hover:bg-white/[0.05] text-left">
                {expandedGroups.includes(groupName) ? <ChevronDown size={14} className="text-gray-500" /> : <ChevronRight size={14} className="text-gray-500" />}
                <span className="font-bold text-yellow-500 text-xs">{groupName}</span>
                <span className="text-[10px] text-gray-600 bg-white/5 px-2 py-0.5 rounded-full">{groupCourses.length} corsi</span>
              </button>

              {expandedGroups.includes(groupName) && groupCourses.map((c) => (
                <div key={c.id} className="grid grid-cols-[60px_60px_1fr_70px_90px_90px_70px] items-center px-3 py-2.5 gap-2 border-b border-white/5 hover:bg-white/[0.02] text-sm">
                  <span><span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${typeBadge(c.parsedType)}`}>{c.parsedType}</span></span>
                  <span><span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${riskBadge(c.parsedRisk)}`}>{c.parsedRisk}</span></span>
                  <span className="text-gray-200 text-xs leading-tight pr-2">{c.title}</span>
                  <span className="text-white font-bold text-center text-xs">{c.parsedDuration || "—"}</span>
                  <span className="text-gray-400 text-xs text-right">{c.price && parseFloat(c.price) > 0 ? `${parseFloat(c.price).toFixed(2)} €` : "—"}</span>
                  <span className="text-green-400 font-bold text-xs text-right">{c.price && parseFloat(c.price) > 0 && discount > 0 ? `${(parseFloat(c.price) * (1 - discount / 100)).toFixed(2)} €` : "—"}</span>
                  <span>
                    <button onClick={() => navigate(`/assign-course?courseId=${c.id}&courseTitle=${encodeURIComponent(c.title)}`)} className="h-6 px-2.5 bg-yellow-500 hover:bg-yellow-600 text-black text-[10px] font-bold rounded flex items-center gap-1">
                      <ShoppingCart size={10} />Vendi
                    </button>
                  </span>
                </div>
              ))}
            </div>
          ))}
        </div>
      )}

    </div>
  );
}
