import { createContext, useContext, ReactNode } from "react";
import { useQuery } from "@tanstack/react-query";

interface AuthUser {
  id: string;
  email: string;
  firstName: string | null;
  lastName: string | null;
  role: number;
  tutorId: number | null;
  tutorName: string | null;
  profileImageUrl: string | null;
  idcompany: number | null;
}

interface AuthCtx {
  user: AuthUser | null;
  isLoading: boolean;
  error: Error | null;
}

const AuthContext = createContext<AuthCtx>({ user: null, isLoading: true, error: null });

export function AuthProvider({ children }: { children: ReactNode }) {
  const { data, isLoading, error } = useQuery<AuthUser>({
    queryKey: ["auth-user"],
    queryFn: async () => {
      const res = await fetch("/api/auth/user", { credentials: "include" });
      if (!res.ok) throw new Error("Not authenticated");
      return res.json();
    },
    retry: false,
    staleTime: 5 * 60 * 1000,
  });

  return (
    <AuthContext.Provider value={{ user: data ?? null, isLoading, error: error as Error | null }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
