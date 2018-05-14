<?php

class Cookie {
    protected static $cookies = array();

    public static function dump() {
        var_dump(static::$cookies);
    }

    public static function session($name, $value) {
        static::set($name, $value);
    }

    public static function forever( $name, $value) {
        static::set($name, $value);
    }

    public static function get($name) {
        if( array_key_exists($name, self::$cookies)) {
            return static::$cookies[$name];
        }

        return null;
    }

    public static function set($name, $valeur) {
        static::$cookies[$name] = $valeur;
    }

    public static function has($name) {
        return array_key_exists($name, static::$cookies);
    }

    public static function reset() {
        static::$cookies = array();
    }

    public static function delete($name) {
        unset( static::$cookies[$name]);
    }
}
