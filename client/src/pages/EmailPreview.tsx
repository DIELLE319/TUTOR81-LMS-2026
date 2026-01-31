import CourseEmailTemplate from '@/components/CourseEmailTemplate';

export default function EmailPreview() {
  return (
    <div className="min-h-screen bg-gray-200 p-8">
      <h1 className="text-2xl font-bold text-center mb-6">Anteprima Email Avvia Corso</h1>
      <CourseEmailTemplate
        tutorName="ABI SERVIZI SRL"
        tutorAddress="Via Vittorio Emanuele II n22, RONCADELLE"
        tutorEmail="business@afabi.it"
        courseName="RLS - BASE - 32 ORE"
        userName="Andrea Monteleone"
        userEmail="andrea.monteleone@email.com"
        startDate="30/01/2026"
        endDate="30/04/2026"
        referentName="Chillemi Giulia"
        referentEmail="formazione@afabi.it"
        username="andrea.monteleone"
        courseUrl="https://avviacorso.tutor81.com"
        instructionsUrl="https://tutor81.com/istruzioni"
      />
    </div>
  );
}
