import { useMutation, useQueryClient } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";
import { type InsertLesson } from "@shared/schema";

export function useCreateLesson() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (data: InsertLesson & { courseId: number }) => {
      const url = buildUrl(api.lessons.create.path, { moduleId: data.moduleId });
      const { moduleId, courseId, ...body } = data;
      
      const res = await fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
        credentials: "include",
      });
      if (!res.ok) throw new Error("Failed to create lesson");
      const newLesson = api.lessons.create.responses[201].parse(await res.json());
      return { ...newLesson, courseId }; // Pass courseId for invalidation
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: [api.courses.get.path, data.courseId] });
    },
  });
}

export function useUpdateLesson() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, courseId, ...data }: { id: number; courseId: number } & Partial<InsertLesson>) => {
      const url = buildUrl(api.lessons.update.path, { id });
      const res = await fetch(url, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data),
        credentials: "include",
      });
      if (!res.ok) throw new Error("Failed to update lesson");
      return { ...api.lessons.update.responses[200].parse(await res.json()), courseId };
    },
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: [api.courses.get.path, data.courseId] });
    },
  });
}

export function useDeleteLesson() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async ({ id, courseId }: { id: number; courseId: number }) => {
      const url = buildUrl(api.lessons.delete.path, { id });
      const res = await fetch(url, { method: "DELETE", credentials: "include" });
      if (!res.ok) throw new Error("Failed to delete lesson");
      return { courseId };
    },
    onSuccess: ({ courseId }) => {
      queryClient.invalidateQueries({ queryKey: [api.courses.get.path, courseId] });
    },
  });
}
