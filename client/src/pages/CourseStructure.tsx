import { useQuery } from "@tanstack/react-query";
import { useParams, Link, useLocation } from "wouter";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { 
  ChevronRight, 
  ChevronDown, 
  BookOpen, 
  FileText, 
  Video, 
  File,
  ArrowLeft,
  Clock,
  Layers
} from "lucide-react";
import { useState } from "react";

interface LearningObject {
  id: number;
  title: string;
  type: string;
  duration: number;
  position: number;
}

interface Lesson {
  id: number;
  title: string;
  duration: number;
  position: number;
  learningObjects: LearningObject[];
}

interface Module {
  id: number;
  title: string;
  description: string;
  duration: number;
  position: number;
  lessons: Lesson[];
}

interface LearningProject {
  id: number;
  title: string;
  category?: string;
}

interface CourseStructure {
  projectId: number;
  project: LearningProject | null;
  modules: Module[];
  stats: {
    totalModules: number;
    totalLessons: number;
    totalLearningObjects: number;
  };
}

function getLoIcon(type: string) {
  switch (type?.toLowerCase()) {
    case 'video':
      return <Video className="h-4 w-4 text-blue-400" />;
    case 'slide':
    case 'slides':
      return <FileText className="h-4 w-4 text-green-400" />;
    case 'document':
    case 'pdf':
      return <File className="h-4 w-4 text-orange-400" />;
    default:
      return <File className="h-4 w-4 text-gray-400" />;
  }
}

function formatDuration(seconds: number) {
  if (!seconds) return "-";
  const mins = Math.floor(seconds / 60);
  const secs = seconds % 60;
  return mins > 0 ? `${mins}m ${secs}s` : `${secs}s`;
}

