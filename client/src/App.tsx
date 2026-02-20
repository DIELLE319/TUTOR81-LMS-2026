import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "@/lib/queryClient";
import { AuthProvider, useAuth } from "@/hooks/use-auth";
import { Route, Switch, Redirect } from "wouter";
import { lazy, Suspense } from "react";
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
const PlayerLogin = lazy(() => import("@/pages/PlayerLogin"));
const NotFound = lazy(() => import("@/pages/NotFound"));

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
        <Route><NotFound /></Route>
      </Switch>
    </Suspense>
  );
}

export default function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <AppRoutes />
      </AuthProvider>
    </QueryClientProvider>
  );
}
