-- ============================================
-- BASE DE DONNÉES PHARMASWISS
-- Réseau de pharmacies suisses
-- Exercice d'entraînement BTEC LO2
-- ============================================

DROP DATABASE IF EXISTS pharmaswiss;
CREATE DATABASE pharmaswiss CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pharmaswiss;

-- ============================================
-- TABLE 1: PHARMACIES
-- ============================================
CREATE TABLE Pharmacies (
    pharmacie_id INT PRIMARY KEY AUTO_INCREMENT,
    nom_pharmacie VARCHAR(100) NOT NULL,
    canton VARCHAR(50) NOT NULL,
    ville VARCHAR(50) NOT NULL,
    adresse VARCHAR(200),
    telephone VARCHAR(15),
    email VARCHAR(100),
    titulaire VARCHAR(100),
    date_ouverture DATE,
    horaires_ouverture VARCHAR(100), -- ex: 'Lun-Ven 8h-19h, Sam 8h-17h'
    garde_24h BOOLEAN DEFAULT FALSE
);

-- ============================================
-- TABLE 2: PHARMACIENS
-- (Employés diplômés)
-- ============================================
CREATE TABLE Pharmaciens (
    pharmacien_id INT PRIMARY KEY AUTO_INCREMENT,
    pharmacie_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    role VARCHAR(50), -- 'Titulaire', 'Pharmacien', 'Assistant'
    diplome_annee YEAR,
    date_embauche DATE,
    salaire_mensuel DECIMAL(8,2), -- Problème: pas de CHECK
    actif BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (pharmacie_id) REFERENCES Pharmacies(pharmacie_id)
);

-- ============================================
-- TABLE 3: PATIENTS
-- (Clients de la pharmacie)
-- Problèmes volontaires: pas de contrainte sur email
-- ============================================
CREATE TABLE Patients (
    patient_id INT PRIMARY KEY AUTO_INCREMENT,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    date_naissance DATE,
    email VARCHAR(100),
    telephone VARCHAR(15),
    adresse VARCHAR(200),
    canton VARCHAR(50),
    assurance VARCHAR(100), -- ex: 'CSS', 'Helsana', 'Assura'
    numero_assure VARCHAR(50),
    pharmacie_habituelle INT,
    date_inscription DATE,
    allergies TEXT, -- Texte libre - améliorable
    FOREIGN KEY (pharmacie_habituelle) REFERENCES Pharmacies(pharmacie_id)
);

-- ============================================
-- TABLE 4: MEDICAMENTS
-- Catalogue avec stock par pharmacie
-- ============================================
CREATE TABLE Medicaments (
    medicament_id INT PRIMARY KEY AUTO_INCREMENT,
    pharmacie_id INT NOT NULL,
    nom_commercial VARCHAR(150) NOT NULL,
    principe_actif VARCHAR(150),
    forme VARCHAR(50), -- 'Comprimé', 'Sirop', 'Pommade', 'Gouttes', 'Injection'
    dosage VARCHAR(50), -- ex: '500mg', '100ml'
    laboratoire VARCHAR(100),
    categorie VARCHAR(50), -- 'Antibiotique', 'Antalgique', 'Anti-inflammatoire', 'Vitamines', 'Cardio', 'Dermato'
    sur_ordonnance BOOLEAN NOT NULL DEFAULT FALSE,
    prix_unitaire DECIMAL(8,2) NOT NULL,
    stock_actuel INT NOT NULL DEFAULT 0, -- Problème: pas de contrainte CHECK >= 0
    stock_minimum INT NOT NULL DEFAULT 10,
    date_peremption DATE,
    FOREIGN KEY (pharmacie_id) REFERENCES Pharmacies(pharmacie_id)
);

