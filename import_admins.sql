INSERT INTO tutor_admins (tutor_id, name, email, username, fiscal_code) VALUES
(2, 'Elisa Riscazzi', 'corsi7489@gmail.com', 'elisa.riscazzi', NULL),
(3, 'Giuli Chillemi', 'business@afabi.it', 'giuli.chillemi', NULL),
(4, 'Giulianabarbara Messina', 'giuliana.messina@eraclitea.it', 'giulianabarbara.messina', NULL),
(5, 'Paola Mancini', 'paolamancini68@gmail.com', 'paola.mancini', NULL),
(6, 'Laura Cinelli', 'Laura.cinelli@contshipitalia.com', 'laura.cinelli', NULL),
(7, 'Chiara Coden', 'info@crsconsulenza.it', 'chiara.coden', NULL),
(8, 'Laura Vitelli', 'laura.vitelli@fondazionelibellula.com', 'laura.vitelli', NULL),
(9, 'Nicola Ludrini', 'info@ludriniconsulenze.it', 'nicola.ludrini', NULL),
(10, 'Costanza Meucci', 'info@masoniconsulting.it', 'costanza.meucci', NULL),
(11, 'Giorgio Berrone', 'commerciale@parentesikuadra.it', 'giorgio.berrone', NULL),
(12, 'Marco Goretti', 'formazione@prometeosrl.it', 'marco.goretti', NULL),
(13, 'Lucia Carati', 'assistenza@tutor81.it', 'lucia.carati', NULL),
(14, 'Martina Moro', 'emangiacapre@tomfordinternational.com', 'martina.moro', NULL),
(15, 'Olgalorena Mittone', 'studio@massimoostoni.it', 'olgalorena.mittone', 'MTTLLR70R43L219B');

SELECT ta.username, ta.name, ta.fiscal_code, t.business_name
FROM tutor_admins ta JOIN tutors t ON t.id = ta.tutor_id
ORDER BY ta.tutor_id;
