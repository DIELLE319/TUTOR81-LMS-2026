import { useQuery, useMutation } from "@tanstack/react-query";
import { useParams, Link, useLocation } from "wouter";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { 
  ChevronUp,
  ChevronDown, 
  Video, 
  FileText, 
  File,
  ArrowLeft,
  Play,
  Presentation,
  FileIcon,
  Edit,
  Save
} from "lucide-react";
import { useState, useEffect } from "react";
import { queryClient, apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";

interface LearningObject {
  id: number;
  title: string;
  type: string;
  duration: number;
  position: number;
  legacyId?: number;
}

interface Lesson {
  id: number;
  title: string;
  duration: number;
  position: number;
  learningObjects: LearningObject[];
  legacyId?: number;
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
  hours?: number;
  totalElearning?: number;
  maxExecutionTime?: number;
  externalIntegration?: string;
  percentageToPass?: number;
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
  const iconClass = "h-5 w-5";
  switch (type?.toLowerCase()) {
    case 'video':
      return <Play className={`${iconClass} text-blue-400`} />;
    case 'slide':
    case 'slides':
      return <Presentation className={`${iconClass} text-yellow-500`} />;
    case 'document':
    case 'pdf':
      return <FileIcon className={`${iconClass} text-orange-400`} />;
    default:
      return <File className={`${iconClass} text-gray-400`} />;
  }
}

function formatDurationLegacy(seconds: number): string {
  if (!seconds || seconds <= 0) return "";
  const totalMinutes = Math.floor(seconds / 60);
  if (totalMinutes >= 60) {
    const hours = Math.floor(totalMinutes / 60);
    const mins = totalMinutes % 60;
    return `${hours} ora ${mins} min`;
  }
  return `${totalMinutes} min`;
}

function calculateLessonDuration(learningObjects: LearningObject[]): number {
  return learningObjects.reduce((sum, lo) => sum + (lo.duration || 0), 0);
}

export default function CourseStructure() {
  const params = useParams();
  const [, setLocation] = useLocation();
  const showObjects = true; // Sempre espanso
  const [selectedProjectId, setSelectedProjectId] = useState<string>(params.id || "");
  const [editOpen, setEditOpen] = useState(false);
  const [editForm, setEditForm] = useState({
    hours: 0,
    totalElearning: 0,
    maxExecutionTime: 90,
    externalIntegration: "",
    percentageToPass: 80
  });
  const { toast } = useToast();

  const { data: projects } = useQuery<LearningProject[]>({
    queryKey: ["/api/learning-projects"]
  });

  const { data: structure, isLoading } = useQuery<CourseStructure>({
    queryKey: ["/api/learning-projects", selectedProjectId, "structure"],
    queryFn: async () => {
      const res = await fetch(`/api/learning-projects/${selectedProjectId}/structure`);
      if (!res.ok) throw new Error("Errore nel caricamento");
      return res.json();
    },
    enabled: !!selectedProjectId
  });

  const updateMutation = useMutation({
    mutationFn: async (data: typeof editForm) => {
      return apiRequest("PATCH", `/api/learning-projects/${selectedProjectId}`, data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/learning-projects", selectedProjectId, "structure"] });
      setEditOpen(false);
      toast({ title: "Corso aggiornato", description: "Le modifiche sono state salvate" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile salvare le modifiche", variant: "destructive" });
    }
  });

  const openEditDialog = () => {
    if (structure?.project) {
      setEditForm({
        hours: structure.project.hours || 0,
        totalElearning: structure.project.totalElearning || 0,
        maxExecutionTime: structure.project.maxExecutionTime || 90,
        externalIntegration: structure.project.externalIntegration || "",
        percentageToPass: structure.project.percentageToPass || 80
      });
      setEditOpen(true);
    }
  };

  return (
    <div className="p-6 max-w-5xl mx-auto">
      <div className="flex items-center gap-4 mb-6">
        <Link href="/catalog">
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

      {structure && (
        <div className="space-y-6">
          {structure.project && (
            <Card className="bg-zinc-900 border-zinc-800">
              <CardContent className="py-2 px-4">
                <div className="flex items-center justify-between gap-4 flex-wrap text-sm">
                  <div className="flex items-center gap-6 flex-wrap">
                    <span><span className="text-gray-500">Durata:</span> <span className="text-white">{structure.project.hours || 0}h</span></span>
                    <span><span className="text-gray-500">E-learning:</span> <span className="text-white">{structure.project.totalElearning || 0}h</span></span>
                    <span><span className="text-gray-500">Videoconf:</span> <span className="text-white">{structure.project.externalIntegration || "-"}</span></span>
                    <span><span className="text-gray-500">Max:</span> <span className="text-white">{structure.project.maxExecutionTime || 90}gg</span></span>
                    <span><span className="text-gray-500">Soglia:</span> <span className="text-white">{structure.project.percentageToPass || 80}%</span></span>
                  </div>
                  <Button 
                    variant="outline" 
                    size="sm" 
                    onClick={openEditDialog}
                    data-testid="button-edit-course"
                  >
                    <Edit className="h-4 w-4 mr-1" />
                    Modifica
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          <Dialog open={editOpen} onOpenChange={setEditOpen}>
            <DialogContent className="bg-zinc-900 border-zinc-700">
              <DialogHeader>
                <DialogTitle className="text-white">Modifica Parametri Corso</DialogTitle>
              </DialogHeader>
              <div className="grid gap-4 py-4">
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="hours">Durata totale (ore)</Label>
                    <Input
                      id="hours"
                      type="number"
                      value={editForm.hours}
                      onChange={(e) => setEditForm({...editForm, hours: Number(e.target.value)})}
                      className="bg-zinc-800 border-zinc-700"
                      data-testid="input-hours"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="totalElearning">E-learning (ore)</Label>
                    <Input
                      id="totalElearning"
                      type="number"
                      value={editForm.totalElearning}
                      onChange={(e) => setEditForm({...editForm, totalElearning: Number(e.target.value)})}
                      className="bg-zinc-800 border-zinc-700"
                      data-testid="input-elearning"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label htmlFor="maxExecutionTime">Tempo max conclusione (giorni)</Label>
                    <Input
                      id="maxExecutionTime"
                      type="number"
                      value={editForm.maxExecutionTime}
                      onChange={(e) => setEditForm({...editForm, maxExecutionTime: Number(e.target.value)})}
                      className="bg-zinc-800 border-zinc-700"
                      data-testid="input-max-time"
                    />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="percentageToPass">Soglia superamento (%)</Label>
                    <Input
                      id="percentageToPass"
                      type="number"
                      min={0}
                      max={100}
                      value={editForm.percentageToPass}
                      onChange={(e) => setEditForm({...editForm, percentageToPass: Number(e.target.value)})}
                      className="bg-zinc-800 border-zinc-700"
                      data-testid="input-percentage"
                    />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="externalIntegration">Videoconferenza / Integrazione esterna</Label>
                  <Input
                    id="externalIntegration"
                    value={editForm.externalIntegration}
                    onChange={(e) => setEditForm({...editForm, externalIntegration: e.target.value})}
                    placeholder="Es: 4 ore videoconferenza"
                    className="bg-zinc-800 border-zinc-700"
                    data-testid="input-external"
                  />
                </div>
              </div>
              <DialogFooter>
                <Button variant="outline" onClick={() => setEditOpen(false)}>
                  Annulla
                </Button>
                <Button 
                  onClick={() => updateMutation.mutate(editForm)}
                  disabled={updateMutation.isPending}
                  data-testid="button-save-course"
                >
                  <Save className="h-4 w-4 mr-1" />
                  {updateMutation.isPending ? "Salvataggio..." : "Salva"}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>

          <div className="flex items-center gap-4">
            <h2 className="text-lg font-semibold text-cyan-400 uppercase tracking-wide">
              Moduli Inseriti
            </h2>
            <div className="flex-1" />
            <Badge variant="outline" className="bg-zinc-800">
              {structure.stats.totalModules} moduli, {structure.stats.totalLessons} lezioni, {structure.stats.totalLearningObjects} LO
            </Badge>
          </div>

          {structure.modules.map((module, moduleIndex) => (
            <Card 
              key={module.id} 
              className="bg-zinc-900 border-zinc-700 border-l-4 border-l-cyan-500" 
              data-testid={`card-module-${module.id}`}
            >
              <CardContent className="pt-4">
                <div className="mb-4">
                  <h3 className="text-cyan-400 font-bold text-lg">
                    MODULO {moduleIndex + 1}: {module.title}
                  </h3>
                  <div className="mt-2 text-sm text-gray-400 space-y-1">
                    <div>
                      <span className="text-gray-500">Durata:</span>{" "}
                      <span className="text-white">{module.duration || 0} ore</span>
                    </div>
                    {module.description && (
                      <div>
                        <span className="text-gray-500">Descrizione:</span>{" "}
                        <span className="text-gray-300">{module.description}</span>
                      </div>
                    )}
                  </div>
                  <Button variant="outline" size="sm" className="mt-3" data-testid={`button-edit-module-${module.id}`}>
                    <Edit className="h-3 w-3 mr-1" />
                    Modifica modulo
                  </Button>
                </div>

                {module.lessons.length > 0 && (
                  <div className="mt-6">
                    <h4 className="text-cyan-400 uppercase text-sm font-semibold mb-3 border-b border-zinc-700 pb-2">
                      Lezioni Inserite
                    </h4>
                    
                    <div className="space-y-4">
                      {module.lessons.map((lesson, lessonIndex) => {
                        const calcDuration = calculateLessonDuration(lesson.learningObjects);
                        
                        return (
                          <div key={lesson.id} data-testid={`lesson-${lesson.id}`}>
                            <div className="flex items-start gap-2">
                              <ChevronUp className="h-4 w-4 text-yellow-500 mt-1" />
                              <div className="flex-1">
                                <div className="flex items-center justify-between">
                                  <span className="text-white">
                                    Lezione {lessonIndex + 1}: ID {lesson.legacyId || lesson.id} - {lesson.title}
                                  </span>
                                  <span className="text-gray-400 text-sm whitespace-nowrap ml-4">
                                    durata calc.: {formatDurationLegacy(calcDuration) || "0 min"}
                                  </span>
                                </div>
                              </div>
                            </div>
                            
                            {showObjects && lesson.learningObjects.length > 0 && (
                              <div className="ml-8 mt-2 space-y-1">
                                {lesson.learningObjects.map((lo) => (
                                  <Link key={lo.id} href={`/learning-objects/${lo.id}`}>
                                    <div 
                                      className="flex items-center gap-2 py-1 px-2 hover:bg-zinc-800/50 rounded cursor-pointer group"
                                      data-testid={`lo-${lo.id}`}
                                    >
                                      {getLoIcon(lo.type)}
                                      <span className="text-cyan-400 group-hover:underline">
                                        ({lo.legacyId || lo.id}){lo.title}
                                      </span>
                                      {lo.duration > 0 && (
                                        <span className="text-gray-500 text-sm ml-auto">
                                          ({formatDurationLegacy(lo.duration)})
                                        </span>
                                      )}
                                    </div>
                                  </Link>
                                ))}
                              </div>
                            )}
                          </div>
                        );
                      })}
                    </div>
                  </div>
                )}

                {module.lessons.length === 0 && (
                  <p className="text-gray-500 text-sm italic">Nessuna lezione in questo modulo</p>
                )}
              </CardContent>
            </Card>
          ))}

          {structure.modules.length === 0 && (
            <Card className="bg-zinc-900 border-zinc-800">
              <CardContent className="py-12 text-center text-gray-400">
                Nessun modulo trovato per questo corso
              </CardContent>
            </Card>
          )}

          {structure.modules.length > 0 && (
            <Card className="bg-zinc-800 border-zinc-700 border-t-4 border-t-yellow-500" data-testid="card-course-total">
              <CardContent className="py-4">
                <div className="flex items-center justify-between">
                  <h3 className="text-yellow-500 font-bold text-lg uppercase">
                    Totale Corso
                  </h3>
                  <div className="text-right">
                    <span className="text-2xl font-bold text-white">
                      {formatDurationLegacy(
                        structure.modules.reduce((total, mod) => 
                          total + mod.lessons.reduce((lessonTotal, lesson) => 
                            lessonTotal + calculateLessonDuration(lesson.learningObjects), 0
                          ), 0
                        )
                      ) || "0 min"}
                    </span>
                    <div className="text-sm text-gray-400">
                      durata calcolata dai learning objects
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      )}
    </div>
  );
}
