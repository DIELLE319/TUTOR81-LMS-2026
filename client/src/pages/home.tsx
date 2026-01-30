import { Layout } from "@/components/layout";
import { useCourses } from "@/hooks/use-courses";
import { CourseCard } from "@/components/course-card";
import { Skeleton } from "@/components/ui/skeleton";
import { Button } from "@/components/ui/button";
import { ArrowRight, Sparkles } from "lucide-react";
import { useAuth } from "@/hooks/use-auth";
import { Link } from "wouter";

export default function Home() {
  const { data: courses, isLoading } = useCourses();
  const { user } = useAuth();

  return (
    <Layout>
      {/* Hero Section */}
      <section className="mb-16 grid gap-8 md:grid-cols-2 md:items-center">
        <div className="space-y-6">
          <div className="inline-flex items-center rounded-full border bg-background px-3 py-1 text-sm font-medium text-primary shadow-sm">
            <Sparkles className="mr-2 h-4 w-4" />
            Start learning today
          </div>
          <h1 className="text-4xl font-extrabold tracking-tight md:text-6xl">
            Master new skills with <span className="text-primary">EduFlow</span>
          </h1>
          <p className="text-lg text-muted-foreground md:text-xl">
            Expert-led courses, hands-on projects, and a supportive community. 
            Level up your career with our premium learning platform.
          </p>
          <div className="flex gap-4">
            {!user && (
              <Button size="lg" asChild className="px-8 shadow-lg shadow-primary/25">
                <a href="/api/login">Get Started <ArrowRight className="ml-2 h-4 w-4" /></a>
              </Button>
            )}
            <Button size="lg" variant="outline" asChild>
              <Link href="#courses">Browse Courses</Link>
            </Button>
          </div>
        </div>
        <div className="relative hidden md:block">
          {/* Decorative Elements */}
          <div className="absolute -left-4 -top-4 h-72 w-72 rounded-full bg-purple-500/20 blur-3xl" />
          <div className="absolute -bottom-4 -right-4 h-72 w-72 rounded-full bg-blue-500/20 blur-3xl" />
          {/* Abstract Image Placeholder */}
          <div className="relative aspect-square overflow-hidden rounded-2xl border bg-gradient-to-br from-white to-gray-50 shadow-2xl dark:from-gray-900 dark:to-gray-950">
             {/* landing page hero abstract 3d shapes learning education */}
             <img 
               src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800&auto=format&fit=crop&q=60" 
               alt="Students learning together"
               className="h-full w-full object-cover"
             />
          </div>
        </div>
      </section>

      {/* Course Listing */}
      <section id="courses" className="space-y-8">
        <div className="flex items-center justify-between">
          <h2 className="text-3xl font-bold">Featured Courses</h2>
          <Button variant="ghost">View All</Button>
        </div>

        {isLoading ? (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="space-y-4">
                <Skeleton className="aspect-video w-full rounded-xl" />
                <Skeleton className="h-4 w-2/3" />
                <Skeleton className="h-4 w-full" />
              </div>
            ))}
          </div>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {courses?.filter(c => c.isPublished).map((course) => (
              <CourseCard key={course.id} course={course} />
            ))}
            {courses?.filter(c => c.isPublished).length === 0 && (
              <div className="col-span-full py-12 text-center text-muted-foreground">
                No courses available at the moment. Check back soon!
              </div>
            )}
          </div>
        )}
      </section>
    </Layout>
  );
}
