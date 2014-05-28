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
}
