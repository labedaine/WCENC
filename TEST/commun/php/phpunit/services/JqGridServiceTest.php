<?php
/**
 * Tests sur le JqGridService
 *
 * PHP version 5
 *
 * @author David Jacques <supervision-jacques.consultant@dgfip.finances.gouv.fr>
 */

$HOME = __DIR__ . "/../../../../../apps";

require_once $HOME."/commun/php/Autoload.php";

class TestClass extends SinapsModel {
    protected $nom;
    protected $prenom;

    public function __construct( $nom, $prenom) {
        $this->nom = $nom;
        $this->prenom = $prenom;
    }
}

class TestClassFormatee extends TestClass {
    protected $nom;

    public function formatNom() {
        return strtoupper($this->nom);
    }
}


class JqGridServiceTest extends PHPUnit_Framework_TestCase {

    public function setUp() {
        App::initialise();
        SinapsApp::registerSingleton("DateService");
        SinapsApp::registerSingleton("TimeService");
        SinapsApp::registerSingleton("JsonService");
    }

    public function __construct() {
        $this->objects = array();
        for($i = 0; $i < 20; $i++) {
            $this->objects[] = new TestClass("nom$i", "prenom$i");
        }
    }

    public function testAffichageDeTouteLaListe() {
        $phpResponse = $this->runService(
            array(
                "sidx" => "nom",
                "sord" => "DESC"
            )
        );

        $this->assertEquals(1, $phpResponse->page);
        $this->assertEquals(1, $phpResponse->total);
        $this->assertEquals(20, $phpResponse->records);
        $this->assertEquals(20, count($phpResponse->rows));
    }

    public function testPaginationPage1() {
        $phpResponse = $this->runService(
            array(
                "rows" => 6,
                "sidx" => "nom",
                "sord" => "DESC"
            )
        );

        $this->assertEquals(1, $phpResponse->page);
        $this->assertEquals(4, $phpResponse->total); 
        $this->assertEquals(20, $phpResponse->records);
        $this->assertEquals(6, count($phpResponse->rows));
    }

    public function testPaginationPage4() {
        $phpResponse = $this->runService(
            array(
                "rows" => 6, 
                "page" => 4,
                "sidx" => "nom",
                "sord" => "DESC"
            )
        );

        $this->assertEquals(4, $phpResponse->page);
        $this->assertEquals(4, $phpResponse->total);
        $this->assertEquals(20, $phpResponse->records);
        $this->assertEquals(2, count($phpResponse->rows));
    }

    public function testSearchMultiple() {
        $phpResponse = $this->runService(
            array(
                "_search" => "true",
                "filters" => '
{
    "groupOp":"OR",
    "rules":[{"field":"nom","op":"eq","data":"nom1"},
             {"field":"prenom","op":"eq","data":"prenom2"}]
}',
                "sidx" => "nom",
                "sord" => "DESC"
            )
        );

        $this->assertEquals(1, $phpResponse->page);
        $this->assertEquals(1, $phpResponse->total);
        $this->assertEquals(2, $phpResponse->records);
        $this->assertEquals(2, count($phpResponse->rows));
    }

    public function testOrder() {
        $phpResponse = $this->runService(
            array(
                "sidx" => "nom",
                "sord" => "DESC"
            )
        );

        $this->assertEquals("nom9", $phpResponse->rows[0]->cell[1]);
    }

    public function testAllInOne() {
        $options = array( "_search" => "true",
                          "filters" => '
{
    "groupOp":"OR",
    "rules":[{"field":"nom","op":"cn","data":"1"},
             {"field":"prenom","op":"eq","data":"prenom2"}]
}', // Contains (cn) 1 => 11 élément, ou prenom = prenom2  => 12 elements en tout
                            "rows" => 5,
                            "page" => 2,
                            "sidx" => "nom",
                            "sord" => "DESC"
                        );

        $phpResponse = $this->runService($options);

        $this->assertEquals(2, $phpResponse->page);
        $this->assertEquals(3, $phpResponse->total);
        $this->assertEquals(12, $phpResponse->records);
        $this->assertEquals(5, count($phpResponse->rows));
        // nom2, nom19, nom18, nom17, nom16 <== page1
        $this->assertEquals("nom15", $phpResponse->rows[0]->cell[1]); 
    }

    public function testRowFormatter() {
        $oldObj = $this->objects;
        $this->objects = array( new TestClassFormatee("clinton", "bill"),
                                new TestClassFormatee("bush", "george"),
                                new TestClassFormatee("reagan", "ronald"));

        $phpResponse = $this->runService(
            array(
                "sidx" => "nom",
                "sord" => "ASC"
            )
        );
        $this->objects = $oldObj;

        $this->assertEquals("BUSH", $phpResponse->rows[0]->cell[1]);
    }


