export interface ContactSubmission {
  id: string;
  fullName: string;
  email: string;
  phone: string;
  message?: string;
  submittedAt: string;
}

export interface Complaint {
  id: string;
  title: string;
  category: "administratif" | "comptable" | "technique" | "autre";
  description: string;
  status: "reçu" | "en_cours" | "résolu";
  createdAt: string;
  apartment: string;
  imageUrl?: string;
}

export interface ChargeBill {
  id: string;
  title: string;
  amount: number;
  dueDate: string;
  status: "paye" | "a_payer" | "en_retard";
  paidAt?: string;
}

export interface AssemblyVote {
  id: string;
  title: string;
  description: string;
  endDate: string;
  options: string[];
  userVote?: string;
  votesCount: { [key: string]: number };
}

export interface CoproprietaireUser {
  name: string;
  apartment: string;
  building: string;
  solde: number;
  avatarUrl: string;
}
