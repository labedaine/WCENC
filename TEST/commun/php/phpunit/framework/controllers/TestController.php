<?php


class TestController extends BaseController {
    public function getPing() {
        global $RESULT;
        $RESULT="getPong";

        return true;
    }

    public function postPing() {
        global $RESULT;
        $RESULT="postPong";

        return true;
    }

    public function putPing() {
        global $RESULT;
        $RESULT="putPong";

        return true;
    }

    public function deletePing() {
        global $RESULT;
        $RESULT="deletePong";

        return true;
    }
}