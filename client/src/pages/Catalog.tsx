import { useState, useMemo } from "react";
import { useQuery } from "@tanstack/react-query";
import { Printer, Download, ChevronDown, ChevronRight, ShoppingCart } from "lucide-react";
import SellCourseModal from "@/components/SellCourseModal";

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
  const t = title.toUpperCase();
  if (t.includes("AGGIORNAMENTO")) return "Aggiornamento";
  return "Base";
}

function parseRisk(title: string, riskLevel: string | null): string {
  if (riskLevel) return riskLevel;
  const t = title.toUpperCase();
  if (t.includes("RISCHIO ALTO") || t.includes("RISCHIO ALTO")) return "Alto";
  if (t.includes("RISCHIO MEDIO")) return "Medio";
  if (t.includes("RISCHIO BASSO")) return "Basso";
  return "N/D";
}

function parseHours(title: string, hours: number | null): number | null {
  if (hours) return hours;
  const m = title.match(/(\d+)\s*ore/i);
  return m ? parseInt(m[1], 10) : null;
}

function riskBadgeClass(risk: string): string {
  switch (risk.toLowerCase()) {
    case "basso": return "bg-green-500 text-white";
    case "medio": return "bg-yellow-500 text-white";
    case "alto": return "bg-red-500 text-white";
    default: return "bg-gray-400 text-white";
  }
}

function typeBadgeClass(type: string): string {
  return type === "Base" ? "bg-green-500 text-white" : "bg-blue-500 text-white";
}

