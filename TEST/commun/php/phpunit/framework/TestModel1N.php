<?php

class Test1N extends SinapsModel {
    protected $clef;
    protected $valeur;
    protected $TestM_id;

    public function testM() {
        return $this->belongsTo("TestM");
    }

    public function test11() {
        return $this->hasOne("Test11");
    }
}