-- ============================================
-- TABLE 5: ORDONNANCES
-- Prescriptions médicales délivrées
-- ============================================
CREATE TABLE Ordonnances (
    ordonnance_id INT PRIMARY KEY AUTO_INCREMENT,
    patient_id INT NOT NULL,
    pharmacien_id INT NOT NULL,
    pharmacie_id INT NOT NULL,
    date_prescription DATE NOT NULL,
    date_delivrance DATETIME,
    medecin_prescripteur VARCHAR(150),
    statut VARCHAR(20) DEFAULT 'En attente', -- 'En attente', 'Délivrée', 'Annulée'
    montant_total DECIMAL(8,2),
    rembourse_assurance BOOLEAN DEFAULT FALSE,
    montant_rembourse DECIMAL(8,2) DEFAULT 0,
    notes TEXT,
    FOREIGN KEY (patient_id) REFERENCES Patients(patient_id),
    FOREIGN KEY (pharmacien_id) REFERENCES Pharmaciens(pharmacien_id),
    FOREIGN KEY (pharmacie_id) REFERENCES Pharmacies(pharmacie_id)
);

-- ============================================
-- TABLE 6: LIGNES_ORDONNANCE
-- Détail des médicaments par ordonnance
-- ============================================
CREATE TABLE Lignes_Ordonnance (
    ligne_id INT PRIMARY KEY AUTO_INCREMENT,
    ordonnance_id INT NOT NULL,
    medicament_id INT NOT NULL,
    quantite INT NOT NULL DEFAULT 1,
    prix_unitaire_applique DECIMAL(8,2) NOT NULL,
    posologie VARCHAR(200), -- ex: '1 comprimé matin et soir pendant 7 jours'
    montant_ligne DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (ordonnance_id) REFERENCES Ordonnances(ordonnance_id),
    FOREIGN KEY (medicament_id) REFERENCES Medicaments(medicament_id)
);

-- ============================================
-- INSERTION DES DONNÉES DE TEST
-- ============================================

-- PHARMACIES (5 pharmacies en Suisse romande)
INSERT INTO Pharmacies (nom_pharmacie, canton, ville, adresse, telephone, email, titulaire, date_ouverture, horaires_ouverture, garde_24h) VALUES
('Pharmacie du Léman', 'Genève', 'Genève', 'Rue du Mont-Blanc 14', '022 731 22 33', 'leman@pharmaswiss.ch', 'Dr. Catherine Renaud', '2015-03-12', 'Lun-Ven 8h-19h, Sam 8h-17h', FALSE),
('Pharmacie Centrale Lausanne', 'Vaud', 'Lausanne', 'Place de la Riponne 5', '021 312 45 67', 'lausanne@pharmaswiss.ch', 'Dr. Pierre Vautier', '2012-09-01', 'Lun-Ven 7h30-20h, Sam-Dim 9h-18h', TRUE),
('Pharmacie de la Gare', 'Fribourg', 'Fribourg', 'Avenue de la Gare 10', '026 322 11 44', 'fribourg@pharmaswiss.ch', 'Dr. Marie-Claude Aebischer', '2018-06-15', 'Lun-Sam 8h-19h', FALSE),
('Pharmacie Saint-Roch', 'Neuchâtel', 'Neuchâtel', 'Rue du Seyon 20', '032 725 33 88', 'neuchatel@pharmaswiss.ch', 'Dr. Antoine Berthoud', '2020-02-10', 'Lun-Ven 8h-18h30, Sam 8h-16h', FALSE),
('Pharmacie des Alpes', 'Valais', 'Sion', 'Rue de Conthey 12', '027 322 55 99', 'sion@pharmaswiss.ch', 'Dr. Sophie Fournier', '2019-11-05', 'Lun-Ven 8h-19h, Sam 8h-17h', TRUE);