    // Tests Historique des Alertes
    public function testSqlWithFilterSimple() {

        $tableauCorrespondance = array( 'nom'       => 'table.nom',
                                        'prenom'    => 'table.prenom',
                                        'date'     => 'table.date',
                                        'dateCn'     => 'table.date'
                                        );
        $filters = new stdClass();
        $ligne = new stdClass();
        $ligne->field = "nom";
        $ligne->op = "cn";
        $ligne->data = "toto";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "prenom";
        $ligne->op = "eq";
        $ligne->data = "prenom2";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "le";
        $ligne->data = "16/10 16:54:03";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "ge";
        $ligne->data = "16/10 16:54";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "ge";
        $ligne->data = "16/10 16";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "le";
        $ligne->data = "16/10 16";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "ge";
        $ligne->data = "16/10";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "cn";
        $ligne->data = "16/10 16:54";

        $rules[] = $ligne;

        $filters->rules = $rules;
        $filters->grouOp = "AND";

        // timestamp de 16/10 16:54:03 = 1381935243
        // timestamp de 16/10 16:54:59 = 1381935299
        // timestamp de 16/10 16:59:59 = 1381935599
        // timestamp de 16/10 23:59:59 = 1381960799

        // timestamp de 16/10 16:54:00 = 1381935240
        // timestamp de 16/10 16:00:00 = 1381932000
        // timestamp de 16/10 00:00:00 = 1381935240

        // Année de référence 2013
        $year = date('Y') - 2013;

        $jqGrid = new JqGridService();
        $filtrageJQGrid = $jqGrid->getSqlFiltreJqGrid($tableauCorrespondance, $filters);

        $this->assertContains("LOWER(table.nom) LIKE LOWER('%toto%')", $filtrageJQGrid, "testSqlWithFilterSimple() -> nom");
        $this->assertContains("LOWER(table.prenom) = 'prenom2'", $filtrageJQGrid, "testSqlWithFilterSimple() -> prenom");
        $this->assertContains("LOWER(table.date) <= ".strtotime('+'.$year+' years', 1381935243), $filtrageJQGrid, "testSqlWithFilterSimple() -> <= date 16/10 16:54:03");
        $this->assertContains("LOWER(table.date) >= ".strtotime('+'.$year+' years', 1381935240), $filtrageJQGrid, "testSqlWithFilterSimple() -> >= date 16/10 16:54");
        $this->assertContains("LOWER(table.date) >= ".strtotime('+'.$year+' years', 1381932000), $filtrageJQGrid, "testSqlWithFilterSimple() -> >= date 16/10 16");
        $this->assertContains("LOWER(table.date) <= ".strtotime('+'.$year+' years', 1381935599), $filtrageJQGrid, "testSqlWithFilterSimple() -> <= date 16/10 16");
        $this->assertContains("LOWER(table.date) >= ".strtotime('+'.$year+' years', 1381935240), $filtrageJQGrid, "testSqlWithFilterSimple() -> >= date 16/10");
        $this->assertContains("LOWER(table.date) LIKE LOWER('%16/10 16:54%')", $filtrageJQGrid, "testSqlWithFilterSimple() -> date cn 16/10 16:54");
    }

    // Tests Historique des Alertes
    public function testSqlWithFilterSimpleWithExclude() {

        $tableauCorrespondance = array( 'nom'       => 'table.nom',
                                        'prenom'    => 'table.prenom',
                                        'date'     => 'table.date',
                                        'dateCn'     => 'table.date'
                                        );
        $filters = new stdClass();
        $ligne = new stdClass();
        $ligne->field = "nom";
        $ligne->op = "cn";
        $ligne->data = "toto";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "prenom";
        $ligne->op = "eq";
        $ligne->data = "prenom2";

        $rules[] = $ligne;

        $ligne = new stdClass();
        $ligne->field = "date";
        $ligne->op = "le";
        $ligne->data = "16/10 16:54:03";

        $rules[] = $ligne;

        $filters->rules = $rules;
        $filters->grouOp = "AND";

        // timestamp de 16/10 16:54:03 = 1381935243

        // Année de référence 2013
        $year = date('Y') - 2013;

        $exclure = array('nom');
        $jqGrid = new JqGridService();
        $filtrageJQGrid = $jqGrid->getSqlFiltreJqGrid($tableauCorrespondance, $filters, $exclure);

        $this->assertNotContains(
            "LOWER(table.nom) LIKE '%toto%'", 
            $filtrageJQGrid, 
            "testSqlWithFilterSimple() -> nom"
        );
        $this->assertContains(
            "LOWER(table.prenom) = 'prenom2'", 
            $filtrageJQGrid,
            "testSqlWithFilterSimple() -> prenom"
        );
        $this->assertContains(
            "LOWER(table.date) <= " . strtotime('+' . $year + ' years', 1381935243), 
            $filtrageJQGrid, 
            "testSqlWithFilterSimple() -> date 16/10 16:54:03"
        );
    }

    protected function runService($options) {
        $jqGrid = new JqGridService();

        $jsonResponse = $jqGrid->createResponseFromModels( $this->objects, $options,array(), true );
        $phpResponse = json_decode($jsonResponse);

        return $phpResponse;
    }

}
