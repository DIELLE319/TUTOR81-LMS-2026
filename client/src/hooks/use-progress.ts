import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";

export function useUpdateProgress() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ lessonId, completed }: { lessonId: number; completed: boolean }) => {
      const url = buildUrl(api.progress.update.path, { lessonId });
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ completed }),
        credentials: "include",
      });
      if (!res.ok) throw new Error("Failed to update progress");
      return api.progress.update.responses[200].parse(await res.json());
    },
    onSuccess: () => {
      // Invalidate enrollments as progress might affect overall course progress calculation if we had it
      // But mainly we might want to refetch the course structure if we displayed progress there
      // For now, simpler invalidation strategy is fine.
    },
  });
}
