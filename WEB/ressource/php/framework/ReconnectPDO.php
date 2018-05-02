<?php
/**
* Surcharge de PDO essayant de se reconnecter N fois en cas de problème d'accès à la BDD.
*
* PHP version 5
*
* @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
*/

class ReconnectPDO  {
    protected $config = array();
    protected $attributs = array();
    private $mysqlConnecErrorCodes = array(
        2055, 2013, 2011, 2006, 2005, 2003,
    );

    public $pdo;

    public function __construct($dsn, $user=NULL, $pass=NULL, $options=NULL) {
        // Save connection details for later
        $this->config = array(
            'dsn' => $dsn,
            'user' => $user,
            'pass' => $pass,
            'options' => $options
        );

        $this->pdo = new PDO($dsn, $user, $pass, $options);
        $this->pdo->query("SET NAMES 'UTF8'");
    }

    public function setAttribute($attribute, $value) {
        $this->attributs[$attribute] = $value;

        $this->pdo->setAttribute($attribute, $value);
    }

    public function prepare($statement, array $driverOptions=array()) {
        $statement = $this->sqlDialect($statement);

        $stmt = new ReconnectPDOStatement($this);
        $stmt->prepare($statement, $driverOptions);

        return $stmt;
    }

    public function query($statement) {
        $statement = $this->sqlDialect($statement);

        return $this->pdo->query($statement);
    }

    public function exec($statement) {
        $statement = $this->sqlDialect($statement);

        return $this->pdo->exec($statement);
    }

    private function sqlDialect($sql) {
//        print "SQL: $sql\n";

        return $sql;
    }

    public function __call ($function, $args) {
        /*
            print "Calling $function\n";
            var_dump($args);
        */

        try {
            $result = call_user_func_array(array($this->pdo, $function), $args);
        } catch(PDOException $exception) {
            if ($this->hasErreurDeConnection($exception)) {
                $this->reconnect();

                $result = call_user_func_array(array($this->pdo, $function), $args);
            } else {
                throw $exception;
            }
        }

        return $result;
    }

    public function hasErreurDeConnection(PDOException $exception) {
        if (in_array($exception->errorInfo[1], $this->mysqlConnecErrorCodes))
            return TRUE;
        else
            return FALSE;
    }

    public function reconnect() {
        $count = 0;
        do {
            $error = FALSE;
            try {
                $this->pdo = new PDO(
                    $this->config["dsn"],
                    $this->config["user"],
                    $this->config["pass"],
                    $this->config["options"]
                );
            } catch(PDOException $exception) {
                $error = TRUE;
                sleep(1);
            }

            $count++;
        } while( $error === TRUE &&
                 $count < 5);

        foreach ($this->attributs as $nom => $valeur) {
            $this->pdo->setAttribute($nom, $valeur);
        }
    }
}
