import { Layout } from "@/components/layout";
import { useAuth } from "@/hooks/use-auth";
import { useCourses, useCreateCourse, useDeleteCourse, useUpdateCourse } from "@/hooks/use-courses";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { zodResolver } from "@hookform/resolvers/zod";
import { useForm } from "react-hook-form";
import { insertCourseSchema, type InsertCourse } from "@shared/schema";
import { Loader2, Plus, Edit, Trash2, Eye, EyeOff } from "lucide-react";
import { useToast } from "@/hooks/use-toast";
import { useState } from "react";
import { Redirect } from "wouter";

export default function InstructorDashboard() {
  const { user, isLoading: isAuthLoading } = useAuth();
  const { data: courses, isLoading: isCoursesLoading } = useCourses();
  
  if (isAuthLoading) return null;
  if (!user) return <Redirect to="/" />;

  // Filter courses created by this user
  // Since we don't have a specific "getMyCourses" endpoint in the original spec,
  // we filter client-side or assume the list returns all (in a real app, use specific endpoint)
  const myCourses = courses?.filter(c => c.instructorId === user.id);

  return (
    <Layout>
      <div className="mb-8 flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Instructor Dashboard</h1>
          <p className="text-muted-foreground">Manage your courses and content</p>
        </div>
        <CreateCourseDialog />
      </div>

      <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {isCoursesLoading ? (
          <div>Loading...</div>
        ) : (
          myCourses?.map((course) => (
            <InstructorCourseCard key={course.id} course={course} />
          ))
        )}
      </div>
    </Layout>
  );
}

function InstructorCourseCard({ course }: { course: any }) {
  const { mutate: deleteCourse } = useDeleteCourse();
  const { mutate: updateCourse } = useUpdateCourse();
  const { toast } = useToast();

  const togglePublish = () => {
    updateCourse({ 
      id: course.id, 
      isPublished: !course.isPublished 
    }, {
      onSuccess: () => {
        toast({ title: course.isPublished ? "Unpublished" : "Published" });
      }
    });
  };

  return (
    <Card>
      <div className="aspect-video w-full bg-muted">
        {course.imageUrl && <img src={course.imageUrl} className="h-full w-full object-cover" />}
      </div>
      <CardHeader>
        <CardTitle className="flex justify-between items-start">
          <span className="line-clamp-1">{course.title}</span>
        </CardTitle>
      </CardHeader>
      <CardContent>
         <div className="flex gap-2">
           <Button variant="outline" size="sm" onClick={togglePublish}>
             {course.isPublished ? <Eye className="mr-2 h-4 w-4" /> : <EyeOff className="mr-2 h-4 w-4" />}
             {course.isPublished ? "Published" : "Draft"}
           </Button>
           <Button variant="outline" size="sm" className="text-destructive hover:text-destructive" onClick={() => deleteCourse(course.id)}>
             <Trash2 className="h-4 w-4" />
           </Button>
         </div>
      </CardContent>
      <CardFooter>
        <Button className="w-full" asChild>
           {/* In a real app this would go to a course builder page */}
           <a href={`/course/${course.id}`}>View / Edit</a> 
        </Button>
      </CardFooter>
    </Card>
  );
}

function CreateCourseDialog() {
  const [open, setOpen] = useState(false);
  const { mutate, isPending } = useCreateCourse();
  const { user } = useAuth();
  const { toast } = useToast();

  const form = useForm<InsertCourse>({
    resolver: zodResolver(insertCourseSchema),
    defaultValues: {
      title: "",
      description: "",
      imageUrl: "",
      price: 0,
      isPublished: false,
      instructorId: user?.id,
    },
  });

  const onSubmit = (data: InsertCourse) => {
    mutate({ ...data, instructorId: user!.id }, {
      onSuccess: () => {
        setOpen(false);
        form.reset();
        toast({ title: "Course created successfully" });
      },
      onError: () => {
        toast({ title: "Failed to create course", variant: "destructive" });
      }
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <Plus className="mr-2 h-4 w-4" /> Create Course
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Create New Course</DialogTitle>
        </DialogHeader>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
            <FormField
              control={form.control}
              name="title"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Title</FormLabel>
                  <FormControl>
                    <Input placeholder="Course Title" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="description"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Description</FormLabel>
                  <FormControl>
                    <Textarea placeholder="Course Description" {...field} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <FormField
              control={form.control}
              name="imageUrl"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Image URL</FormLabel>
                  <FormControl>
                    <Input placeholder="https://..." {...field} value={field.value || ''} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <Button type="submit" className="w-full" disabled={isPending}>
              {isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
              Create
            </Button>
          </form>
        </Form>
      </DialogContent>
    </Dialog>
  );
}
