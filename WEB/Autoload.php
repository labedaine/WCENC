<?php

spl_autoload_register( function ($class) {
    $asControllerPath = __DIR__."/controller/".$class.".php";
    if (file_exists($asControllerPath)) {
        require_once $asControllerPath;
    }

    $asServicePath = __DIR__."/service/".$class.".php";
    if (file_exists($asServicePath)) {
        require_once $asServicePath;
    }

    $asDTOPath = __DIR__."/DTO/".$class.".php";
    if (file_exists($asDTOPath)) {
        require_once $asDTOPath;
    }
});
