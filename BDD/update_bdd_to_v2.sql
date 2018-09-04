

-- Mise à jour de la base de données de pari v2
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
  rang SMALLINT NOT NULL DEFAULT 0,
  competition VARCHAR(255) NULL DEFAULT NULL ,  
  saison VARCHAR(255) NULL DEFAULT NULL ,  
  points integer NOT NULL DEFAULT 0,
  utilisateur_id INTEGER, -- Utilisateur du palmares [utilisateur 1-1 palmares] Le palmares de l'utilisateur
PRIMARY KEY (id, utilisateur_id),
CONSTRAINT fk_palmares_utilisateur FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE ON UPDATE NO ACTION
);

CREATE INDEX fk_palmares_utilisateur ON session ( utilisateur_id ASC);

-- notification des matchs par mail

ALTER TABLE utilisateur ADD COLUMN notification SMALLINT NOT NULL DEFAULT 0;

-- Table competition
DROP TABLE IF EXISTS competition CASCADE;
CREATE TABLE competition (
  id integer NOT NULL,
  libelle VARCHAR(255) NOT NULL ,  
PRIMARY KEY (id)
);

INSERT INTO competition (id, libelle) VALUES (467, 'Coupe du Monde 2018');
INSERT INTO competition (id, libelle) VALUES (2001,'Ligue des Champions 2018/19');


-- Pronostic gagnant competition

DROP SEQUENCE IF EXISTS pronostic_id_seq CASCADE;
CREATE SEQUENCE pronostic_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;
    
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
