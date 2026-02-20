import { Link } from "wouter";

export default function NotFound() {
  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 p-4">
      <div className="text-center">
        <h1 className="text-6xl font-black text-gray-200 mb-4">404</h1>
        <p className="text-lg text-gray-600 mb-6">Pagina non trovata</p>
        <Link href="/" className="px-6 py-3 bg-yellow-500 hover:bg-yellow-600 text-black font-bold rounded-lg text-sm">
          Torna alla Dashboard
        </Link>
      </div>
    </div>
  );
}
