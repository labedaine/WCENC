<?php

class Test extends SinapsModel {
	protected $nom;
	protected $date;
	protected $description;

    static public $formats = array( "date" => "timestamp");
}