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

    public function testOutdatedSnowCrashException()
    {
        try {
            $this->commandTester->execute(
                array(
                    'command' => $this->command->getName(),
                    'input-file' => 'api_outdated.json',
                    '--path' => getcwd().'/test'
                )
            );
        } catch (Exception $e) {
            $message = $e->getMessage();
        }

        $this->assertEquals($message, 'Your API Blueprint needs to be build with Snow Crash 0.9.0 or higher.');
    }

    public function testParsingUriWithoutParams()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleParseUriMethod();

        $resource = new stdClass();
        $resource->uriTemplate = '/players';
        $action = new stdClass();

        $result = $method->invokeArgs($cmd, array($resource, $action));

        $this->assertEquals('/players', $result);
    }

    public function testParsingUriWithSingleQueryParam()
    {
        $cmd = new ConvertCommand();
        $method = $this->getAccessibleParseUriMethod();

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
        $method = $this->getAccessibleParseUriMethod();

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
        $method = $this->getAccessibleParseUriMethod();

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
        $method = $this->getAccessibleParseUriMethod();

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
        $method = $this->getAccessibleParseUriMethod();

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

    /**
     * Helper to call `parseUri` on `ConvertCommand`
     *
     * @return ReflectionMethod Accessible `ConvertCommand::parseUri`
     */
    private function getAccessibleParseUriMethod()
    {
        $reflection = new ReflectionClass('\Blueman\Console\Command\ConvertCommand');
        $method = $reflection->getMethod('parseUri');
        $method->setAccessible(true);

        return $method;
    }
}
