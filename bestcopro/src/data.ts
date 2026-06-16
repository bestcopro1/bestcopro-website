import { ChargeBill, Complaint, AssemblyVote, CoproprietaireUser } from "./types";

export interface Residence {
  id: string;
  name: string;
  location: string;
  imageUrl: string;
  details: string;
}

export const RESIDENCES_DATA: Residence[] = [
  {
    id: "andalucia",
    name: "Résidence Andalucia",
    location: "Hay Riad, Rabat",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuAYUkLnhoGqCcBeXAojl_jNX97KFybhISwxr52ANxrQa6OFbJ7UPEEKgqquifETnCQ0Z_G3EM1UDiaF8m7mOgcBD1k1M5yXR_XUY3tvy8pELHeHydGI61FRS2VH0yS_RcA-7M-tAlb4CLjIHsbSZgFvX055SOfpEUx-8_w4tk3PprCQSI2kPWEbqmObXi2kMI3yJqZ3UCPHOhxyNKT0m20WLTAa2Dwi5IJYb2h_mDSCN57-x3BIC4WrUH8zFFR_ZPUCEpZxnqDr_ic",
    details: "Complexe de standing avec piscine olympique, jardins andalous et sécurité renforcée 24/7."
  },
  {
    id: "marines",
    name: "Résidence Les Marines",
    location: "Plages des Nations, Salé",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuC-asU0hjQFA3nRTZq9dtVgK4tnJpR_4YRA0IvYj3BwGxY32lhX9EP9Xh43-QCltXYcjwcr8NyD66y5AB-VsmM9PpUn3rgCKGQQjaAkrPxdFQ2OcBeoVyMGQJ9g_K-2xUeN3dKuctOHrFw7yry9dNOU-_n1In1lY3syjwiidBp1KstyT3xzJ0jaKcyu0YOkCa66P6U1rUxkgeTe0GPZsCeu2gNV-1It2wjr2v7PkGOX7aIaAQdrK0bbPii8_39YnalfrH6ht8L4ml8",
    details: "Résidence haut de gamme en front de mer, appartements spacieux et espaces de loisirs."
  },
  {
    id: "bouregreg",
    name: "Les Terrasses de Bouregreg",
    location: "Marina de Salé",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuCV3Ds38xly89TWxScea3BeBiH4-2gKoFWebAPf0djhrBbqMcuxVMMcsEx5sgQAlX9Il206djV6t8_1jlFy4v7CJn-qcegGT0JiM1yI8Go4jJidrmJtQDITC67oQRDF4ZOFllDtBmdw8c4io3zvSZHeweJy2AjqrPvLofm6OvdX7m5CwE07m6rjqmoh6biFm0nP3kH0tMlZn34_4HH_84tRadC1N1IBc2SA2SaPiEI0n9X-r5ndQwYNIrcVezbaWtacDI8lMwdQrHQ",
    details: "Architecture contemporaine avec terrasses panoramiques sur la vallée et la marina."
  },
  {
    id: "marbella",
    name: "Résidence Marbella",
    location: "Hay Riad, Rabat",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuDAi4g2AZNSokzdEiSWB-uL78HwANa4wr6KQoRTlB_PpG-fMb5VVzRygcMHsVeJVmespmybh-bJr2TXl0RE8HKHmn7vUVxcRNWXh0aE2BtQDlCCdyvIcDni8a64EjLVqE3AnjIsGkJSVmfOMCogltCOjB7dbusAcbv9Xo8hnlbZb_S0lLurMxhAFIWph3aj4EovzfMx9Me66JS1XN6LI63wEUuA3ZVXQk8yEBBCMV3ns1V1S5aVgjEI0znPgU3sM6JsC6hN7aT1UNE",
    details: "Quartier calme, finitions en marbre noble, parkings souterrains et ascenseurs intelligents."
  },
  {
    id: "oceanica",
    name: "Résidence Océanica",
    location: "Smir Park, M'diq",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuBZYKGDAx8DHbp1ADVW1hGjLxIxmMqJEKyRp_beRLYPQufZ0ZmvAQ-3kqeQwGMe7LW42D1g1y-vhIN8XHDRnFZwsU1wQFoznBW3-_1gNcnTQosHkrBuFDKzXqSre2LuyXwiV6Rt_mEJ3jtXYhKlK7ugifjXyf5gL9_wWTHD2At4RrOBXd8OAQV1VIP0QZIl-IrQw1d_nx87txF0g8K2fcLP7BPIIywn5kU0Gn9XnAztChJRhwTY9GIfC9YwqJ-LdaUsE6EnkTmBIH4",
    details: "Résidence balnéaire de prestige, de superbes piscines thématiques avec accès privé plage."
  },
  {
    id: "sevilla",
    name: "Résidence Sevilla",
    location: "Hay Riad, Rabat",
    imageUrl: "https://lh3.googleusercontent.com/aida-public/AB6AXuAObDIW_9s0D9c1v_CDTNMnaWobGGeVaiNOut5rW7tTBZjF-hrkyoxt-bprgQzO9l5Uomx9xy-s6gEaDSt70BpHSrUxDiN2WdinTafb03X3Zib8R7P1fL8BWGMYED0NEbP4uT1lU7omcXK7MsHydetqXIU8HbhOPVJF45oUgqehYu12WVmfbCyq3On5gUmdXHonwXCqSF30myCIwBMz_Oyd4vzVZndZTPLYMH3herJ-wjGwPi42-nRfCerqnPWaESwhHHBPGuIhTPM",
    details: "Allure chic néo-andalouse, larges baies vitrées, isolations thermique et acoustique."
  }
];

