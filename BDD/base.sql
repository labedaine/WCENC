
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
SET row_security = off;

--
-- Name: plpgsql; Type: EXTENSION; Schema: -; Owner: 
--

CREATE EXTENSION IF NOT EXISTS plpgsql WITH SCHEMA pg_catalog;


--
-- Name: EXTENSION plpgsql; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION plpgsql IS 'PL/pgSQL procedural language';


SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: equipe; Type: TABLE; Schema: public; Owner: pse13
--

CREATE TABLE equipe (
    code_equipe character(3) NOT NULL,
    pays character(50) NOT NULL,
    code_groupe "char"
);


ALTER TABLE equipe OWNER TO pse13;

--
-- Name: etat_match; Type: TABLE; Schema: public; Owner: pse13
--

CREATE TABLE etat_match (
    code_etat_match character(3) NOT NULL,
    libelle character varying(10) NOT NULL
);


ALTER TABLE etat_match OWNER TO pse13;

--
-- Name: groupe; Type: TABLE; Schema: public; Owner: pse13
--

CREATE TABLE groupe (
    code_groupe "char" NOT NULL
);


ALTER TABLE groupe OWNER TO pse13;

--
-- Name: match; Type: TABLE; Schema: public; Owner: pse13
--

CREATE TABLE match (
    id_match integer NOT NULL,
    date_match timestamp with time zone NOT NULL,
    code_equipe_1 character(3),
    code_equipe_2 character(3),
    code_etat_match character(3) DEFAULT 'AVE'::bpchar NOT NULL,
    id_stade integer NOT NULL,
    score_equipe_1 integer DEFAULT 0,
    score_equipe_2 integer DEFAULT 0,
    id_phase integer NOT NULL
);


ALTER TABLE match OWNER TO pse13;

--
-- Name: match_id_match_seq; Type: SEQUENCE; Schema: public; Owner: pse13
--

CREATE SEQUENCE match_id_match_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE match_id_match_seq OWNER TO pse13;

--
-- Name: match_id_match_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: pse13
--

ALTER SEQUENCE match_id_match_seq OWNED BY match.id_match;


--
-- Name: phase; Type: TABLE; Schema: public; Owner: pse13
--

CREATE TABLE phase (
    id_phase integer NOT NULL,
    libelle character varying(30) NOT NULL
);


ALTER TABLE phase OWNER TO pse13;

--
-- Name: stade; Type: TABLE; Schema: public; Owner: pse13
--

CREATE TABLE stade (
    id_stade integer NOT NULL,
    nom character varying(50) NOT NULL,
    ville character varying(50) NOT NULL
);


ALTER TABLE stade OWNER TO pse13;

--
-- Name: stade_id_stade_seq; Type: SEQUENCE; Schema: public; Owner: pse13
--

CREATE SEQUENCE stade_id_stade_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE stade_id_stade_seq OWNER TO pse13;

--
-- Name: stade_id_stade_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: pse13
--

ALTER SEQUENCE stade_id_stade_seq OWNED BY stade.id_stade;


--
-- Name: id_match; Type: DEFAULT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match ALTER COLUMN id_match SET DEFAULT nextval('match_id_match_seq'::regclass);


--
-- Name: id_stade; Type: DEFAULT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY stade ALTER COLUMN id_stade SET DEFAULT nextval('stade_id_stade_seq'::regclass);


--
-- Data for Name: equipe; Type: TABLE DATA; Schema: public; Owner: pse13
--

COPY equipe (code_equipe, pays, code_groupe) FROM stdin;
RUS	Russie                                            	A
SAU	Arabie Saoudite                                   	A
EGY	Egypte                                            	A
URU	Uruguay                                           	A
POR	Portugal                                          	B
ESP	Espagne                                           	B
MAR	Maroc                                             	B
IRN	Iran                                              	B
FRA	France                                            	C
AUS	Australie                                         	C
PER	Pérou                                             	C
DNK	Danemark                                          	C
ARG	Argentina                                         	C
ISL	Islande                                           	C
HRV	Croatie                                           	C
NGA	Nigeria                                           	C
BR 	Brazil                                            	E
CHE	Suisse                                            	E
CRI	Costa Rica                                        	E
SRB	Serbie                                            	E
DEU	Allemagne                                         	F
MEX	Mexique                                           	F
SWE	Suède                                             	F
KOR	Corée du Sud                                      	F
BEL	Belgique                                          	G
PAN	Panama                                            	G
TUN	Tunisie                                           	G
ENG	Angleterre                                        	G
POL	Pologne                                           	G
SEN	Sénégal                                           	G
COL	Colombie                                          	G
JPN	Japon                                             	G
BRA	Brésil                                            	E
\.


--
-- Data for Name: etat_match; Type: TABLE DATA; Schema: public; Owner: pse13
--

COPY etat_match (code_etat_match, libelle) FROM stdin;
AVE	A venir
ENC	En cours
TER	Terminé
REP	Reporté
ANN	Annulé
INT	Interrompu
\.


