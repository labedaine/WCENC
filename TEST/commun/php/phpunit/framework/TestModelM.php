<?php

class TestM extends SinapsModel {
    protected $nom;
    protected $description;

    public function test11() {
        return $this->hasOne("Test11");
    }

    public function test1N() {
        return $this->hasMany("Test1N");
    }
}