export const TESTIMONIALS_DATA = [
  {
    id: "1",
    name: "Sara Mellali",
    avatarUrl: "https://lh3.googleusercontent.com/aida/AP1WRLsf7yvlymPRc4hBGiRRsrfu4OtqIVBOKgLcZgY4Ido9fbJ8iaOHlXLaqJeFzL8A29BrMcN9A8o8eqDPqtwCfPNPZ0wWa4FH6kgamqs7XQharpkur40FpDqf42MviYhcSK3Mk5GsmC3YpzAF1MgtlDIA1KPEfztF4toOSh26253-BQfgsZFDZUDfRxISOEj_Zt03xpwyB9Ko1Iu1H740XC_kOliVv4wrp4YYtxjZjzB5wH2tLVSU5yBV2mA",
    text: "Pour faire une réclamation, c'est via l'application Mobile de Bestcopro; cela me permet de résoudre le soucis en un temps record.",
    role: "Propriétaire - Résidence Les Marines"
  },
  {
    id: "2",
    name: "Hisham Koundous",
    avatarUrl: "https://lh3.googleusercontent.com/aida/AP1WRLvN756-53E5RtW3jm-mrtJ-18kkc6i6y1PtuEMPz6S3SuCSDbYY4P-m86aZJhHUi0gt9zucPtZztoNE0g-ZveoWRrmEcCAHChwNasIY__jpzr2We0h0dBIP57niwitMeQ1WUnK8UB-GioC2xC5XxXKxEuvhejnj2e_S2z4uA6P_TZKkIlGadCN9kZbXBFWpRMapvNwqKzJr9cQ-L_Y6xKI064oKnEudquWEnL1husbKGgGZ4OQ5LUGscbs",
    text: "L'application mobile a vraiment simplifié notre vie. Nous pouvons accéder aux informations importantes telles que les réunions.",
    role: "Conseil Syndical - Résidence Andalucia"
  },
  {
    id: "3",
    name: "Ali Derraf",
    avatarUrl: "https://lh3.googleusercontent.com/aida/AP1WRLttOWdLUdlqkhcuBgcIHKOuj_Z-gFidupFybhSoK93QNgaGj0n_iFpwkm4ThUYdk16X6qXGcWcPfg7ShrVERfpEBsZzrSEq0XbcT4CfhrbVJvsGfEEKhWf-N7-cl9gVlOTDjDIfIXxhAdHvHkkj5Xx0QBdMVP_UpNrdxBwGA783t3CIRHihLzsIlNXKyHB-KbxxiE1jpRftiLlKPKUkdgWdnB31_ugT1TqzYbckEod-BLkUy0QkI9AI8A",
    text: "Avant l'application mobile, il était difficile de rester informé. Mais maintenant... je reçois des notifications instantanées.",
    role: "Copropriétaire - Les Terrasses de Bouregreg"
  }
];

