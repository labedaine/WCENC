<?php
 
require_once dirname(__FILE__).'/../vendor/autoload.php';
 
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Behat\Behat\Console\Command\BehatCommand;
 


class BehatTest extends PHPUnit_Framework_TestCase
{
    public function __construct() {
        // Pour ICE...
        PHPUnit_Framework_Error_Deprecated::$enabled = FALSE;
    }

    /**
     * @group behat
     */
   public function testThatBehatScenariosMeetAcceptanceCriteria()
    {
        $BASEDIR=dirname(dirname(dirname(__DIR__)));
        $features = 'features';
 
        try {
            $input = new ArrayInput(array( '--format' => 'progress', 
                                           '-c' => __DIR__.'/../config/behat.yml', 
                                           "--tags" => "506",
                                           "features" => "$BASEDIR/hyperviseur/moteur/features")); 
            $output = new ConsoleOutput();
 
            $app = new \Behat\Behat\Console\BehatApplication('DEV');
            $app->setAutoExit(false);
 
            $result = $app->run($input, $output);
 
            $this->assertEquals(0, $result);
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }
}