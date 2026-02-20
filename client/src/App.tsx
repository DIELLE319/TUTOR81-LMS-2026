import { Switch, Route, Redirect } from "wouter";
import { queryClient } from "./lib/queryClient";
import { QueryClientProvider } from "@tanstack/react-query";
import { Toaster } from "@/components/ui/toaster";
import { TooltipProvider } from "@/components/ui/tooltip";
import { AuthProvider, useAuth } from "@/hooks/use-auth";
import Layout from "@/components/Layout";
import EnvironmentBanner from "@/components/EnvironmentBanner";
import PublicHeader from "@/components/PublicHeader";

import Dashboard from "@/pages/Dashboard";
import Tutors from "@/pages/Tutors";
import Clients from "@/pages/Clients";
import Catalog from "@/pages/Catalog";
import Sales from "@/pages/Sales";
import Users from "@/pages/Users";
import Certificates from "@/pages/Certificates";
import CreateCompany from "@/pages/CreateCompany";
import ActivatedCourses from "@/pages/ActivatedCourses";
import Invoicing from "@/pages/Invoicing";
import ContentManagement from "@/pages/ContentManagement";
import EmailPreview from "@/pages/EmailPreview";
import LearningObjects from "@/pages/LearningObjects";
import LearningObjectDetail from "@/pages/LearningObjectDetail";
import CourseStructure from "@/pages/CourseStructure";
import CoursePlayer from "@/pages/CoursePlayer";
import CoursePlayerVideo from "@/pages/CoursePlayerVideo";
import PlayerLogin from "@/pages/PlayerLogin";
import Videoconference from "@/pages/Videoconference";
import Tracking from "@/pages/Tracking";
import Feedback from "@/pages/Feedback";
import MyCompany from "@/pages/MyCompany";
import CompanyUsers from "@/pages/CompanyUsers";
import AdminLoginMarker from "@/pages/AdminLoginMarker";
import SuperAdminEntry from "@/pages/SuperAdminEntry";
import NotFound from "@/pages/not-found";

function ProtectedRoute({ children }: { children: React.ReactNode }) {
  const { user, isLoading } = useAuth();
  
  if (isLoading) {
    return (
      <div className="min-h-screen bg-black flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full mx-auto mb-4"></div>
          <p className="text-gray-500">Caricamento...</p>
        </div>
      </div>
    );
  }

  if (!user) {
    return <Redirect to="/login" />;
  }

  return <Layout>{children}</Layout>;
}

