import { Link } from "wouter";
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Clock, PlayCircle } from "lucide-react";
import { type Course } from "@shared/schema";

interface CourseCardProps {
  course: Course;
  isEnrolled?: boolean;
}

export function CourseCard({ course, isEnrolled }: CourseCardProps) {
  return (
    <Card className="group overflow-hidden border-border/50 bg-card transition-all hover:shadow-lg hover:-translate-y-1">
      <div className="aspect-video w-full overflow-hidden bg-muted">
        {course.imageUrl ? (
          <img
            src={course.imageUrl}
            alt={course.title}
            className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center bg-primary/5 text-primary">
            <PlayCircle className="h-12 w-12 opacity-50" />
          </div>
        )}
      </div>
      <CardHeader>
        <div className="flex items-center justify-between">
          <Badge variant={course.price === 0 ? "secondary" : "default"}>
            {course.price === 0 ? "Free" : `$${(course.price / 100).toFixed(2)}`}
          </Badge>
          {isEnrolled && (
            <Badge variant="outline" className="border-primary text-primary">
              Enrolled
            </Badge>
          )}
        </div>
        <CardTitle className="line-clamp-1 mt-2 text-xl font-bold">{course.title}</CardTitle>
      </CardHeader>
      <CardContent>
        <p className="line-clamp-2 text-sm text-muted-foreground">
          {course.description}
        </p>
      </CardContent>
      <CardFooter className="border-t bg-muted/20 p-4">
        <Button asChild className="w-full">
          <Link href={`/course/${course.id}`}>
            {isEnrolled ? "Continue Learning" : "View Course"}
          </Link>
        </Button>
      </CardFooter>
    </Card>
  );
}
