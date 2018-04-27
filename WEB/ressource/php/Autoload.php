<?php

spl_autoload_register( function ($class) {
    
    $asModelPath = __DIR__."../model/".$class.".php";
    if (file_exists($asModelPath)) {
        include $asModelPath;
    }

    $asSrvPath = __DIR__."/controller/".$class.".php";
    if (file_exists($asSrvPath)) {
        include $asSrvPath;
    }
    
    $asSrvPath = __DIR__."/services/".$class.".php";
    if (file_exists($asSrvPath)) {
        include $asSrvPath;
    }

    $asSrvPath = __DIR__."/../../service/".$class.".php";
    if (file_exists($asSrvPath)) {
        include $asSrvPath;
    }

    $asFmkPath = __DIR__."/framework/".$class.".php";
    if (file_exists($asFmkPath)) {
        include $asFmkPath;
    }

    $asPath = __DIR__."/filters/".$class.".php";
    if (file_exists($asPath)) {
        include $asPath;
    }

    $asUtilsPath = __DIR__."/utils/".$class.".php";
    if (file_exists($asUtilsPath)) {
        include $asUtilsPath;
    }

    if ($class === "SinapsApp") {
        include __DIR__."/SinapsApp.php";
    }

});
