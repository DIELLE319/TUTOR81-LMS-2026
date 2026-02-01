import { useState, useEffect } from "react";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import { RadioGroup, RadioGroupItem } from "@/components/ui/radio-group";
import { Label } from "@/components/ui/label";
import { 
  LogOut, 
  MessageCircle, 
  HelpCircle, 
  GraduationCap,
  Play,
  Pause,
  Volume2,
  ChevronRight,
  FileText,
  Video,
  Presentation,
  Check
} from "lucide-react";

interface LearningObject {
  id: number;
  title: string;
  type: "video" | "slide" | "document";
  duration: number;
  completed: boolean;
  jwplayerCode?: string | null;
}

interface Lesson {
  id: number;
  title: string;
  learningObjects: LearningObject[];
}

interface Module {
  id: number;
  title: string;
  lessons: Lesson[];
}

interface Answer {
  id: number;
  text: string;
  isCorrect: boolean;
}

interface QuestionData {
  id: number;
  text: string;
  answers: Answer[];
}

interface InterruptionPoint {
  id: number;
  time: number;
  timeSeconds: number;
  questions: QuestionData[];
}

interface CourseData {
  title: string;
  modules: Module[];
}

function getLoIcon(type: string) {
  switch (type) {
    case "video":
      return <Video className="h-4 w-4 text-blue-500" />;
    case "slide":
      return <Presentation className="h-4 w-4 text-yellow-600" />;
    default:
      return <FileText className="h-4 w-4 text-orange-500" />;
  }
}

