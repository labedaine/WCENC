<?php
/**
* Surcharge de PDO essayant de se reconnecter N fois en cas de problème d'accès à la BDD.
*
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/

class ReconnectPDOStatement {
    protected $caller;
    protected $stmt;
    protected $statement;
    protected $driverOptions;
    protected $fetchMode;

    public function __construct(ReconnectPDO $caller) {
        $this->caller = $caller;
    }

    public function prepare($statement, array $driverOptions=array()) {
        $this->statement = $statement;
        $this->driverOptions = $driverOptions;

        $this->stmt = $this->caller->pdo->prepare($statement, $driverOptions);
    }

    public function setFetchMode() {
        $this->fetchMode = func_get_args();

        call_user_func_array(
            array( $this->stmt, "setFetchMode"),
            $this->fetchMode
        );
    }

    public function bindParam($parameter, &$variable, $dataType=PDO::PARAM_STR) {
        return $this->stmt->bindParam($parameter, $variable, $dataType);
    }

    public function getQueryString() {
        return $this->stmt->queryString;
    }

    public function __call($function, $args) {
        try {
            $result = call_user_func_array(array($this->stmt, $function), $args);
        } catch(PDOException $exception) {
            if ($this->caller->hasErreurDeConnection($exception)) {
                $this->caller->reconnect();
                $this->stmt = $this->caller->pdo->prepare($this->statement, $this->driverOptions);
                $result = call_user_func_array(array($this->stmt, $function), $args);
                if ($this->fetchMode) {
                    call_user_func_array(
                        array( $this->stmt, "setFetchMode"),
                        $this->fetchMode
                    );
                }
            } else {
                throw $exception;
            }
        }

        return $result;
    }
}