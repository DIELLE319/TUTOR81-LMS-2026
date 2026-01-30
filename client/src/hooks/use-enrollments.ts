import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";

export function useEnrollments() {
  return useQuery({
    queryKey: [api.enrollments.list.path],
    queryFn: async () => {
      const res = await fetch(api.enrollments.list.path, { credentials: "include" });
      if (!res.ok) throw new Error("Failed to fetch enrollments");
      return api.enrollments.list.responses[200].parse(await res.json());
    },
  });
}

export function useCheckEnrollment(courseId: number) {
  return useQuery({
    queryKey: [api.enrollments.check.path, courseId],
    queryFn: async () => {
      const url = buildUrl(api.enrollments.check.path, { courseId });
      const res = await fetch(url, { credentials: "include" });
      if (!res.ok) throw new Error("Failed to check enrollment");
      return api.enrollments.check.responses[200].parse(await res.json());
    },
    enabled: !!courseId,
  });
}

export function useEnroll() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async (courseId: number) => {
      const url = buildUrl(api.enrollments.enroll.path, { courseId });
      const res = await fetch(url, { method: "POST", credentials: "include" });
      if (!res.ok) throw new Error("Failed to enroll");
      return api.enrollments.enroll.responses[201].parse(await res.json());
    },
    onSuccess: (_, courseId) => {
      queryClient.invalidateQueries({ queryKey: [api.enrollments.list.path] });
      queryClient.invalidateQueries({ queryKey: [api.enrollments.check.path, courseId] });
    },
  });
}
