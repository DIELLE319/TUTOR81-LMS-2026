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

  // Mock data - in produzione verrà da API
  const userData = {
    name: "Luigiptrlgu80a01",
    surname: "a944a Paterno",
    company: "AZIENDA DIMOSTRATIVA SPA",
    loginTime: "13:39 01-02-2026",
    ip: "87.4.37.9"
  };

  const courseData: CourseData = {
    title: "CORSO DIMOSTRATIVO TUTOR81 2022",
    modules: [
      {
        id: 1,
        title: "Modulo 1",
        lessons: [
          {
            id: 1,
            title: "Demo T81 - Lezioni dimostrative",
            learningObjects: [
              { id: 1, title: "Demo T81 - Es. 1 - Diritti e doveri", type: "video", duration: 120, completed: false },
              { id: 2, title: "Demo T81 - Es.2 - Introduzione al Decreto 81", type: "slide", duration: 90, completed: false },
              { id: 3, title: "Demo T81 - Es. 3 - Introduzione Decreto 81 parte 2", type: "slide", duration: 60, completed: false },
              { id: 4, title: "Demo T81 - Es. 4 - La costituzione", type: "video", duration: 45, completed: false },
              { id: 5, title: "Demo T81 - Es. 5 - Rischio MMC", type: "video", duration: 80, completed: false },
              { id: 6, title: "Demo T81 - Es. 6 - Rischio di mansione", type: "slide", duration: 55, completed: false },
              { id: 7, title: "Demo T81 - Es. 7 - Lezione personalizzata Azienda", type: "document", duration: 30, completed: false },
            ]
          }
        ]
      }
    ]
  };

  const currentLesson = courseData.modules[0]?.lessons[currentLessonIndex];
  const currentLo = currentLesson?.learningObjects[currentLoIndex];
  const loDuration = currentLo?.duration || 120;

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
    if (currentTime >= loDuration && !showQuiz) {
      const allLessons = courseData.modules.flatMap(m => m.lessons);
      const currentLessonObj = allLessons[currentLessonIndex];
      
      if (currentLessonObj && currentLoIndex < currentLessonObj.learningObjects.length - 1) {
        // Next LO in same lesson
        setCurrentLoIndex(prev => prev + 1);
        setCurrentTime(0);
      } else if (currentLessonIndex < allLessons.length - 1) {
        // Next lesson
        setCurrentLessonIndex(prev => prev + 1);
        setCurrentLoIndex(0);
        setCurrentTime(0);
      }
      // If no more LOs/lessons, course is complete
    }
  }, [currentTime, loDuration, showQuiz, currentLessonIndex, currentLoIndex]);

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
                        <div className={`flex items-center gap-2 ${lastAnswerCorrect ? 'text-green-600' : 'text-green-600'}`}>
                          <span className="text-green-600">Corretto</span>
                          <span>- Risposta Corretta</span>
                        </div>
                        <div className={`flex items-center gap-2 ${!lastAnswerCorrect ? 'text-red-600' : 'text-red-600'}`}>
                          <span className="text-red-600">Non corretto</span>
                          <span>- Risposta Errata</span>
                        </div>
                        
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
                /* Normal video mode */
                <div className="flex-1 flex items-center justify-center">
                  <div 
                    className="bg-gradient-to-br from-yellow-400 via-yellow-500 to-blue-500 rounded-lg shadow-xl w-full max-w-2xl aspect-video flex items-center justify-center relative overflow-hidden cursor-pointer"
                    onClick={triggerDemoQuiz}
                    data-testid="video-area"
                  >
                    <div className="absolute inset-0 bg-gradient-to-r from-yellow-400 to-transparent" />
                    <div className="relative z-10 p-8 text-left">
                      <h2 className="text-4xl font-black text-gray-900 mb-2">
                        DIMOSTRATIVO TUTOR81
                      </h2>
                      <p className="text-lg text-gray-800 mb-4">
                        Esempio corso per Lavoratore
                      </p>
                      <p className="text-gray-700">Il metodo Tutor81</p>
                      <p className="text-xs text-gray-500 mt-4">(Clicca per testare una domanda)</p>
                    </div>
                    <div className="absolute right-0 top-0 bottom-0 w-1/3 bg-blue-600 flex flex-col justify-center p-4 text-white">
                      <h3 className="font-bold mb-4">In questo Dimostrativo:</h3>
                      <ul className="space-y-2 text-sm">
                        <li>Esempi da lezioni STANDARD</li>
                        <li>Lezioni Rischio specifico</li>
                        <li>Lezioni di comparto produttivo</li>
                      </ul>
                    </div>
                  </div>
                </div>
              )}
            </div>

            {/* Pannello verifica apprendimento */}
            <div className="w-64 bg-white border-l p-4">
              <h3 className="font-semibold text-gray-700 mb-4">Verifica apprendimento:</h3>
              <div className="flex gap-2 h-24 mb-4">
                <div className="flex-1 bg-green-500 text-white rounded flex items-center justify-center">
                  <span className="text-4xl font-bold">{correctAnswers}</span>
                </div>
                <div className="flex-1 bg-red-500 text-white rounded flex items-center justify-center">
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