-- PHARMACIENS (10 employés répartis sur les 5 pharmacies)
INSERT INTO Pharmaciens (pharmacie_id, nom, prenom, email, role, diplome_annee, date_embauche, salaire_mensuel, actif) VALUES
(1, 'Renaud', 'Catherine', 'c.renaud@pharmaswiss.ch', 'Titulaire', 1998, '2015-03-12', 9500.00, TRUE),
(1, 'Mercier', 'Olivier', 'o.mercier@pharmaswiss.ch', 'Pharmacien', 2010, '2016-01-15', 7200.00, TRUE),
(1, 'Beck', 'Valérie', 'v.beck@pharmaswiss.ch', 'Assistant', 2018, '2020-09-01', 4800.00, TRUE),
(2, 'Vautier', 'Pierre', 'p.vautier@pharmaswiss.ch', 'Titulaire', 1995, '2012-09-01', 9800.00, TRUE),
(2, 'Lopez', 'Maria', 'm.lopez@pharmaswiss.ch', 'Pharmacien', 2015, '2018-04-20', 7000.00, TRUE),
(3, 'Aebischer', 'Marie-Claude', 'mc.aebischer@pharmaswiss.ch', 'Titulaire', 2002, '2018-06-15', 9200.00, TRUE),
(3, 'Schorderet', 'Lukas', 'l.schorderet@pharmaswiss.ch', 'Assistant', 2020, '2021-08-10', 4600.00, TRUE),
(4, 'Berthoud', 'Antoine', 'a.berthoud@pharmaswiss.ch', 'Titulaire', 2005, '2020-02-10', 9000.00, TRUE),
(5, 'Fournier', 'Sophie', 's.fournier@pharmaswiss.ch', 'Titulaire', 2008, '2019-11-05', 9100.00, TRUE),
(5, 'Carron', 'Julien', 'j.carron@pharmaswiss.ch', 'Pharmacien', 2017, '2021-03-15', 6900.00, TRUE);

-- PATIENTS (15 patients)
INSERT INTO Patients (nom, prenom, date_naissance, email, telephone, adresse, canton, assurance, numero_assure, pharmacie_habituelle, date_inscription, allergies) VALUES
('Dupuis', 'Hélène', '1955-04-22', 'h.dupuis@email.ch', '079 111 22 33', 'Chemin Rieu 12, 1208 Genève', 'Genève', 'CSS', 'CSS-2055-89234', 1, '2020-05-15', 'Pénicilline'),
('Aubert', 'Jean-Marc', '1972-11-08', 'jm.aubert@email.ch', '078 222 33 44', 'Avenue Vibert 45, 1227 Genève', 'Genève', 'Helsana', 'HEL-1972-56712', 1, '2019-09-22', NULL),
('Liechti', 'Sandra', '1988-03-15', 's.liechti@email.ch', '076 333 44 55', 'Rue de la Paix 7, 1003 Lausanne', 'Vaud', 'Assura', 'ASS-1988-23489', 2, '2021-02-10', 'Aspirine, ibuprofène'),
('Zwahlen', 'Marc', '1965-07-30', 'm.zwahlen@email.ch', '079 444 55 66', 'Avenue de Rumine 22, 1005 Lausanne', 'Vaud', 'CSS', 'CSS-1965-67823', 2, '2018-11-05', 'Lactose'),
('Pittet', 'Caroline', '1992-01-18', 'c.pittet@email.ch', '077 555 66 77', 'Place du Marché 3, 1700 Fribourg', 'Fribourg', 'Sanitas', 'SAN-1992-44567', 3, '2022-03-20', NULL),
('Genoud', 'Bernard', '1948-09-12', 'b.genoud@email.ch', '078 666 77 88', 'Route de Bertigny 8, 1700 Fribourg', 'Fribourg', 'Helsana', 'HEL-1948-12389', 3, '2017-06-08', 'Anti-inflammatoires'),
('Tritten', 'Aline', '1980-05-25', 'a.tritten@email.ch', '076 777 88 99', 'Rue des Beaux-Arts 14, 2000 Neuchâtel', 'Neuchâtel', 'CSS', 'CSS-1980-78912', 4, '2020-12-15', NULL),
('Robert-Tissot', 'Daniel', '1959-12-03', 'd.roberttissot@email.ch', '079 888 99 11', 'Rue du Plan 28, 2000 Neuchâtel', 'Neuchâtel', 'Assura', 'ASS-1959-45678', 4, '2019-04-22', 'Iode'),
('Pralong', 'Isabelle', '1975-08-19', 'i.pralong@email.ch', '077 999 11 22', 'Rue de Lausanne 3, 1950 Sion', 'Valais', 'Helsana', 'HEL-1975-78934', 5, '2021-09-10', NULL),
('Quennoz', 'Frédéric', '1990-02-28', 'f.quennoz@email.ch', '078 111 22 44', 'Avenue de France 18, 1950 Sion', 'Valais', 'Sanitas', 'SAN-1990-23467', 5, '2022-01-12', 'Pénicilline, sulfamides'),
('Wyss', 'Brigitte', '1962-06-14', 'b.wyss@email.ch', '079 222 33 55', 'Quai Wilson 8, 1201 Genève', 'Genève', 'CSS', 'CSS-1962-89134', 1, '2018-07-20', NULL),
('Boillat', 'Pascal', '1985-10-07', 'p.boillat@email.ch', '076 333 44 66', 'Rue Centrale 12, 1003 Lausanne', 'Vaud', 'Assura', 'ASS-1985-67823', 2, '2020-03-15', 'Codéine'),
('Kunz', 'Martina', '1995-04-11', 'm.kunz@email.ch', '077 444 55 77', 'Avenue Tivoli 5, 1700 Fribourg', 'Fribourg', 'Helsana', 'HEL-1995-34589', 3, '2023-01-08', NULL),
('Maillard', 'Christian', '1958-08-23', 'c.maillard@email.ch', '078 555 66 88', 'Rue Pourtalès 10, 2000 Neuchâtel', 'Neuchâtel', 'CSS', 'CSS-1958-23467', 4, '2019-10-30', 'Acide acétylsalicylique'),
('Schmid', 'Laure', '1983-12-29', 'l.schmid@email.ch', '079 666 77 99', 'Rue du Rhône 22, 1950 Sion', 'Valais', 'Sanitas', 'SAN-1983-89234', 5, '2021-05-18', NULL);

