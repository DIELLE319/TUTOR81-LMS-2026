import { QueryClient } from "@tanstack/react-query";

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: { refetchOnWindowFocus: false, retry: 1, staleTime: 5 * 60 * 1000 },
  },
});

export async function apiRequest(method: string, url: string, body?: any) {
  const opts: RequestInit = {
    method,
    headers: { "Content-Type": "application/json" },
    credentials: "include",
  };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  if (!res.ok) {
    const data = await res.json().catch(() => ({ error: res.statusText }));
    throw new Error(data.error || data.message || "Request failed");
  }
  return res.json();
}
