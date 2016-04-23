<?php

use Blueman\Console\Command\ConvertCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ConvertCommandTest extends \PHPUnit_Framework_TestCase
{
    protected $command = null;
    protected $commandTester = null;

    public function setUp()
    {
        $application = new Application();
        $application->add(new ConvertCommand());

        $this->command = $application->find('convert');
        $this->commandTester = new CommandTester($this->command);
    }

    public function testSuccess()
    {
        $this->commandTester->execute(
            array(
                'command' => $this->command->getName(),
                'input-file' => 'api.json',
                '--path' => getcwd().'/test'
            )
        );

        $this->assertRegExp('/Done/', $this->commandTester->getDisplay());
    }

    public function testFileNotFoundException()
    {
        try {
            $this->commandTester->execute(
                array(
                    'command' => $this->command->getName(),
                    'input-file' => 'xxx.json',
                    '--path' => getcwd().'/test'
                )
            );
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertRegExp('/API Blueprint file \[.*\] not found/', $message);
    }

    public function testNonAstException()
    {
        try {
            $this->commandTester->execute(
                array(
                    'command' => $this->command->getName(),
                    'input-file' => 'api_non-ast.json',
                    '--path' => getcwd().'/test'
                )
            );
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertContains('Your API Blueprint file is not in the AST format.', $message);
    }

    public function testParsingUriWithoutParams()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseUri');
        
        $resource = new stdClass();
        $resource->uriTemplate = '/players';
        $action = new stdClass();

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players', $result);
    }

    public function testParsingUriWithSingleQueryParam()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseUri');

        $resource = new stdClass();
        $resource->uriTemplate = '/players{?name}';

        $nameParam = new stdClass();
        $nameParam->name = 'name';
        $nameParam->example = 'John';

        $action = new stdClass();
        $action->parameters = array($nameParam);

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players?name=John', $result);
    }

    public function testParsingUriWithMultipleQueryParams()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseUri');

        $resource = new stdClass();
        $resource->uriTemplate = '/players{?name,age}';

        $nameParam = new stdClass();
        $nameParam->name = 'name';
        $nameParam->example = 'John';

        $ageParam = new stdClass();
        $ageParam->name = 'age';
        $ageParam->example = 25;

        $action = new stdClass();
        $action->parameters = array($nameParam, $ageParam);

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players?name=John&age=25', $result);
    }

    public function testParsingUriWithSingleUriParam()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseUri');

        $resource = new stdClass();
        $resource->uriTemplate = '/players/{name}';

        $nameParam = new stdClass();
        $nameParam->name = 'name';
        $nameParam->example = 'John';

        $action = new stdClass();
        $action->parameters = array($nameParam);

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players/John', $result);
    }

    public function testParsingUriWithMultipleUriParams()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseUri');

        $resource = new stdClass();
        $resource->uriTemplate = '/players/{name}/games/{game_id}';

        $nameParam = new stdClass();
        $nameParam->name = 'name';
        $nameParam->example = 'John';

        $gameParam = new stdClass();
        $gameParam->name = 'game_id';
        $gameParam->example = '52387';

        $action = new stdClass();
        $action->parameters = array($nameParam, $gameParam);

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players/John/games/52387', $result);
    }

    public function testParsingUriWithMultipleUriAndQueryParams()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseUri');

        $resource = new stdClass();
        $resource->uriTemplate = '/players/{name}/games/{game_id}{?filter,locale}';

        $nameParam = new stdClass();
        $nameParam->name = 'name';
        $nameParam->example = 'John';

        $gameParam = new stdClass();
        $gameParam->name = 'game_id';
        $gameParam->example = '52387';

        $filterParam = new stdClass();
        $filterParam->name = 'filter';
        $filterParam->example = 'flunkyball';

        $localeParam = new stdClass();
        $localeParam->name = 'locale';
        $localeParam->example = 'US';

        $action = new stdClass();
        $action->parameters = array($nameParam, $gameParam, $filterParam, $localeParam);

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players/John/games/52387?filter=flunkyball&locale=US', $result);
    }
    
    public function testPostmanTests() {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleMethod('parseTestsFile');

        $result = $method->invokeArgs($cmd, array('test/api.test.md'));

        /**
         * Isset code to prepend
         */
        $this->assertArrayHasKey(0, $result);
        $this->assertNotEmpty($result[0]);
        /**
         * Isset tests for action
         */
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey('Create a Player', $result[1]);
        $this->assertNotEmpty($result[1]['Create a Player'], 'Empty tests for action: Create a Player');
        $this->assertNotEmpty($result[1]['Example 1'], 'Empty tests for action: Example 1');
        /**
         * No test for actions
         */
        $this->assertArrayNotHasKey('Another action', $result[1]);
        $this->assertArrayNotHasKey('Example 0', $result[1]);
        $this->assertArrayNotHasKey('Example 2', $result[1]);
    }

    /**
     * Helper to get accessible method from `ConvertCommand`
     *
     * @param string $method
     * @return ReflectionMethod
     */
    private function getAccessibleMethod($method)
    {
        $reflection = new ReflectionClass('\Blueman\Console\Command\ConvertCommand');
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        return $method;
    }
}
