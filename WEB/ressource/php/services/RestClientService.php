<?php
/**
 * Service permettant d'appeler des services REST.
 *
  * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class RestClientService {
    /**
     * Logger.
     *
     * @var Log2
     */
    protected $logger;

    public function __construct() {
    }

    public function getURL($url, array $vars=NULL, $curlDebug=FALSE, $timeOut=3) {
        if (is_null($curlDebug)) $curlDebug = FALSE;
        if (is_null($timeOut)) $timeOut = 3;
        $requete = $url;
        if ($vars !== NULL) {
            $requete .= '?' . http_build_query($vars);
        }
        $ch = curl_init($requete);
        if ($curlDebug) curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_PROXY, "");
        curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($timeOut == 3) {
            $timeOut = SinapsApp::getConfigValue("framework.curl.timeout", 3);
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeOut);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        $response = curl_exec($ch);

        if ($curlErrNo = curl_errno($ch)) {
            // Quelques cas qu'on souhaite gérer au niveau applicatif
            if ($curlErrNo === CURLE_COULDNT_CONNECT) {
                throw new SinapsException('Failed to connect');
            }
            if ($curlErrNo === CURLE_OPERATION_TIMEOUTED) {
                throw new SinapsException('Connection timeout');
            }

            // Sinon, on fait remonter le problème
            throw new SinapsException(curl_error($ch). " Url : ".$url);
        }
        $infos = curl_getinfo($ch);

        if ($infos['http_code'] === "404") {
            throw new SinapsException(
                'L\'URL demandée ' .
                $infos['url'] .
                'n\'a pas été trouvée sur ce serveur.',
                404
            );
        }

        if ($infos['http_code'] === "500") {
            throw new SinapsException($response, 500);
        }

        curl_close($ch);
        return $response;
    }

    public function throwExceptionOnError($resultat) {
        $result = json_decode($resultat);
        if (!$result)
            throw new SinapsException($resultat);

        if ($result->success !== TRUE) {
            if ($result->code == NULL)
                throw new SinapsException($result->payload);
            else
                throw new SinapsException($result->payload, $result->code);
        }
    }
}