--
-- Data for Name: groupe; Type: TABLE DATA; Schema: public; Owner: pse13
--

COPY groupe (code_groupe) FROM stdin;
A
B
C
D
E
F
G
H
\.


--
-- Data for Name: match; Type: TABLE DATA; Schema: public; Owner: pse13
--

COPY match (id_match, date_match, code_equipe_1, code_equipe_2, code_etat_match, id_stade, score_equipe_1, score_equipe_2, id_phase) FROM stdin;
1	2018-06-14 17:00:00+02	RUS	SAU	AVE	1	0	0	1
2	2018-06-15 14:00:00+02	EGY	URU	AVE	6	0	0	1
3	2018-06-15 20:00:00+02	POR	ESP	AVE	10	0	0	1
4	2018-06-15 17:00:00+02	MAR	IRN	AVE	3	0	0	1
5	2018-06-16 12:00:00+02	FRA	AUS	AVE	8	0	0	1
6	2018-06-16 18:00:00+02	PER	DNK	AVE	7	0	0	1
7	2018-06-16 15:00:00+02	ARG	ISL	AVE	2	0	0	1
8	2018-06-16 21:00:00+02	HRV	NGA	AVE	5	0	0	1
9	2018-06-17 20:00:00+02	BRA	CHE	AVE	9	0	0	1
10	2018-06-17 14:00:00+02	CRI	SRB	AVE	12	0	0	1
11	2018-06-17 17:00:00+02	DEU	MEX	AVE	1	0	0	1
12	2018-06-18 14:00:00+02	SWE	KOR	AVE	4	0	0	1
13	2018-06-18 17:00:00+02	BEL	PAN	AVE	10	0	0	1
14	2018-06-18 20:00:00+02	TUN	ENG	AVE	11	0	0	1
15	2018-06-19 17:00:00+02	POL	SEN	AVE	2	0	0	1
16	2018-06-19 14:00:00+02	COL	JPN	AVE	7	0	0	1
17	2018-06-19 20:00:00+02	RUS	EGY	AVE	3	0	0	1
18	2018-06-20 17:00:00+02	URU	SAU	AVE	9	0	0	1
19	2018-06-20 14:00:00+02	POR	MAR	AVE	1	0	0	1
20	2018-06-20 20:00:00+02	IRN	ESP	AVE	8	0	0	1
21	2018-06-21 17:00:00+02	FRA	PER	AVE	6	0	0	1
22	2018-06-21 14:00:00+02	DNK	AUS	AVE	12	0	0	1
23	2018-06-21 20:00:00+02	ARG	HRV	AVE	4	0	0	1
24	2018-06-22 17:00:00+02	NGA	ISL	AVE	11	0	0	1
25	2018-06-22 14:00:00+02	BRA	CRI	AVE	3	0	0	1
26	2018-06-22 20:00:00+02	SRB	CHE	AVE	5	0	0	1
27	2018-06-23 20:00:00+02	DEU	SWE	AVE	10	0	0	1
28	2018-06-23 17:00:00+02	KOR	MEX	AVE	9	0	0	1
29	2018-06-23 14:00:00+02	BEL	TUN	AVE	2	0	0	1
30	2018-06-24 14:00:00+02	ENG	PAN	AVE	4	0	0	1
31	2018-06-24 20:00:00+02	POL	COL	AVE	8	0	0	1
32	2018-06-24 17:00:00+02	JPN	SEN	AVE	6	0	0	1
33	2018-06-25 16:00:00+02	URU	RUS	AVE	12	0	0	1
34	2018-06-25 16:00:00+02	SAU	EGY	AVE	11	0	0	1
35	2018-06-25 20:00:00+02	IRN	POR	AVE	7	0	0	1
36	2018-06-25 20:00:00+02	ESP	MAR	AVE	5	0	0	1
37	2018-06-26 16:00:00+02	DNK	FRA	AVE	1	0	0	1
38	2018-06-26 16:00:00+02	AUS	PER	AVE	10	0	0	1
39	2018-06-26 20:00:00+02	NGA	ARG	AVE	3	0	0	1
40	2018-06-26 20:00:00+02	ISL	HRV	AVE	9	0	0	1
41	2018-06-27 20:00:00+02	SRB	BRA	AVE	2	0	0	1
42	2018-06-27 20:00:00+02	CHE	CRI	AVE	4	0	0	1
43	2018-06-27 16:00:00+02	KOR	DEU	AVE	8	0	0	1
44	2018-06-27 16:00:00+02	MEX	SWE	AVE	6	0	0	1
45	2018-06-28 20:00:00+02	ENG	BEL	AVE	5	0	0	1
46	2018-06-28 20:00:00+02	PAN	TUN	AVE	7	0	0	1
47	2018-06-28 16:00:00+02	JPN	POL	AVE	11	0	0	1
48	2018-06-28 16:00:00+02	SEN	COL	AVE	12	0	0	1
49	2018-06-30 16:00:00+02	\N	\N	AVE	8	0	0	2
50	2018-06-30 20:00:00+02	\N	\N	AVE	10	0	0	2
51	2018-07-01 16:00:00+02	\N	\N	AVE	1	0	0	2
52	2018-07-01 20:00:00+02	\N	\N	AVE	4	0	0	2
53	2018-07-02 16:00:00+02	\N	\N	AVE	12	0	0	2
54	2018-07-02 20:00:00+02	\N	\N	AVE	9	0	0	2
55	2018-07-03 16:00:00+02	\N	\N	AVE	3	0	0	2
56	2018-07-03 20:00:00+02	\N	\N	AVE	2	0	0	2
57	2018-07-06 16:00:00+02	\N	\N	AVE	4	0	0	3
58	2018-07-06 20:00:00+02	\N	\N	AVE	8	0	0	3
59	2018-07-07 16:00:00+02	\N	\N	AVE	12	0	0	3
60	2018-07-07 20:00:00+02	\N	\N	AVE	10	0	0	3
61	2018-07-10 20:00:00+02	\N	\N	AVE	3	0	0	4
62	2018-07-11 20:00:00+02	\N	\N	AVE	1	0	0	4
63	2018-07-14 16:00:00+02	\N	\N	AVE	3	0	0	5
64	2018-07-14 17:00:00+02	\N	\N	AVE	1	0	0	6
\.