-- MEDICAMENTS (25 médicaments répartis sur les pharmacies)
-- Pharmacie 1 (Genève) - 6 médicaments
INSERT INTO Medicaments (pharmacie_id, nom_commercial, principe_actif, forme, dosage, laboratoire, categorie, sur_ordonnance, prix_unitaire, stock_actuel, stock_minimum, date_peremption) VALUES
(1, 'Dafalgan 500', 'Paracétamol', 'Comprimé', '500mg', 'UPSA', 'Antalgique', FALSE, 8.50, 120, 30, '2026-08-15'),
(1, 'Augmentin 1g', 'Amoxicilline + Acide clavulanique', 'Comprimé', '1g', 'GSK', 'Antibiotique', TRUE, 24.80, 45, 15, '2026-04-20'),
(1, 'Voltaren Emulgel', 'Diclofénac', 'Gel', '1%', 'Novartis', 'Anti-inflammatoire', FALSE, 18.90, 80, 20, '2027-01-10'),
(1, 'Coversum 5mg', 'Périndopril', 'Comprimé', '5mg', 'Servier', 'Cardio', TRUE, 32.50, 25, 10, '2026-09-30'),
(1, 'Vitamine D3 Wild', 'Cholécalciférol', 'Gouttes', '4000UI', 'Wild', 'Vitamines', FALSE, 22.00, 60, 15, '2027-03-22'),
(1, 'Bepanthen Plus', 'Dexpantenol + Chlorhexidine', 'Crème', '5%', 'Bayer', 'Dermato', FALSE, 14.50, 95, 25, '2026-11-18'),

