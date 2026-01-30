import { Layout } from "@/components/layout";
import { useCourse } from "@/hooks/use-courses";
import { useCheckEnrollment, useEnroll } from "@/hooks/use-enrollments";
import { Button } from "@/components/ui/button";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { Skeleton } from "@/components/ui/skeleton";
import { Badge } from "@/components/ui/badge";
import { CheckCircle2, Lock, PlayCircle, Clock } from "lucide-react";
import { useRoute, Link } from "wouter";
import { useAuth } from "@/hooks/use-auth";
import { useToast } from "@/hooks/use-toast";

export default function CourseDetails() {
  const [, params] = useRoute("/course/:id");
  const courseId = params ? parseInt(params.id) : 0;
  
  const { data: course, isLoading: isCourseLoading } = useCourse(courseId);
  const { data: enrollment, isLoading: isEnrollmentLoading } = useCheckEnrollment(courseId);
  const { mutate: enroll, isPending: isEnrolling } = useEnroll();
  const { user } = useAuth();
  const { toast } = useToast();

  if (isCourseLoading || isEnrollmentLoading) {
    return (
      <Layout>
        <div className="space-y-8">
          <Skeleton className="h-64 w-full rounded-2xl" />
          <div className="space-y-4">
            <Skeleton className="h-12 w-2/3" />
            <Skeleton className="h-6 w-1/3" />
          </div>
        </div>
      </Layout>
    );
  }

  if (!course) return <Layout><div>Course not found</div></Layout>;

  const handleEnroll = () => {
    if (!user) {
      window.location.href = "/api/login";
      return;
    }
    enroll(courseId, {
      onSuccess: () => {
        toast({
          title: "Enrolled Successfully!",
          description: "You can now start learning.",
        });
      },
      onError: () => {
        toast({
          title: "Enrollment Failed",
          description: "Please try again later.",
          variant: "destructive",
        });
      }
    });
  };

  const isEnrolled = enrollment?.enrolled;

  return (
    <Layout>
      <div className="grid gap-8 lg:grid-cols-3">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-8">
          <div className="relative overflow-hidden rounded-2xl bg-black">
             {/* course detail abstract learning */}
             <img 
               src={course.imageUrl || "https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=1200&auto=format&fit=crop&q=60"}
               alt={course.title}
               className="h-full w-full object-cover opacity-80"
             />
             <div className="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent flex items-end p-8">
               <div className="space-y-4 text-white">
                 <Badge className="bg-primary/80 backdrop-blur">
                   {course.modules.length} Modules
                 </Badge>
                 <h1 className="text-4xl font-bold">{course.title}</h1>
               </div>
             </div>
          </div>

          <div className="prose max-w-none dark:prose-invert">
            <h3>About this course</h3>
            <p className="text-lg text-muted-foreground">{course.description}</p>
          </div>

          <div className="space-y-4">
            <h3 className="text-2xl font-bold">Course Syllabus</h3>
            <Accordion type="single" collapsible className="w-full">
              {course.modules.sort((a, b) => a.order - b.order).map((module) => (
                <AccordionItem key={module.id} value={`item-${module.id}`}>
                  <AccordionTrigger className="hover:no-underline">
                    <span className="flex items-center gap-4 text-left font-medium">
                      <span className="flex h-8 w-8 items-center justify-center rounded-full bg-muted text-sm font-bold">
                        {module.order}
                      </span>
                      {module.title}
                    </span>
                  </AccordionTrigger>
                  <AccordionContent>
                    <div className="space-y-2 pt-2">
                      {module.lessons.sort((a, b) => a.order - b.order).map((lesson) => (
                        <div key={lesson.id} className="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50">
                          <div className="flex items-center gap-3">
                            {isEnrolled ? (
                              <PlayCircle className="h-5 w-5 text-primary" />
                            ) : (
                              <Lock className="h-4 w-4 text-muted-foreground" />
                            )}
                            <span className={isEnrolled ? "text-foreground" : "text-muted-foreground"}>
                              {lesson.title}
                            </span>
                          </div>
                          {lesson.duration && (
                            <span className="flex items-center text-xs text-muted-foreground">
                              <Clock className="mr-1 h-3 w-3" />
                              {lesson.duration}m
                            </span>
                          )}
                          {isEnrolled && (
                            <Button size="sm" variant="ghost" asChild>
                              <Link href={`/lesson/${lesson.id}`}>Watch</Link>
                            </Button>
                          )}
                        </div>
                      ))}
                    </div>
                  </AccordionContent>
                </AccordionItem>
              ))}
            </Accordion>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          <div className="sticky top-24 rounded-2xl border bg-card p-6 shadow-lg">
            <div className="mb-6 text-center">
              <span className="text-3xl font-bold">
                {course.price === 0 ? "Free" : `$${(course.price / 100).toFixed(2)}`}
              </span>
            </div>

            {isEnrolled ? (
              <Button className="w-full" size="lg" asChild>
                {/* Find the first lesson of the first module to start */}
                <Link href={`/lesson/${course.modules[0]?.lessons[0]?.id || ''}`}>
                  Continue Learning
                </Link>
              </Button>
            ) : (
              <Button 
                className="w-full" 
                size="lg" 
                onClick={handleEnroll}
                disabled={isEnrolling}
              >
                {isEnrolling ? "Enrolling..." : "Enroll Now"}
              </Button>
            )}

            <div className="mt-6 space-y-4 text-sm text-muted-foreground">
              <div className="flex items-center gap-3">
                <CheckCircle2 className="h-5 w-5 text-green-500" />
                <span>Full lifetime access</span>
              </div>
              <div className="flex items-center gap-3">
                <CheckCircle2 className="h-5 w-5 text-green-500" />
                <span>Access on mobile and desktop</span>
              </div>
              <div className="flex items-center gap-3">
                <CheckCircle2 className="h-5 w-5 text-green-500" />
                <span>Certificate of completion</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Layout>
  );
}
