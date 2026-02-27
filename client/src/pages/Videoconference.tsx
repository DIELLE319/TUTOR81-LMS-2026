import { useState, useEffect, useRef } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { apiRequest } from "@/lib/queryClient";
import { useToast } from "@/hooks/use-toast";
import { Video, Plus, X, Play, Trash2, Copy, ExternalLink, Clock, Users, Calendar, Edit2 } from "lucide-react";

interface Room {
  id: number;
  tutorId: number | null;
  roomName: string;
  jitsiRoomId: string;
  description: string | null;
  scheduledAt: string | null;
  duration: number | null;
  participantEmails: string | null;
  status: string | null;
  createdAt: string | null;
  tutorName: string | null;
}

const JITSI_DOMAIN = "meet.jit.si";

function statusBadge(status: string | null) {
  switch (status) {
    case "active": return "bg-green-500 text-white";
    case "ended": return "bg-gray-500 text-white";
    default: return "bg-yellow-500 text-black";
  }
}
function statusLabel(status: string | null) {
  switch (status) {
    case "active": return "In corso";
    case "ended": return "Terminata";
    default: return "Programmata";
  }
}

export default function Videoconference() {
  const [showCreate, setShowCreate] = useState(false);
  const [activeRoom, setActiveRoom] = useState<Room | null>(null);
  const [form, setForm] = useState({ roomName: "", description: "", scheduledAt: "", duration: 60, participantEmails: "" });
  const jitsiContainerRef = useRef<HTMLDivElement>(null);
  const jitsiApiRef = useRef<any>(null);
  const qc = useQueryClient();
  const { toast } = useToast();

  const { data: rooms = [], isLoading } = useQuery<Room[]>({
    queryKey: ["video-rooms"],
    queryFn: async () => {
      const r = await fetch("/api/video-rooms", { credentials: "include" });
      if (!r.ok) throw new Error("Errore");
      return r.json();
    },
  });

  const createMut = useMutation({
    mutationFn: (data: typeof form) => apiRequest("POST", "/api/video-rooms", data),
    onSuccess: () => {
      toast({ title: "Stanza creata!" });
      qc.invalidateQueries({ queryKey: ["video-rooms"] });
      setShowCreate(false);
      setForm({ roomName: "", description: "", scheduledAt: "", duration: 60, participantEmails: "" });
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const updateMut = useMutation({
    mutationFn: ({ id, ...data }: { id: number; status?: string }) => apiRequest("PATCH", `/api/video-rooms/${id}`, data),
    onSuccess: () => qc.invalidateQueries({ queryKey: ["video-rooms"] }),
  });

  const deleteMut = useMutation({
    mutationFn: (id: number) => apiRequest("DELETE", `/api/video-rooms/${id}`),
    onSuccess: () => {
      toast({ title: "Stanza eliminata" });
      qc.invalidateQueries({ queryKey: ["video-rooms"] });
    },
    onError: (e: Error) => toast({ title: "Errore", description: e.message, variant: "destructive" }),
  });

  const joinRoom = (room: Room) => {
    setActiveRoom(room);
    if (room.status === "scheduled") {
      updateMut.mutate({ id: room.id, status: "active" });
    }
  };

  const leaveRoom = () => {
    if (jitsiApiRef.current) {
      jitsiApiRef.current.dispose();
      jitsiApiRef.current = null;
    }
    if (activeRoom) {
      updateMut.mutate({ id: activeRoom.id, status: "ended" });
    }
    setActiveRoom(null);
  };

  // Load Jitsi when activeRoom changes
  useEffect(() => {
    if (!activeRoom || !jitsiContainerRef.current) return;

    // Load Jitsi external API script if not present
    const loadJitsi = () => {
      if (jitsiApiRef.current) {
        jitsiApiRef.current.dispose();
        jitsiApiRef.current = null;
      }

      const api = new (window as any).JitsiMeetExternalAPI(JITSI_DOMAIN, {
        roomName: `tutor81-${activeRoom.jitsiRoomId}`,
        parentNode: jitsiContainerRef.current!,
        width: "100%",
        height: "100%",
        configOverwrite: {
          startWithAudioMuted: true,
          startWithVideoMuted: false,
          prejoinConfig: { enabled: false },
          disableDeepLinking: true,
          toolbarButtons: [
            "camera", "chat", "closedcaptions", "desktop", "download",
            "filmstrip", "fullscreen", "hangup", "microphone",
            "participants-pane", "raisehand", "select-background",
            "settings", "shareaudio", "sharedvideo", "shortcuts",
            "tileview", "toggle-camera",
          ],
        },
        interfaceConfigOverwrite: {
          SHOW_JITSI_WATERMARK: false,
          SHOW_WATERMARK_FOR_GUESTS: false,
          DEFAULT_BACKGROUND: "#0a0a0a",
          TOOLBAR_ALWAYS_VISIBLE: true,
        },
        userInfo: {
          displayName: activeRoom.roomName,
        },
      });

      api.addEventListener("readyToClose", () => leaveRoom());
      jitsiApiRef.current = api;
    };

    if ((window as any).JitsiMeetExternalAPI) {
      loadJitsi();
    } else {
      const script = document.createElement("script");
      script.src = `https://${JITSI_DOMAIN}/external_api.js`;
      script.async = true;
      script.onload = loadJitsi;
      document.head.appendChild(script);
    }

    return () => {
      if (jitsiApiRef.current) {
        jitsiApiRef.current.dispose();
        jitsiApiRef.current = null;
      }
    };
  }, [activeRoom]);

  const copyLink = (room: Room) => {
    const url = `https://${JITSI_DOMAIN}/tutor81-${room.jitsiRoomId}`;
    navigator.clipboard.writeText(url);
    toast({ title: "Link copiato!" });
  };

  const openExternal = (room: Room) => {
    window.open(`https://${JITSI_DOMAIN}/tutor81-${room.jitsiRoomId}`, "_blank");
  };

  // If a room is active, show the Jitsi embed full screen
  if (activeRoom) {
    return (
      <div className="flex flex-col h-[calc(100vh-80px)]">
        <div className="flex items-center justify-between bg-[#141414] border border-white/5 rounded-t-xl px-4 py-2">
          <div className="flex items-center gap-3">
            <Video size={16} className="text-green-400" />
            <span className="text-sm font-bold text-gray-200">{activeRoom.roomName}</span>
            <span className="text-[10px] font-bold px-2 py-0.5 rounded bg-green-500 text-white">IN CORSO</span>
          </div>
          <div className="flex items-center gap-2">
            <button onClick={() => copyLink(activeRoom)} className="h-7 px-3 bg-white/5 border border-white/10 text-gray-300 rounded text-xs flex items-center gap-1.5 hover:bg-white/10">
              <Copy size={11} />Copia Link
            </button>
            <button onClick={leaveRoom} className="h-7 px-3 bg-red-500 hover:bg-red-600 text-white rounded text-xs font-bold flex items-center gap-1.5">
              <X size={11} />Termina
            </button>
          </div>
        </div>
        <div ref={jitsiContainerRef} className="flex-1 bg-black rounded-b-xl overflow-hidden" />
      </div>
    );
  }

  return (
    <div>
      {/* Header */}
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-xl font-bold text-yellow-500">Videoconferenza</h1>
        <button onClick={() => setShowCreate(true)} className="h-8 px-4 bg-yellow-500 text-black rounded-lg text-xs font-bold flex items-center gap-1.5 hover:bg-yellow-600">
          <Plus size={13} />Nuova Stanza
        </button>
      </div>

      {/* Yellow toolbar */}
      <div className="bg-yellow-500 rounded-t-xl px-4 py-2.5 flex items-center gap-3">
        <Video size={16} className="text-black" />
        <span className="text-sm font-bold text-black">Stanze videoconferenza</span>
        <span className="ml-auto text-sm font-bold text-black">Totale: {rooms.length} stanze</span>
      </div>

      {/* Room list */}
      {isLoading ? (
        <div className="text-center py-12 text-gray-500 bg-[#141414] rounded-b-xl border border-white/5 border-t-0">Caricamento...</div>
      ) : rooms.length === 0 ? (
        <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 p-12 text-center">
          <Video size={48} className="mx-auto text-gray-600 mb-4" />
          <p className="text-gray-500 mb-4">Nessuna stanza videoconferenza creata</p>
          <button onClick={() => setShowCreate(true)} className="h-8 px-4 bg-yellow-500 text-black rounded-lg text-xs font-bold hover:bg-yellow-600">
            Crea la prima stanza
          </button>
        </div>
      ) : (
        <div className="bg-[#141414] rounded-b-xl border border-white/5 border-t-0 overflow-x-auto">
          <table className="w-full text-sm">
            <thead>
              <tr className="bg-yellow-500 text-black text-[11px] font-bold uppercase">
                <th className="p-2.5 text-left w-12">ID</th>
                <th className="p-2.5 text-left">Stanza</th>
                <th className="p-2.5 text-left">Descrizione</th>
                <th className="p-2.5 text-left">Programmata</th>
                <th className="p-2.5 text-left">Durata</th>
                <th className="p-2.5 text-left">Partecipanti</th>
                <th className="p-2.5 text-left">Stato</th>
                <th className="p-2.5 text-left">Azioni</th>
              </tr>
            </thead>
            <tbody>
              {rooms.map((r, i) => (
                <tr key={r.id} className={`border-b border-white/5 hover:bg-white/[0.03] ${i % 2 === 0 ? "bg-[#141414]" : "bg-[#1a1a1a]"}`}>
                  <td className="p-2.5 text-yellow-500 font-bold text-xs">{r.id}</td>
                  <td className="p-2.5">
                    <div className="text-gray-200 text-xs font-medium">{r.roomName}</div>
                    <div className="text-[10px] text-gray-600 font-mono">{r.jitsiRoomId}</div>
                  </td>
                  <td className="p-2.5 text-gray-400 text-xs">{r.description || "—"}</td>
                  <td className="p-2.5 text-gray-400 text-xs">
                    {r.scheduledAt ? (
                      <div className="flex items-center gap-1">
                        <Calendar size={10} className="text-cyan-400" />
                        {new Date(r.scheduledAt).toLocaleString("it-IT", { day: "2-digit", month: "2-digit", year: "numeric", hour: "2-digit", minute: "2-digit" })}
                      </div>
                    ) : "—"}
                  </td>
                  <td className="p-2.5 text-gray-400 text-xs">
                    <div className="flex items-center gap-1">
                      <Clock size={10} className="text-orange-400" />{r.duration || 60} min
                    </div>
                  </td>
                  <td className="p-2.5 text-gray-400 text-xs">
                    {r.participantEmails ? (
                      <div className="flex items-center gap-1">
                        <Users size={10} className="text-blue-400" />
                        {r.participantEmails.split(",").length}
                      </div>
                    ) : "—"}
                  </td>
                  <td className="p-2.5">
                    <span className={`text-[10px] font-bold px-2 py-0.5 rounded ${statusBadge(r.status)}`}>
                      {statusLabel(r.status)}
                    </span>
                  </td>
                  <td className="p-2.5">
                    <div className="flex items-center gap-1">
                      {r.status !== "ended" && (
                        <button onClick={() => joinRoom(r)} className="h-6 px-2 bg-green-500 hover:bg-green-600 text-white text-[10px] font-bold rounded flex items-center gap-1" title="Avvia">
                          <Play size={10} />Entra
                        </button>
                      )}
                      <button onClick={() => copyLink(r)} className="w-6 h-6 rounded bg-white/5 hover:bg-white/10 flex items-center justify-center text-blue-400" title="Copia link">
                        <Copy size={11} />
                      </button>
                      <button onClick={() => openExternal(r)} className="w-6 h-6 rounded bg-white/5 hover:bg-white/10 flex items-center justify-center text-cyan-400" title="Apri in nuova tab">
                        <ExternalLink size={11} />
                      </button>
                      <button onClick={() => { if (confirm(`Eliminare "${r.roomName}"?`)) deleteMut.mutate(r.id); }}
                        className="w-6 h-6 rounded bg-white/5 hover:bg-red-500/20 flex items-center justify-center text-red-400" title="Elimina">
                        <Trash2 size={11} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {/* Create Modal */}
      {showCreate && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4" onClick={() => setShowCreate(false)}>
          <div className="fixed inset-0 bg-black/70" />
          <div className="relative bg-[#1a1a1a] border border-white/10 rounded-2xl shadow-2xl w-full max-w-lg" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between p-5 border-b border-white/10">
              <h2 className="text-lg font-bold text-yellow-500">Nuova Stanza Videoconferenza</h2>
              <button onClick={() => setShowCreate(false)} className="text-gray-500 hover:text-gray-300"><X size={20} /></button>
            </div>
            <div className="p-5 space-y-4">
              <div>
                <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Nome Stanza *</label>
                <input type="text" value={form.roomName} onChange={(e) => setForm({ ...form, roomName: e.target.value })}
                  placeholder="es. Riunione Formazione Sicurezza"
                  className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
              </div>
              <div>
                <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Descrizione</label>
                <input type="text" value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })}
                  placeholder="Opzionale"
                  className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Data e Ora</label>
                  <input type="datetime-local" value={form.scheduledAt} onChange={(e) => setForm({ ...form, scheduledAt: e.target.value })}
                    className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                </div>
                <div>
                  <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Durata (minuti)</label>
                  <input type="number" value={form.duration} onChange={(e) => setForm({ ...form, duration: parseInt(e.target.value) || 60 })}
                    className="w-full h-9 px-3 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50" />
                </div>
              </div>
              <div>
                <label className="block text-[11px] font-semibold text-gray-500 uppercase mb-1">Email Partecipanti (separate da virgola)</label>
                <textarea value={form.participantEmails} onChange={(e) => setForm({ ...form, participantEmails: e.target.value })}
                  placeholder="mario@azienda.it, luigi@azienda.it"
                  rows={2}
                  className="w-full px-3 py-2 bg-[#141414] border border-white/10 rounded-lg text-sm text-gray-200 focus:outline-none focus:border-yellow-500/50 resize-none" />
              </div>
            </div>
            <div className="flex justify-end gap-2 p-5 border-t border-white/10">
              <button onClick={() => setShowCreate(false)} className="h-9 px-4 bg-white/5 border border-white/10 text-gray-300 rounded-lg text-xs font-medium hover:bg-white/10">
                Annulla
              </button>
              <button onClick={() => createMut.mutate(form)} disabled={!form.roomName || createMut.isPending}
                className="h-9 px-6 bg-yellow-500 text-black rounded-lg text-xs font-bold hover:bg-yellow-600 disabled:opacity-50">
                {createMut.isPending ? "Creazione..." : "Crea Stanza"}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