export default function Catalog() {
  const [sellCourse, setSellCourse] = useState<CourseRaw | null>(null);
  const [filterAudience, setFilterAudience] = useState("Tutti");
  const [filterType, setFilterType] = useState("Tutti");
  const [filterRisk, setFilterRisk] = useState("Tutti");
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

  const audiences = useMemo(() => {
    const set = new Set(courses.map((c) => c.subcategory || "altro").map((s) => s.toUpperCase()));
    return ["Tutti", ...Array.from(set).sort()];
  }, [courses]);

  const filtered = useMemo(() => {
    return courses.filter((c) => {
      if (filterAudience !== "Tutti" && (c.subcategory || "altro").toUpperCase() !== filterAudience) return false;
      if (filterType !== "Tutti" && c.parsedType !== filterType) return false;
      if (filterRisk !== "Tutti" && c.parsedRisk !== filterRisk) return false;
      return true;
    });
  }, [courses, filterAudience, filterType, filterRisk]);

  const grouped = useMemo(() => {
    const map: Record<string, CourseRow[]> = {};
    for (const c of filtered) {
      const key = (c.subcategory || "altro").toUpperCase();
      if (!map[key]) map[key] = [];
      map[key].push(c);
    }
    return Object.entries(map).sort(([a], [b]) => a.localeCompare(b));
  }, [filtered]);

  const toggleGroup = (key: string) => {
    setExpandedGroups((prev) => prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key]);
  };

  const expandAll = () => setExpandedGroups(grouped.map(([k]) => k));

  const exportCsv = () => {
    const header = "ID;Tipo;Rischio;Nome Corso;Settore;Ore;Modalità;Listino;Costo\n";
    const rows = filtered.map((c) =>
      `${c.id};${c.parsedType};${c.parsedRisk};${c.title};${c.sector || ""};${c.parsedHours || ""};${c.parsedModality};${c.listPrice || ""};${c.tutorCost || ""}`
    ).join("\n");
    const blob = new Blob([header + rows], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "catalogo-corsi.csv";
    a.click();
    URL.revokeObjectURL(url);
  };

  // Auto-expand all groups on first load
  if (grouped.length > 0 && expandedGroups.length === 0) {
    expandAll();
  }

  return (
    <div>
      {/* Filters bar */}
      <div className="bg-gray-800 rounded-xl p-4 mb-4 flex flex-wrap items-end gap-4">
        <div className="flex-1 min-w-[180px]">
          <label className="block text-xs text-gray-300 mb-1">Chi seguirà il corso?</label>
          <select value={filterAudience} onChange={(e) => setFilterAudience(e.target.value)}
            className="w-full h-9 px-3 bg-white border-0 rounded text-sm text-gray-900">
            {audiences.map((a) => <option key={a} value={a}>{a}</option>)}
          </select>
        </div>
        <div className="flex-1 min-w-[180px]">
          <label className="block text-xs text-gray-300 mb-1">Aggiornamento o base?</label>
          <select value={filterType} onChange={(e) => setFilterType(e.target.value)}
            className="w-full h-9 px-3 bg-white border-0 rounded text-sm text-gray-900">
            <option value="Tutti">Tutti</option>
            <option value="Base">Base</option>
            <option value="Aggiornamento">Aggiornamento</option>
          </select>
        </div>
        <div className="flex-1 min-w-[180px]">
          <label className="block text-xs text-gray-300 mb-1">Grado di rischio?</label>
          <select value={filterRisk} onChange={(e) => setFilterRisk(e.target.value)}
            className="w-full h-9 px-3 bg-white border-0 rounded text-sm text-gray-900">
            <option value="Tutti">Tutti</option>
            <option value="Basso">Basso</option>
            <option value="Medio">Medio</option>
            <option value="Alto">Alto</option>
          </select>
        </div>
        <div className="flex gap-2">
          <button onClick={() => window.print()} className="h-9 px-4 bg-white text-gray-800 rounded text-sm font-medium flex items-center gap-2 hover:bg-gray-100">
            <Printer size={14} />Stampa
          </button>
          <button onClick={exportCsv} className="h-9 px-4 bg-yellow-500 text-black rounded text-sm font-bold flex items-center gap-2 hover:bg-yellow-600">
            <Download size={14} />CSV
          </button>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-12 text-gray-500">Caricamento...</div>
      ) : filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-500">Nessun corso trovato</div>
      ) : (
        <div className="bg-white rounded-xl border border-gray-200 overflow-hidden">
          {/* Table header */}
          <div className="bg-yellow-500 text-black text-xs font-bold uppercase tracking-wide">
            <div className="grid grid-cols-[60px_80px_80px_1fr_100px_50px_100px_80px_80px_90px] items-center px-3 py-2.5 gap-2">
              <span>ID</span>
              <span>Tipo</span>
              <span>Rischio</span>
              <span>Nome Corso</span>
              <span>Settore</span>
              <span>Ore</span>
              <span>Modalità</span>
              <span>Listino</span>
              <span>Costo</span>
              <span>Azione</span>
            </div>
          </div>

          {/* Groups */}
          {grouped.map(([groupName, groupCourses]) => (
            <div key={groupName}>
              {/* Group header */}
              <button onClick={() => toggleGroup(groupName)}
                className="w-full flex items-center gap-3 px-4 py-3 bg-gray-50 border-b border-gray-200 hover:bg-gray-100 text-left">
                {expandedGroups.includes(groupName) ? <ChevronDown size={16} className="text-gray-500" /> : <ChevronRight size={16} className="text-gray-500" />}
                <span className="font-bold text-gray-800 text-sm">{groupName}</span>
                <span className="text-xs text-gray-500 bg-gray-200 px-2 py-0.5 rounded-full">{groupCourses.length} corsi</span>
              </button>

              {/* Group rows */}
              {expandedGroups.includes(groupName) && groupCourses.map((c) => (
                <div key={c.id} className="grid grid-cols-[60px_80px_80px_1fr_100px_50px_100px_80px_80px_90px] items-center px-3 py-3 gap-2 border-b border-gray-100 hover:bg-gray-50 text-sm">
                  <span className="text-gray-500 font-mono text-xs">{c.id}</span>
                  <span><span className={`text-[11px] font-bold px-2 py-0.5 rounded ${typeBadgeClass(c.parsedType)}`}>{c.parsedType}</span></span>
                  <span><span className={`text-[11px] font-bold px-2 py-0.5 rounded ${riskBadgeClass(c.parsedRisk)}`}>{c.parsedRisk}</span></span>
                  <span className="text-gray-800 font-medium text-xs leading-tight pr-2">{c.title}</span>
                  <span className="text-gray-500 text-xs">{c.sector || "—"}</span>
                  <span className="text-gray-800 font-bold text-center">{c.parsedHours || "—"}</span>
                  <span className="text-gray-500 text-xs">{c.parsedModality}</span>
                  <span className="text-gray-600 text-xs text-right">{c.listPrice && parseFloat(c.listPrice) > 0 ? `${parseFloat(c.listPrice).toFixed(2)} €` : "—"}</span>
                  <span className="text-green-600 font-bold text-xs text-right">{c.tutorCost && parseFloat(c.tutorCost) > 0 ? `${parseFloat(c.tutorCost).toFixed(2)} €` : "—"}</span>
                  <span>
                    <button onClick={() => setSellCourse(c)} className="h-7 px-3 bg-green-500 hover:bg-green-600 text-white text-[11px] font-bold rounded flex items-center gap-1">
                      <ShoppingCart size={11} />Iscrivi
                    </button>
                  </span>
                </div>
              ))}
            </div>
          ))}
        </div>
      )}

      {sellCourse && <SellCourseModal course={sellCourse} onClose={() => setSellCourse(null)} />}
    </div>
  );
}
