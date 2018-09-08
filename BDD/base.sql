
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

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = false;
--
-- Name: match; Type: TABLE; Schema: public; Owner: pari
--
-- DROP SEQUENCE IF EXISTS utilisateur_id_seq CASCADE;
-- CREATE SEQUENCE utilisateur_id_seq
--     START WITH 1
--     INCREMENT BY 1
--    NO MINVALUE
--     NO MAXVALUE
--     CACHE 1;

-- DROP TABLE IF EXISTS utilisateur CASCADE;
-- CREATE TABLE utilisateur (
--   id integer NOT NULL DEFAULT nextval('utilisateur_id_seq'::regclass),
--   nom VARCHAR(255) NULL DEFAULT NULL ,
--   prenom VARCHAR(255) NULL DEFAULT NULL ,
--   login VARCHAR(255) NOT NULL ,
--   email VARCHAR(255) NOT NULL ,
--   password VARCHAR(255) NOT NULL ,
--   promotion SMALLINT NOT NULL DEFAULT 0,
--   isactif SMALLINT NOT NULL DEFAULT 0,
--   isadmin SMALLINT NOT NULL DEFAULT 0,
--   points integer NOT NULL DEFAULT 0,
--   notification SMALLINT NOT NULL DEFAULT 0,
-- PRIMARY KEY (id)
-- );

-- CREATE UNIQUE INDEX utilisateur_id_idx ON utilisateur ( id ASC NULLS LAST);
-- CREATE INDEX login_unique_utilisateur ON utilisateur ( login ASC);
-- CREATE INDEX mail_unique_utilisateur ON utilisateur ( email ASC);

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
-- Name: equipe; Type: TABLE; Schema: public; Owner: pari
--
DROP TABLE IF EXISTS equipe CASCADE;
CREATE TABLE equipe (
    id integer NOT NULL,
    pays varchar(50) NOT NULL,
    code_groupe varchar(1),
PRIMARY KEY(id)
);

--
-- Name: etat; Type: TABLE; Schema: public; Owner: pari
--
DROP TABLE IF EXISTS etat CASCADE;
CREATE TABLE etat (
    id integer NOT NULL,
    libelle varchar(10) NOT NULL,
PRIMARY KEY (id)
);

--
-- Name: phase; Type: TABLE; Schema: public; Owner: pari
--
DROP TABLE IF EXISTS phase CASCADE;
CREATE TABLE phase (
    id integer NOT NULL,
    libelle  varchar(50) NOT NULL,
PRIMARY KEY (id)
);

--
-- Name: stade; Type: TABLE; Schema: public; Owner: pari
--
DROP SEQUENCE IF EXISTS stade_id_seq CASCADE;
DROP TABLE IF EXISTS stade CASCADE;

--
-- Name: match; Type: TABLE; Schema: public; Owner: pari
--

DROP SEQUENCE IF EXISTS match_id_seq CASCADE;

DROP TABLE IF EXISTS match CASCADE;
CREATE TABLE match (
    id integer NOT NULL,
    date_match timestamp with time zone NOT NULL,
    equipe_id_dom integer DEFAULT NULL,
    equipe_id_ext integer DEFAULT NULL,
    etat_id integer NOT NULL, -- État du match [etat 1-N match] match
    score_dom integer DEFAULT NULL,
    score_ext integer DEFAULT NULL,
    phase_id integer NOT NULL, -- La phase du match [phase 1-N match] match
PRIMARY KEY (id),
CONSTRAINT fk_match_phase FOREIGN KEY (phase_id) REFERENCES phase (id),
CONSTRAINT fk_match_equipe_dom FOREIGN KEY (equipe_id_dom) REFERENCES equipe (id),
CONSTRAINT fk_match_equipe_ext FOREIGN KEY (equipe_id_ext) REFERENCES equipe (id),
CONSTRAINT fk_match_etat FOREIGN KEY (etat_id) REFERENCES etat (id)
);

--
-- Name: paris; Type: TABLE; Schema: public; Owner: pari
--
DROP SEQUENCE IF EXISTS paris_id_seq CASCADE;
CREATE SEQUENCE paris_id_seq;

DROP TABLE IF EXISTS paris CASCADE;
CREATE TABLE paris (
    id integer NOT NULL DEFAULT nextval('paris_id_seq'::regclass),
    match_id integer NOT NULL, -- Match parie [match 1-N paris] paris
    utilisateur_id integer NOT NULL, -- Utilisateur faisant le paris [utilisateur 1-N paris] paris
    score_dom integer NOT NULL,
    score_ext integer NOT NULL,
    points_acquis integer NOT NULL DEFAULT '0',
PRIMARY KEY(match_id, utilisateur_id),
CONSTRAINT fk_paris_match FOREIGN KEY (match_id) REFERENCES match (id),
CONSTRAINT fk_paris_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)
);

-- Update v2
DROP SEQUENCE IF EXISTS palmares_id_seq CASCADE;
CREATE SEQUENCE palmares_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

DROP TABLE IF EXISTS palmares CASCADE;
CREATE TABLE palmares (
  id integer NOT NULL DEFAULT nextval('palmares_id_seq'::regclass),
  competition VARCHAR(255) NULL DEFAULT NULL ,   
  points integer NOT NULL DEFAULT 0,
  utilisateur_id INTEGER, -- Utilisateur du palmares [utilisateur 1-1 palmares] Le palmares de l'utilisateur
PRIMARY KEY (id, utilisateur_id),
CONSTRAINT fk_palmares_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE ON UPDATE NO ACTION
);

CREATE INDEX fk_palmares_utilisateur ON session ( utilisateur_id ASC);

-- Table competition

CREATE SEQUENCE competition_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

CREATE TABLE competition (
  id integer NOT NULL DEFAULT nextval('competition_id_seq'::regclass),
  libelle VARCHAR(255) NOT NULL ,
  apiid integer NOT NULL,
  moffset integer NOT NULL,
  encours integer NOT NULL,
PRIMARY KEY (id)
);

-- Pronostic gagnant competition

DROP SEQUENCE IF EXISTS pronostic_id_seq CASCADE;
CREATE SEQUENCE pronostic_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;

-- Sert pour trouver le gagnant d'une compét avant qu'elle ne commence

DROP TABLE IF EXISTS pronostic CASCADE;
CREATE TABLE pronostic (
  id integer NOT NULL DEFAULT nextval('pronostic_id_seq'::regclass),
  libelle VARCHAR(255) NOT NULL , 
  competition_id INTEGER, -- La competition sur laquelle on a un pronostic [competition 1-1 pronostic] Le pronostic de sur une competition
  utilisateur_id INTEGER, -- Utilisateur du pronostic [utilisateur 1-1 pronostic] Le pronostic de l'utilisateur
PRIMARY KEY (id, competition_id, utilisateur_id),
CONSTRAINT fk_pronostic_competition FOREIGN KEY (competition_id) REFERENCES competition (id) ON DELETE CASCADE ON UPDATE NO ACTION,
CONSTRAINT fk_pronostic_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE ON UPDATE NO ACTION
);


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