export default function CoursePlayerVideo() {
  const [isPaused, setIsPaused] = useState(false);
  const [currentTime, setCurrentTime] = useState(0);
  const [currentLessonIndex, setCurrentLessonIndex] = useState(0);
  const [currentLoIndex, setCurrentLoIndex] = useState(0);
  const [isLoading, setIsLoading] = useState(true);
  const [courseData, setCourseData] = useState<CourseData | null>(null);
  
  // Quiz state
  const [showQuiz, setShowQuiz] = useState(false);
  const [quizTimer, setQuizTimer] = useState(30);
  const [selectedAnswer, setSelectedAnswer] = useState<string>("");
  const [correctAnswers, setCorrectAnswers] = useState(0);
  const [wrongAnswers, setWrongAnswers] = useState(0);
  const [currentQuestion, setCurrentQuestion] = useState<QuestionData | null>(null);
  const [showFeedback, setShowFeedback] = useState(false);
  const [lastAnswerCorrect, setLastAnswerCorrect] = useState(false);
  const [showTimeoutPopup, setShowTimeoutPopup] = useState(false);
  
  // Interruption points from database
  const [interruptionPoints, setInterruptionPoints] = useState<InterruptionPoint[]>([]);
  const [triggeredPoints, setTriggeredPoints] = useState<Set<number>>(new Set());
  const [totalQuestionsCount, setTotalQuestionsCount] = useState(0);

  // Load course structure from API
  useEffect(() => {
    const loadCourseStructure = async () => {
      const enrollment = localStorage.getItem("playerEnrollment");
      if (!enrollment) {
        window.location.href = "/player-login";
        return;
      }

      try {
        const enrollmentData = JSON.parse(enrollment);
        const courseId = enrollmentData.learningProjectId || enrollmentData.courseId;
        
        const response = await fetch(`/api/player/course/${courseId}/structure`);
        if (!response.ok) throw new Error("Failed to load course");
        
        const data = await response.json();
        
        // Transform API data to component format
        const transformedModules = data.modules.map((module: any) => ({
          id: module.id,
          title: module.title,
          lessons: module.lessons.map((lesson: any) => ({
            id: lesson.id,
            title: lesson.title,
            learningObjects: lesson.learningObjects.map((lo: any) => ({
              id: lo.id,
              title: lo.title,
              type: lo.type || "video",
              duration: lo.duration || 60,
              completed: false,
              jwplayerCode: lo.jwplayerCode
            }))
          }))
        }));

        setCourseData({
          title: data.title,
          modules: transformedModules
        });
      } catch (error) {
        console.error("Failed to load course structure:", error);
      } finally {
        setIsLoading(false);
      }
    };

    loadCourseStructure();
  }, []);

  // Get user data from localStorage
  const storedUser = localStorage.getItem("playerUser");
  const userData = storedUser ? JSON.parse(storedUser) : {
    firstName: "Utente",
    lastName: "",
    company: ""
  };

  // Get all lessons flat
  const allLessons = courseData?.modules.flatMap(m => m.lessons) || [];
  const currentLesson = allLessons[currentLessonIndex];
  const currentLo = currentLesson?.learningObjects[currentLoIndex];
  // Duration from DB is in minutes, convert to seconds
  const loDuration = (currentLo?.duration || 1) * 60;

  // Timer for current learning object
  useEffect(() => {
    if (!isPaused && !showQuiz && currentTime < loDuration) {
      const timer = setInterval(() => {
        setCurrentTime(prev => prev + 1);
      }, 1000);
      return () => clearInterval(timer);
    }
  }, [isPaused, showQuiz, currentTime, loDuration]);

  // Auto-advance to next learning object when current one ends
  useEffect(() => {
    if (!courseData || currentTime < loDuration || showQuiz) return;
    
    const lessons = courseData.modules.flatMap(m => m.lessons);
    const currentLessonObj = lessons[currentLessonIndex];
    
    if (currentLessonObj && currentLoIndex < currentLessonObj.learningObjects.length - 1) {
      // Next LO in same lesson
      setCurrentLoIndex(prev => prev + 1);
      setCurrentTime(0);
    } else if (currentLessonIndex < lessons.length - 1) {
      // Next lesson
      setCurrentLessonIndex(prev => prev + 1);
      setCurrentLoIndex(0);
      setCurrentTime(0);
    }
    // If no more LOs/lessons, course is complete
  }, [currentTime, loDuration, showQuiz, currentLessonIndex, currentLoIndex, courseData]);

  const formatTime = (seconds: number) => {
    const mins = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return mins > 0 ? `${mins} min ${secs} sec` : `${secs} sec.`;
  };

  const handleExit = () => {
    window.location.href = "/player";
  };

  // Load interruption points when learning object changes
  useEffect(() => {
    const loadInterruptions = async () => {
      if (!currentLo?.id) return;
      
      try {
        const response = await fetch(`/api/learning-objects/${currentLo.id}/interruptions`);
        if (response.ok) {
          const data = await response.json();
          setInterruptionPoints(data);
          setTriggeredPoints(new Set());
          
          // Count total questions
          const total = data.reduce((sum: number, point: InterruptionPoint) => 
            sum + point.questions.length, 0);
          setTotalQuestionsCount(total);
        }
      } catch (error) {
        console.error("Failed to load interruptions:", error);
      }
    };
    
    loadInterruptions();
  }, [currentLo?.id]);

  // Trigger quiz at interruption points
  useEffect(() => {
    if (showQuiz || interruptionPoints.length === 0) return;
    
    // Find interruption point that matches current time
    const matchingPoint = interruptionPoints.find(point => 
      point.timeSeconds === currentTime && 
      !triggeredPoints.has(point.id) &&
      point.questions.length > 0
    );
    
    if (matchingPoint) {
      // Mark point as triggered
      setTriggeredPoints(prev => new Set(prev).add(matchingPoint.id));
      
      // Pick first question from this point
      const question = matchingPoint.questions[0];
      if (question) {
        setCurrentQuestion(question);
        setShowQuiz(true);
        setQuizTimer(30);
        setSelectedAnswer("");
      }
    }
  }, [currentTime, showQuiz, interruptionPoints, triggeredPoints]);

  // Quiz timer countdown
  useEffect(() => {
    if (showQuiz && quizTimer > 0) {
      const timer = setInterval(() => {
        setQuizTimer(prev => prev - 1);
      }, 1000);
      return () => clearInterval(timer);
    } else if (showQuiz && quizTimer === 0) {
      // Time expired - show timeout popup (user must exit)
      setShowQuiz(false);
      setShowTimeoutPopup(true);
    }
  }, [showQuiz, quizTimer]);

  const handleExitCourse = () => {
    const enrollment = localStorage.getItem("playerEnrollment");
    const user = localStorage.getItem("playerUser");
    
    if (enrollment) {
      const enrollmentData = JSON.parse(enrollment);
      const userData = user ? JSON.parse(user) : null;
      
      // Check if demo user (demo.demo with CF 1)
      const isDemo = userData?.firstName?.toLowerCase() === "demo" && 
                     userData?.lastName?.toLowerCase() === "demo";
      
      if (isDemo) {
        // Demo user: reset progress for testing
        fetch("/api/player/demo/reset", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ enrollmentId: enrollmentData.id })
        });
      } else {
        // Normal user: save progress to resume later
        fetch("/api/player/progress", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ 
            enrollmentId: enrollmentData.id,
            lessonId: currentLessonIndex,
            completed: false
          })
        });
      }
    }
    localStorage.removeItem("playerUser");
    localStorage.removeItem("playerEnrollment");
    window.location.href = "/player-login";
  };

  const handleConfirmAnswer = () => {
    if (!currentQuestion || !selectedAnswer) return;
    
    const selectedAnswerId = parseInt(selectedAnswer);
    const selectedAnswerObj = currentQuestion.answers.find(a => a.id === selectedAnswerId);
    const isCorrect = selectedAnswerObj?.isCorrect || false;
    
    if (isCorrect) {
      setCorrectAnswers(prev => prev + 1);
    } else {
      setWrongAnswers(prev => prev + 1);
    }
    
    setLastAnswerCorrect(isCorrect);
    setShowFeedback(true);
  };

  const handleContinueFromFeedback = () => {
    setShowQuiz(false);
    setShowFeedback(false);
    setCurrentQuestion(null);
    setSelectedAnswer("");
  };

  // Demo: trigger quiz manually for testing
  const triggerDemoQuiz = () => {
    if (!showQuiz && interruptionPoints.length > 0) {
      // Pick a random question from any interruption point
      const allQuestions = interruptionPoints.flatMap(p => p.questions);
      if (allQuestions.length > 0) {
        const randomQ = allQuestions[Math.floor(Math.random() * allQuestions.length)];
        setCurrentQuestion(randomQ);
        setShowQuiz(true);
        setQuizTimer(30);
        setSelectedAnswer("");
      }
    }
  };

  // Show loading state
  if (isLoading) {
    return (
      <div className="h-screen flex items-center justify-center bg-gray-100">
        <div className="text-center">
          <div className="animate-spin w-12 h-12 border-4 border-yellow-500 border-t-transparent rounded-full mx-auto mb-4"></div>
          <p className="text-gray-600">Caricamento corso...</p>
        </div>
      </div>
    );
  }

  // Show error if no course data
  if (!courseData || courseData.modules.length === 0) {
    return (
      <div className="h-screen flex items-center justify-center bg-gray-100">
        <div className="text-center">
          <p className="text-red-600 text-xl mb-4">Nessun contenuto disponibile per questo corso</p>
          <Button onClick={() => window.location.href = "/player"}>
            Torna ai corsi
          </Button>
        </div>
      </div>
    );
  }

  return (
    <div className="h-screen flex flex-col bg-gray-100 overflow-hidden">
      <div className="flex-1 flex overflow-hidden">
        {/* Sidebar sinistra */}
        <div className="w-48 bg-zinc-800 text-white flex flex-col">
          <div className="p-2 border-b border-zinc-700">
            <button className="flex items-center gap-2 text-white hover:text-yellow-400" data-testid="button-volume">
              <div className="w-8 h-8 bg-zinc-700 rounded flex items-center justify-center">
                <ChevronRight className="h-5 w-5" />
              </div>
              <Volume2 className="h-5 w-5" />
            </button>
          </div>
          
          <button 
            onClick={handleExit}
            className="flex items-center gap-3 px-4 py-3 hover:bg-zinc-700 border-b border-zinc-700"
            data-testid="button-exit"
          >
            <LogOut className="h-5 w-5" />
            <span className="font-semibold">ESCI</span>
          </button>

          <div className="px-4 py-3 border-b border-zinc-700 text-sm">
            <div className="font-bold text-yellow-400">{userData.name}</div>
            <div className="text-gray-400">{userData.surname}</div>
          </div>

          <div className="px-4 py-2 border-b border-zinc-700 text-xs text-gray-400">
            <div className="font-semibold text-white">{userData.company}</div>
          </div>

          <div className="px-4 py-2 border-b border-zinc-700 text-xs">
            <div className="text-gray-400">{userData.loginTime}</div>
          </div>

          <div className="px-4 py-2 border-b border-zinc-700 text-xs">
            <div className="text-gray-400">{userData.ip}</div>
          </div>

          <button className="flex items-center gap-3 px-4 py-3 hover:bg-zinc-700 border-b border-zinc-700">
            <MessageCircle className="h-5 w-5" />
            <span>Chat</span>
          </button>

          <button className="flex items-center gap-3 px-4 py-3 hover:bg-zinc-700 border-b border-zinc-700">
            <HelpCircle className="h-5 w-5" />
            <span>Assistenza</span>
          </button>

          <button className="flex items-center gap-3 px-4 py-3 hover:bg-zinc-700">
            <GraduationCap className="h-5 w-5" />
            <span className="text-sm">Tutor Didattico</span>
          </button>

          <div className="flex-1" />
        </div>

        {/* Pannello PROGRAMMA */}
        <div className="w-72 bg-yellow-100 border-r border-yellow-300 overflow-y-auto">
          <div className="bg-yellow-400 px-4 py-2 font-bold text-gray-800">
            PROGRAMMA
          </div>
          
          {courseData.modules.map((module) => (
            <div key={module.id} className="text-sm">
              <div className="px-4 py-2 font-semibold text-gray-700 bg-yellow-200">
                {module.title}
              </div>
              
              {module.lessons.map((lesson, lessonIdx) => (
                <div key={lesson.id}>
                  <div className="px-4 py-2 text-gray-800 font-medium flex items-center gap-2">
                    <span className="text-gray-500">{lessonIdx + 1}.</span>
                    {lesson.title}
                  </div>
                  
                  {lesson.learningObjects.map((lo, loIdx) => (
                    <button
                      key={lo.id}
                      onClick={() => {
                        setCurrentLessonIndex(lessonIdx);
                        setCurrentLoIndex(loIdx);
                      }}
                      className={`w-full text-left px-6 py-1.5 flex items-center gap-2 text-xs hover:bg-yellow-200 ${
                        currentLessonIndex === lessonIdx && currentLoIndex === loIdx
                          ? "bg-yellow-300 font-semibold"
                          : ""
                      }`}
                      data-testid={`lo-${lo.id}`}
                    >
                      {getLoIcon(lo.type)}
                      <span className={lo.completed ? "text-green-700" : "text-gray-700"}>
                        {lo.title}
                      </span>
                    </button>
                  ))}
                </div>
              ))}
            </div>
          ))}
        </div>

        {/* Area principale */}
        <div className="flex-1 flex flex-col">
          {/* Header corso */}
          <div className="bg-white border-b px-6 py-3">
            <h1 className="text-xl font-bold text-gray-900">
              Corso: {courseData.title}
            </h1>
            <div className="text-sm text-gray-600">
              <span>Lezione: {currentLesson?.title}</span>
            </div>
            <div className="text-xs text-gray-500">
              Oggetto: {currentLo?.title} - {currentLo?.duration ? Math.floor(currentLo.duration / 60) : 0} minuti
            </div>
          </div>

          {/* Area video/contenuto */}
          <div className="flex-1 flex">
            <div className="flex-1 bg-gray-200 flex flex-col p-8">
              {showQuiz && currentQuestion ? (
                /* Quiz mode - blocca il video */
                <div className="flex-1 flex">
                  <div className="flex-1 bg-white rounded-lg shadow p-8">
                    <h2 className="text-xl font-bold text-gray-900 mb-6">Domanda:</h2>
                    <p className="text-gray-800 mb-8 text-lg">
                      {currentQuestion.text}
                    </p>
                    
                    {!showFeedback ? (
                      /* Fase risposta */
                      <>
                        <RadioGroup 
                          value={selectedAnswer} 
                          onValueChange={setSelectedAnswer}
                          className="space-y-4"
                        >
                          {(currentQuestion.answers || []).map((answer) => (
                            <div key={answer.id} className="flex items-center space-x-3">
                              <RadioGroupItem 
                                value={answer.id.toString()} 
                                id={`option-${answer.id}`}
                                className="border-gray-400"
                                data-testid={`quiz-option-${answer.id}`}
                              />
                              <Label 
                                htmlFor={`option-${answer.id}`}
                                className="text-gray-700 cursor-pointer"
                              >
                                {answer.text}
                              </Label>
                            </div>
                          ))}
                        </RadioGroup>
                        
                        <Button
                          onClick={handleConfirmAnswer}
                          disabled={!selectedAnswer}
                          className="mt-8 bg-orange-500 hover:bg-orange-600 text-white"
                          data-testid="button-confirm-answer"
                        >
                          <Check className="h-4 w-4 mr-2" />
                          Conferma risposta
                        </Button>
                      </>
                    ) : (
                      /* Fase feedback */
                      <div className="space-y-4">
                        {lastAnswerCorrect ? (
                          <div className="flex items-center gap-2 text-green-600 text-xl">
                            <Check className="h-6 w-6" />
                            <span className="font-bold">Risposta Corretta!</span>
                          </div>
                        ) : (
                          <div className="flex items-center gap-2 text-red-600 text-xl">
                            <span className="font-bold">Risposta Errata</span>
                          </div>
                        )}
                        
                        <Button
                          onClick={handleContinueFromFeedback}
                          className="mt-4 bg-blue-500 hover:bg-blue-600 text-white"
                          data-testid="button-continue"
                        >
                          <Check className="h-4 w-4 mr-2" />
                          Continua
                        </Button>
                      </div>
                    )}
                  </div>
                  
                  {/* Timer grande */}
                  {!showFeedback && (
                    <div className="w-48 flex items-center justify-center">
                      <div className="text-8xl font-bold text-blue-500">
                        {quizTimer}
                      </div>
                    </div>
                  )}
                </div>
              ) : (
                /* Normal content mode - video, slide, document */
                <div className="flex-1 flex items-center justify-center p-4">
                  <div 
                    className="bg-black rounded-lg shadow-xl w-full max-w-4xl aspect-video flex items-center justify-center relative overflow-hidden"
                    data-testid="video-area"
                  >
                    {/* Video with JWPlayer */}
                    {currentLo?.type === "video" && currentLo?.jwplayerCode ? (
                      <iframe
                        key={currentLo.jwplayerCode}
                        src={`https://cdn.jwplayer.com/players/${currentLo.jwplayerCode}.html`}
                        width="100%"
                        height="100%"
                        frameBorder="0"
                        scrolling="auto"
                        title={currentLo.title}
                        allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                        allowFullScreen
                        className="absolute inset-0"
                        data-testid="jwplayer-iframe"
                      />
                    ) : currentLo?.type === "slide" ? (
                      /* Slide content */
                      <div className="absolute inset-0 bg-gradient-to-br from-green-800 to-green-900 flex items-center justify-center">
                        <div className="text-center text-white p-8">
                          <FileText className="h-20 w-20 mx-auto text-green-400 mb-4" />
                          <h2 className="text-2xl font-bold mb-2">{currentLo?.title}</h2>
                          <p className="text-green-300 mb-4">Slide - Oggetto {currentLoIndex + 1} di {currentLesson?.learningObjects.length || 0}</p>
                          <div className="text-yellow-400 text-2xl font-mono mb-4">
                            {formatTime(currentTime)} / {formatTime(loDuration)}
                          </div>
                          <p className="text-sm text-green-200">Le slide verranno caricate dal sistema OVH</p>
                        </div>
                      </div>
                    ) : currentLo?.type === "document" ? (
                      /* Document content */
                      <div className="absolute inset-0 bg-gradient-to-br from-blue-800 to-blue-900 flex items-center justify-center">
                        <div className="text-center text-white p-8">
                          <FileText className="h-20 w-20 mx-auto text-blue-400 mb-4" />
                          <h2 className="text-2xl font-bold mb-2">{currentLo?.title}</h2>
                          <p className="text-blue-300 mb-4">Documento - Oggetto {currentLoIndex + 1} di {currentLesson?.learningObjects.length || 0}</p>
                          <div className="text-yellow-400 text-2xl font-mono mb-4">
                            {formatTime(currentTime)} / {formatTime(loDuration)}
                          </div>
                          <p className="text-sm text-blue-200">I documenti verranno caricati dal sistema OVH</p>
                        </div>
                      </div>
                    ) : (
                      /* Fallback - video without JWPlayer code */
                      <div className="absolute inset-0 bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
                        <div className="text-center text-white p-8">
                          <Video className="h-20 w-20 mx-auto text-yellow-500 mb-4" />
                          <h2 className="text-2xl font-bold mb-2">{currentLo?.title || "Caricamento..."}</h2>
                          <p className="text-gray-400 mb-4">Oggetto {currentLoIndex + 1} di {currentLesson?.learningObjects.length || 0}</p>
                          <div className="text-yellow-500 text-2xl font-mono mb-4">
                            {formatTime(currentTime)} / {formatTime(loDuration)}
                          </div>
                          <p className="text-sm text-gray-500">Video in caricamento...</p>
                        </div>
                      </div>
                    )}
                  </div>
                </div>
              )}
            </div>

            {/* Pannello verifica apprendimento */}
            <div className="w-64 bg-white border-l p-4">
              <h3 className="font-semibold text-gray-700 mb-4">Verifica apprendimento:</h3>
              <div className="flex flex-col gap-2 mb-4">
                <div className="h-24 bg-green-500 text-white rounded flex items-center justify-center">
                  <span className="text-4xl font-bold">{correctAnswers}</span>
                </div>
                <div className="h-24 bg-red-500 text-white rounded flex items-center justify-center">
                  <span className="text-4xl font-bold">{wrongAnswers}</span>
                </div>
              </div>
              <p className="text-sm text-gray-500 text-center">
                Totale domande previste: {totalQuestionsCount}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Barra inferiore */}
      <div className="h-14 bg-zinc-900 flex items-center px-4 gap-4">
        <div className="text-2xl font-bold">
          <span className="text-white">tutor</span>
          <span className="text-yellow-500">81</span>
        </div>

        <div className="flex-1 flex items-center gap-4">
          <div className="flex-1 relative">
            <div className="h-6 bg-zinc-700 rounded-full overflow-hidden">
              <div 
                className="h-full bg-green-500 transition-all duration-1000"
                style={{ width: `${(currentTime / loDuration) * 100}%` }}
              />
            </div>
            <div className="absolute inset-0 flex items-center justify-center">
              <span className="text-white text-sm font-medium">
                {formatTime(currentTime)}
              </span>
            </div>
          </div>
        </div>

        <button
          onClick={() => setIsPaused(!isPaused)}
          className="flex items-center gap-2 text-white hover:text-yellow-400"
          data-testid="button-pause"
        >
          {isPaused ? (
            <>
              <Play className="h-8 w-8" />
              <span className="font-semibold">PLAY</span>
            </>
          ) : (
            <>
              <Pause className="h-8 w-8" />
              <span className="font-semibold">PAUSA</span>
            </>
          )}
        </button>
      </div>

      {/* Popup timeout forzato */}
      {showTimeoutPopup && (
        <div className="fixed inset-0 bg-black/70 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
            <div className="bg-yellow-500 px-6 py-4">
              <h2 className="text-xl font-bold text-gray-900">
                Attenzione: interruzione del corso
              </h2>
            </div>
            <div className="p-6">
              <p className="text-gray-700 mb-4">
                Vuoi interrompere la lezione in corso? E' sempre consigliabile non interrompere volontariamente la lezione ma attendere la sua fine.
              </p>
              <p className="text-gray-700 mb-4">
                Quando ti riconnetterai DOVRAI ATTENDERE affinché il sistema carichi l'ultimo punto utile.
              </p>
              <p className="font-semibold text-gray-900 mb-6">
                Confermi di voler interrompere il corso?
              </p>
              <div className="flex items-center gap-4">
                <div className="text-6xl font-bold text-gray-400">00</div>
                <Button 
                  onClick={handleExitCourse}
                  className="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-6"
                  data-testid="btn-exit-timeout"
                >
                  Interrompi
                </Button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
