import { Layout } from "@/components/layout";
import { useCourse } from "@/hooks/use-courses";
import { useUpdateProgress } from "@/hooks/use-progress";
import { VideoPlayer } from "@/components/video-player";
import { Button } from "@/components/ui/button";
import { ScrollArea } from "@/components/ui/scroll-area";
import { CheckCircle, ChevronLeft, ChevronRight, Menu } from "lucide-react";
import { useRoute, Link } from "wouter";
import { useState } from "react";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { useQuery } from "@tanstack/react-query";
import { api, buildUrl } from "@shared/routes";

// Need a specific query to get course by lesson ID or just fetch all courses and find it?
// Since schema structure is nested, we might need a better way. 
// For now, let's assume we can fetch the course details if we know the courseId,
// but we only have lessonId from URL. 
// A real app would have an endpoint GET /api/lessons/:id which returns the lesson + courseId.
// Let's implement a workaround by fetching the lesson first.

function useLesson(id: number) {
  // We don't have a direct useLesson hook in the original plan that returns parent info easily
  // But our schema says lessons belong to modules belong to courses.
  // Ideally, the backend would return parent context.
  // For this demo, let's assume we pass courseId in URL or fetch a "LessonContext"
  // Let's implement a quick custom fetcher for this page.
  return useQuery({
    queryKey: ['/api/lessons', id, 'details'],
    queryFn: async () => {
      // Fetch all courses to find the one containing this lesson (inefficient but works for MVP)
      const res = await fetch(api.courses.list.path, { credentials: "include" });
      const courses = await res.json();
      
      for (const c of courses) {
        const fullCourseRes = await fetch(buildUrl(api.courses.get.path, { id: c.id }), { credentials: "include" });
        const fullCourse = await fullCourseRes.json();
        for (const m of fullCourse.modules) {
          const lesson = m.lessons.find((l: any) => l.id === id);
          if (lesson) {
            return { course: fullCourse, module: m, lesson };
          }
        }
      }
      throw new Error("Lesson not found");
    },
    enabled: !!id
  });
}

export default function LessonView() {
  const [, params] = useRoute("/lesson/:id");
  const lessonId = params ? parseInt(params.id) : 0;
  
  const { data, isLoading } = useLesson(lessonId);
  const { mutate: markComplete } = useUpdateProgress();
  const [isSidebarOpen, setSidebarOpen] = useState(false);

  if (isLoading) return <div className="flex h-screen items-center justify-center">Loading...</div>;
  if (!data) return <div>Lesson not found</div>;

  const { course, lesson } = data;

  const handleVideoEnd = () => {
    markComplete({ lessonId, completed: true });
  };

  return (
    <div className="flex h-screen flex-col bg-background">
      {/* Header */}
      <header className="flex h-16 items-center justify-between border-b px-4 lg:px-6">
        <div className="flex items-center gap-4">
          <Button variant="ghost" size="icon" asChild>
            <Link href={`/course/${course.id}`}>
              <ChevronLeft className="h-5 w-5" />
            </Link>
          </Button>
          <h1 className="text-lg font-semibold line-clamp-1">{course.title}</h1>
        </div>
        
        <Sheet>
          <SheetTrigger asChild>
            <Button variant="outline" size="sm" className="lg:hidden">
              <Menu className="mr-2 h-4 w-4" />
              Syllabus
            </Button>
          </SheetTrigger>
          <SheetContent side="right" className="w-80 p-0">
             <CourseSidebar course={course} currentLessonId={lessonId} />
          </SheetContent>
        </Sheet>
      </header>

      <div className="flex flex-1 overflow-hidden">
        {/* Main Content */}
        <main className="flex-1 overflow-y-auto p-4 lg:p-8">
          <div className="mx-auto max-w-4xl space-y-6">
            <VideoPlayer url={lesson.videoUrl || ""} onEnded={handleVideoEnd} />
            
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold">{lesson.title}</h2>
                <Button onClick={() => markComplete({ lessonId, completed: true })}>
                  <CheckCircle className="mr-2 h-4 w-4" />
                  Mark Complete
                </Button>
              </div>
              <div className="prose max-w-none dark:prose-invert">
                {lesson.content}
              </div>
            </div>
            
            {/* Navigation Buttons */}
            <div className="flex justify-between pt-8">
               <Button variant="outline">Previous Lesson</Button>
               <Button>Next Lesson <ChevronRight className="ml-2 h-4 w-4" /></Button>
            </div>
          </div>
        </main>

        {/* Desktop Sidebar */}
        <aside className="hidden w-80 border-l bg-muted/10 lg:block">
          <CourseSidebar course={course} currentLessonId={lessonId} />
        </aside>
      </div>
    </div>
  );
}

function CourseSidebar({ course, currentLessonId }: { course: any, currentLessonId: number }) {
  return (
    <div className="flex h-full flex-col">
      <div className="border-b p-4">
        <h3 className="font-semibold">Course Content</h3>
      </div>
      <ScrollArea className="flex-1">
        <div className="p-4 space-y-6">
          {course.modules.sort((a: any, b: any) => a.order - b.order).map((module: any) => (
            <div key={module.id} className="space-y-2">
              <h4 className="text-sm font-medium text-muted-foreground uppercase tracking-wider">
                {module.title}
              </h4>
              <div className="space-y-1">
                {module.lessons.sort((a: any, b: any) => a.order - b.order).map((l: any) => (
                  <Link key={l.id} href={`/lesson/${l.id}`}>
                    <div className={`flex cursor-pointer items-center gap-2 rounded-md p-2 text-sm transition-colors hover:bg-muted ${
                      l.id === currentLessonId ? "bg-primary/10 text-primary font-medium" : ""
                    }`}>
                      {l.id === currentLessonId ? (
                        <PlayCircle className="h-4 w-4" />
                      ) : (
                        <div className="h-4 w-4 rounded-full border border-current opacity-30" />
                      )}
                      <span className="line-clamp-1">{l.title}</span>
                    </div>
                  </Link>
                ))}
              </div>
            </div>
          ))}
        </div>
      </ScrollArea>
    </div>
  );
}