function LoginPage() {
  const { user, isLoading, login } = useAuth();

  if (isLoading) {
    return (
      <div className="min-h-screen bg-black flex items-center justify-center">
        <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  if (user) {
    return <Redirect to={user.role === 1000 ? "/superadmin" : "/dashboard"} />;
  }

  return (
    <div className="min-h-screen bg-black flex flex-col relative overflow-hidden font-sans">
      <EnvironmentBanner />
      <PublicHeader />

      <div className="flex-1 flex items-center justify-center p-4">
      <div className="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div className="absolute -top-[50%] -left-[20%] w-[100%] h-[100%] bg-gradient-to-br from-yellow-500/10 to-transparent rounded-full blur-[100px] animate-spin-slow" />
        <div className="absolute -bottom-[50%] -right-[20%] w-[100%] h-[100%] bg-gradient-to-tl from-blue-900/20 to-transparent rounded-full blur-[100px]" />
      </div>

      <div className="bg-zinc-900/80 backdrop-blur-xl border border-zinc-800 p-8 rounded-2xl shadow-2xl w-full max-w-md relative z-10">
        <div className="text-center mb-8">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-500 mb-4 shadow-lg shadow-yellow-500/20">
            <svg className="w-8 h-8 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h1 className="text-3xl font-bold text-white mb-2">Benvenuto</h1>
          <p className="text-zinc-400">Accedi alla piattaforma Tutor81 LMS</p>
        </div>

        <button
          onClick={login}
          className="w-full bg-yellow-500 hover:bg-yellow-400 text-black font-bold py-4 rounded-xl shadow-lg shadow-yellow-500/20 hover:shadow-yellow-500/40 transition-all active:scale-95 flex items-center justify-center gap-2"
          data-testid="button-login"
        >
          Accedi
          <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 8l4 4m0 0l-4 4m4-4H3" />
          </svg>
        </button>

        <div className="mt-8 text-center text-zinc-500 text-xs">
          &copy; {new Date().getFullYear()} Tutor81. Tutti i diritti riservati.
        </div>
      </div>
      </div>
    </div>
  );
}

function RoleHomeRedirect() {
  const { user, isLoading } = useAuth();

  if (isLoading) {
    return (
      <div className="min-h-screen bg-black flex items-center justify-center">
        <div className="animate-spin w-8 h-8 border-2 border-yellow-500 border-t-transparent rounded-full"></div>
      </div>
    );
  }

  if (!user) return <Redirect to="/login" />;

  return <Redirect to={user.role === 1000 ? "/superadmin" : "/dashboard"} />;
}

function Router() {
  return (
    <Switch>
      <Route path="/admin-login" component={AdminLoginMarker} />
      <Route path="/login" component={LoginPage} />

      <Route path="/superadmin">
        <ProtectedRoute><SuperAdminEntry /></ProtectedRoute>
      </Route>
      
      <Route path="/dashboard">
        <ProtectedRoute><Dashboard /></ProtectedRoute>
      </Route>
      
      <Route path="/tutors">
        <ProtectedRoute><Tutors /></ProtectedRoute>
      </Route>
      
      <Route path="/clients">
        <ProtectedRoute><Clients /></ProtectedRoute>
      </Route>
      
      <Route path="/companies/new">
        <ProtectedRoute><CreateCompany /></ProtectedRoute>
      </Route>
      
      <Route path="/catalog">
        <ProtectedRoute><Catalog /></ProtectedRoute>
      </Route>
      
      <Route path="/sales">
        <ProtectedRoute><Sales /></ProtectedRoute>
      </Route>
      
      <Route path="/invoicing">
        <ProtectedRoute><Invoicing /></ProtectedRoute>
      </Route>
      
      <Route path="/users">
        <ProtectedRoute><Users /></ProtectedRoute>
      </Route>
      
      <Route path="/certificates">
        <ProtectedRoute><Certificates /></ProtectedRoute>
      </Route>
      
      <Route path="/courses/active">
        <ProtectedRoute><ActivatedCourses /></ProtectedRoute>
      </Route>
      
      <Route path="/courses/expiring">
        <ProtectedRoute>
          <div className="p-6 bg-black min-h-screen">
            <h1 className="text-2xl font-bold text-white">Corsi da Ripetere</h1>
            <p className="text-gray-500 mt-2">Funzionalità in arrivo...</p>
          </div>
        </ProtectedRoute>
      </Route>
      
      <Route path="/users/import">
        <ProtectedRoute>
          <div className="p-6 bg-black min-h-screen">
            <h1 className="text-2xl font-bold text-white">Importa Utenti</h1>
            <p className="text-gray-500 mt-2">Funzionalità in arrivo...</p>
          </div>
        </ProtectedRoute>
      </Route>

      <Route path="/my-company">
        <ProtectedRoute><MyCompany /></ProtectedRoute>
      </Route>

      <Route path="/company-users">
        <ProtectedRoute><CompanyUsers /></ProtectedRoute>
      </Route>

      <Route path="/content-management">
        <ProtectedRoute><ContentManagement /></ProtectedRoute>
      </Route>

      <Route path="/videoconference">
        <ProtectedRoute><Videoconference /></ProtectedRoute>
      </Route>

      <Route path="/tracking">
        <ProtectedRoute><Tracking /></ProtectedRoute>
      </Route>

      <Route path="/feedback">
        <ProtectedRoute><Feedback /></ProtectedRoute>
      </Route>

      <Route path="/learning-objects">
        <ProtectedRoute><LearningObjects /></ProtectedRoute>
      </Route>

      <Route path="/learning-objects/:id">
        {(params) => (
          <ProtectedRoute><LearningObjectDetail id={params.id} /></ProtectedRoute>
        )}
      </Route>

      <Route path="/course-structure/:id">
        <ProtectedRoute><CourseStructure /></ProtectedRoute>
      </Route>

      <Route path="/course-structure">
        <ProtectedRoute><CourseStructure /></ProtectedRoute>
      </Route>

      <Route path="/email-preview">
        <EmailPreview />
      </Route>

      <Route path="/player-login">
        <PlayerLogin />
      </Route>

      <Route path="/player/:enrollmentId">
        <CoursePlayer />
      </Route>

      <Route path="/player">
        <CoursePlayer />
      </Route>

      <Route path="/player/course/:id">
        <CoursePlayerVideo />
      </Route>

      <Route path="/">
        <RoleHomeRedirect />
      </Route>
      
      <Route component={NotFound} />
    </Switch>
  );
}

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <AuthProvider>
          <Router />
          <Toaster />
        </AuthProvider>
      </TooltipProvider>
    </QueryClientProvider>
  );
}

export default App;
