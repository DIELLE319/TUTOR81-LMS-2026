import { useState, useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { ChevronDown, ChevronRight, ShoppingCart } from "lucide-react";
import { useLocation } from "wouter";

interface CourseRaw {
  id: number;
  title: string;
  description: string | null;
  category: string | null;
  subcategory: string | null;
  hours: number | null;
  listPrice: string | null;
  tutorCost: string | null;
  riskLevel: string | null;
  sector: string | null;
  modality: string | null;
}

interface CourseRow extends CourseRaw {
  parsedType: string;
  parsedRisk: string;
  parsedHours: number | null;
  parsedModality: string;
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

function parseHours(title: string, hours: number | null): number | null {
  if (hours) return hours;
  const m = title.match(/(\d+)\s*ore/i);
  return m ? parseInt(m[1], 10) : null;
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
  const [, navigate] = useLocation();
  const [expandedGroups, setExpandedGroups] = useState<string[]>([]);

  const { data: rawCourses = [], isLoading } = useQuery<CourseRaw[]>({
    queryKey: ["courses"],
    queryFn: () => fetch("/api/courses", { credentials: "include" }).then((r) => r.json()),
  });

  const courses: CourseRow[] = useMemo(() =>
    rawCourses.map((c) => ({
      ...c,
      parsedType: parseType(c.title),
      parsedRisk: parseRisk(c.title, c.riskLevel),
      parsedHours: parseHours(c.title, c.hours),
      parsedModality: c.modality || "E-LEARNING",
    })),
    [rawCourses]
  );

  const grouped = useMemo(() => {
    const map: Record<string, CourseRow[]> = {};
    for (const c of courses) {
      const key = (c.subcategory || "altro").toUpperCase();
      if (!map[key]) map[key] = [];
      map[key].push(c);
    }
    return Object.entries(map).sort(([a], [b]) => a.localeCompare(b));
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
            <div className="grid grid-cols-[60px_60px_1fr_80px_50px_90px_80px_80px_70px] items-center px-3 py-2.5 gap-2 text-gray-400 text-[11px] font-semibold uppercase tracking-wide">
              <span>Tipo</span>
              <span>Rischio</span>
              <span>Nome Corso</span>
              <span>Settore</span>
              <span>Ore</span>
              <span>Modalità</span>
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
                <div key={c.id} className="grid grid-cols-[60px_60px_1fr_80px_50px_90px_80px_80px_70px] items-center px-3 py-2.5 gap-2 border-b border-white/5 hover:bg-white/[0.02] text-sm">
                  <span><span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${typeBadge(c.parsedType)}`}>{c.parsedType}</span></span>
                  <span><span className={`text-[10px] font-bold px-1.5 py-0.5 rounded ${riskBadge(c.parsedRisk)}`}>{c.parsedRisk}</span></span>
                  <span className="text-gray-200 text-xs leading-tight pr-2">{c.title}</span>
                  <span className="text-gray-500 text-xs">—</span>
                  <span className="text-white font-bold text-center text-xs">{c.parsedHours || "—"}</span>
                  <span className="text-gray-400 text-xs">{c.parsedModality}</span>
                  <span className="text-gray-400 text-xs text-right">{c.listPrice && parseFloat(c.listPrice) > 0 ? `${parseFloat(c.listPrice).toFixed(2)} €` : "—"}</span>
                  <span className="text-green-400 font-bold text-xs text-right">{c.tutorCost && parseFloat(c.tutorCost) > 0 ? `${parseFloat(c.tutorCost).toFixed(2)} €` : "—"}</span>
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
