
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

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;
--
-- Name: match; Type: TABLE; Schema: public; Owner: pse13
--
DROP SEQUENCE IF EXISTS utilisateur_id_seq CASCADE;
CREATE SEQUENCE utilisateur_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

DROP TABLE IF EXISTS utilisateur CASCADE;
CREATE TABLE utilisateur (
  id integer NOT NULL DEFAULT nextval('utilisateur_id_seq'::regclass),
  nom VARCHAR(255) NULL DEFAULT NULL ,
  prenom VARCHAR(255) NULL DEFAULT NULL ,
  login VARCHAR(255) NOT NULL ,
  email VARCHAR(255) NOT NULL ,
  password VARCHAR(255) NOT NULL ,
  isactif SMALLINT,
  isadmin SMALLINT,
PRIMARY KEY (id)
);

CREATE UNIQUE INDEX utilisateur_id_idx ON utilisateur ( id ASC NULLS LAST);
CREATE INDEX login_unique_utilisateur ON utilisateur ( login ASC);
CREATE INDEX mail_unique_utilisateur ON utilisateur ( email ASC);

DROP SEQUENCE IF EXISTS session_id_seq CASCADE;
CREATE SEQUENCE session_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

DROP TABLE IF EXISTS session CASCADE;
CREATE TABLE session (
  id integer DEFAULT nextval('session_id_seq'::regclass),
  token VARCHAR(45) NOT NULL ,
  date TIMESTAMP NOT NULL ,
  utilisateur_id INTEGER, -- Utilisateur a qui appartient la session [utilisateur 1-1 session] La session http du user
PRIMARY KEY (id, utilisateur_id),
CONSTRAINT fk_session_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE ON UPDATE NO ACTION
);

COMMENT ON TABLE session IS 'Objet gérant la session utilisateur en lien avec le cookie token';

CREATE UNIQUE INDEX session_id_idx ON session ( id ASC NULLS LAST);
CREATE INDEX fk_session_utilisateur_idx ON session ( utilisateur_id ASC);
CREATE INDEX idx_session_token ON session ( token ASC);
--
-- Name: equipe; Type: TABLE; Schema: public; Owner: pse13
--
DROP TABLE IF EXISTS equipe CASCADE;
CREATE TABLE equipe (
    code_equipe varchar(3) NOT NULL,
    pays varchar(50) NOT NULL,
    code_groupe varchar(1),
PRIMARY KEY(code_equipe)
);

--
-- Name: etat_match; Type: TABLE; Schema: public; Owner: pse13
--
DROP TABLE IF EXISTS etat_match CASCADE;
CREATE TABLE etat_match (
    code_etat_match varchar(3) NOT NULL,
    libelle varchar(10) NOT NULL,
PRIMARY KEY (code_etat_match)
);

--
-- Name: phase; Type: TABLE; Schema: public; Owner: pse13
--
DROP TABLE IF EXISTS phase CASCADE;
CREATE TABLE phase (
    id integer NOT NULL,
    libelle  varchar(30) NOT NULL,
PRIMARY KEY (id)
);

--
-- Name: stade; Type: TABLE; Schema: public; Owner: pse13
--
DROP SEQUENCE IF EXISTS stade_id_seq CASCADE;
CREATE SEQUENCE stade_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

DROP TABLE IF EXISTS stade CASCADE;
CREATE TABLE stade (
    id integer NOT NULL DEFAULT nextval('stade_id_seq'::regclass),
    nom  varchar(50) NOT NULL,
    ville  varchar(50) NOT NULL,
PRIMARY KEY (id)
);

--
-- Name: match; Type: TABLE; Schema: public; Owner: pse13
--
DROP SEQUENCE IF EXISTS match_id_seq CASCADE;
CREATE SEQUENCE match_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

DROP TABLE IF EXISTS match CASCADE;
CREATE TABLE match (
    id integer NOT NULL DEFAULT nextval('match_id_seq'::regclass),
    date_match timestamp with time zone NOT NULL,
    code_equipe_1 varchar(3), -- Équipe 1 jouant le match [equipe1 1-N matchsDomicile] matchs
    code_equipe_2 varchar(3), -- Équipe 2 jouant le match [equipe2 1-N matchsVisiteur] matchs
    code_etat_match varchar(3) DEFAULT 'AVE'::bpchar NOT NULL, -- État du match [etat 1-N match] match
    stade_id integer NOT NULL, -- Le stade où se déroule le match [stade 1-N match] match
    score_equipe_1 integer DEFAULT 0,
    score_equipe_2 integer DEFAULT 0,
    phase_id integer NOT NULL, -- La phase du match [phase 1-N match] match
PRIMARY KEY (id),
CONSTRAINT fk_match_phase FOREIGN KEY (phase_id) REFERENCES phase (id),
CONSTRAINT fk_match_stade FOREIGN KEY (stade_id) REFERENCES stade (id),
CONSTRAINT fk_match_equipe1 FOREIGN KEY (code_equipe_1) REFERENCES equipe (code_equipe),
CONSTRAINT fk_match_equipe2 FOREIGN KEY (code_equipe_2) REFERENCES equipe (code_equipe),
CONSTRAINT fk_match_etat_match FOREIGN KEY (code_etat_match) REFERENCES etat_match (code_etat_match)
);


-- SELECT pg_catalog.setval('stade_id_stade_seq', 12, true);

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