export default function CourseStructure() {
  const params = useParams();
  const [, setLocation] = useLocation();
  const [expandedModules, setExpandedModules] = useState<Set<number>>(new Set());
  const [expandedLessons, setExpandedLessons] = useState<Set<number>>(new Set());
  const [selectedProjectId, setSelectedProjectId] = useState<string>(params.id || "");

  const { data: projects } = useQuery<LearningProject[]>({
    queryKey: ["/api/learning-projects"]
  });

  const { data: structure, isLoading, error } = useQuery<CourseStructure>({
    queryKey: ["/api/learning-projects", selectedProjectId, "structure"],
    queryFn: async () => {
      const res = await fetch(`/api/learning-projects/${selectedProjectId}/structure`);
      if (!res.ok) throw new Error("Errore nel caricamento");
      return res.json();
    },
    enabled: !!selectedProjectId
  });

  const toggleModule = (moduleId: number) => {
    const newExpanded = new Set(expandedModules);
    if (newExpanded.has(moduleId)) {
      newExpanded.delete(moduleId);
    } else {
      newExpanded.add(moduleId);
    }
    setExpandedModules(newExpanded);
  };

  const toggleLesson = (lessonId: number) => {
    const newExpanded = new Set(expandedLessons);
    if (newExpanded.has(lessonId)) {
      newExpanded.delete(lessonId);
    } else {
      newExpanded.add(lessonId);
    }
    setExpandedLessons(newExpanded);
  };

  const expandAll = () => {
    if (!structure) return;
    const allModules = new Set(structure.modules.map(m => m.id));
    const allLessons = new Set(structure.modules.flatMap(m => m.lessons.map(l => l.id)));
    setExpandedModules(allModules);
    setExpandedLessons(allLessons);
  };

  const collapseAll = () => {
    setExpandedModules(new Set());
    setExpandedLessons(new Set());
  };

  return (
    <div className="p-6 max-w-6xl mx-auto">
      <div className="flex items-center gap-4 mb-6">
        <Link href="/content">
          <Button variant="ghost" size="icon" data-testid="button-back">
            <ArrowLeft className="h-5 w-5" />
          </Button>
        </Link>
        <div>
          <h1 className="text-2xl font-bold text-white">Struttura Corso</h1>
          <p className="text-gray-400">Visualizza moduli, lezioni e learning objects</p>
        </div>
      </div>

      <Card className="mb-6 bg-zinc-900 border-zinc-800">
        <CardContent className="pt-4">
          <div className="flex items-center gap-4 flex-wrap">
            <div className="flex-1 min-w-[300px]">
              <Select
                value={selectedProjectId}
                onValueChange={(value) => {
                  setSelectedProjectId(value);
                  setLocation(`/course-structure/${value}`);
                }}
              >
                <SelectTrigger className="bg-zinc-800 border-zinc-700" data-testid="select-course">
                  <SelectValue placeholder="Seleziona un corso..." />
                </SelectTrigger>
                <SelectContent className="max-h-80">
                  {projects?.map((p) => (
                    <SelectItem key={p.id} value={String(p.id)} data-testid={`option-course-${p.id}`}>
                      {p.title}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <Button variant="outline" size="sm" onClick={expandAll} data-testid="button-expand-all">
              Espandi tutto
            </Button>
            <Button variant="outline" size="sm" onClick={collapseAll} data-testid="button-collapse-all">
              Comprimi tutto
            </Button>
          </div>
        </CardContent>
      </Card>

      {isLoading && (
        <div className="flex items-center justify-center py-12">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full"></div>
        </div>
      )}

      {!selectedProjectId && (
        <Card className="bg-zinc-900 border-zinc-800">
          <CardContent className="py-12 text-center text-gray-400">
            Seleziona un corso dal menu per visualizzare la struttura
          </CardContent>
        </Card>
      )}

      {error && selectedProjectId && (
        <Card className="bg-red-900/20 border-red-800">
          <CardContent className="pt-4 text-red-400">
            Nessun dato trovato per il corso selezionato
          </CardContent>
        </Card>
      )}

      {structure && (
        <>
          <Card className="mb-6 bg-zinc-900 border-zinc-800">
            <CardHeader className="pb-2">
              <CardTitle className="text-lg flex items-center gap-2">
                <Layers className="h-5 w-5 text-yellow-500" />
                {structure.project?.title || `Corso ID: ${structure.projectId}`}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex gap-6 text-sm">
                <div className="flex items-center gap-2">
                  <Badge variant="outline" className="bg-blue-900/30 border-blue-700">
                    {structure.stats.totalModules} Moduli
                  </Badge>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant="outline" className="bg-green-900/30 border-green-700">
                    {structure.stats.totalLessons} Lezioni
                  </Badge>
                </div>
                <div className="flex items-center gap-2">
                  <Badge variant="outline" className="bg-purple-900/30 border-purple-700">
                    {structure.stats.totalLearningObjects} Learning Objects
                  </Badge>
                </div>
              </div>
            </CardContent>
          </Card>

          <div className="space-y-3">
            {structure.modules.map((module) => (
              <Card key={module.id} className="bg-zinc-900 border-zinc-800" data-testid={`card-module-${module.id}`}>
                <div
                  className="flex items-center gap-3 p-4 cursor-pointer hover:bg-zinc-800/50 transition-colors"
                  onClick={() => toggleModule(module.id)}
                  data-testid={`button-toggle-module-${module.id}`}
                >
                  {expandedModules.has(module.id) ? (
                    <ChevronDown className="h-5 w-5 text-yellow-500" />
                  ) : (
                    <ChevronRight className="h-5 w-5 text-gray-500" />
                  )}
                  <BookOpen className="h-5 w-5 text-yellow-500" />
                  <div className="flex-1">
                    <div className="font-medium text-white">{module.title}</div>
                    {module.description && (
                      <div className="text-sm text-gray-400 mt-1">{module.description}</div>
                    )}
                  </div>
                  <Badge variant="outline" className="text-xs">
                    {module.lessons.length} lezioni
                  </Badge>
                  {module.duration > 0 && (
                    <div className="flex items-center gap-1 text-xs text-gray-400">
                      <Clock className="h-3 w-3" />
                      {formatDuration(module.duration)}
                    </div>
                  )}
                </div>

                {expandedModules.has(module.id) && module.lessons.length > 0 && (
                  <div className="border-t border-zinc-800 px-4 pb-4">
                    {module.lessons.map((lesson) => (
                      <div key={lesson.id} className="mt-3" data-testid={`lesson-${lesson.id}`}>
                        <div
                          className="flex items-center gap-3 p-3 bg-zinc-800/50 rounded-lg cursor-pointer hover:bg-zinc-800 transition-colors ml-6"
                          onClick={() => toggleLesson(lesson.id)}
                          data-testid={`button-toggle-lesson-${lesson.id}`}
                        >
                          {expandedLessons.has(lesson.id) ? (
                            <ChevronDown className="h-4 w-4 text-green-500" />
                          ) : (
                            <ChevronRight className="h-4 w-4 text-gray-500" />
                          )}
                          <FileText className="h-4 w-4 text-green-500" />
                          <div className="flex-1 text-sm text-white">{lesson.title}</div>
                          <Badge variant="outline" className="text-xs">
                            {lesson.learningObjects.length} LO
                          </Badge>
                        </div>

                        {expandedLessons.has(lesson.id) && lesson.learningObjects.length > 0 && (
                          <div className="ml-14 mt-2 space-y-1">
                            {lesson.learningObjects.map((lo) => (
                              <Link key={lo.id} href={`/learning-objects/${lo.id}`}>
                                <div
                                  className="flex items-center gap-3 p-2 bg-zinc-800/30 rounded hover:bg-zinc-700/50 transition-colors cursor-pointer"
                                  data-testid={`lo-${lo.id}`}
                                >
                                  {getLoIcon(lo.type)}
                                  <span className="flex-1 text-sm text-gray-300">{lo.title}</span>
                                  <Badge variant="outline" className="text-xs bg-zinc-900">
                                    {lo.type || "N/A"}
                                  </Badge>
                                  {lo.duration > 0 && (
                                    <span className="text-xs text-gray-500">
                                      {formatDuration(lo.duration)}
                                    </span>
                                  )}
                                </div>
                              </Link>
                            ))}
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </Card>
            ))}
          </div>

          {structure.modules.length === 0 && (
            <Card className="bg-zinc-900 border-zinc-800">
              <CardContent className="py-12 text-center text-gray-400">
                Nessun modulo trovato per questo corso
              </CardContent>
            </Card>
          )}
        </>
      )}
    </div>
  );
}