-- Pharmacie 2 (Lausanne) - 6 médicaments
(2, 'Dafalgan 500', 'Paracétamol', 'Comprimé', '500mg', 'UPSA', 'Antalgique', FALSE, 8.50, 200, 50, '2026-07-10'),
(2, 'Aspirin Cardio', 'Acide acétylsalicylique', 'Comprimé', '100mg', 'Bayer', 'Cardio', TRUE, 16.40, 70, 20, '2026-12-15'),
(2, 'Co-Amoxi Mepha', 'Amoxicilline + Clavulanate', 'Comprimé', '625mg', 'Mepha', 'Antibiotique', TRUE, 22.00, 50, 15, '2026-05-08'),
(2, 'Brufen 600', 'Ibuprofène', 'Comprimé', '600mg', 'Mylan', 'Anti-inflammatoire', TRUE, 12.80, 90, 25, '2026-10-22'),
(2, 'Magnesium Diasporal', 'Magnésium', 'Sachet', '300mg', 'Protina', 'Vitamines', FALSE, 28.50, 55, 15, '2027-02-14'),
(2, 'Inderal LA', 'Propranolol', 'Comprimé', '80mg', 'AstraZeneca', 'Cardio', TRUE, 38.00, 18, 10, '2026-08-30'),

-- Pharmacie 3 (Fribourg) - 5 médicaments
(3, 'Algifor Liquid', 'Ibuprofène', 'Suspension', '200mg/5ml', 'Vifor', 'Anti-inflammatoire', FALSE, 19.80, 70, 20, '2026-06-25'),
(3, 'Dafalgan 1000', 'Paracétamol', 'Comprimé', '1g', 'UPSA', 'Antalgique', TRUE, 11.20, 100, 30, '2026-09-12'),
(3, 'Klacid 500', 'Clarithromycine', 'Comprimé', '500mg', 'Abbott', 'Antibiotique', TRUE, 45.60, 28, 10, '2026-04-15'),
(3, 'Fucidine Crème', 'Acide fusidique', 'Crème', '2%', 'Leo Pharma', 'Dermato', TRUE, 26.30, 35, 10, '2026-11-08'),
(3, 'Multivitamines Burgerstein', 'Multivitamines', 'Comprimé', 'CELA', 'Burgerstein', 'Vitamines', FALSE, 32.00, 65, 20, '2027-04-05'),

-- Pharmacie 4 (Neuchâtel) - 4 médicaments
(4, 'Voltaren 50', 'Diclofénac', 'Comprimé', '50mg', 'Novartis', 'Anti-inflammatoire', TRUE, 21.50, 5, 15, '2026-07-20'), -- Stock bas !
(4, 'Aspirine Bayer', 'Acide acétylsalicylique', 'Comprimé', '500mg', 'Bayer', 'Antalgique', FALSE, 9.80, 110, 30, '2026-12-30'),
(4, 'Concor 5mg', 'Bisoprolol', 'Comprimé', '5mg', 'Merck', 'Cardio', TRUE, 28.40, 22, 10, '2026-08-18'),
(4, 'Daktarin Crème', 'Miconazole', 'Crème', '2%', 'Janssen', 'Dermato', FALSE, 16.70, 0, 10, '2026-10-05'), -- Stock à 0 !

-- Pharmacie 5 (Sion) - 4 médicaments
(5, 'Augmentin 1g', 'Amoxicilline + Acide clavulanique', 'Comprimé', '1g', 'GSK', 'Antibiotique', TRUE, 24.80, 40, 15, '2026-05-12'),
(5, 'Dafalgan 500', 'Paracétamol', 'Comprimé', '500mg', 'UPSA', 'Antalgique', FALSE, 8.50, 150, 40, '2026-11-25'),
(5, 'Voltaren Emulgel', 'Diclofénac', 'Gel', '1%', 'Novartis', 'Anti-inflammatoire', FALSE, 18.90, 60, 20, '2027-02-28'),
(5, 'Vitamine C Burgerstein', 'Acide ascorbique', 'Comprimé', '500mg', 'Burgerstein', 'Vitamines', FALSE, 24.50, 45, 15, '2027-05-10');

