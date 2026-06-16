import React, { useState } from "react";
import { Menu, X, Landmark, ShieldCheck, LogIn, LayoutDashboard, Globe } from "lucide-react";

interface HeaderProps {
  currentView: "public" | "dashboard";
  setView: (view: "public" | "dashboard") => void;
  onContactClick: () => void;
}

export default function Header({ currentView, setView, onContactClick }: HeaderProps) {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <nav className="bg-white/95 backdrop-blur-md text-[#002046] fixed top-0 w-full z-50 border-b border-[#E2E8F0] shadow-sm transition-all duration-300">
      <div className="flex justify-between items-center h-20 px-6 max-w-7xl mx-auto">
        {/* Logo Section */}
        <div 
          onClick={() => setView("public")} 
          className="flex items-center gap-3 cursor-pointer select-none"
          id="brand-logo-container"
        >
          <div className="w-10 h-10 bg-[#002046] rounded-lg flex items-center justify-center text-white shadow-md">
            <Landmark className="w-6 h-6 text-[#aec7f7]" />
          </div>
          <div>
            <div className="font-display font-bold text-lg leading-tight tracking-tight flex items-center gap-1 group">
              BEST<span className="text-[#bb0027]">COPRO</span>
            </div>
            <div className="text-[10px] font-sans font-medium text-gray-500 uppercase tracking-widest leading-none">
              Syndic Digital Maroc
            </div>
          </div>
        </div>

        {/* Desktop Links */}
        <div className="hidden md:flex items-center gap-8">
          <button 
            onClick={() => { setView("public"); }}
            className={`font-sans text-sm font-semibold py-2 transition-colors duration-200 cursor-pointer ${
              currentView === "public" ? "text-[#bb0027] border-b-2 border-[#bb0027]" : "text-gray-700 hover:text-[#002046]"
            }`}
          >
            Accueil
          </button>
          
          <a 
            href="#expertise" 
            onClick={(e) => {
              if (currentView !== "public") {
                setView("public");
                setTimeout(() => document.getElementById("expertise")?.scrollIntoView({ behavior: "smooth" }), 100);
              }
            }}
            className="font-sans text-sm font-semibold text-gray-700 hover:text-[#002046] py-2 transition-colors cursor-pointer"
          >
            Notre Expertise
          </a>

          <a 
            href="#references"
            onClick={(e) => {
              if (currentView !== "public") {
                setView("public");
                setTimeout(() => document.getElementById("references")?.scrollIntoView({ behavior: "smooth" }), 100);
              }
            }}
            className="font-sans text-sm font-semibold text-gray-700 hover:text-[#002046] py-2 transition-colors cursor-pointer"
          >
            Exemples de Résidences
          </a>

          <a 
            href="#app-teaser" 
            onClick={(e) => {
              if (currentView !== "public") {
                setView("public");
                setTimeout(() => document.getElementById("app-teaser")?.scrollIntoView({ behavior: "smooth" }), 100);
              }
            }}
            className="font-sans text-sm font-semibold text-gray-700 hover:text-[#002046] py-2 transition-colors cursor-pointer"
          >
            Application Mobile
          </a>
        </div>

        {/* Action Buttons */}
        <div className="hidden md:flex items-center gap-4">
          <button
            onClick={() => setView(currentView === "public" ? "dashboard" : "public")}
            className={`cursor-pointer inline-flex items-center gap-2 px-5 py-2.5 text-sm font-bold rounded-lg transition-all duration-300 ${
              currentView === "dashboard"
                ? "bg-gray-100 hover:bg-gray-200 text-gray-800"
                : "bg-gradient-to-r from-[#002046] to-[#1b365d] hover:to-[#002046] text-white shadow-sm hover:shadow-md"
            }`}
            id="toggle-space-btn"
          >
            {currentView === "public" ? (
              <>
                <LogIn className="w-4 h-4" />
                Espace Copropriétaire
              </>
            ) : (
              <>
                <Globe className="w-4 h-4" />
                Retour au Site
              </>
            )}
          </button>

          {currentView === "public" && (
            <button 
              onClick={onContactClick}
              className="cursor-pointer bg-[#bb0027] hover:bg-[#A50D26] text-white text-sm font-bold px-5 py-2.5 rounded-lg shadow-sm hover:shadow-md transition-all duration-300 transform"
              id="cta-contact-btn"
            >
              Nous Contacter
            </button>
          )}
        </div>

        {/* Mobile menu button */}
        <button 
          onClick={() => setIsOpen(!isOpen)} 
          className="md:hidden p-2 text-[#002046] hover:bg-gray-100 rounded-lg cursor-pointer"
          id="tab-mobile-toggle"
        >
          {isOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
        </button>
      </div>

      {/* Mobile Menu */}
      {isOpen && (
        <div className="md:hidden bg-white border-t border-gray-100 px-4 py-6 space-y-4 animate-fadeIn shadow-inner">
          <button 
            onClick={() => { setView("public"); setIsOpen(false); }}
            className={`w-full text-left font-sans text-base font-semibold py-2 px-3 rounded-lg ${
              currentView === "public" ? "bg-red-50 text-[#bb0027]" : "text-gray-700 hover:bg-gray-50"
            }`}
          >
            Accueil Public
          </button>
          <a 
            href="#expertise" 
            onClick={() => { setView("public"); setIsOpen(false); }}
            className="block font-sans text-base font-semibold text-gray-700 hover:bg-gray-50 py-2 px-3 rounded-lg"
          >
            Notre Expertise
          </a>
          <a 
            href="#references" 
            onClick={() => { setView("public"); setIsOpen(false); }}
            className="block font-sans text-base font-semibold text-gray-700 hover:bg-gray-50 py-2 px-3 rounded-lg"
          >
            Nos Références
          </a>
          <a 
            href="#app-teaser" 
            onClick={() => { setView("public"); setIsOpen(false); }}
            className="block font-sans text-base font-semibold text-gray-700 hover:bg-gray-50 py-2 px-3 rounded-lg"
          >
            Application Mobile
          </a>
          <hr className="border-gray-100 my-2" />
          <div className="flex flex-col gap-3 px-3">
            <button
              onClick={() => { setView(currentView === "public" ? "dashboard" : "public"); setIsOpen(false); }}
              className="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#002046] text-white text-sm font-bold rounded-lg hover:bg-[#1b365d] transition-all"
            >
              {currentView === "public" ? (
                <>
                  <LayoutDashboard className="w-4 h-4" />
                  Espace Copropriétaire (Mohammed)
                </>
              ) : (
                <>
                  <Globe className="w-4 h-4" />
                  Voir le Site Public
                </>
              )}
            </button>
            {currentView === "public" && (
              <button 
                onClick={() => { onContactClick(); setIsOpen(false); }}
                className="w-full bg-[#bb0027] text-white text-sm font-bold px-4 py-3 rounded-lg hover:bg-[#A50D26] transition-all text-center"
              >
                Nous Contacter
              </button>
            )}
          </div>
        </div>
      )}
    </nav>
  );
}
