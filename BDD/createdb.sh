#!/bin/bash

createdb pari;
psql pari < ./base.sql