--
-- Name: match_id_match_seq; Type: SEQUENCE SET; Schema: public; Owner: pse13
--

SELECT pg_catalog.setval('match_id_match_seq', 64, true);


--
-- Data for Name: phase; Type: TABLE DATA; Schema: public; Owner: pse13
--

COPY phase (id_phase, libelle) FROM stdin;
1	Phase de groupes
2	Huitièmes de finale
3	Quarts de finale
4	Demi-finales
5	Match pour la troisième place
6	Finale
\.


--
-- Data for Name: stade; Type: TABLE DATA; Schema: public; Owner: pse13
--

COPY stade (id_stade, nom, ville) FROM stdin;
1	Stade Loujniki	Moscou
2	Otkrytie Arena	Moscou
3	Stade Krestovski	Saint-Pétersbourg
4	Stade de Nijni Novgorod	Nijni Novgorod
5	Baltika Arena	Kaliningrad
6	lekaterinbourg Arena	Iekaterinbourg
7	Stade de Mordovie	Saransk
8	Kazan-Arena	Kazan
9	Rostov Arena	Rostov-sur-le-Don
10	Stade Ficht	Sotchi
11	Volgograd Arena	Volgograd
12	Cosmos Arena	Samara
\.


--
-- Name: stade_id_stade_seq; Type: SEQUENCE SET; Schema: public; Owner: pse13
--

SELECT pg_catalog.setval('stade_id_stade_seq', 12, true);


--
-- Name: equipe_pkey; Type: CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY equipe
    ADD CONSTRAINT equipe_pkey PRIMARY KEY (code_equipe);


--
-- Name: etat_match_pkey; Type: CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY etat_match
    ADD CONSTRAINT etat_match_pkey PRIMARY KEY (code_etat_match);


--
-- Name: groupe_pkey; Type: CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY groupe
    ADD CONSTRAINT groupe_pkey PRIMARY KEY (code_groupe);


--
-- Name: match_pkey; Type: CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match
    ADD CONSTRAINT match_pkey PRIMARY KEY (id_match);


--
-- Name: phase_competition_pkey; Type: CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY phase
    ADD CONSTRAINT phase_competition_pkey PRIMARY KEY (id_phase);


--
-- Name: stade_pkey; Type: CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY stade
    ADD CONSTRAINT stade_pkey PRIMARY KEY (id_stade);


--
-- Name: equipe_code_groupe_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY equipe
    ADD CONSTRAINT equipe_code_groupe_fkey FOREIGN KEY (code_groupe) REFERENCES groupe(code_groupe) ON UPDATE SET DEFAULT ON DELETE SET DEFAULT;


--
-- Name: match_code_equipe_1_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match
    ADD CONSTRAINT match_code_equipe_1_fkey FOREIGN KEY (code_equipe_1) REFERENCES equipe(code_equipe);


--
-- Name: match_code_equipe_2_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match
    ADD CONSTRAINT match_code_equipe_2_fkey FOREIGN KEY (code_equipe_2) REFERENCES equipe(code_equipe);


--
-- Name: match_code_etat_match_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match
    ADD CONSTRAINT match_code_etat_match_fkey FOREIGN KEY (code_etat_match) REFERENCES etat_match(code_etat_match);


--
-- Name: match_id_phase_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match
    ADD CONSTRAINT match_id_phase_fkey FOREIGN KEY (id_phase) REFERENCES phase(id_phase);


--
-- Name: match_id_stade_fkey; Type: FK CONSTRAINT; Schema: public; Owner: pse13
--

ALTER TABLE ONLY match
    ADD CONSTRAINT match_id_stade_fkey FOREIGN KEY (id_stade) REFERENCES stade(id_stade);


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

