-- Aggiorna CF da OVH
UPDATE tutor_admins SET fiscal_code = 'RSCLSE78L54D969W' WHERE username = 'elisa.riscazzi';
UPDATE tutor_admins SET fiscal_code = 'MSSGNB93E58C351Q' WHERE username = 'giulianabarbara.messina';
UPDATE tutor_admins SET fiscal_code = 'MNCPLA68E69L219W' WHERE username = 'paola.mancini';
UPDATE tutor_admins SET fiscal_code = 'CNLLRA81P53E463Y' WHERE username = 'laura.cinelli';
UPDATE tutor_admins SET fiscal_code = 'CDNCHR77H47F999A' WHERE username = 'chiara.coden';
UPDATE tutor_admins SET fiscal_code = 'VTLLRA80A41H501S' WHERE username = 'laura.vitelli';
UPDATE tutor_admins SET fiscal_code = 'LDRNCL80A01F205J' WHERE username = 'nicola.ludrini';
UPDATE tutor_admins SET fiscal_code = 'MCCCTN76C52I726E' WHERE username = 'costanza.meucci';
UPDATE tutor_admins SET fiscal_code = 'GRTMRC55A29A390X' WHERE username = 'marco.goretti';
UPDATE tutor_admins SET fiscal_code = 'DLMMTN95M69D612O' WHERE username = 'martina.moro';
-- dario.causio CF reale da OVH
UPDATE tutor_admins SET fiscal_code = 'CSAMRA80A01F205J' WHERE username = 'dario.causio';
-- Allinea anche nella tabella students
UPDATE students SET fiscal_code = 'CSAMRA80A01F205J' WHERE fiscal_code = 'CSADRA80A01H501U';
-- olgalorena.mittone già OK (MTTLLR70R43L219B)

SELECT username, name, fiscal_code FROM tutor_admins ORDER BY tutor_id;
