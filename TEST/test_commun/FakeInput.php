<?php
/**
 * Permet de simuler les paramètres passés par le browser web.
 *
 * PHP Version 5
 *
 * @author cgi <cgi@cgi.com>
 */

class FakeInput {
    protected $inputs = array();

    static public function init() {
        Input::init(new FakeInput());
    }

    public function setOnInstance($name, $value) {
        $this->inputs[$name] = $value;
    }

    public function getOnInstance($name, $default="") {
        if (array_key_exists($name, $this->inputs)) {
            return $this->inputs[$name];
        } else {
            return $default;
        }
    }

    public function resetOnInstance() {
        $this->inputs = array();
    }
}
