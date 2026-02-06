import { useState } from "react";
import { useQuery, useMutation } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { queryClient, apiRequest } from "@/lib/queryClient";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { 
  Video, 
  FileText, 
  Image, 
  Search, 
  Pause, 
  Play,
  AlertTriangle,
  CheckCircle,
  HelpCircle
} from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface LearningObject {
  id: number;
  legacyId: number | null;
  title: string;
  objectType: number;
  jwplayerCode: string | null;
  duration: number;
  percentageToPass: number;
  suspended: boolean;
  inUse: boolean;
  questionsCount?: number;
}

export default function LearningObjects() {
  const [searchTerm, setSearchTerm] = useState("");
  const [filterType, setFilterType] = useState<"all" | "unused" | "suspended">("all");
  const [, navigate] = useLocation();
  const { toast } = useToast();

  const { data: loResponse, isLoading } = useQuery<{ data: LearningObject[], pagination: { total: number } }>({
    queryKey: ["/api/learning-objects"],
  });
  const objects = loResponse?.data || [];

  const suspendMutation = useMutation({
    mutationFn: async ({ id, suspended }: { id: number; suspended: boolean }) => {
      return apiRequest("PATCH", `/api/learning-objects/${id}/suspend`, { suspended });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/learning-objects"] });
      toast({ title: "Oggetto aggiornato" });
    },
    onError: () => {
      toast({ title: "Errore", description: "Impossibile aggiornare l'oggetto", variant: "destructive" });
    },
  });

  const getObjectTypeIcon = (type: number) => {
    switch (type) {
      case 1:
        return <Video className="w-4 h-4" />;
      case 2:
        return <Image className="w-4 h-4" />;
      case 3:
        return <FileText className="w-4 h-4" />;
      default:
        return <HelpCircle className="w-4 h-4" />;
    }
  };

  const getObjectTypeName = (type: number) => {
    switch (type) {
      case 1:
        return "Video";
      case 2:
        return "Slide";
      case 3:
        return "Documento";
      default:
        return "Altro";
    }
  };

  const filteredObjects = objects.filter((obj) => {
    const matchesSearch = obj.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      obj.legacyId?.toString().includes(searchTerm);
    
    if (filterType === "unused") return matchesSearch && !obj.inUse;
    if (filterType === "suspended") return matchesSearch && obj.suspended;
    return matchesSearch;
  });

  const unusedCount = objects.filter(o => !o.inUse).length;
  const suspendedCount = objects.filter(o => o.suspended).length;

  return (
    <div className="p-6 bg-black min-h-screen">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-white">Oggetti Multimediali</h1>
          <p className="text-gray-500 mt-1">
            Gestisci video, slide e documenti dei corsi
          </p>
        </div>
        <div className="flex items-center gap-4">
          <Badge variant="outline" className="text-gray-400 border-gray-700">
            Totale: {objects.length}
          </Badge>
          <Badge variant="outline" className="text-red-400 border-red-900">
            Non in uso: {unusedCount}
          </Badge>
          <Badge variant="outline" className="text-yellow-400 border-yellow-900">
            Sospesi: {suspendedCount}
          </Badge>
        </div>
      </div>

      <div className="bg-white rounded-xl overflow-hidden border-2 border-black/70 mb-4">
        <div className="bg-yellow-400 px-4 py-3 border-b border-black/20">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div className="relative flex-1 max-w-md">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-black/60" />
              <Input
                placeholder="Cerca per titolo o ID..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10 bg-white border-yellow-600/30 text-black placeholder-black/50"
                data-testid="input-search"
              />
            </div>

            <div className="flex flex-wrap gap-2 justify-end">
              <Button
                variant="outline"
                size="sm"
                onClick={() => setFilterType("all")}
                className={filterType === 'all' ? 'bg-yellow-200 border-black/10 text-black' : 'bg-white border-black/10 text-black hover:bg-yellow-50'}
                data-testid="button-filter-all"
              >
                Tutti
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setFilterType("unused")}
                className={filterType === 'unused' ? 'bg-yellow-200 border-black/10 text-black' : 'bg-white border-black/10 text-black hover:bg-yellow-50'}
                data-testid="button-filter-unused"
              >
                <AlertTriangle className="w-4 h-4 mr-1" />
                Non in uso ({unusedCount})
              </Button>
              <Button
                variant="outline"
                size="sm"
                onClick={() => setFilterType("suspended")}
                className={filterType === 'suspended' ? 'bg-yellow-200 border-black/10 text-black' : 'bg-white border-black/10 text-black hover:bg-yellow-50'}
                data-testid="button-filter-suspended"
              >
                Sospesi ({suspendedCount})
              </Button>
            </div>
          </div>
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center h-64">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full" />
        </div>
      ) : (
        <div className="bg-white rounded-xl overflow-hidden border-2 border-black/70">
          <Table>
            <TableHeader>
              <TableRow className="bg-yellow-500 border-b border-yellow-600/30">
                <TableHead className="text-black font-bold uppercase w-16">Tipo</TableHead>
                <TableHead className="text-black font-bold uppercase w-20">ID</TableHead>
                <TableHead className="text-black font-bold uppercase">Titolo</TableHead>
                <TableHead className="text-black font-bold uppercase w-24">Durata</TableHead>
                <TableHead className="text-black font-bold uppercase w-24">Domande</TableHead>
                <TableHead className="text-black font-bold uppercase w-24">Stato</TableHead>
                <TableHead className="text-black font-bold uppercase w-32">Azione</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredObjects.map((obj) => (
                <TableRow
                  key={obj.id}
                  className={`border-b border-gray-200 cursor-pointer ${
                    !obj.inUse && !obj.suspended
                      ? "bg-red-50 hover:bg-red-100"
                      : obj.suspended
                      ? "bg-gray-50 hover:bg-gray-100 opacity-60"
                      : "hover:bg-gray-50"
                  }`}
                  onClick={() => navigate(`/learning-objects/${obj.id}`)}
                  data-testid={`row-object-${obj.id}`}
                >
                  <TableCell>
                    <div className="flex items-center justify-center text-gray-600">
                      {getObjectTypeIcon(obj.objectType)}
                    </div>
                  </TableCell>
                  <TableCell className="text-gray-600 font-mono text-sm">
                    {obj.legacyId || obj.id}
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center gap-2">
                      <span className={`${!obj.inUse ? "text-red-700" : "text-black"}`}>
                        {obj.title}
                      </span>
                      {!obj.inUse && !obj.suspended && (
                        <Badge variant="destructive" className="text-xs">
                          Da sospendere
                        </Badge>
                      )}
                    </div>
                  </TableCell>
                  <TableCell className="text-gray-700">
                    {obj.duration} min
                  </TableCell>
                  <TableCell className="text-gray-700">
                    {obj.questionsCount || "-"}
                  </TableCell>
                  <TableCell>
                    {obj.suspended ? (
                      <Badge variant="secondary" className="bg-gray-200 text-gray-700">
                        <Pause className="w-3 h-3 mr-1" />
                        Sospeso
                      </Badge>
                    ) : obj.inUse ? (
                      <Badge className="bg-green-100 text-green-800 border border-green-200">
                        <CheckCircle className="w-3 h-3 mr-1" />
                        Attivo
                      </Badge>
                    ) : (
                      <Badge variant="destructive">
                        <AlertTriangle className="w-3 h-3 mr-1" />
                        Inutilizzato
                      </Badge>
                    )}
                  </TableCell>
                  <TableCell>
                    <Button
                      variant={obj.suspended ? "outline" : "destructive"}
                      size="sm"
                      onClick={(e) => {
                        e.stopPropagation();
                        suspendMutation.mutate({ id: obj.id, suspended: !obj.suspended });
                      }}
                      disabled={suspendMutation.isPending}
                      data-testid={`button-suspend-${obj.id}`}
                    >
                      {obj.suspended ? (
                        <>
                          <Play className="w-3 h-3 mr-1" />
                          Riattiva
                        </>
                      ) : (
                        <>
                          <Pause className="w-3 h-3 mr-1" />
                          Sospendi
                        </>
                      )}
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          {filteredObjects.length === 0 && (
            <div className="text-center py-12 text-gray-500">
              Nessun oggetto trovato
            </div>
          )}
        </div>
      )}
    </div>
  );
}
