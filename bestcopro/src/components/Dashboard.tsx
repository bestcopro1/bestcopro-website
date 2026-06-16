import React, { useState } from "react";
import { 
  DollarSign, 
  Wrench, 
  Vote, 
  Bell, 
  FileCheck, 
  LogOut, 
  CheckCircle, 
  AlertTriangle, 
  Clock, 
  Plus, 
  FileText, 
  CreditCard,
  Printer, 
  ShieldAlert,
  Download,
  Info
} from "lucide-react";
import { 
  DEFAULT_BILLS, 
  DEFAULT_COMPLAINTS, 
  DEFAULT_VOTES, 
  MOCK_USER, 
  MOCK_NOTIFICATIONS 
} from "../data";
import { ChargeBill, Complaint, AssemblyVote } from "../types";

export default function Dashboard() {
  // Shared states
  const [user, setUser] = useState(MOCK_USER);
  const [bills, setBills] = useState<ChargeBill[]>(DEFAULT_BILLS);
  const [complaints, setComplaints] = useState<Complaint[]>(DEFAULT_COMPLAINTS);
  const [votes, setVotes] = useState<AssemblyVote[]>(DEFAULT_VOTES);
  
  // Controls
  const [activeTab, setActiveTab] = useState<"compte" | "reclamations" | "votes" | "docs">("compte");
  const [showPaymentModal, setShowPaymentModal] = useState(false);
  const [showNewComplaintModal, setShowNewComplaintModal] = useState(false);
  const [selectedBillForPayment, setSelectedBillForPayment] = useState<ChargeBill | null>(null);
  
  // Form states
  const [paymentCardName, setPaymentCardName] = useState("");
  const [paymentCardNum, setPaymentCardNum] = useState("");
  const [paymentCardExp, setPaymentCardExp] = useState("");
  const [paymentCardCVC, setPaymentCardCVC] = useState("");
  const [paymentProcessing, setPaymentProcessing] = useState(false);
  const [paymentSuccess, setPaymentSuccess] = useState(false);
  const [receiptCode, setReceiptCode] = useState("");

  const [newComplaintTitle, setNewComplaintTitle] = useState("");
  const [newComplaintCat, setNewComplaintCat] = useState<"technical" | "admin" | "condo">("technical");
  const [newComplaintDesc, setNewComplaintDesc] = useState("");
  const [newComplaintImage, setNewComplaintImage] = useState("");

  // Payment process handler
  const handleOpenPayment = (bill: ChargeBill) => {
    setSelectedBillForPayment(bill);
    setPaymentCardName(user.name);
    setPaymentCardNum("");
    setPaymentCardCVC("");
    setPaymentCardExp("");
    setShowPaymentModal(true);
    setPaymentSuccess(false);
  };

  const processPayment = (e: React.FormEvent) => {
    e.preventDefault();
    setPaymentProcessing(true);
    
    setTimeout(() => {
      setPaymentProcessing(false);
      setPaymentSuccess(true);
      const code = "RC-" + Math.floor(100000 + Math.random() * 900000);
      setReceiptCode(code);

      if (selectedBillForPayment) {
        // Update bill status to paye
        setBills(prev => prev.map(b => b.id === selectedBillForPayment.id 
          ? { ...b, status: "paye" as const, paidAt: new Date().toISOString().split('T')[0] } 
          : b
        ));
        
        // Subtract from user solde
        setUser(prev => ({
          ...prev,
          solde: Math.max(0, parseFloat((prev.solde - selectedBillForPayment.amount).toFixed(2)))
        }));
      } else {
        // Pay entire balance
        setBills(prev => prev.map(b => b.status === "a_payer" 
          ? { ...b, status: "paye" as const, paidAt: new Date().toISOString().split('T')[0] } 
          : b
        ));
        setUser(prev => ({ ...prev, solde: 0 }));
      }
    }, 1800);
  };

  // Submit new complaint
  const handleAddComplaint = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newComplaintTitle || !newComplaintDesc) return;

    const mappedCatMap: Record<string, "administratif" | "comptable" | "technique" | "autre"> = {
      technical: "technique",
      admin: "administratif",
      condo: "comptable"
    };

    const newCompl: Complaint = {
      id: "c-new-" + Date.now(),
      title: newComplaintTitle,
      category: mappedCatMap[newComplaintCat] || "technique",
      description: newComplaintDesc,
      status: "reçu",
      createdAt: new Date().toISOString().replace('T', ' ').substring(0, 16),
      apartment: user.apartment,
      imageUrl: newComplaintImage || undefined
    };

    setComplaints([newCompl, ...complaints]);
    setNewComplaintTitle("");
    setNewComplaintDesc("");
    setNewComplaintImage("");
    setShowNewComplaintModal(false);
  };

  // Handle assembly voting
  const handleVoteSubmit = (voteId: string, selection: string) => {
    setVotes(prev => prev.map(v => {
      if (v.id === voteId) {
        const alreadyVoted = v.userVote;
        const newVotesCount = { ...v.votesCount };
        
        // Remove previous vote count if editing vote
        if (alreadyVoted && newVotesCount[alreadyVoted]) {
          newVotesCount[alreadyVoted] = Math.max(0, newVotesCount[alreadyVoted] - 1);
        }

        newVotesCount[selection] = (newVotesCount[selection] || 0) + 1;
        
        return {
          ...v,
          userVote: selection,
          votesCount: newVotesCount
        };
      }
      return v;
    }));
  };

  // Safe Category Badges lookup
  const getCategoryBadge = (cat: string) => {
    switch (cat) {
      case "technique":
        return <span className="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Technique</span>;
      case "administratif":
        return <span className="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Administratif</span>;
      case "comptable":
        return <span className="bg-purple-50 text-purple-700 border border-purple-200 text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Comptable</span>;
      default:
        return <span className="bg-gray-50 text-gray-700 border border-gray-200 text-[10px] px-2 py-0.5 rounded font-bold uppercase tracking-wider">Autre</span>;
    }
  };

  // Safe Status Badges
  const getStatusBadge = (status: string) => {
    switch (status) {
      case "reçu":
        return (
          <span className="flex items-center gap-1.5 font-bold text-xs text-blue-600 bg-blue-50 border border-blue-100 px-2.5 py-1 rounded-full">
            <Clock className="w-3 h-3" /> Signalement Reçu
          </span>
        );
      case "en_cours":
        return (
          <span className="flex items-center gap-1.5 font-bold text-xs text-orange-600 bg-orange-50 border border-orange-100 px-2.5 py-1 rounded-full">
            <span className="w-1.5 h-1.5 rounded-full bg-orange-500 animate-pulse"></span>
            Travaux Lancés
          </span>
        );
      case "résolu":
        return (
          <span className="flex items-center gap-1.5 font-bold text-xs text-green-600 bg-green-50 border border-green-100 px-2.5 py-1 rounded-full">
            <CheckCircle className="w-3.5 h-3.5 text-green-500" /> Clôturé / Résolu
          </span>
        );
      default:
        return null;
    }
  };

  return (
    <div className="pt-24 pb-16 min-h-screen bg-[#F1F4F8]">
      <div className="max-w-7xl mx-auto px-6">
        
        {/* Dashboard Title Bar */}
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100/80 mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
          <div className="flex items-center gap-4">
            <img 
              alt={user.name} 
              className="w-16 h-16 rounded-2xl object-cover ring-4 ring-[#e3efff] shadow-sm"
              src={user.avatarUrl}
            />
            <div>
              <div className="font-sans text-xs text-gray-500 uppercase tracking-widest font-bold">Espace Copropriétaire</div>
              <h1 className="font-display text-2xl font-black text-[#002046] tracking-tight">{user.name}</h1>
              <p className="text-xs text-gray-500 mt-0.5">{user.apartment} • <span className="font-semibold text-gray-600 uppercase text-[10px]">{user.building}</span></p>
            </div>
          </div>
          
          {/* Quick Balance Status */}
          <div className="flex items-center gap-6 w-full md:w-auto border-t md:border-t-0 md:border-l border-gray-100 pt-6 md:pt-0 md:pl-8">
            <div className="grow md:grow-0">
              <span className="block text-[10px] text-gray-400 uppercase tracking-widest font-bold">Solde de charges dû</span>
              <div className={`font-display text-2xl font-extrabold mt-0.5 ${user.solde > 0 ? "text-[#bb0027]" : "text-green-600"}`}>
                {user.solde.toLocaleString()} MAD
              </div>
            </div>
            {user.solde > 0 ? (
              <button 
                onClick={() => {
                  setSelectedBillForPayment(null);
                  setPaymentCardName(user.name);
                  setPaymentCardNum("");
                  setPaymentCardCVC("");
                  setPaymentCardExp("");
                  setShowPaymentModal(true);
                  setPaymentSuccess(false);
                }}
                className="cursor-pointer bg-[#bb0027] hover:bg-[#A50D26] text-white text-xs font-bold px-4 py-2.5 rounded-lg shadow-md hover:shadow-lg transition-all flex items-center gap-2"
              >
                <CreditCard className="w-4 h-4" />
                Tout Payer
              </button>
            ) : (
              <span className="bg-green-50 border border-green-200 text-green-700 text-xs px-3 py-1.5 rounded-lg flex items-center gap-1 font-bold">
                <CheckCircle className="w-4 h-4" /> Compte à jour
              </span>
            )}
          </div>
        </div>

        {/* Outer Bento Layout Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
          
          {/* LEFT: Tab navigation Menu (Royal Blue design element) */}
          <div className="lg:col-span-3 bg-[#002046] rounded-2xl p-4 text-white shadow-md border border-white/10 shrink-0">
            <h3 className="text-xs text-slate-400 font-bold uppercase tracking-widest p-3 border-b border-white/5 mb-2">
              Menu Espace Client
            </h3>
            <div className="space-y-1.5">
              <button 
                onClick={() => setActiveTab("compte")}
                className={`w-full text-left font-sans text-sm font-semibold p-3 rounded-xl flex items-center gap-3 transition-all cursor-pointer ${
                  activeTab === "compte" ? "bg-[#bb0027] text-white shadow-sm" : "hover:bg-white/5 text-gray-200"
                }`}
              >
                <DollarSign className="w-4 h-4" />
                Situation Financière
              </button>

              <button 
                onClick={() => setActiveTab("reclamations")}
                className={`w-full text-left font-sans text-sm font-semibold p-3 rounded-xl flex items-center gap-3 transition-all cursor-pointer ${
                  activeTab === "reclamations" ? "bg-[#bb0027] text-white shadow-sm" : "hover:bg-white/5 text-gray-200"
                }`}
              >
                <Wrench className="w-4 h-4" />
                Réclamations ({complaints.length})
              </button>

              <button 
                onClick={() => setActiveTab("votes")}
                className={`w-full text-left font-sans text-sm font-semibold p-3 rounded-xl flex items-center gap-3 transition-all cursor-pointer ${
                  activeTab === "votes" ? "bg-[#bb0027] text-white shadow-sm" : "hover:bg-white/5 text-gray-200"
                }`}
              >
                <Vote className="w-4 h-4" />
                Votes & AGs ({votes.length})
              </button>

              <button 
                onClick={() => setActiveTab("docs")}
                className={`w-full text-left font-sans text-sm font-semibold p-3 rounded-xl flex items-center gap-3 transition-all cursor-pointer ${
                  activeTab === "docs" ? "bg-[#bb0027] text-white shadow-sm" : "hover:bg-white/5 text-gray-200"
                }`}
              >
                <FileCheck className="w-4 h-4" />
                Registres & PV
              </button>
            </div>
            
            {/* System advisory info */}
            <div className="mt-8 p-3 bg-white/5 rounded-xl border border-white/5 text-[11px] text-slate-400">
              <div className="flex gap-2 items-start">
                <Info className="w-4 h-4 text-[#aec7f7] shrink-0" />
                <p className="leading-snug">
                  Syndic officiel agréé Bestcopro. Pour toute urgence technique, composez le <span className="text-white font-bold font-mono">06 63 36 37 62</span>.
                </p>
              </div>
            </div>
          </div>

          {/* RIGHT: Content panels */}
          <div className="lg:col-span-9 space-y-8">
            
            {/* Tab: FINANCIAL POSITION */}
            {activeTab === "compte" && (
              <div className="space-y-6">
                
                {/* Outstanding invoices warning */}
                {user.solde > 0 && (
                  <div className="p-4 bg-amber-50 rounded-xl border border-amber-100 flex gap-4 items-center mb-2">
                    <ShieldAlert className="w-6 h-6 text-amber-600 shrink-0" />
                    <div>
                      <h4 className="font-bold text-sm text-gray-900">Charges de copropriété en attente</h4>
                      <p className="text-xs text-gray-500 leading-normal">
                        Il reste deux factures en suspens pour votre logement. Veuillez régler ces montants pour préserver la trésorerie de votre immeuble.
                      </p>
                    </div>
                  </div>
                )}

                {/* Bills Table Card */}
                <div className="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                  <div className="p-6 border-b border-gray-50 flex justify-between items-center bg-gray-50/50">
                    <div>
                      <h3 className="font-display font-extrabold text-lg text-[#002046]">Relevé de Compte</h3>
                      <p className="text-xs text-gray-400">Consultez l'historique de vos paiements et vos charges actuelles.</p>
                    </div>
                    <span className="text-xs font-mono bg-[#edf4ff] text-[#002046] px-2.5 py-1 rounded font-bold">Loi 18-00</span>
                  </div>

                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-sm border-collapse">
                      <thead>
                        <tr className="border-b border-gray-100 bg-gray-50 text-[10px] text-gray-400 uppercase font-extrabold tracking-widest pl-4">
                          <th className="py-4 px-6">Désignation</th>
                          <th className="py-4 px-4">Échéance</th>
                          <th className="py-4 px-4 text-right">Montant</th>
                          <th className="py-4 px-4 text-center">Statut</th>
                          <th className="py-4 px-6 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-50 font-sans">
                        {bills.map((bill) => (
                          <tr key={bill.id} className="hover:bg-gray-50/50 transition-colors">
                            <td className="py-4 px-6">
                              <div className="font-bold text-gray-800">{bill.title}</div>
                              <div className="text-[10px] text-gray-400 font-medium">Réf: {bill.id.toUpperCase()}</div>
                            </td>
                            <td className="py-4 px-4 text-gray-500 text-xs">
                              {bill.dueDate}
                            </td>
                            <td className="py-4 px-4 text-right font-bold text-gray-700">
                              {bill.amount.toLocaleString()} MAD
                            </td>
                            <td className="py-4 px-4 text-center">
                              {bill.status === "paye" ? (
                                <span className="inline-flex items-center gap-1.5 bg-green-50 text-green-700 text-xs px-2.5 py-1 rounded-full font-bold">
                                  Payé
                                </span>
                              ) : bill.status === "a_payer" ? (
                                <span className="inline-flex items-center gap-1.5 bg-red-50 text-red-700 text-xs px-2.5 py-1 rounded-full font-bold">
                                  À Payer
                                </span>
                              ) : (
                                <span className="inline-flex items-center gap-1.5 bg-rose-100 text-rose-800 text-xs px-2.5 py-1 rounded-full font-bold">
                                  Retard
                                </span>
                              )}
                            </td>
                            <td className="py-4 px-6 text-right">
                              {bill.status !== "paye" ? (
                                <button 
                                  onClick={() => handleOpenPayment(bill)}
                                  className="cursor-pointer bg-blue-50 text-[#002046] hover:bg-[#002046] hover:text-white transition-all text-xs font-bold px-3 py-1.5 rounded-lg border border-blue-100"
                                >
                                  Régler
                                </button>
                              ) : (
                                <span className="text-xs text-gray-400 flex items-center justify-end gap-1 font-medium">
                                  Le {bill.paidAt}
                                </span>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                </div>

                {/* Expense Pie Chart description (Static visually compelling block) */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {/* Financial Report Info */}
                  <div className="bg-white rounded-2xl p-6 shadow-xs border border-gray-100">
                    <h4 className="font-display font-extrabold text-[#002046] text-sm md:text-base uppercase tracking-wider mb-3">Répartition Administrative</h4>
                    <p className="text-xs text-gray-500 mb-4">La répartition des charges mensuelles ordinaires de la Résidence Andalucia s'effectue conformément à la Loi 18-00 relative aux quotités :</p>
                    <div className="space-y-2 text-xs">
                      <div className="flex justify-between p-2 rounded bg-gray-50">
                        <span className="text-gray-500">Sécurité & Gardiennage (Sahara Securité)</span>
                        <span className="font-bold text-gray-800">40%</span>
                      </div>
                      <div className="flex justify-between p-2 rounded bg-gray-50">
                        <span className="text-gray-500">Énergie & Eau (Parties Communes)</span>
                        <span className="font-bold text-gray-800">25%</span>
                      </div>
                      <div className="flex justify-between p-2 rounded bg-gray-50">
                        <span className="text-gray-500">Entretien (Ascenseur & Espaces verts)</span>
                        <span className="font-bold text-gray-800">20%</span>
                      </div>
                      <div className="flex justify-between p-2 rounded bg-gray-50">
                        <span className="text-gray-500">Honoraires de gestion (Bestcopro)</span>
                        <span className="font-bold text-[#bb0027]">15%</span>
                      </div>
                    </div>
                  </div>

                  {/* Quitus de syndic box */}
                  <div className="bg-gradient-to-br from-[#002046] to-[#1b365d] text-white rounded-2xl p-6 shadow-md flex flex-col justify-between">
                    <div>
                      <span className="bg-white/10 text-white/80 uppercase text-[9px] px-2 py-0.5 rounded font-bold tracking-widest inline-block mb-3">CONFORMITÉ VENTE / CERTIFICAT</span>
                      <h4 className="font-display font-extrabold text-lg mb-2">Quitus de Syndic</h4>
                      <p className="text-xs text-slate-300 leading-relaxed mb-4">
                        Vous vendez votre appartement ou avez besoin d'attester de votre bonne situation financière pour l'obtention d'un certificat notarié ? Générez et téléchargez votre quitus de syndic provisoire signé numériquement.
                      </p>
                    </div>
                    <div>
                      {user.solde === 0 ? (
                        <button 
                          onClick={() => alert("Génération de votre Quitus en PDF sécurisé...")}
                          className="w-full bg-[#bb0027] hover:bg-red-700 text-white font-sans text-xs font-bold py-2.5 rounded-lg flex items-center justify-center gap-2 transition-all cursor-pointer"
                        >
                          <FileText className="w-4 h-4" />
                          Générer le Quitus PDF
                        </button>
                      ) : (
                        <div className="p-3 bg-red-500/10 border border-red-500/20 text-red-200 text-xs rounded-lg flex items-center gap-2">
                          <AlertTriangle className="w-5 h-5 shrink-0" />
                          <span>Réglez vos {user.solde.toLocaleString()} MAD restants pour débloquer votre quitus de syndic.</span>
                        </div>
                      )}
                    </div>
                  </div>
                </div>

              </div>
            )}

            {/* Tab: COMPLAINTS / RÉCLAMATIONS */}
            {activeTab === "reclamations" && (
              <div className="space-y-6">
                
                {/* Title and submit trigger */}
                <div className="flex justify-between items-center">
                  <div>
                    <h2 className="font-display font-extrabold text-[#002046] text-xl tracking-tight">Cahier de Doléances</h2>
                    <p className="text-xs text-gray-500">Soumettez des signalements d'entretien ou d'administration à Bestcopro.</p>
                  </div>
                  <button 
                    onClick={() => setShowNewComplaintModal(true)}
                    className="cursor-pointer bg-[#bb0027] hover:bg-[#A50D26] text-white text-xs font-bold px-4 py-2.5 rounded-xl flex items-center gap-1.5 shadow-sm hover:shadow-md transition-all"
                  >
                    <Plus className="w-4 h-4" />
                    Signaler un Incident
                  </button>
                </div>

                {/* Complaint items list */}
                <div className="space-y-4">
                  {complaints.map((complaint) => (
                    <div 
                      key={complaint.id}
                      className="bg-white rounded-2xl p-6 shadow-xs border border-gray-100 flex flex-col md:flex-row gap-6 hover:shadow-md transition-shadow"
                    >
                      {complaint.imageUrl && (
                        <div className="w-full md:w-40 h-28 rounded-lg overflow-hidden shrink-0 bg-gray-50">
                          <img 
                            alt={complaint.title} 
                            className="w-full h-full object-cover"
                            src={complaint.imageUrl}
                          />
                        </div>
                      )}
                      
                      <div className="grow flex flex-col justify-between">
                        <div>
                          <div className="flex flex-wrap items-center gap-2.5 mb-2">
                            {getCategoryBadge(complaint.category)}
                            <span className="text-[10px] text-gray-400 font-mono">{complaint.createdAt}</span>
                          </div>
                          <h4 className="font-display font-bold text-gray-800 text-base leading-snug">{complaint.title}</h4>
                          <p className="text-xs text-gray-500 mt-1.5 leading-relaxed">{complaint.description}</p>
                        </div>

                        <div className="flex justify-between items-center pt-4 border-t border-gray-50 mt-4">
                          <span className="text-[10px] text-gray-400">Logement: <span className="font-bold text-gray-600">{complaint.apartment}</span></span>
                          {getStatusBadge(complaint.status)}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>

              </div>
            )}

            {/* Tab: VOTES AND ASSEMBLIES */}
            {activeTab === "votes" && (
              <div className="space-y-6">
                
                {/* Header section */}
                <div>
                  <h2 className="font-display font-extrabold text-[#002046] text-xl tracking-tight">Consultations Collectives</h2>
                  <p className="text-xs text-gray-500">Exprimez votre avis avant l'assemblée officielle pour l'amélioration de la vie commune.</p>
                </div>

                {/* Assemblies notifications/invitations */}
                <div className="bg-white rounded-2xl p-6 border border-gray-100/80 shadow-xs">
                  <h3 className="font-display font-bold text-gray-800 text-base mb-4 flex items-center gap-2">
                    <Bell className="w-5 h-5 text-red-600" /> Notifications de Conseil Syndical
                  </h3>
                  <div className="space-y-3">
                    {MOCK_NOTIFICATIONS.map((notif) => (
                      <div key={notif.id} className="p-4 rounded-xl border border-gray-50 bg-[#F1F4F8]/60 text-xs leading-relaxed">
                        <div className="flex justify-between text-[10px] text-gray-400 font-bold uppercase mb-1.5">
                          <span>{notif.date}</span>
                          <span className={notif.type === "warning" ? "text-amber-600" : "text-blue-600"}>{notif.type.toUpperCase()}</span>
                        </div>
                        <h5 className="font-sans font-bold text-gray-800 mb-1">{notif.title}</h5>
                        <p className="text-gray-500 leading-relaxed font-normal">{notif.message}</p>
                      </div>
                    ))}
                  </div>
                </div>

                {/* Votes List cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                  {votes.map((vote) => {
                    // compute percentage
                    const votesValues = Object.values(vote.votesCount) as number[];
                    const totalVotes = votesValues.reduce((a, b) => a + b, 0);
                    
                    return (
                      <div key={vote.id} className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                          <div className="flex justify-between text-[11px] font-bold text-gray-400 mb-3">
                            <span>Fin : {vote.endDate}</span>
                            <span>{totalVotes} vote(s)</span>
                          </div>
                          <h4 className="font-display font-bold text-[#002046] text-base leading-snug mb-2">{vote.title}</h4>
                          <p className="text-xs text-gray-500 leading-normal mb-6 font-normal">{vote.description}</p>
                        </div>
                        
                        <div className="space-y-3 pt-4 border-t border-gray-50">
                          {vote.options.map((opt) => {
                            const optVoteCount = (vote.votesCount[opt] as number) || 0;
                            const pct = totalVotes > 0 ? Math.round((optVoteCount / totalVotes) * 100) : 0;
                            const isSelected = vote.userVote === opt;
                            
                            return (
                              <div key={opt} className="relative">
                                {/* Voting progress background color */}
                                <div 
                                  className={`absolute inset-y-0 left-0 rounded-lg transition-all duration-500 ${
                                    isSelected ? "bg-red-100/70" : "bg-gray-100/50"
                                  }`} 
                                  style={{ width: `${pct}%` }}
                                ></div>
                                
                                <button
                                  onClick={() => handleVoteSubmit(vote.id, opt)}
                                  className={`w-full p-3 rounded-lg border text-left text-xs font-semibold relative z-10 flex justify-between items-center transition-all ${
                                    isSelected 
                                      ? "border-[#bb0027] text-[#bb0027]" 
                                      : "border-gray-200 text-gray-700 hover:border-gray-300"
                                  }`}
                                >
                                  <span>{opt}</span>
                                  <span className="font-mono text-[11px] font-bold">{pct}% ({vote.votesCount[opt] || 0})</span>
                                </button>
                              </div>
                            );
                          })}
                        </div>
                      </div>
                    );
                  })}
                </div>

              </div>
            )}

            {/* Tab: PV & REGISTERS */}
            {activeTab === "docs" && (
              <div className="space-y-6">
                <div>
                  <h2 className="font-display font-extrabold text-[#002046] text-xl tracking-tight">Registres & Procès-Verbaux</h2>
                  <p className="text-xs text-gray-500">Accédez en toute transparence aux documents légaux de votre copropriété.</p>
                </div>

                <div className="bg-white rounded-2xl shadow-xs border border-gray-100 divide-y divide-gray-100 overflow-hidden">
                  
                  {/* Doc 1 */}
                  <div className="p-5 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <div className="flex gap-4 items-center">
                      <div className="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center text-[#bb0027]">
                        <FileText className="w-5 h-5" />
                      </div>
                      <div>
                        <h4 className="font-sans font-bold text-gray-800 text-sm">Procès-Verbal - Assemblée Générale Ordinaire 2025</h4>
                        <p className="text-xs text-gray-400">PDF • 5.1 Mo • Approuvé le 15 Décembre 2025</p>
                      </div>
                    </div>
                    <button 
                      onClick={() => alert("Téléchargement du fichier PV_AGO_2025_Bestcopro.pdf...")} 
                      className="cursor-pointer bg-[#edf4ff] hover:bg-[#002046] text-[#002046] hover:text-white transition-all text-xs font-extrabold px-3.5 py-2 rounded-lg flex items-center gap-1"
                    >
                      <Download className="w-4 h-4" />
                      Télécharger
                    </button>
                  </div>

                  {/* Doc 2 */}
                  <div className="p-5 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <div className="flex gap-4 items-center">
                      <div className="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center text-blue-600">
                        <FileText className="w-5 h-5" />
                      </div>
                      <div>
                        <h4 className="font-sans font-bold text-gray-800 text-sm">Compte d'exploitation & de Trésorerie détaillé - Année 2025</h4>
                        <p className="text-xs text-gray-400">XLSX • 1.4 Mo • Validé par le Conseil Syndical</p>
                      </div>
                    </div>
                    <button 
                      onClick={() => alert("Téléchargement du fichier Rapports_Financier_2025.xlsx...")} 
                      className="cursor-pointer bg-[#edf4ff] hover:bg-[#002046] text-[#002046] hover:text-white transition-all text-xs font-extrabold px-3.5 py-2 rounded-lg flex items-center gap-1"
                    >
                      <Download className="w-4 h-4" />
                      Télécharger
                    </button>
                  </div>

                  {/* Doc 3 */}
                  <div className="p-5 flex justify-between items-center hover:bg-gray-50 transition-colors">
                    <div className="flex gap-4 items-center">
                      <div className="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center text-green-600">
                        <FileText className="w-5 h-5" />
                      </div>
                      <div>
                        <h4 className="font-sans font-bold text-gray-800 text-sm">Règlement de copropriété enregistré de la Résidence</h4>
                        <p className="text-xs text-gray-400">PDF • 12.8 Mo • Enregistré à la conservation foncière</p>
                      </div>
                    </div>
                    <button 
                      onClick={() => alert("Téléchargement du fichier Reglement_Copropriete_Andalucia.pdf...")} 
                      className="cursor-pointer bg-[#edf4ff] hover:bg-[#002046] text-[#002046] hover:text-white transition-all text-xs font-extrabold px-3.5 py-2 rounded-lg flex items-center gap-1"
                    >
                      <Download className="w-4 h-4" />
                      Télécharger
                    </button>
                  </div>

                </div>

              </div>
            )}

          </div>

        </div>

        {/* MODAL 1: Payment Checkout (Simulator) */}
        {showPaymentModal && (
          <div className="fixed inset-0 bg-[#002046]/40 backdrop-blur-xs flex items-center justify-center z-50 p-4 animate-fadeIn">
            <div className="bg-white rounded-3xl p-6 sm:p-10 w-full max-w-md shadow-2xl border border-gray-100 relative">
              <button 
                onClick={() => setShowPaymentModal(false)}
                className="absolute top-5 right-5 text-gray-400 hover:text-gray-600 cursor-pointer text-lg font-bold p-1 bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center"
              >
                ×
              </button>
              
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 bg-red-50 text-[#bb0027] rounded-lg flex items-center justify-center">
                  <CreditCard className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-display font-extrabold text-lg text-gray-900 leading-tight">Paiement Sécurisé</h3>
                  <p className="text-[11px] text-gray-400">Canal crypté Maroc CMI / Bestcopro Pay</p>
                </div>
              </div>

              {paymentSuccess ? (
                <div className="text-center py-6 animate-fadeIn">
                  <CheckCircle className="w-14 h-14 text-green-500 mx-auto mb-4" />
                  <h4 className="font-display font-black text-[#002046] text-xl leading-snug">Paiement Réussi !</h4>
                  <p className="text-xs text-gray-500 mt-2 leading-relaxed">
                    Le solde de {selectedBillForPayment ? selectedBillForPayment.amount + " MAD" : "toutes vos charges"} a été décomptabilisé avec succès.
                  </p>
                  
                  <div className="mt-6 bg-[#F1F4F8] p-3.5 rounded-xl text-left text-xs space-y-1">
                    <div className="flex justify-between border-b border-gray-100 pb-1 text-gray-400 uppercase text-[9px] font-bold">
                      <span>RÉCEPISSÉ DE TRANSACTION</span>
                    </div>
                    <div className="flex justify-between pt-1">
                      <span className="text-gray-500">Numéro d'opération:</span>
                      <span className="font-mono font-bold text-gray-800">{receiptCode}</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-500">Bénéficiaire :</span>
                      <span className="font-semibold text-[#002046]">Bestcopro Salé</span>
                    </div>
                    <div className="flex justify-between">
                      <span className="text-gray-500">Date :</span>
                      <span className="text-gray-600">{new Date().toLocaleString()}</span>
                    </div>
                  </div>

                  <div className="mt-6 flex gap-3">
                    <button 
                      onClick={() => window.print()}
                      className="cursor-pointer basis-1/2 justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-sans text-xs font-bold py-3 rounded-lg flex items-center gap-1.5"
                    >
                      <Printer className="w-4 h-4" /> Imprimer
                    </button>
                    <button 
                      onClick={() => setShowPaymentModal(false)}
                      className="cursor-pointer basis-1/2 justify-center bg-[#002046] text-white font-sans text-xs font-bold py-3 rounded-lg flex items-center"
                    >
                      Terminer
                    </button>
                  </div>
                </div>
              ) : (
                <form onSubmit={processPayment} className="space-y-4 font-sans text-xs">
                  <div className="bg-gray-50 p-4 rounded-xl flex justify-between items-center border border-gray-100">
                    <span className="text-gray-500 font-medium">À payer :</span>
                    <span className="text-lg font-extrabold text-[#002046]">
                      {selectedBillForPayment ? selectedBillForPayment.amount.toLocaleString() : user.solde.toLocaleString()} MAD
                    </span>
                  </div>

                  <div>
                    <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Nom du titulaire de carte</label>
                    <input 
                      type="text" 
                      required
                      placeholder="Ex: Mohammed El Alami"
                      value={paymentCardName}
                      onChange={(e) => setPaymentCardName(e.target.value)}
                      className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs"
                    />
                  </div>

                  <div>
                    <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Numéro de carte bancaire</label>
                    <input 
                      type="text" 
                      required
                      maxLength={19}
                      placeholder="4000 1234 5678 9010"
                      value={paymentCardNum}
                      onChange={(e) => {
                        // basic space formatting
                        const v = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                        const matches = v.match(/\d{4,16}/g);
                        const match = matches && matches[0] || '';
                        const parts = [];

                        for (let i=0, len=match.length; i<len; i+=4) {
                          parts.push(match.substring(i, i+4));
                        }

                        if (parts.length > 0) {
                          setPaymentCardNum(parts.join(' '));
                        } else {
                          setPaymentCardNum(v);
                        }
                      }}
                      className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs font-mono"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Expiration</label>
                      <input 
                        type="text" 
                        required
                        maxLength={5}
                        placeholder="MM/AA"
                        value={paymentCardExp}
                        onChange={(e) => setPaymentCardExp(e.target.value)}
                        className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs text-center font-mono"
                      />
                    </div>
                    <div>
                      <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Code CVV</label>
                      <input 
                        type="password" 
                        required
                        maxLength={3}
                        placeholder="123"
                        value={paymentCardCVC}
                        onChange={(e) => setPaymentCardCVC(e.target.value)}
                        className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs text-center font-mono"
                      />
                    </div>
                  </div>

                  <div className="pt-4 flex items-center gap-1.5 text-gray-400 text-[10px] pb-2 leading-relaxed justify-center text-center">
                    <span className="w-1.5 h-1.5 rounded-full bg-green-500 shrink-0"></span>
                    <span>Toutes les transactions sont hébergées au Maroc par cryptage certifié PCI-DSS.</span>
                  </div>

                  <button 
                    type="submit"
                    disabled={paymentProcessing}
                    className="cursor-pointer w-full bg-[#bb0027] hover:bg-[#A50D26] text-white font-sans text-xs font-bold py-3.5 rounded-lg transition-transform flex items-center justify-center gap-2"
                  >
                    {paymentProcessing ? (
                      <>
                        <span className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        Traitement CMI en cours...
                      </>
                    ) : (
                      <>
                        Confirmer le Paiement
                      </>
                    )}
                  </button>
                </form>
              )}
            </div>
          </div>
        )}

        {/* MODAL 2: New Complaint Submit Form */}
        {showNewComplaintModal && (
          <div className="fixed inset-0 bg-[#002046]/40 backdrop-blur-xs flex items-center justify-center z-50 p-4 animate-fadeIn">
            <div className="bg-white rounded-3xl p-6 sm:p-8 w-full max-w-md shadow-2xl border border-gray-100 relative">
              <button 
                onClick={() => setShowNewComplaintModal(false)}
                className="absolute top-5 right-5 text-gray-400 hover:text-gray-600 cursor-pointer text-lg font-bold p-1 bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center"
              >
                ×
              </button>
              
              <div className="flex items-center gap-3 mb-6">
                <div className="w-10 h-10 bg-red-50 text-[#bb0027] rounded-lg flex items-center justify-center">
                  <Wrench className="w-5 h-5" />
                </div>
                <div>
                  <h3 className="font-display font-extrabold text-base text-gray-900 leading-tight">Soumettre un Signalement</h3>
                  <p className="text-[11px] text-gray-400">Pour réparation, nettoyage ou incident de voisinage</p>
                </div>
              </div>

              <form onSubmit={handleAddComplaint} className="space-y-4 font-sans text-xs">
                <div>
                  <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Catégorie</label>
                  <select 
                    value={newComplaintCat} 
                    onChange={(e) => setNewComplaintCat(e.target.value as any)}
                    className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs font-semibold"
                  >
                    <option value="technical">Incident Technique de l'immeuble (Électricité, Eau, Ascenseur)</option>
                    <option value="admin">Doliance Administrative de copropriété (PV, Règlement)</option>
                    <option value="condo">Comptable / Cotisations financières</option>
                  </select>
                </div>

                <div>
                  <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Intitulé du problème</label>
                  <input 
                    type="text" 
                    required
                    placeholder="Ex: Interphone en panne / Fuite d'eau garage"
                    value={newComplaintTitle}
                    onChange={(e) => setNewComplaintTitle(e.target.value)}
                    className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs"
                  />
                </div>

                <div>
                  <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Description détaillée</label>
                  <textarea 
                    required
                    placeholder="Veuillez décrire le problème avec le plus de précisions possibles (étage, localisation exacte et gravité)."
                    value={newComplaintDesc}
                    rows={4}
                    onChange={(e) => setNewComplaintDesc(e.target.value)}
                    className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs resize-none"
                  />
                </div>

                <div>
                  <label className="block text-gray-400 font-bold uppercase text-[9px] mb-1.5">Lien d'image de preuve (Optionnel)</label>
                  <input 
                    type="text" 
                    placeholder="Ex: https://images.unsplash.com/photo-..."
                    value={newComplaintImage}
                    onChange={(e) => setNewComplaintImage(e.target.value)}
                    className="w-full p-2.5 rounded-lg border border-gray-200 text-[#091d2e] focus:outline-none focus:ring-2 focus:ring-[#aec7f7] bg-white text-xs"
                  />
                </div>

                <div className="pt-3">
                  <button 
                    type="submit"
                    className="cursor-pointer w-full bg-[#bb0027] hover:bg-[#A50D26] text-white font-sans text-xs font-bold py-3 rounded-lg text-center"
                  >
                    Envoyer au Syndic
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
