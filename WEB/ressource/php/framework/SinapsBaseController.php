<?php
/**
 * Etend BaseController pour tranformer les retours d'erreur HTTP en erreur logicielle.
 * 
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

class SinapsBaseController extends BaseController {
    /**
     * Le formateur pour les réponses json.
     * 
     * @var JsonService
     */
    protected $jsonService;

    public function __construct() {
        $this->jsonService = SinapsApp::make("JsonService");
    }

   /**
     * Appelé lorsqu'une SinapsException est déclenchée
     * 
     * A surcharger par les controllers fils
     * 
     * @param SinapsException $e l'exception qui a été levée
     */
    protected function handleException(SinapsException $exception) {
        return $this->jsonService->createErrorResponse(
            $exception->getCode(),
            $exception->getMessage()
        );
    }
}