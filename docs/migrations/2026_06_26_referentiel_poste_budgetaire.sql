CREATE TABLE IF NOT EXISTS referentiel_poste_budgetaire (
  id INT(11) NOT NULL AUTO_INCREMENT,
  code VARCHAR(20) NOT NULL,
  budget ENUM('Fonctionnement', 'Investissement') NOT NULL,
  poste VARCHAR(255) NOT NULL,
  rubrique VARCHAR(255) NOT NULL,
  signature CHAR(32) NOT NULL,
  actif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uniq_ref_signature (signature),
  KEY idx_ref_code (code),
  KEY idx_ref_budget_poste (budget, poste(120))
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;