
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

