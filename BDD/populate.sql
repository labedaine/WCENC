

-- Création de la base de données de pari


--
-- PostgreSQL database dump
--

-- Dumped from database version 9.5.10
-- Dumped by pg_dump version 9.5.10

SET statement_timeout = 0;
SET lock_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SET check_function_bodies = false;
SET client_min_messages = warning;
-- SET row_security = off;


--
-- Data for Name: equipe; Type: TABLE DATA; Schema: public; Owner: pari
--

COPY equipe (code_equipe, pays, code_groupe) FROM '/tmp/equipe.txt' DELIMITER ';';

--
-- Data for Name: stade; Type: TABLE DATA; Schema: public; Owner: pari
--

COPY stade (id, nom, ville) FROM '/tmp/stade.txt' DELIMITER ';';

--
-- Name: stade_id_stade_seq; Type: SEQUENCE SET; Schema: public; Owner: pari
--

SELECT pg_catalog.setval('stade_id_seq', 12, true);

--
-- Data for Name: phase; Type: TABLE DATA; Schema: public; Owner: pari
--
COPY phase (id, libelle) FROM '/tmp/phase.txt' DELIMITER ';';

--
-- Data for Name: etat_match; Type: TABLE DATA; Schema: public; Owner: pari
--

COPY etat_match (code_etat_match, libelle) FROM '/tmp/etat_match.txt' DELIMITER ';';

--
-- Data for Name: match; Type: TABLE DATA; Schema: public; Owner: pari
--

COPY match (id, date_match, code_equipe_1, code_equipe_2, code_etat_match, stade_id, score_equipe_1, score_equipe_2, phase_id) FROM '/tmp/match.txt' DELIMITER ';';

--
-- Name: match_id_match_seq; Type: SEQUENCE SET; Schema: public; Owner: pari
--

SELECT pg_catalog.setval('match_id_seq', 64, true);


COPY utilisateur (id, nom, prenom, login, email, password, promotion, isactif, isadmin) FROM '/tmp/utilisateur.txt' DELIMITER ';';

--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

