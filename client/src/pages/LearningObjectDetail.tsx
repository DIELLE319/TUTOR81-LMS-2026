import { useQuery } from "@tanstack/react-query";
import { useLocation } from "wouter";
import { ArrowLeft, Play, FileText, Image, CheckCircle, XCircle, AlertCircle } from "lucide-react";
import { useToast } from "@/hooks/use-toast";

interface LearningObject {
  id: number;
  legacyId: number;
  title: string;
  objectType: number;
  jwplayerCode: string | null;
  videoFilename: string | null;
  slideFilename: string | null;
  documentFilename: string | null;
  duration: number;
  percentageToPass: number;
  suspended: boolean;
  inUse: boolean;
}

export default function LearningObjectDetail({ id }: { id: string }) {
  const [, navigate] = useLocation();
  const { toast } = useToast();
  const loId = parseInt(id);

  const { data: loDetails, isLoading } = useQuery<{
    learningObject: LearningObject;
    interruptionPoints: any[];
  }>({
    queryKey: ['/api/learning-objects', loId, 'details'],
    enabled: !!loId,
  });

  if (isLoading) {
    return (
      <div className="p-6 bg-gray-50 min-h-screen flex items-center justify-center">
        <div className="animate-spin w-8 h-8 border-2 border-[#4a90a4] border-t-transparent rounded-full"></div>
      </div>
    );
  }

  if (!loDetails?.learningObject) {
    return (
      <div className="p-6 bg-gray-50 min-h-screen">
        <p className="text-gray-500">Learning Object non trovato</p>
      </div>
    );
  }

  const lo = loDetails.learningObject;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-[#4a90a4] text-white px-6 py-4">
        <div className="flex items-center gap-4">
          <button
            onClick={() => navigate('/learning-objects')}
            className="flex items-center gap-2 text-white/80 hover:text-white transition-colors"
            data-testid="button-back"
          >
            <ArrowLeft size={20} />
            <span>Torna alla lista</span>
          </button>
          <div className="h-6 w-px bg-white/30" />
          <h1 className="text-xl font-bold">Learning Object #{lo.legacyId || lo.id}</h1>
          <div className="ml-auto flex items-center gap-3">
            <span className={`px-3 py-1 rounded-full text-sm font-medium ${
              lo.suspended ? 'bg-gray-500' : lo.inUse ? 'bg-green-500' : 'bg-red-500'
            }`}>
              {lo.suspended ? 'Sospeso' : lo.inUse ? 'Attivo' : 'Non in uso'}
            </span>
          </div>
        </div>
      </div>

      <div className="p-6">
        <div className="grid grid-cols-3 gap-6">
          {/* Colonna sinistra - Video Player */}
          <div className="col-span-2">
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
              <div className="p-4 border-b border-gray-100">
                <h2 className="font-semibold text-gray-800">{lo.title}</h2>
                <div className="flex items-center gap-4 mt-2 text-sm text-gray-500">
                  <span className="flex items-center gap-1">
                    {lo.objectType === 1 ? <Play size={14} /> : lo.objectType === 2 ? <Image size={14} /> : <FileText size={14} />}
                    {lo.objectType === 1 ? 'Video' : lo.objectType === 2 ? 'Slide' : 'Documento'}
                  </span>
                  <span>{lo.duration} minuti</span>
                  <span>Soglia: {lo.percentageToPass}%</span>
                </div>
              </div>

              {/* Video Player */}
              {lo.jwplayerCode ? (
                <div className="relative bg-black" style={{ aspectRatio: '16/9' }}>
                  <iframe
                    src={`https://cdn.jwplayer.com/players/${lo.jwplayerCode}-ZXcv1712.html`}
                    width="100%"
                    height="100%"
                    frameBorder="0"
                    allow="autoplay; fullscreen"
                    allowFullScreen
                  />
                  {/* Anteprima domande sul video */}
                  {loDetails.interruptionPoints?.length > 0 && (
                    <div className="absolute top-0 left-0 right-0 bg-gradient-to-b from-black/80 to-transparent p-3">
                      <div className="flex gap-2 flex-wrap">
                        {loDetails.interruptionPoints.map((ip: any) => {
                          const totalSeconds = Math.floor(ip.time / 1000);
                          const minutes = Math.floor(totalSeconds / 60);
                          const seconds = totalSeconds % 60;
                          return (
                            <div 
                              key={ip.id}
                              className="bg-yellow-500 text-black text-xs font-bold px-2 py-1 rounded cursor-pointer hover:bg-yellow-400"
                              title={ip.questions?.filter((q: any) => q.id).map((q: any) => q.text).join('\n')}
                            >
                              {minutes}:{String(seconds).padStart(2, '0')}
                            </div>
                          );
                        })}
                      </div>
                    </div>
                  )}
                </div>
              ) : (
                <div className="bg-gray-100 flex items-center justify-center text-gray-400" style={{ aspectRatio: '16/9' }}>
                  <div className="text-center">
                    <AlertCircle size={48} className="mx-auto mb-2" />
                    <p>Nessun video disponibile</p>
                  </div>
                </div>
              )}
            </div>

            {/* Domande associate */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 mt-6">
              <div className="p-4 border-b border-gray-100">
                <h3 className="font-semibold text-gray-800">
                  Domande Associate ({loDetails.interruptionPoints?.length || 0} interruzioni)
                </h3>
              </div>
              <div className="p-4 max-h-[500px] overflow-y-auto">
                {loDetails.interruptionPoints?.length > 0 ? (
                  <div className="space-y-6">
                    {loDetails.interruptionPoints.map((ip: any, idx: number) => {
                      const totalSeconds = Math.floor(ip.time / 1000);
                      const minutes = Math.floor(totalSeconds / 60);
                      const seconds = totalSeconds % 60;
                      return (
                        <div key={ip.id} className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                          <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center gap-3">
                              <span className="bg-[#4a90a4] text-white text-xs font-bold px-2 py-1 rounded">
                                Interruzione #{idx + 1}
                              </span>
                              <div className="flex items-center gap-1">
                                <input 
                                  type="number" 
                                  defaultValue={minutes} 
                                  className="w-12 px-2 py-1 border border-gray-300 rounded text-[#4a90a4] font-mono font-medium text-center text-sm"
                                  min="0"
                                />
                                <span className="text-gray-500">:</span>
                                <input 
                                  type="number" 
                                  defaultValue={seconds} 
                                  className="w-12 px-2 py-1 border border-gray-300 rounded text-[#4a90a4] font-mono font-medium text-center text-sm"
                                  min="0"
                                  max="59"
                                />
                                <span className="text-xs text-gray-400 ml-1">(mm:ss)</span>
                              </div>
                            </div>
                            <label className="flex items-center gap-2 cursor-pointer">
                              <input type="checkbox" className="w-4 h-4 accent-[#4a90a4]" />
                              <span className="text-xs text-gray-600">Domanda a fine lezione</span>
                            </label>
                          </div>
                          
                          {ip.questions?.filter((q: any) => q.id).map((q: any) => (
                            <div key={q.id} className="mb-4 last:mb-0 bg-white rounded p-3 border border-gray-100">
                              <p className="text-sm font-medium text-gray-800 mb-2">{q.text}</p>
                              <div className="pl-4 space-y-1">
                                {q.answers?.map((a: any) => (
                                  <div key={a.id} className="flex items-start gap-2 text-sm">
                                    {a.isCorrect ? (
                                      <CheckCircle size={16} className="text-green-500 mt-0.5 flex-shrink-0" />
                                    ) : (
                                      <XCircle size={16} className="text-gray-300 mt-0.5 flex-shrink-0" />
                                    )}
                                    <span className={a.isCorrect ? 'text-green-700 font-medium' : 'text-gray-600'}>
                                      {a.text}
                                    </span>
                                  </div>
                                ))}
                              </div>
                            </div>
                          ))}
                        </div>
                      );
                    })}
                  </div>
                ) : (
                  <p className="text-gray-500 text-center py-8">Nessuna domanda associata</p>
                )}
              </div>
            </div>
          </div>

          {/* Colonna destra - Info e Azioni */}
          <div className="space-y-6">
            {/* Info */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <h3 className="font-semibold text-gray-800 mb-4">Informazioni</h3>
              <div className="space-y-3 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-500">ID Legacy</span>
                  <span className="font-medium">{lo.legacyId || '-'}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">Tipo</span>
                  <span className="font-medium">
                    {lo.objectType === 1 ? 'Video' : lo.objectType === 2 ? 'Slide' : 'Documento'}
                  </span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">Durata</span>
                  <span className="font-medium">{lo.duration} min</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">Soglia superamento</span>
                  <span className="font-medium">{lo.percentageToPass}%</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-500">JWPlayer Code</span>
                  <span className="font-mono text-xs">{lo.jwplayerCode || '-'}</span>
                </div>
              </div>
            </div>

            {/* Categoria */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <h3 className="font-semibold text-gray-800 mb-4">Categoria</h3>
              <select className="w-full text-sm border border-gray-300 rounded px-3 py-2 bg-white mb-2">
                <option value="">Seleziona categoria</option>
                <option value="sicurezza">SICUREZZA</option>
                <option value="informatica">INFORMATICA</option>
                <option value="haccp">HACCP</option>
                <option value="231">231</option>
                <option value="hr">HR</option>
              </select>
              <button 
                className="text-xs text-[#4a90a4] hover:underline"
                onClick={() => {
                  const newCat = prompt("Inserisci il nome della nuova categoria:");
                  if (newCat && newCat.trim()) {
                    toast({ title: `Categoria "${newCat.trim().toUpperCase()}" da aggiungere`, description: "Funzionalità in sviluppo" });
                  }
                }}
              >
                + Aggiungi categoria
              </button>
            </div>

            {/* Azioni */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <h3 className="font-semibold text-gray-800 mb-4">Azioni</h3>
              <div className="space-y-2">
                <button className="w-full px-4 py-2 bg-[#4a90a4] text-white rounded hover:bg-[#3d7a8c] transition-colors text-sm">
                  Salva Modifiche
                </button>
                <button className={`w-full px-4 py-2 rounded text-sm transition-colors ${
                  lo.suspended 
                    ? 'bg-green-500 text-white hover:bg-green-600' 
                    : 'bg-red-500 text-white hover:bg-red-600'
                }`}>
                  {lo.suspended ? 'Riattiva' : 'Sospendi'}
                </button>
                <button className="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50 transition-colors text-sm">
                  Duplica
                </button>
              </div>
            </div>

            {/* File associati */}
            <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
              <h3 className="font-semibold text-gray-800 mb-4">File Associati</h3>
              <div className="space-y-2 text-sm">
                {lo.videoFilename && (
                  <div className="flex items-center gap-2 text-gray-600">
                    <Play size={14} />
                    <span className="truncate">{lo.videoFilename}</span>
                  </div>
                )}
                {lo.slideFilename && (
                  <div className="flex items-center gap-2 text-gray-600">
                    <Image size={14} />
                    <span className="truncate">{lo.slideFilename}</span>
                  </div>
                )}
                {lo.documentFilename && (
                  <div className="flex items-center gap-2 text-gray-600">
                    <FileText size={14} />
                    <span className="truncate">{lo.documentFilename}</span>
                  </div>
                )}
                {!lo.videoFilename && !lo.slideFilename && !lo.documentFilename && (
                  <p className="text-gray-400">Nessun file associato</p>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
