-- =============================================================================
-- PHARMASWISS - Création des utilisateurs MySQL avec permissions
-- À exécuter EN TANT QUE root APRÈS pharmaswiss_database.sql
-- =============================================================================

USE pharmaswiss;

-- Nettoyage
DROP USER IF EXISTS 'assistant_pharmacie'@'localhost';
DROP USER IF EXISTS 'pharmacien_titulaire'@'localhost';
DROP USER IF EXISTS 'administrateur_pharma'@'localhost';

-- =============================================================================
-- UTILISATEUR 1 : ASSISTANT_PHARMACIE (droits limités)
-- =============================================================================
CREATE USER 'assistant_pharmacie'@'localhost' IDENTIFIED BY 'AssistantP@ss2025';

-- SELECT sur les tables de consultation
GRANT SELECT ON pharmaswiss.Patients TO 'assistant_pharmacie'@'localhost';
GRANT SELECT ON pharmaswiss.Medicaments TO 'assistant_pharmacie'@'localhost';
GRANT SELECT ON pharmaswiss.Pharmacies TO 'assistant_pharmacie'@'localhost';

-- INSERT uniquement sur Ordonnances et Lignes_Ordonnance
GRANT INSERT, SELECT ON pharmaswiss.Ordonnances TO 'assistant_pharmacie'@'localhost';
GRANT INSERT, SELECT ON pharmaswiss.Lignes_Ordonnance TO 'assistant_pharmacie'@'localhost';

-- UPDATE limité sur Medicaments (uniquement le stock)
GRANT UPDATE (stock_actuel) ON pharmaswiss.Medicaments TO 'assistant_pharmacie'@'localhost';

-- PAS d'accès à Pharmaciens (RH)

-- =============================================================================
-- UTILISATEUR 2 : PHARMACIEN_TITULAIRE (droits étendus)
-- =============================================================================
CREATE USER 'pharmacien_titulaire'@'localhost' IDENTIFIED BY 'TitulaireP@ss2025';

-- Lecture sur tout
GRANT SELECT ON pharmaswiss.* TO 'pharmacien_titulaire'@'localhost';

-- INSERT/UPDATE sur Ordonnances et Lignes_Ordonnance
GRANT INSERT, UPDATE ON pharmaswiss.Ordonnances TO 'pharmacien_titulaire'@'localhost';
GRANT INSERT, UPDATE, DELETE ON pharmaswiss.Lignes_Ordonnance TO 'pharmacien_titulaire'@'localhost';

-- INSERT/UPDATE sur Medicaments (gestion catalogue)
GRANT INSERT, UPDATE ON pharmaswiss.Medicaments TO 'pharmacien_titulaire'@'localhost';

-- INSERT/UPDATE sur Patients (création de nouveaux patients)
GRANT INSERT, UPDATE ON pharmaswiss.Patients TO 'pharmacien_titulaire'@'localhost';

-- =============================================================================
-- UTILISATEUR 3 : ADMINISTRATEUR_PHARMA (tous les droits)
-- =============================================================================
CREATE USER 'administrateur_pharma'@'localhost' IDENTIFIED BY 'AdminP@ss2025';

GRANT ALL PRIVILEGES ON pharmaswiss.* TO 'administrateur_pharma'@'localhost';

-- =============================================================================
FLUSH PRIVILEGES;
SELECT 'Utilisateurs PharmaSwiss créés avec succès' AS message;
