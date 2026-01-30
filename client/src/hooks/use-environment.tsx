import { createContext, useContext, useState, useEffect, ReactNode } from "react";

type Environment = "lms" | "content";

interface EnvironmentContextType {
  environment: Environment;
  setEnvironment: (env: Environment) => void;
}

const EnvironmentContext = createContext<EnvironmentContextType | null>(null);

export function EnvironmentProvider({ children }: { children: ReactNode }) {
  const [environment, setEnvironment] = useState<Environment>(() => {
    const stored = localStorage.getItem("tutor81_environment");
    return (stored as Environment) || "lms";
  });

  useEffect(() => {
    localStorage.setItem("tutor81_environment", environment);
  }, [environment]);

  return (
    <EnvironmentContext.Provider value={{ environment, setEnvironment }}>
      {children}
    </EnvironmentContext.Provider>
  );
}

export function useEnvironment() {
  const context = useContext(EnvironmentContext);
  if (!context) {
    throw new Error("useEnvironment must be used within EnvironmentProvider");
  }
  return context;
}
