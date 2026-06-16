import React, { useState } from "react";
import { 
  Building, 
  MapPin, 
  Phone, 
  Mail, 
  ArrowRight, 
  FileText, 
  DollarSign, 
  Wrench, 
  Smartphone, 
  CheckCircle, 
  Share2, 
  Instagram, 
  Globe, 
  Star,
  Download
} from "lucide-react";
import { RESIDENCES_DATA, TESTIMONIALS_DATA } from "../data";
import { ContactSubmission } from "../types";

interface LandingPageProps {
  onContactSubmit: (submission: Omit<ContactSubmission, "id" | "submittedAt">) => void;
  submissionsCount: number;
}

export default function LandingPage({ onContactSubmit, submissionsCount }: LandingPageProps) {
  // Accordion for residences
  const [activeResidence, setActiveResidence] = useState<string>("andalucia");
  
  // Local state for the contact form
  const [fullName, setFullName] = useState("");
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");
  const [message, setMessage] = useState("");
  const [showSuccess, setShowSuccess] = useState(false);
  const [lastSubmission, setLastSubmission] = useState<any>(null);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!fullName || !email || !phone) return;

    onContactSubmit({
      fullName,
      email,
      phone,
      message: message || "Demande d'information/contact depuis la landing page."
    });

    setLastSubmission({ fullName, email, phone });
    setFullName("");
    setEmail("");
    setPhone("");
    setMessage("");
    setShowSuccess(true);
    setTimeout(() => {
      setShowSuccess(false);
    }, 8000);
  };

  return (
    <div className="bg-[#f7f9ff] text-[#091d2e] font-sans antialiased">
      
      {/* 1. Hero Section */}
      <section className="relative overflow-hidden min-h-[580px] md:min-h-[660px] flex items-center pt-24 pb-16">
        <div className="absolute inset-0 z-0">
          <img 
            alt="Immeuble moderne à Rabat" 
            className="w-full h-full object-cover opacity-25 scale-105 transition-transform duration-10000 ease-out" 
            src="https://lh3.googleusercontent.com/aida-public/AB6AXuAFua32oylC4XHlsFr4yN70_-__U4mG-vqSq94CdxH8hR0iYHOr-6mJJSi3mdziPeNL0MPufcwDMSIqRaBuMfDsdKH0nDeXgqwShtu_x6ctrc5pO_VlLkICc-OpVyB-cJRNDkSXE0zTy66bxjtC10wOyxE2UI_cEJZnADgS2DQI7G30lWPWk-KTTOPdtXs1I8zHtGa8RkQDfuwcY2pMpLtfWXiGpitNH7OtwmgnLN8ZWlQTWxUqcPCMwLyBo9Oy9yaEo4ktvKcBz64"
          />
          <div className="absolute inset-0 bg-gradient-to-b from-[#f7f9ff]/90 via-transparent to-[#f7f9ff]/90"></div>
          {/* Subtle grid pattern overlay */}
          <div className="absolute inset-0 bg-[radial-gradient(#e3efff_1px,transparent_1px)] [background-size:16px_16px] opacity-40"></div>
        </div>

        <div className="max-w-7xl mx-auto px-6 w-full relative z-10 text-center">
          <div className="max-w-4xl mx-auto">
            <span className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#e3efff] text-[#002046] font-sans font-bold text-xs tracking-wider uppercase mb-6 shadow-sm border border-white">
              <CheckCircle className="w-3.5 h-3.5 text-[#bb0027]" />
              Leader Marocain du Syndic de Copropriété
            </span>
            <h1 className="font-display text-4xl sm:text-5xl md:text-6xl text-[#002046] font-extrabold mb-6 leading-tight tracking-tight">
              Gérez votre copropriété en toute <span className="text-[#bb0027] inline-block relative font-black">simplicité</span>
            </h1>
            <p className="font-sans text-lg sm:text-xl text-gray-600 mb-10 leading-relaxed max-w-2xl mx-auto">
              La plateforme digitale leader pour une gestion transparente, efficace et moderne de votre patrimoine immobilier au Maroc.
            </p>
            <div className="flex flex-col sm:flex-row items-center justify-center gap-4">
              <a 
                href="#contact" 
                className="w-full sm:w-auto bg-[#bb0027] hover:bg-[#A50D26] text-white text-base font-bold px-8 py-4 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300 text-center flex items-center justify-center gap-3 group"
              >
                Demander un devis gratuit
                <ArrowRight className="w-4 h-4 group-hover:translate-x-1 transition-transform" />
              </a>
              <a 
                href="#app-teaser" 
                className="w-full sm:w-auto border-2 border-[#002046] text-[#002046] hover:bg-[#002046] hover:text-white text-base font-bold px-8 py-3.5 rounded-lg transition-all duration-300 text-center"
              >
                Découvrir l'App Mobile
              </a>
            </div>
            
            {/* Quick stats banner */}
            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-16 p-4 rounded-2xl bg-white/70 backdrop-blur-sm border border-white/80 shadow-md">
              <div className="text-center p-2">
                <div className="font-display font-extrabold text-2xl md:text-3xl text-[#002046]">120+</div>
                <div className="text-xs text-gray-500 uppercase tracking-widest font-semibold mt-1">Résidences Gérées</div>
              </div>
              <div className="text-center p-2 border-l border-gray-100">
                <div className="font-display font-extrabold text-2xl md:text-3xl text-[#002046]">10 000+</div>
                <div className="text-xs text-gray-500 uppercase tracking-widest font-semibold mt-1">Copropriétaires</div>
              </div>
              <div className="text-center p-2 border-l border-gray-100">
                <div className="font-display font-extrabold text-2xl md:text-3xl text-[#002046]">98%</div>
                <div className="text-xs text-gray-500 uppercase tracking-widest font-semibold mt-1">Satisfaction</div>
              </div>
              <div className="text-center p-2 border-l border-gray-100">
                <div className="font-display font-extrabold text-2xl md:text-3xl text-[#002046]">24/7</div>
                <div className="text-xs text-gray-500 uppercase tracking-widest font-semibold mt-1">Assistance Sinistres</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 2. Value Proposition (Our Expertise) */}
      <section id="expertise" className="py-20 bg-[#F1F4F8] border-y border-gray-100">
        <div className="max-w-7xl mx-auto px-6">
          <div className="text-center max-w-2xl mx-auto mb-16">
            <span className="text-[#bb0027] font-bold text-xs uppercase tracking-widest inline-block mb-3">CONTRATS & SERVICES</span>
            <h2 className="font-display text-3xl md:text-4xl text-[#002046] font-bold tracking-tight mb-4">Notre Expertise</h2>
            <p className="font-sans text-gray-500 text-base md:text-lg">Une gestion complète, transparente et sécurisée pour votre tranquillité.</p>
          </div>
          
          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            {/* Card 1 */}
            <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:border-[#aec7f7] hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between">
              <div>
                <div className="w-12 h-12 rounded-lg bg-[#edf4ff] flex items-center justify-center text-[#002046] group-hover:bg-[#002046] group-hover:text-white transition-colors mb-6">
                  <FileText className="w-6 h-6" />
                </div>
                <h3 className="font-display text-xl text-[#002046] font-bold mb-3">Gestion administrative</h3>
                <p className="font-sans text-sm text-gray-500 leading-relaxed">
                  Organisation et tenue des assemblées générales rigoureuses, rédaction professionnelle et envoi immédiat des procès-verbaux, tenue du registre.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-gray-50 flex items-center text-[#bb0027] font-semibold text-xs uppercase tracking-wider">
                Excellence Administrative
              </div>
            </div>

            {/* Card 2 */}
            <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:border-[#aec7f7] hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between">
              <div>
                <div className="w-12 h-12 rounded-lg bg-[#edf4ff] flex items-center justify-center text-[#002046] group-hover:bg-[#002046] group-hover:text-white transition-colors mb-6">
                  <DollarSign className="w-6 h-6" />
                </div>
                <h3 className="font-display text-xl text-[#002046] font-bold mb-3">Gestion comptable</h3>
                <p className="font-sans text-sm text-gray-500 leading-relaxed">
                  Établissement du budget prévisionnel annuel, tenue de la comptabilité générale en partie double, relance et recouvrement amiable des charges.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-gray-50 flex items-center text-[#bb0027] font-semibold text-xs uppercase tracking-wider">
                Réf. Comptable Maroc
              </div>
            </div>

            {/* Card 3 */}
            <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:border-[#aec7f7] hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between">
              <div>
                <div className="w-12 h-12 rounded-lg bg-[#edf4ff] flex items-center justify-center text-[#002046] group-hover:bg-[#002046] group-hover:text-white transition-colors mb-6">
                  <Wrench className="w-6 h-6" />
                </div>
                <h3 className="font-display text-xl text-[#002046] font-bold mb-3">Gestion technique</h3>
                <p className="font-sans text-sm text-gray-500 leading-relaxed">
                  Négociation des contrats d'entretien, suivi régulier des équipes de nettoyage et sécurité, visites planifiées, mise en œuvre du plan de travaux.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-gray-50 flex items-center text-[#bb0027] font-semibold text-xs uppercase tracking-wider">
                Suivi chantiers
              </div>
            </div>

            {/* Card 4 */}
            <div className="bg-white p-8 rounded-xl shadow-sm border border-gray-100 hover:border-[#aec7f7] hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between">
              <div>
                <div className="w-12 h-12 rounded-lg bg-[#edf4ff] flex items-center justify-center text-[#002046] group-hover:bg-[#002046] group-hover:text-white transition-colors mb-6">
                  <Smartphone className="w-6 h-6" />
                </div>
                <h3 className="font-display text-xl text-[#002046] font-bold mb-3">Transparence Digitale</h3>
                <p className="font-sans text-sm text-gray-500 leading-relaxed">
                  Application mobile et espace web privatifs dédiés au suivi live. Accusés de paiement, comptes de la copropriété et dépôts de réclamations.
                </p>
              </div>
              <div className="mt-6 pt-4 border-t border-[#edf4ff] flex items-center text-[#bb0027] font-semibold text-xs uppercase tracking-wider">
                Web & Mobile Intégrés
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 3. About Us / Digitalisation */}
      <section className="py-20 bg-white">
        <div className="max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div className="order-2 lg:order-1">
              <span className="text-[#bb0027] font-bold text-xs uppercase tracking-widest inline-block mb-3">QUI SOMMES-NOUS ?</span>
              <h2 className="font-display text-3xl md:text-4xl text-[#002046] font-black tracking-tight mb-6">
                Nous avons <span className="text-[#bb0027]">digitalisé</span> le secteur de la copropriété au Maroc
              </h2>
              <p className="font-sans text-gray-600 mb-6 leading-relaxed">
                Notre société de gestion de copropriété est spécialisée dans la gestion administrative, comptable et technique des immeubles résidentiels et professionnels en copropriété. Nous dépassons le syndic traditionnel en apportant des technologies modernes d'automatisation.
              </p>
              <p className="font-sans text-gray-600 mb-8 leading-relaxed">
                Grâce à notre équipe d'experts chevronnés (juristes, comptables agréés, ingénieurs d'entretien), nous offrons un service irréprochable et un accompagnement de proximité, soutenu par des tableaux de bord interactifs en temps réel.
              </p>
              
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
                <div className="flex items-start gap-3">
                  <CheckCircle className="w-5 h-5 text-[#bb0027] shrink-0 mt-0.5" />
                  <div>
                    <h4 className="font-bold text-[#002046] text-sm">Zéro paperasse</h4>
                    <p className="text-xs text-gray-500">Comptes, PV et budgets scannés et disponibles H24.</p>
                  </div>
                </div>
                <div className="flex items-start gap-3">
                  <CheckCircle className="w-5 h-5 text-[#bb0027] shrink-0 mt-0.5" />
                  <div>
                    <h4 className="font-bold text-[#002046] text-sm">Rapport de syndic mensuel</h4>
                    <p className="text-xs text-gray-500">Un bilan clair envoyé chaque mois par email et SMS.</p>
                  </div>
                </div>
              </div>

              <a 
                href="#contact"
                className="inline-flex items-center gap-3 border-2 border-[#002046] text-[#002046] hover:bg-[#002046] hover:text-white font-sans text-sm font-bold px-6 py-3 rounded-lg transition-all duration-300"
              >
                En Savoir Plus
                <ArrowRight className="w-4 h-4" />
              </a>
            </div>
            
            <div className="order-1 lg:order-2 relative rounded-2xl overflow-hidden shadow-xl aspect-4/3 lg:aspect-square">
              <img 
                alt="Résidence de luxe moderne" 
                className="absolute inset-0 w-full h-full object-cover transform hover:scale-105 transition-transform duration-700" 
                src="https://lh3.googleusercontent.com/aida/AP1WRLvna_8g4_3LTw7oymng4cleaRDPE5TU-ZUI9DPfhKotqccvL2QvciW78w9uVnXIk5FVVEnXluXzBc2t4UeOmfwdQLhQ4jhS7E1bjUllQ72hdvZc_attsVZScxXn_6aJCYukKMtzMs9sTmB4RBBKLw0uCGKFMtmWPyuEXdHhE6vwlLcONfLcy9dgvdO25vCS9ULDReDe6cc-Z2TZdjWF9vavy3SA2HEufAlbxHC19PXj8pssacGzCRss9xw"
              />
              <div className="absolute inset-0 bg-gradient-to-t from-[#002046]/40 to-transparent"></div>
              
              {/* Overlapping floating card */}
              <div className="absolute bottom-6 left-6 right-6 bg-white/90 backdrop-blur-md p-5 rounded-xl border border-white/40 shadow-lg">
                <div className="flex items-center gap-4">
                  <div className="w-10 h-10 bg-[#bb0027] rounded-full flex items-center justify-center text-white font-bold text-sm">
                    100%
                  </div>
                  <div>
                    <h5 className="font-bold text-sm text-[#002046] leading-snug">Conformité Loi 18-00</h5>
                    <p className="text-xs text-gray-500 leading-normal">Toutes nos résidences respectent la législation marocaine sur la copropriété.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* 4. Residences Showcase (Updated with custom dynamic Accordion) */}
      <section id="references" className="py-20 bg-[#f0f4f9] overflow-hidden">
        <div className="max-w-7xl mx-auto px-6 mb-12 text-center">
          <span className="text-[#bb0027] font-bold text-xs uppercase tracking-widest inline-block mb-3">NOS RÉFÉRENCES</span>
          <h2 className="font-display text-3xl md:text-4xl text-[#002046] font-bold tracking-tight mb-4">Résidences de Prestige</h2>
          <p className="font-sans text-gray-500 text-base md:text-lg max-w-2xl mx-auto">
            Découvrez quelques-unes des copropriétés résidentielles marocaines de haut standing qui font confiance à Bestcopro.
          </p>
        </div>

        {/* Accordion Container */}
        <div className="max-w-7xl mx-auto px-6">
          <div className="flex flex-col lg:flex-row w-full min-h-[460px] lg:min-h-[520px] rounded-2xl overflow-hidden shadow-xl border border-white bg-[#002046]">
            {RESIDENCES_DATA.map((residence) => {
              const isActive = activeResidence === residence.id;
              
              return (
                <div 
                  key={residence.id}
                  onMouseEnter={() => {
                    // Desktop hover
                    if (window.innerWidth >= 1024) setActiveResidence(residence.id);
                  }}
                  onClick={() => {
                    // Mobile & general click fallback
                    setActiveResidence(residence.id);
                  }}
                  className={`relative cursor-pointer overflow-hidden border-b lg:border-b-0 lg:border-r border-white/10 transition-all duration-700 ease-in-out ${
                    isActive ? "flex-[5] lg:flex-[5] min-h-[220px]" : "flex-[1] lg:flex-[1] min-h-[70px] lg:min-h-0"
                  }`}
                >
                  {/* Background Image */}
                  <img 
                    alt={residence.name} 
                    className={`absolute inset-0 w-full h-full object-cover transition-all duration-700 ${
                      isActive ? "scale-105 opacity-90" : "scale-100 opacity-40 hover:opacity-60"
                    }`}
                    src={residence.imageUrl}
                  />
                  
                  {/* Gradient Overlay */}
                  <div className={`absolute inset-0 transition-opacity duration-500 bg-gradient-to-t ${
                    isActive 
                      ? "from-[#002046]/95 via-[#002046]/40 to-transparent" 
                      : "from-[#002046]/80 to-[#002046]/40"
                  }`}></div>

                  {/* Vertical Name (Hidden when active on Desktop) */}
                  <div className={`hidden lg:block absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 rotate-270 white-space-nowrap font-display font-extrabold text-base tracking-widest text-[#d1e4fb] uppercase transition-all duration-500 pointer-events-none select-none uppercase-letter-spacing-all ${
                    isActive ? "opacity-0 scale-90 translate-y-10" : "opacity-100 scale-100"
                  }`}>
                    {residence.name}
                  </div>

                  {/* Mobile Simple Label (Hidden when active) */}
                  <div className={`lg:hidden absolute inset-0 flex items-center justify-between px-6 transition-all duration-300 ${
                    isActive ? "opacity-0 pointer-events-none" : "opacity-100"
                  }`}>
                    <span className="font-display font-bold text-white text-base">{residence.name}</span>
                    <span className="text-xs text-[#aec7f7] font-semibold">{residence.location}</span>
                  </div>

                  {/* Content (Visible only when Active) */}
                  <div className={`absolute inset-0 p-6 sm:p-8 flex flex-col justify-end transition-all duration-500 ${
                    isActive ? "opacity-100 translate-y-0" : "opacity-0 translate-y-8 pointer-events-none"
                  }`}>
                    <div className="max-w-xl">
                      <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded bg-[#bb0027] text-white font-sans font-bold text-xs tracking-wider uppercase mb-3">
                        <MapPin className="w-3.5 h-3.5" />
                        {residence.location}
                      </span>
                      <h3 className="font-display text-2xl sm:text-3xl font-extrabold text-white mb-2 tracking-tight">
                        {residence.name}
                      </h3>
                      <p className="font-sans text-sm sm:text-base text-gray-200 mb-4 leading-relaxed">
                        {residence.details}
                      </p>
                      
                      <div className="flex items-center gap-6 pt-3 border-t border-white/20">
                        <div className="text-left">
                          <span className="block font-sans text-xs text-gray-400 uppercase tracking-widest">Syndic depuis</span>
                          <span className="font-bold text-[#aec7f7] text-sm">Janvier 2024</span>
                        </div>
                        <div className="text-left">
                          <span className="block font-sans text-xs text-gray-400 uppercase tracking-widest">Statut</span>
                          <span className="font-bold text-[#e3efff] text-sm flex items-center gap-1.5 mt-0.5">
                            <span className="w-2 h-2 rounded-full bg-green-400 animate-pulse"></span>
                            Gestion Active
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </section>

      {/* 5. Mobile App Teaser */}
      <section id="app-teaser" className="py-20 bg-gradient-to-br from-[#002046] via-[#11223D] to-[#1b365d] text-white overflow-hidden relative">
        {/* Dynamic shape effects */}
        <div className="absolute top-1/4 right-0 w-96 h-96 bg-[#bb0027]/10 rounded-full blur-3xl pointer-events-none"></div>

        <div className="max-w-7xl mx-auto px-6 relative z-10">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-16 items-center">
            
            {/* Visual Phone side-by-side */}
            <div className="lg:col-span-5 flex justify-center order-2 lg:order-1">
              <div className="relative w-full max-w-[340px] aspect-9/19 bg-black rounded-[48px] p-3 shadow-2xl border-4 border-gray-800 ring-12 ring-gray-900/10">
                {/* Speaker pill */}
                <div className="absolute top-0 left-1/2 -translate-x-1/2 h-6 w-32 bg-black rounded-b-2xl z-45 flex items-center justify-center">
                  <div className="w-12 h-1 bg-gray-800 rounded-full"></div>
                </div>
                
                {/* Internal Screen mockup */}
                <div className="w-full h-full rounded-[38px] overflow-hidden bg-[#f7f9ff] text-black shrink-0 relative flex flex-col justify-between">
                  {/* Top phone header */}
                  <div className="pt-8 px-4 pb-3 bg-[#002046] text-white flex justify-between items-center">
                    <div>
                      <div className="text-[10px] font-sans text-gray-400">Bonjour</div>
                      <div className="text-xs font-bold font-display">M. Mohammed 👋</div>
                    </div>
                    <div className="bg-[#bb0027] text-[9px] px-2 py-0.5 rounded-full font-bold">
                      Appt N°14
                    </div>
                  </div>
                  
                  {/* Stats card */}
                  <div className="p-3 grow flex flex-col gap-3 justify-start overflow-y-auto hide-scrollbar">
                    <div className="bg-white p-3 rounded-xl shadow-xs border border-gray-100 flex justify-between items-center">
                      <div>
                        <span className="text-[9px] text-gray-500 uppercase font-semibold">Charges d'immeuble</span>
                        <div className="text-sm font-black text-[#002046]">1 883,75 MAD</div>
                      </div>
                      <span className="text-[10px] font-bold text-[#bb0027] px-2 py-0.5 rounded bg-red-50">En attente</span>
                    </div>

                    <div className="space-y-2 mt-1">
                      <span className="text-[9px] text-gray-400 uppercase font-bold tracking-wider">Vos raccourcis</span>
                      <div className="grid grid-cols-2 gap-2">
                        <div className="p-2 bg-[#e3efff] rounded-lg text-center aspect-square flex flex-col justify-center items-center cursor-pointer">
                          <DollarSign className="w-4 h-4 text-[#002046] mb-1" />
                          <span className="text-[8px] font-bold text-[#002046]">Payer Solde</span>
                        </div>
                        <div className="p-2 bg-red-50 rounded-lg text-center aspect-square flex flex-col justify-center items-center cursor-pointer">
                          <Wrench className="w-4 h-4 text-[#bb0027] mb-1" />
                          <span className="text-[8px] font-bold text-[#bb0027]">Réclamation</span>
                        </div>
                      </div>
                    </div>

                    {/* Timeline mockup */}
                    <div className="space-y-1.5 mt-1 text-left">
                      <span className="text-[9px] text-gray-400 uppercase font-bold">Suivi des travaux</span>
                      <div className="p-2 bg-white rounded-lg border border-gray-100 text-[10px]">
                        <div className="font-bold text-xs text-gray-800">Peinture couloirs</div>
                        <div className="text-gray-500 text-[9px]">Commencé le 04 Juin • 40%</div>
                        <div className="w-full bg-gray-100 h-1.5 rounded-full mt-1.5 overflow-hidden">
                          <div className="bg-green-500 h-full rounded-full" style={{ width: "40%" }}></div>
                        </div>
                      </div>
                    </div>
                  </div>

                  {/* App Footer mockup */}
                  <div className="border-t border-gray-100 bg-white p-2.5 flex justify-around text-gray-400 text-[10px]">
                    <span className="text-[#002046] font-bold">Accueil</span>
                    <span>Suivi</span>
                    <span>Votes</span>
                    <span>Moi</span>
                  </div>
                </div>
              </div>
            </div>

            {/* Description side */}
            <div className="lg:col-span-7 lg:pl-8 order-1 lg:order-2">
              <span className="text-[#bb0027] bg-white/10 px-3 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest inline-block mb-4">
                APPLICATION MOBILE
              </span>
              <h2 className="font-display text-3xl md:text-5xl font-black mb-6 leading-tight tracking-tight">
                BESTCOPRO Mobile Application
              </h2>
              <p className="font-sans text-base md:text-lg text-[#87a0cd] mb-8 leading-relaxed">
                Votre copropriété dans votre poche. Il est crucial d'instaurer des canaux d'échange clairs et immédiats pour permettre aux copropriétaires d'accéder au suivi technique, d'effectuer des réclamations ou de voter en un clic.
              </p>
              
              <div className="space-y-4 mb-10">
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-full bg-[#bb0027] flex items-center justify-center font-bold text-sm">1</div>
                  <span className="font-sans text-sm sm:text-base text-gray-200">Paiement mobile instantané par carte bancaire ou CMI.</span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-full bg-[#bb0027] flex items-center justify-center font-bold text-sm">2</div>
                  <span className="font-sans text-sm sm:text-base text-gray-200">Notifications push instantanées de pannes, d'AG ou d'eau.</span>
                </div>
                <div className="flex items-center gap-3">
                  <div className="w-8 h-8 rounded-full bg-[#bb0027] flex items-center justify-center font-bold text-sm">3</div>
                  <span className="font-sans text-sm sm:text-base text-gray-200">Suivi des travaux de l'immeuble avec photos avant/après.</span>
                </div>
              </div>

              {/* Download buttons */}
              <div className="flex flex-wrap gap-4">
                <a 
                  href="#"
                  className="bg-white hover:bg-gray-100 text-[#002046] font-sans text-xs sm:text-sm font-bold px-6 py-3.5 rounded-xl shadow-lg transition-all duration-300 flex items-center gap-3"
                  onClick={(e) => e.preventDefault()}
                >
                  <Download className="w-5 h-5 text-[#bb0027]" />
                  <div className="text-left">
                    <span className="block text-[9px] text-gray-500 uppercase font-semibold leading-none">Télécharger sur l'</span>
                    <span className="text-sm font-bold leading-none">App Store</span>
                  </div>
                </a>
                <a 
                  href="#"
                  className="bg-white hover:bg-gray-100 text-[#002046] font-sans text-xs sm:text-sm font-bold px-6 py-3.5 rounded-xl shadow-lg transition-all duration-300 flex items-center gap-3"
                  onClick={(e) => e.preventDefault()}
                >
                  <Download className="w-5 h-5 text-green-600" />
                  <div className="text-left">
                    <span className="block text-[9px] text-gray-500 uppercase font-semibold leading-none">Disponible sur</span>
                    <span className="text-sm font-bold leading-none">Google Play</span>
                  </div>
                </a>
              </div>
            </div>
            
          </div>
        </div>
      </section>

      {/* 6. Dynamic Testimonials (Loop / Manual) */}
      <section className="py-20 bg-white overflow-hidden">
        <div className="max-w-7xl mx-auto px-6 mb-12">
          <div className="text-center">
            <span className="text-[#bb0027] font-bold text-xs uppercase tracking-widest inline-block mb-3">TÉMOIGNAGES</span>
            <h2 className="font-display text-3xl md:text-4xl text-[#002046] font-bold tracking-tight mb-4">Témoignages</h2>
            <p className="font-sans text-gray-500 text-sm sm:text-base">Nos clients de Rabat-Salé ont testé et aimé notre réactivité.</p>
          </div>
        </div>

        {/* Carousel strip */}
        <div className="relative w-full overflow-hidden py-4 bg-gray-50/50 border-y border-gray-100">
          <div className="flex w-[200%] animate-scroll">
            <div className="flex gap-8 px-4 w-1/2">
              {TESTIMONIALS_DATA.map((testimonial, i) => (
                <div 
                  key={`t1-${i}`}
                  className="w-[340px] sm:w-[400px] shrink-0 bg-white p-6 sm:p-8 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between"
                >
                  <div className="flex flex-col gap-4">
                    <div className="flex items-center gap-1 text-amber-500">
                      {[...Array(5)].map((_, idx) => (
                        <Star key={idx} className="w-4 h-4 fill-amber-500" />
                      ))}
                    </div>
                    <p className="font-sans text-sm text-gray-600 italic leading-relaxed">
                      "{testimonial.text}"
                    </p>
                  </div>
                  
                  <div className="flex items-center gap-4 mt-6 pt-4 border-t border-gray-50">
                    <img 
                      alt={testimonial.name} 
                      className="w-12 h-12 rounded-full object-cover border-2 border-[#e3efff]" 
                      src={testimonial.avatarUrl}
                    />
                    <div>
                      <h4 className="font-sans font-bold text-[#002046] text-sm leading-snug">{testimonial.name}</h4>
                      <p className="text-xs text-gray-400 font-medium">{testimonial.role}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="flex gap-8 px-4 w-1/2">
              {TESTIMONIALS_DATA.map((testimonial, i) => (
                <div 
                  key={`t2-${i}`}
                  className="w-[340px] sm:w-[400px] shrink-0 bg-white p-6 sm:p-8 rounded-xl border border-gray-100 shadow-sm flex flex-col justify-between"
                >
                  <div className="flex flex-col gap-4">
                    <div className="flex items-center gap-1 text-amber-500">
                      {[...Array(5)].map((_, idx) => (
                        <Star key={idx} className="w-4 h-4 fill-amber-500" />
                      ))}
                    </div>
                    <p className="font-sans text-sm text-gray-600 italic leading-relaxed">
                      "{testimonial.text}"
                    </p>
                  </div>
                  
                  <div className="flex items-center gap-4 mt-6 pt-4 border-t border-gray-50">
                    <img 
                      alt={testimonial.name} 
                      className="w-12 h-12 rounded-full object-cover border-2 border-[#e3efff]" 
                      src={testimonial.avatarUrl}
                    />
                    <div>
                      <h4 className="font-sans font-bold text-[#002046] text-sm leading-snug">{testimonial.name}</h4>
                      <p className="text-xs text-gray-500 font-medium">{testimonial.role}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </section>

      {/* 7. Contact Details & Form */}
      <section id="contact" className="py-20 bg-[#f7f9ff] relative overflow-hidden">
        {/* Dynamic shape */}
        <div className="absolute bottom-[-100px] left-[-100px] w-96 h-96 bg-gray-200/50 rounded-full blur-3xl pointer-events-none"></div>

        <div className="max-w-7xl mx-auto px-6">
          <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-stretch">
            
            {/* Left Info Column */}
            <div className="lg:col-span-5 flex flex-col justify-between">
              <div>
                <div className="flex items-center gap-2 mb-4">
                  <div className="w-1 h-6 bg-[#bb0027]"></div>
                  <span className="font-sans font-bold text-xs text-[#bb0027] uppercase tracking-widest">CONTACT</span>
                </div>
                <h2 className="font-display text-3xl sm:text-4xl text-[#002046] font-bold mb-6 tracking-tight">
                  CONTACTEZ-NOUS DÈS AUJOURD'HUI
                </h2>
                <p className="font-sans text-gray-500 text-sm sm:text-base leading-relaxed mb-8 max-w-sm">
                  Notre équipe de conseillers est à votre écoute pour auditer bénévolement les comptes de votre syndic actuel et formuler une proposition sur-mesure.
                </p>

                <div className="space-y-6">
                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 bg-white border border-gray-100 rounded-lg flex items-center justify-center text-[#bb0027] shadow-xs">
                      <MapPin className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-bold text-sm text-[#002046]">Adresse Administrative</h4>
                      <p className="text-xs sm:text-sm text-gray-500">Avenue Moulay Rachid N°468 2ème étage Hay Al Amal, Salé, Maroc</p>
                    </div>
                  </div>

                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 bg-white border border-gray-100 rounded-lg flex items-center justify-center text-[#bb0027] shadow-xs">
                      <Mail className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-bold text-sm text-[#002046]">Adresse Email</h4>
                      <p className="text-xs sm:text-sm text-gray-500 hover:text-[#bb0027] transition-colors">
                        <a href="mailto:contact@bestcopro.ma">contact@bestcopro.ma</a>
                      </p>
                    </div>
                  </div>

                  <div className="flex items-start gap-4">
                    <div className="w-10 h-10 bg-white border border-gray-100 rounded-lg flex items-center justify-center text-[#bb0027] shadow-xs">
                      <Phone className="w-5 h-5" />
                    </div>
                    <div>
                      <h4 className="font-bold text-sm text-[#002046]">Téléphone Fixe & Mobile</h4>
                      <p className="text-xs sm:text-sm text-gray-500">+212 66 03 010 51 / +212 66 36 376 20</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Social links */}
              <div className="pt-8 mt-12 border-t border-gray-100">
                <h5 className="font-bold text-xs text-gray-400 uppercase tracking-widest mb-4">Suivez-nous :</h5>
                <div className="flex gap-4">
                  <a href="#" className="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-600 hover:bg-[#002046] hover:text-white transition-all shadow-xs" onClick={(e) => e.preventDefault()}>
                    <Share2 className="w-5 h-5" />
                  </a>
                  <a href="#" className="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-600 hover:bg-[#bb0027] hover:text-white transition-all shadow-xs" onClick={(e) => e.preventDefault()}>
                    <Instagram className="w-5 h-5" />
                  </a>
                  <a href="#" className="w-10 h-10 rounded-full bg-white border border-gray-100 flex items-center justify-center text-gray-600 hover:bg-blue-600 hover:text-white transition-all shadow-xs" onClick={(e) => e.preventDefault()}>
                    <Globe className="w-5 h-5" />
                  </a>
                </div>
              </div>
            </div>

            {/* Right solid Crimson Form */}
            <div className="lg:col-span-7 bg-[#bb0027] p-8 sm:p-12 rounded-3xl shadow-xl text-white relative overflow-hidden flex flex-col justify-between">
              {/* Decorative graphic layout from mockup image */}
              <div className="absolute right-[-60px] bottom-[-60px] w-64 h-64 border-[32px] border-white/5 rounded-full pointer-events-none select-none"></div>
              
              <div className="relative z-10">
                <h3 className="font-display font-extrabold text-2xl mb-2">Demande de Renseignements</h3>
                <p className="text-sm text-red-100 mb-8 max-w-md">Remplissez le formulaire ci-dessous et obtenez une réponse en moins de 24 heures.</p>
                
                {showSuccess && lastSubmission ? (
                  <div className="p-6 bg-white/14 backdrop-blur-md rounded-xl border border-white/20 mb-8 animate-fadeIn text-center">
                    <CheckCircle className="w-12 h-12 text-white mx-auto mb-3" />
                    <h4 className="font-bold text-lg mb-1">Merci, {lastSubmission.fullName} !</h4>
                    <p className="text-xs text-red-50 leading-relaxed mb-4">
                      Votre demande a bien été envoyée. Nos conseillers de Salé vont vous recontacter par email ({lastSubmission.email}) ou téléphone ({lastSubmission.phone}).
                    </p>
                    <span className="text-[10px] font-mono uppercase bg-white/10 px-2.5 py-1 rounded-full">Message enregistré localement</span>
                  </div>
                ) : null}

                <form onSubmit={handleSubmit} className="space-y-5">
                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider mb-2 text-red-50">Nom complet</label>
                    <input 
                      type="text" 
                      required
                      placeholder="Ex: Hicham El Alami"
                      value={fullName}
                      onChange={(e) => setFullName(e.target.value)}
                      className="w-full px-4 py-3 rounded-lg bg-white text-[#091d2e] border-none focus:outline-none focus:ring-2 focus:ring-[#aec7f7] shadow-sm text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider mb-2 text-red-50">Adresse Email</label>
                    <input 
                      type="email" 
                      required
                      placeholder="Ex: hicham@domain.ma"
                      value={email}
                      onChange={(e) => setEmail(e.target.value)}
                      className="w-full px-4 py-3 rounded-lg bg-white text-[#091d2e] border-none focus:outline-none focus:ring-2 focus:ring-[#aec7f7] shadow-sm text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider mb-2 text-red-50">Numéro de téléphone</label>
                    <input 
                      type="tel" 
                      required
                      placeholder="Ex: +212 600 000 000"
                      value={phone}
                      onChange={(e) => setPhone(e.target.value)}
                      className="w-full px-4 py-3 rounded-lg bg-white text-[#091d2e] border-none focus:outline-none focus:ring-2 focus:ring-[#aec7f7] shadow-sm text-sm"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-bold uppercase tracking-wider mb-2 text-red-50">Description de la demande (Optionnel)</label>
                    <textarea 
                      placeholder="Nombre d'appartements, adresse de la résidence, préoccupations..."
                      value={message}
                      rows={3}
                      onChange={(e) => setMessage(e.target.value)}
                      className="w-full px-4 py-3 rounded-lg bg-white text-[#091d2e] border-none focus:outline-none focus:ring-2 focus:ring-[#aec7f7] shadow-sm text-sm resize-none"
                    />
                  </div>

                  <div className="pt-2">
                    <button 
                      type="submit" 
                      className="cursor-pointer w-full sm:w-auto bg-white hover:bg-red-50 text-[#bb0027] font-sans text-sm font-bold px-8 py-3.5 rounded-lg shadow-md hover:shadow-xl transition-all duration-300 flex items-center justify-center gap-2"
                    >
                      Envoyer
                      <ArrowRight className="w-4 h-4" />
                    </button>
                  </div>
                </form>
              </div>

              <div className="mt-8 pt-6 border-t border-white/10 text-center text-xs text-red-200">
                Vous avez soumis <span className="font-bold text-white font-mono bg-white/14 px-1.5 py-0.5 rounded">{submissionsCount}</span> contact(s) durant cette session.
              </div>
            </div>

          </div>
        </div>
      </section>

    </div>
  );
}
