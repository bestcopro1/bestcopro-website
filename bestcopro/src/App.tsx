import React, { useState, useEffect } from "react";
import { Landmark, Mail, Phone, MapPin, Download } from "lucide-react";
import Header from "./components/Header";
import LandingPage from "./components/LandingPage";
import Dashboard from "./components/Dashboard";
import { ContactSubmission } from "./types";

export default function App() {
  const [currentView, setView] = useState<"public" | "dashboard">("public");
  const [submissionsCount, setSubmissionsCount] = useState<number>(0);

  // Read count of submitted contacts from localStorage during app load
  useEffect(() => {
    const saved = localStorage.getItem("bestcopro_submissions_count");
    if (saved) {
      setSubmissionsCount(parseInt(saved, 10));
    }
  }, []);

  const handleContactSubmit = (submissionData: Omit<ContactSubmission, "id" | "submittedAt">) => {
    const newCount = submissionsCount + 1;
    setSubmissionsCount(newCount);
    localStorage.setItem("bestcopro_submissions_count", newCount.toString());
    
    // Save to contact log in localStorage (for simulation purposes)
    const currentSubmissions = JSON.parse(localStorage.getItem("bestcopro_contacts") || "[]");
    const newSubmission: ContactSubmission = {
      ...submissionData,
      id: "contact-" + Date.now(),
      submittedAt: new Date().toISOString()
    };
    localStorage.setItem("bestcopro_contacts", JSON.stringify([...currentSubmissions, newSubmission]));
  };

  const handleContactScroll = () => {
    if (currentView !== "public") {
      setView("public");
    }
    // Smooth scroll down to contact form
    setTimeout(() => {
      document.getElementById("contact")?.scrollIntoView({ behavior: "smooth" });
    }, 100);
  };

  return (
    <div className="flex flex-col min-h-screen bg-[#f7f9ff] text-[#091d2e] selection:bg-[#aec7f7]">
      {/* Sticky Header with public/client-portal selector */}
      <Header 
        currentView={currentView} 
        setView={setView} 
        onContactClick={handleContactScroll}
      />

      {/* Main app body */}
      <main className="flex-grow">
        {currentView === "public" ? (
          <LandingPage 
            onContactSubmit={handleContactSubmit}
            submissionsCount={submissionsCount}
          />
        ) : (
          <Dashboard />
        )}
      </main>

      {/* Footer matching mockup EXACTLY */}
      <footer className="bg-[#002046] text-white pt-16 pb-8 border-t border-[#1b365d]">
        <div className="max-w-7xl mx-auto px-6 grid grid-cols-1 md:grid-cols-12 gap-10">
          
          {/* Brand & info Column (col-span-4) */}
          <div className="col-span-1 md:col-span-5 space-y-5">
            <div className="flex items-center gap-3">
              <div className="w-9 h-9 bg-white/10 rounded-lg flex items-center justify-center text-white">
                <Landmark className="w-5 h-5 text-[#aec7f7]" />
              </div>
              <div className="font-display font-bold text-lg tracking-tight">
                BEST<span className="text-[#bb0027]">COPRO</span>
              </div>
            </div>
            
            <p className="font-sans text-xs text-gray-400 leading-relaxed max-w-sm">
              Besoin d'un conseil, une question à poser... contactez-nous. Notre centrale de Salé est active du lundi au samedi pour simplifier la vie de votre syndic.
            </p>

            <div className="space-y-2 text-xs text-gray-400">
              <div className="flex items-center gap-2">
                <MapPin className="w-4 h-4 text-[#aec7f7] shrink-0" />
                <span>Avenue Moulay Rachid, Salé, Maroc</span>
              </div>
              <div className="flex items-center gap-2">
                <Phone className="w-4 h-4 text-[#aec7f7] shrink-0" />
                <span>+212 66 03 010 51 / +212 66 36 376 20</span>
              </div>
              <div className="flex items-center gap-2">
                <Mail className="w-4 h-4 text-[#aec7f7] shrink-0" />
                <a href="mailto:contact@bestcopro.ma" className="hover:text-white transition-colors">contact@bestcopro.ma</a>
              </div>
            </div>
          </div>

          {/* Links Column (col-span-4) */}
          <div className="col-span-1 md:col-span-4 space-y-4">
            <h4 className="font-display font-bold text-sm text-[#aec7f7] uppercase tracking-wider">Liens Utiles</h4>
            <ul className="space-y-2 text-xs scroll-smooth">
              <li>
                <a 
                  href="#expertise" 
                  onClick={(e) => {
                    setView("public");
                    setTimeout(() => document.getElementById("expertise")?.scrollIntoView({ behavior: "smooth" }), 100);
                  }}
                  className="text-gray-400 hover:text-white hover:translate-x-1.5 transition-all inline-block"
                >
                  Notre Expertise Métier
                </a>
              </li>
              <li>
                <a 
                  href="#references" 
                  onClick={(e) => {
                    setView("public");
                    setTimeout(() => document.getElementById("references")?.scrollIntoView({ behavior: "smooth" }), 100);
                  }}
                  className="text-gray-400 hover:text-white hover:translate-x-1.5 transition-all inline-block"
                >
                  Exemples de Résidences Gérées
                </a>
              </li>
              <li>
                <a 
                  href="#contact" 
                  onClick={(e) => {
                    setView("public");
                    setTimeout(() => document.getElementById("contact")?.scrollIntoView({ behavior: "smooth" }), 100);
                  }}
                  className="text-gray-400 hover:text-white hover:translate-x-1.5 transition-all inline-block"
                >
                  Demander un audit gratuit
                </a>
              </li>
              <li>
                <button 
                  onClick={() => setView("dashboard")}
                  className="text-left text-gray-400 hover:text-[#aec7f7] hover:translate-x-1.5 transition-all inline-block cursor-pointer outline-none"
                >
                  Se connecter à l'Espace Copropriétaire
                </button>
              </li>
              <li>
                <span className="text-gray-500 italic block mt-1">Conformité légale Loi 18-00 au Maroc</span>
              </li>
            </ul>
          </div>

          {/* App download section (col-span-3) */}
          <div className="col-span-1 md:col-span-3 space-y-4">
            <h4 className="font-display font-bold text-sm text-[#aec7f7] uppercase tracking-wider">Télécharger l'App</h4>
            <div className="flex flex-col gap-2.5">
              <a 
                href="#" 
                onClick={(e) => e.preventDefault()} 
                className="inline-flex items-center gap-2 px-3 py-2 bg-white/5 hover:bg-white/10 rounded-lg text-[10px] text-gray-300 font-bold tracking-tight transition-transform duration-300 hover:-translate-y-0.5"
              >
                <Download className="w-4 h-4 text-[#bb0027]" />
                <div className="text-left leading-tight">
                  <span className="block text-[8px] text-gray-500 font-normal">Disponible pour</span>
                  App Store (iOS)
                </div>
              </a>
              <a 
                href="#" 
                onClick={(e) => e.preventDefault()} 
                className="inline-flex items-center gap-2 px-3 py-2 bg-white/5 hover:bg-white/10 rounded-lg text-[10px] text-gray-300 font-bold tracking-tight transition-transform duration-300 hover:-translate-y-0.5"
              >
                <Download className="w-4 h-4 text-green-500" />
                <div className="text-left leading-tight">
                  <span className="block text-[8px] text-gray-500 font-normal">Téléchargement libre</span>
                  Google Play (Android)
                </div>
              </a>
            </div>
          </div>

        </div>

        {/* Legal copyright bar */}
        <div className="max-w-7xl mx-auto px-6 mt-16 pt-6 border-t border-white/5 text-center md:text-left flex flex-col md:flex-row justify-between items-center gap-4 text-xs text-gray-500">
          <p>© {new Date().getFullYear()} Bestcopro. Tous droits réservés. Gestion professionnelle de propriétés de luxe au Maroc.</p>
          <div className="flex gap-4">
            <span className="cursor-pointer hover:text-gray-300 transition-colors">Politique de Confidentialité</span>
            <span>•</span>
            <span className="cursor-pointer hover:text-gray-300 transition-colors">Conditions Générales</span>
          </div>
        </div>
      </footer>
    </div>
  );
}
