import { Layout } from "@/components/layout";
import { useAuth } from "@/hooks/use-auth";
import { useEnrollments } from "@/hooks/use-enrollments";
import { CourseCard } from "@/components/course-card";
import { Skeleton } from "@/components/ui/skeleton";
import { GraduationCap, Trophy } from "lucide-react";
import { Redirect } from "wouter";

export default function Dashboard() {
  const { user, isLoading: isAuthLoading } = useAuth();
  const { data: enrollments, isLoading: isEnrollmentsLoading } = useEnrollments();

  if (isAuthLoading) return null;
  if (!user) return <Redirect to="/" />;

  return (
    <Layout>
      <div className="mb-8 flex items-center gap-4">
        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10 text-primary">
          <GraduationCap className="h-6 w-6" />
        </div>
        <div>
          <h1 className="text-3xl font-bold">My Learning</h1>
          <p className="text-muted-foreground">Welcome back, {user.firstName || "Student"}!</p>
        </div>
      </div>

      <div className="grid gap-8">
        <section>
          <div className="mb-6 flex items-center gap-2">
            <Trophy className="h-5 w-5 text-yellow-500" />
            <h2 className="text-xl font-semibold">Enrolled Courses</h2>
          </div>

          {isEnrollmentsLoading ? (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {[1, 2].map((i) => (
                <Skeleton key={i} className="aspect-video h-64 rounded-xl" />
              ))}
            </div>
          ) : (
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {enrollments?.map((enrollment) => (
                <CourseCard 
                  key={enrollment.id} 
                  course={enrollment.course} 
                  isEnrolled={true} 
                />
              ))}
              {enrollments?.length === 0 && (
                <div className="col-span-full rounded-xl border border-dashed p-8 text-center text-muted-foreground">
                  You haven't enrolled in any courses yet.
                </div>
              )}
            </div>
          )}
        </section>
      </div>
    </Layout>
  );
}
