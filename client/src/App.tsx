import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "@/lib/queryClient";
import { AuthProvider, useAuth } from "@/hooks/use-auth";
import { Route, Switch, Redirect } from "wouter";
import { lazy, Suspense, Component } from "react";
import type { ReactNode, ErrorInfo } from "react";
import Layout from "@/components/Layout";

const Dashboard = lazy(() => import("@/pages/Dashboard"));
const Catalog = lazy(() => import("@/pages/Catalog"));
const ActivatedCourses = lazy(() => import("@/pages/ActivatedCourses"));
const Clients = lazy(() => import("@/pages/Clients"));
const Users = lazy(() => import("@/pages/Users"));
const Certificates = lazy(() => import("@/pages/Certificates"));
const Sales = lazy(() => import("@/pages/Sales"));
const Tracking = lazy(() => import("@/pages/Tracking"));
const Feedback = lazy(() => import("@/pages/Feedback"));
const Invoicing = lazy(() => import("@/pages/Invoicing"));
const Videoconference = lazy(() => import("@/pages/Videoconference"));
const CreateCompany = lazy(() => import("@/pages/CreateCompany"));
const Tutors = lazy(() => import("@/pages/Tutors"));
const AssignCourse = lazy(() => import("@/pages/AssignCourse"));
const PlayerLogin = lazy(() => import("@/pages/PlayerLogin"));
const PlayerDashboard = lazy(() => import("@/pages/PlayerDashboard"));
const PlayerCourse = lazy(() => import("@/pages/PlayerCourse"));
const NotFound = lazy(() => import("@/pages/NotFound"));

class ErrorBoundary extends Component<{ children: ReactNode }, { hasError: boolean; error: Error | null }> {
  constructor(props: { children: ReactNode }) {
    super(props);
    this.state = { hasError: false, error: null };
  }
  static getDerivedStateFromError(error: Error) {
    return { hasError: true, error };
  }
  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error("[ErrorBoundary]", error, info);
  }
  render() {
    if (this.state.hasError) {
      return (
        <div className="min-h-screen bg-[#0a0a0a] flex items-center justify-center p-8">
          <div className="bg-[#1a1a1a] border border-red-500/30 rounded-xl p-8 max-w-lg text-center">
            <h2 className="text-xl font-bold text-red-400 mb-3">Errore di caricamento</h2>
            <p className="text-gray-400 text-sm mb-4">{this.state.error?.message || "Si è verificato un errore"}</p>
            <button onClick={() => window.location.reload()} className="px-6 py-2 bg-yellow-500 text-black font-bold rounded-lg text-sm hover:bg-yellow-600">Ricarica pagina</button>
          </div>
        </div>
      );
    }
    return this.props.children;
  }
}

function Loading() {
  return (
    <div className="flex items-center justify-center h-64">
      <div className="animate-spin w-8 h-8 border-4 border-yellow-500 border-t-transparent rounded-full" />
    </div>
  );
}

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth();
  if (isLoading) return <Loading />;
  if (!user) {
    window.location.href = "/api/login";
    return null;
  }
  return <Layout>{children}</Layout>;
}

function AppRoutes() {
  return (
    <Suspense fallback={<Loading />}>
      <Switch>
        <Route path="/player-login"><PlayerLogin /></Route>
        <Route path="/player/dashboard"><PlayerDashboard /></Route>
        <Route path="/player/course/:id"><PlayerCourse /></Route>
        <Route path="/login">{() => { window.location.href = "/api/login"; return null; }}</Route>
        <Route path="/">
          <ProtectedRoute><Dashboard /></ProtectedRoute>
        </Route>
        <Route path="/catalog">
          <ProtectedRoute><Catalog /></ProtectedRoute>
        </Route>
        <Route path="/activated-courses">
          <ProtectedRoute><ActivatedCourses /></ProtectedRoute>
        </Route>
        <Route path="/clients">
          <ProtectedRoute><Clients /></ProtectedRoute>
        </Route>
        <Route path="/create-company">
          <ProtectedRoute><CreateCompany /></ProtectedRoute>
        </Route>
        <Route path="/users">
          <ProtectedRoute><Users /></ProtectedRoute>
        </Route>
        <Route path="/certificates">
          <ProtectedRoute><Certificates /></ProtectedRoute>
        </Route>
        <Route path="/sales">
          <ProtectedRoute><Sales /></ProtectedRoute>
        </Route>
        <Route path="/tracking">
          <ProtectedRoute><Tracking /></ProtectedRoute>
        </Route>
        <Route path="/feedback">
          <ProtectedRoute><Feedback /></ProtectedRoute>
        </Route>
        <Route path="/invoicing">
          <ProtectedRoute><Invoicing /></ProtectedRoute>
        </Route>
        <Route path="/videoconference">
          <ProtectedRoute><Videoconference /></ProtectedRoute>
        </Route>
        <Route path="/tutors">
          <ProtectedRoute><Tutors /></ProtectedRoute>
        </Route>
        <Route path="/assign-course">
          <ProtectedRoute><AssignCourse /></ProtectedRoute>
        </Route>
        <Route><NotFound /></Route>
      </Switch>
    </Suspense>
  );
}

export default function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <AppRoutes />
        </AuthProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  );
}
