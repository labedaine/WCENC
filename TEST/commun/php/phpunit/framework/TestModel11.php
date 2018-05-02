<?php

class Test11 extends SinapsModel {
    protected $login;
    protected $TestM_id;
    protected $Test1N_id;

    public function testM() {
        return $this->belongsTo("TestM");
    }
    public function test1N() {
        return $this->belongsTo("Test1N");
    }
}