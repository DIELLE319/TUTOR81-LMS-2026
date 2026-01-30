import { Link, useLocation } from "wouter";
import { useAuth } from "@/hooks/use-auth";
import { Button } from "@/components/ui/button";
import { LogOut, BookOpen, LayoutDashboard, User } from "lucide-react";

export function Layout({ children }: { children: React.ReactNode }) {
  const { user, logout } = useAuth();
  const [location] = useLocation();

  const navItems = [
    { href: "/", label: "Home", icon: BookOpen },
    ...(user ? [{ href: "/dashboard", label: "My Learning", icon: LayoutDashboard }] : []),
    ...(user ? [{ href: "/instructor", label: "Instructor Mode", icon: User }] : []),
  ];

  return (
    <div className="min-h-screen bg-background font-sans">
      <header className="sticky top-0 z-50 w-full border-b bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container flex h-16 items-center px-4 md:px-6">
          <div className="mr-8 flex items-center gap-2 font-display text-xl font-bold text-primary">
            <BookOpen className="h-6 w-6" />
            <span>EduFlow</span>
          </div>
          
          <nav className="flex items-center gap-6 text-sm font-medium">
            {navItems.map((item) => (
              <Link
                key={item.href}
                href={item.href}
                className={`flex items-center gap-2 transition-colors hover:text-foreground/80 ${
                  location === item.href ? "text-foreground" : "text-foreground/60"
                }`}
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </Link>
            ))}
          </nav>

          <div className="ml-auto flex items-center gap-4">
            {user ? (
              <div className="flex items-center gap-4">
                <span className="hidden text-sm text-muted-foreground md:inline-block">
                  Hello, {user.firstName || user.email}
                </span>
                <Button variant="ghost" size="sm" onClick={() => logout()}>
                  <LogOut className="mr-2 h-4 w-4" />
                  Logout
                </Button>
              </div>
            ) : (
              <Button asChild>
                <a href="/api/login">Login / Register</a>
              </Button>
            )}
          </div>
        </div>
      </header>
      <main className="container px-4 py-8 md:px-6 md:py-12">
        {children}
      </main>
      <footer className="border-t py-6 md:py-0">
        <div className="container flex flex-col items-center justify-between gap-4 md:h-24 md:flex-row px-4 md:px-6">
          <p className="text-center text-sm leading-loose text-muted-foreground md:text-left">
            Built with ❤️ for better learning experiences.
          </p>
        </div>
      </footer>
    </div>
  );
}