-- ORDONNANCES (20 ordonnances : 15 délivrées + 5 en attente)
INSERT INTO Ordonnances (patient_id, pharmacien_id, pharmacie_id, date_prescription, date_delivrance, medecin_prescripteur, statut, montant_total, rembourse_assurance, montant_rembourse, notes) VALUES
-- Délivrées
(1, 1, 1, '2025-09-10', '2025-09-10 14:30:00', 'Dr. Lenoir Catherine', 'Délivrée', 73.10, TRUE, 65.79, 'Renouvellement traitement chronique'),
(2, 2, 1, '2025-09-15', '2025-09-15 10:00:00', 'Dr. Bouvier Robert', 'Délivrée', 24.80, TRUE, 22.32, NULL),
(3, 4, 2, '2025-09-18', '2025-09-18 11:15:00', 'Dr. Marin Sylvain', 'Délivrée', 28.40, TRUE, 25.56, 'Allergique à plusieurs molécules - vérifier'),
(4, 4, 2, '2025-09-20', '2025-09-20 16:00:00', 'Dr. Roy Daniel', 'Délivrée', 38.00, TRUE, 34.20, 'Suivi cardio'),
(5, 6, 3, '2025-09-22', '2025-09-22 09:30:00', 'Dr. Aebischer Pierre', 'Délivrée', 19.80, FALSE, 0, NULL),
(6, 6, 3, '2025-09-25', '2025-09-25 15:45:00', 'Dr. Chappuis Léon', 'Délivrée', 45.60, TRUE, 41.04, 'Infection ORL'),
(7, 8, 4, '2025-10-02', '2025-10-02 11:00:00', 'Dr. Martinez Eva', 'Délivrée', 21.50, TRUE, 19.35, NULL),
(8, 8, 4, '2025-10-05', '2025-10-05 14:20:00', 'Dr. Frey Anne', 'Délivrée', 28.40, TRUE, 25.56, 'Renouvellement bisoprolol'),
(9, 9, 5, '2025-10-08', '2025-10-08 10:45:00', 'Dr. Bonvin Sébastien', 'Délivrée', 24.80, TRUE, 22.32, 'Antibiotique 7 jours'),
(10, 10, 5, '2025-10-10', '2025-10-10 16:30:00', 'Dr. Métrailler Olivier', 'Délivrée', 24.80, TRUE, 22.32, 'PATIENT ALLERGIQUE PÉNICILLINE - vérifier substitution !'),
(11, 1, 1, '2025-10-15', '2025-10-15 09:00:00', 'Dr. Lenoir Catherine', 'Délivrée', 32.50, TRUE, 29.25, 'Suivi tension'),
(12, 5, 2, '2025-10-18', '2025-10-18 13:30:00', 'Dr. Bouvier Robert', 'Délivrée', 16.40, TRUE, 14.76, NULL),
(13, 7, 3, '2025-10-20', '2025-10-20 11:00:00', 'Dr. Aebischer Pierre', 'Délivrée', 32.00, FALSE, 0, NULL),
(14, 8, 4, '2025-10-22', '2025-10-22 15:15:00', 'Dr. Martinez Eva', 'Délivrée', 9.80, FALSE, 0, NULL),
(15, 10, 5, '2025-10-25', '2025-10-25 10:00:00', 'Dr. Bonvin Sébastien', 'Délivrée', 18.90, FALSE, 0, NULL),
-- En attente
(1, 1, 1, '2025-10-30', NULL, 'Dr. Lenoir Catherine', 'En attente', 32.50, TRUE, 0, 'Réservation client'),
(3, 5, 2, '2025-11-02', NULL, 'Dr. Marin Sylvain', 'En attente', 22.00, TRUE, 0, NULL),
(7, 8, 4, '2025-11-04', NULL, 'Dr. Frey Anne', 'En attente', 9.80, FALSE, 0, NULL),
(9, 9, 5, '2025-11-05', NULL, 'Dr. Bonvin Sébastien', 'En attente', 18.90, FALSE, 0, NULL),
(11, 2, 1, '2025-11-06', NULL, 'Dr. Bouvier Robert', 'En attente', 14.50, FALSE, 0, NULL);