export const MOCK_USER: CoproprietaireUser = {
  name: "M. Mohammed",
  apartment: "Appt N°14 - 3ème étage",
  building: "Résidence Andalucia - Immeuble B",
  solde: 1883.75,
  avatarUrl: "https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80"
};

export const DEFAULT_BILLS: ChargeBill[] = [
  {
    id: "b1",
    title: "Appel de charges - Juin 2026",
    amount: 1400.00,
    dueDate: "2026-06-30",
    status: "a_payer"
  },
  {
    id: "b2",
    title: "Appel de charges - Mai 2026",
    amount: 1400.00,
    dueDate: "2026-05-30",
    status: "paye",
    paidAt: "2026-05-28"
  },
  {
    id: "b3",
    title: "Ravi de réparation - Pompe de piscine",
    amount: 483.75,
    dueDate: "2026-05-15",
    status: "a_payer"
  },
  {
    id: "b4",
    title: "Appel de charges - Avril 2026",
    amount: 1400.00,
    dueDate: "2026-04-30",
    status: "paye",
    paidAt: "2026-04-29"
  }
];

export const DEFAULT_COMPLAINTS: Complaint[] = [
  {
    id: "c1",
    title: "Panne de l'ascenseur principal - Entrée B",
    category: "technique",
    description: "L'ascenseur se bloque régulièrement au 3ème étage. Un voyant rouge est allumé sur le tableau de commande.",
    status: "en_cours",
    createdAt: "2026-06-05 10:30",
    apartment: "Appt N°14 - 3ème étage",
    imageUrl: "https://images.unsplash.com/photo-1549488344-1f9b8d2bd1f3?w=500&auto=format&fit=crop&q=80"
  },
  {
    id: "c2",
    title: "Ampoule grillée dans le hall",
    category: "technique",
    description: "Ampoule du plafonnier principal juste en face de la loge du gardien est grillée.",
    status: "résolu",
    createdAt: "2026-05-20 14:15",
    apartment: "Appt N°14 - 3ème étage"
  }
];

export const DEFAULT_VOTES: AssemblyVote[] = [
  {
    id: "v1",
    title: "Ravalement de façade principal",
    description: "Vote pour la sélection de l'entreprise chargée de repeindre et ravaler la façade de l'immeuble. budget prévisionnel de 120 000 MAD à répartir selon les tantièmes.",
    endDate: "2026-06-25",
    options: ["Oui, Entreprise Souss Peinture", "Oui, Entreprise Atlas BTP", "Non, reporter à l'année prochaine"],
    votesCount: {
      "Oui, Entreprise Souss Peinture": 24,
      "Oui, Entreprise Atlas BTP": 12,
      "Non, reporter à l'année prochaine": 5
    }
  },
  {
    id: "v2",
    title: "Installation de caméras supplémentaires dans le parking",
    description: "Proposition d'installer 4 caméras de vidéosurveillance additionnelles pour couvrir les angles morts du sous-sol.",
    endDate: "2026-06-18",
    options: ["Pour (Budget 8 000 MAD)", "Contre", "Abstention"],
    votesCount: {
      "Pour (Budget 8 000 MAD)": 31,
      "Contre": 4,
      "Abstention": 2
    }
  }
];

export const MOCK_NOTIFICATIONS = [
  {
    id: "n1",
    type: "info",
    title: "Assemblée Générale Ordinaire",
    message: "L'AGO aura lieu le samedi 20 juin 2026 à 16h00 dans la salle de réunion du rez-de-chaussée. La présence de tous les copropriétaires est fortement recommandée.",
    date: "Il y a 1 jour"
  },
  {
    id: "n2",
    type: "warning",
    title: "Coupure d'eau programmée",
    message: "Le distributeur effectuera des travaux de maintenance le mercredi 10 juin, coupure de l'eau collective entre 14h00 et 17h00.",
    date: "Il y a 2 jours"
  }
];
