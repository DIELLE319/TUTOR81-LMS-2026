BEGIN;

-- Aggiorna id=1 (ENTE FORMATIVO DI PROVA)
UPDATE tutors SET address = 'Via Mazzolari, 45 - Gussago (BS)', email = 'assistenza@tutor81.it', subscription_type = 'ENTI AUTORIZZATI 1500' WHERE id = 1;

-- Inserisci 14 nuovi enti
INSERT INTO tutors (business_name, address, phone, email, subscription_type, is_active) VALUES
('7489 SRLS', 'VIA - GENOVA - 25100', NULL, 'corsi7489@gmail.com', 'CONSULENTI 1500', true),
('ABI SERVIZI SRL', 'Via Vittorio Emanuele II n22', '302510145', 'business@afabi.it', 'ENTI AUTORIZZATI 1500', true),
('ACCADEMIA ERACLITEA', 'Viale della Liberta n. 106 - CATANIA - 95129', NULL, 'giuliana.messina@eraclitea.it', 'ENTI AUTORIZZATI 1500', true),
('COMETA', 'Via Cadorna, 24 - 19121 La Spezia', NULL, 'paolamancini68@gmail.com', 'CONSULENTI 500', true),
('CONTSHIP ITALIA GROUP FORMAZIONE', 'Via san bartolomeo 20 - La Spezia - 19126', NULL, 'Laura.cinelli@contshipitalia.com', 'ENTI AUTORIZZATI 1500', true),
('CRS CONSULTING SRL', 'Via Vicenza n.32 - 31050', NULL, 'info@crsconsulenza.it', 'CONSULENTI 500', true),
('FONDAZIONE LIBELLULA IMPRESA SOCIALE', 'Viale Ortles 54/4 Milano', NULL, 'laura.vitelli@fondazionelibellula.com', 'ENTI AUTORIZZATI 1500', true),
('LUDRINI GEOM. NICOLA', 'PIAZZA CADUTI DI NASSIRYA 5', NULL, 'info@ludriniconsulenze.it', 'CONSULENTI 500', true),
('MASONI CONSULTING S.R.L.', E'Via S. Allende 35 - Santa Croce sull\'Arno - 56029', '0571/360096', 'info@masoniconsulting.it', 'ENTI AUTORIZZATI 1500', true),
('PARENTESIKUADRA SRL', 'Cascina Cagnolata 29 Rosignano Monferrato 15030', NULL, 'commerciale@parentesikuadra.it', 'ENTI AUTORIZZATI 1500', true),
('PROMETEO SRL', 'Via Caduti del Lavoro 11', NULL, 'formazione@prometeosrl.it', 'ENTI AUTORIZZATI 1500', true),
('SODEXO ITALIA SPA', 'VIA FRATELLI GRACCHI 36 - CINISELLO BALSAMO - 20092', NULL, 'assistenza@tutor81.it', 'ENTI AUTORIZZATI 1500', true),
('TOM FORD SPA - FORMAZIONE', 'VIA DELLE CALANDRE, 36 - SESTO FIORENTINO - 50019', NULL, 'emangiacapre@tomfordinternational.com', 'CONSULENTI 500', true),
('STUDIO OSTONI', 'Via A.Toscanini 14 - Torino (TO)', NULL, 'studio@massimoostoni.it', 'CONSULENTI 1500', true);

COMMIT;

SELECT id, business_name, subscription_type FROM tutors ORDER BY id;