-- LIGNES_ORDONNANCE (détails)
INSERT INTO Lignes_Ordonnance (ordonnance_id, medicament_id, quantite, prix_unitaire_applique, posologie, montant_ligne) VALUES
-- Ordonnance 1: Dupuis Hélène - 73.10 CHF
(1, 4, 1, 32.50, '1 comprimé/jour le matin pendant 3 mois', 32.50),
(1, 1, 2, 8.50, '1 comprimé en cas de douleur, max 4/jour', 17.00),
(1, 5, 1, 22.00, '5 gouttes/jour pendant l''hiver', 22.00),
(1, 6, 1, 14.50, 'Application 2x/jour sur la zone concernée', -- erreur volontaire dans calcul
 1.60),
-- Ordonnance 2
(2, 2, 1, 24.80, '1 comprimé matin et soir pendant 7 jours', 24.80),
-- Ordonnance 3
(3, 12, 1, 38.00, '1 comprimé/jour le matin', 38.00), -- erreur volontaire dans la pharmacie ?
-- Ordonnance 4
(4, 12, 1, 38.00, '1 comprimé/jour le matin', 38.00),
-- Ordonnance 5
(5, 13, 1, 19.80, '5ml 3x/jour pendant 5 jours', 19.80),
-- Ordonnance 6
(6, 15, 1, 45.60, '1 comprimé matin et soir pendant 7 jours', 45.60),
-- Ordonnance 7
(7, 18, 1, 21.50, '1 comprimé 3x/jour après les repas', 21.50),
-- Ordonnance 8
(8, 20, 1, 28.40, '1 comprimé/jour le matin', 28.40),
-- Ordonnance 9
(9, 22, 1, 24.80, '1 comprimé matin et soir pendant 7 jours', 24.80),
-- Ordonnance 10 - PROBLÈME: patient allergique pénicilline + Augmentin
(10, 22, 1, 24.80, '1 comprimé matin et soir pendant 7 jours', 24.80),
-- Ordonnance 11
(11, 4, 1, 32.50, '1 comprimé/jour le matin', 32.50),
-- Ordonnance 12
(12, 8, 1, 16.40, '1 comprimé/jour', 16.40),
-- Ordonnance 13
(13, 17, 1, 32.00, '1 comprimé/jour avec un grand verre d''eau', 32.00),
-- Ordonnance 14
(14, 19, 1, 9.80, '1 comprimé en cas de douleur, max 3/jour', 9.80),
-- Ordonnance 15
(15, 24, 1, 18.90, 'Application 3x/jour', 18.90),
-- Ordonnance 16 (en attente)
(16, 4, 1, 32.50, '1 comprimé/jour le matin', 32.50),
-- Ordonnance 17 (en attente)
(17, 9, 1, 22.00, '1 comprimé matin et soir pendant 5 jours', 22.00),
-- Ordonnance 18 (en attente)
(18, 19, 1, 9.80, '1 comprimé/jour si douleur', 9.80),
-- Ordonnance 19 (en attente)
(19, 24, 1, 18.90, 'Application 2x/jour', 18.90),
-- Ordonnance 20 (en attente)
(20, 6, 1, 14.50, 'Application 2x/jour', 14.50);

-- ============================================
-- VÉRIFICATIONS
-- ============================================
SELECT 'Pharmacies:' AS Info, COUNT(*) AS Total FROM Pharmacies
UNION ALL SELECT 'Pharmaciens:', COUNT(*) FROM Pharmaciens
UNION ALL SELECT 'Patients:', COUNT(*) FROM Patients
UNION ALL SELECT 'Medicaments:', COUNT(*) FROM Medicaments
UNION ALL SELECT 'Ordonnances:', COUNT(*) FROM Ordonnances
UNION ALL SELECT 'Ordonnances délivrées:', COUNT(*) FROM Ordonnances WHERE statut = 'Délivrée'
UNION ALL SELECT 'Ordonnances en attente:', COUNT(*) FROM Ordonnances WHERE statut = 'En attente'
UNION ALL SELECT 'Lignes ordonnance:', COUNT(*) FROM Lignes_Ordonnance;
