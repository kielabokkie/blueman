<?php
namespace Blueman\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Rhumsaa\Uuid\Uuid;

class ConvertCommand extends Command
{
    protected function configure()
    {
        $this->setName("convert")
            ->setDescription("Converts an API Blueprint JSON file into a Postman collection")
            ->addArgument(
                'input-file',
                InputArgument::REQUIRED,
                'The JSON file to convert'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'The absolute path pointing to the location of your JSON file.',
                getcwd()
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_OPTIONAL,
                'The location (including the filename) of where your collection should be saved.',
                sprintf('%s/collection.json', getcwd())
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'The base host of your API (e.g. https://api.example.com/v1).'
            )
            ->addOption(
                'tests-filename',
                null,
                InputOption::VALUE_OPTIONAL,
                'The JSON file name with Postman tests (located at --path)',
                'blueman.tests.md'
            )
            ->addOption(
                'tests-include',
                null,
                InputOption::VALUE_OPTIONAL,
                'Add Postman tests to result JSON collection file (see --tests-filename)',
                false
            )
            ->setHelp(<<<EOT
The <info>convert</info> command converts an API Blueprint JSON file into a Postman collection.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $input->getOption('path');

        $output->writeln('<info>Working Path: ' . $filePath . '</info>');

        $file = $filePath . DIRECTORY_SEPARATOR . $input->getArgument('input-file');

        if (!file_exists($file)) {
            throw new \Exception(
                sprintf("API Blueprint file [%s] not found.", $file)
            );
        }

        $blueprint = json_decode(file_get_contents($file));

        if (isset($blueprint->ast) === false) {
            throw new \Exception(
                'Your API Blueprint file is not in the AST format. When parsing your API Blueprint file with Drafter add the -f flag to set the parse result type, e.g. `drafter -f json -t ast -o api.json api.md`.'
            );
        }

        $tests = array();
        if('true' === $input->getOption('tests-include')) {

            $testsFile = $filePath . $input->getOption('tests-filename');

            if($testsFileExists = file_exists($testsFile)) {
                $output->writeln('<info>Using Blueman file with Postman tests: ' . $testsFile . '</info>');
                $tests = $this->parseTestsFile($testsFile);
            } else {
                $output->writeln('<comment>Blueman file with Postman tests NOT found:' . $testsFile . '</comment>');
            }
        }

        $blueprint = $blueprint->ast;

        $collection = array();
        $collection['id'] = (string) Uuid::uuid4();
        $collection['name'] = $blueprint->name;
        $collection['description'] = $blueprint->description;

        $host = $input->getOption('host');

        if (is_null($host)) {
            // Check if the default host is set in the metadata
            foreach ($blueprint->metadata as $metadata) {
                if ($metadata->name === 'HOST') {
                    $host = $metadata->value;
                }
            }
        }

        if (empty($host)) {
            $dialog = $this->getHelperSet()->get('dialog');
            $host = $dialog->ask(
                $output,
                "\n<info>Enter the base uri of your API</info> [<comment>https://api.example.com/v1</comment>]: ",
                'https://api.example.com/v1'
            );
        }

        $requests = array();

        foreach ($blueprint->resourceGroups as $resourceGroup) {
            $folders['id'] = (string)Uuid::uuid4();
            $folders['name'] = $resourceGroup->name;
            $folders['description'] = $resourceGroup->description;

            $folders['order'] = array();
            foreach ($resourceGroup->resources as $resource) {
                /** @var object $action */
                foreach ($resource->actions as $action) {
                    $actionId = (string)Uuid::uuid4();

                    $folders['order'][] = $actionId;

                    foreach ($action->examples as $example) {
                        $request['id'] = $actionId;
                        foreach ($example->requests as $exampleRequest) {
                            $headers = array();
                            foreach ($exampleRequest->headers as $header) {
                                $headers[] = sprintf('%s: %s', $header->name, $header->value);
                            }
                            $request['headers'] = implode("\n", $headers);
                            $request['data'] = (string) $exampleRequest->body;
                            $request['dataMode'] = 'raw';
                            $request['collectionId'] = $collection['id'];
                        }
                        $request['url'] = $host . $this->parseUri($resource, $action);
                        $request['name'] = $resource->uriTemplate;
                        $request['method'] = $action->method;
                        if($tests) {
                            $request['tests'] = $this->getTest($action->name, $tests);
                        }

                        $requests[] = $request;
                    }
                }
            }
            $folders['collection_name'] = $collection['name'];
            $folders['collection_id'] = $collection['id'];

            $collection['folders'][] = $folders;
        }

        $collection['timestamp'] = time();
        $collection['synced'] = 'false';
        $collection['order'] = array();

        $collection['requests'] = $requests;

        $file = file_put_contents($input->getOption('output'), json_encode($collection));
        if ($file === false) {
            throw new \Exception(
                "Failed to write, permission denied."
            );
        }

        $output->writeln("\n<info>Done.</info>\n");
    }

    /**
     * Parses the URI to make sure any parameters are replaced with actual values
     *
     * @param  object $resource The current resource
     * @param  object $action   The current action
     * @return string              The parsed URI
     */
    private function parseUri($resource, $action)
    {
        $uriTemplate  = $resource->uriTemplate;
        $convertedUri = '';

        if ($this->hasQueryParams($uriTemplate)) {
            $convertedUri = $this->replaceQueryParams(
                $uriTemplate,
                $action->parameters
            );
        }

        if ($this->hasUriParams($uriTemplate)) {
            $convertedUri = $this->replaceUriParams(
                strlen($convertedUri) > 0 ? $convertedUri : $uriTemplate,
                $action->parameters
            );
        }

        return strlen($convertedUri) > 0 ? $convertedUri : $uriTemplate;
    }

    /**
     * Replace query parameters in given uri.
     *
     * E.g.: /players{?name,age} -> /players?name=John&age=25
     *
     * @param string $uriTemplate /players{?name,age}
     * @param array $parameters
     * @return string /players?name=John&age=25
     */
    private function replaceQueryParams($uriTemplate, array $parameters)
    {
        preg_match('/{\?(.*)}/', $uriTemplate, $matches);
        $resultString  = '?' . $matches[1];
        $urlParameters = null;

        if (strpos($resultString, '?') !== false) {
            $resultString = str_replace(',', '&', substr($resultString, 1));
            $urlParameters = explode('&', $resultString);
        }

        foreach ($urlParameters as $key => $urlParameter) {
            $parameter = $this->getParameter($urlParameter, $parameters);
            if (is_object($parameter) && property_exists($parameter, 'example')) {
                $urlParameters[$key] = $urlParameter . '=' . $parameter->example;
            }
        }

        $start = strpos($uriTemplate, '{?');
        $convertedUri = substr_replace($uriTemplate, '', $start);

        foreach ($urlParameters as $key => $urlParameter) {
            $convertedUri .= ($key === 0 ? '?' : '&') . $urlParameter;
        }

        return $convertedUri;
    }

    /**
     * Replace uri parameters in given uri.
     *
     * E.g.: /players/{name} -> /players/John
     *
     * @param string $uriTemplate /players/{name}
     * @param array $parameters
     * @return string /players/John
     */
    private function replaceUriParams($uriTemplate, array $parameters)
    {
        $convertedUri = $uriTemplate;

        foreach ($parameters as $parameter) {
            $convertedUri = str_replace('{'.$parameter->name.'}', $parameter->example, $convertedUri);
        }

        return $convertedUri;
    }

    /**
     * Get parameter by name from given parameter bag.
     *
     * @param string $name
     * @param array $params
     * @return string|null
     */
    private function getParameter($name, array $params)
    {
        foreach ($params as $param) {
            if ($param->name === $name) {
                return $param;
            }
        }

        return null;
    }

    /**
     * Helper to inspect a given uri for query params.
     *
     * @param string $uri /players{?name,age}
     * @return bool
     */
    private function hasQueryParams($uri)
    {
        return strpos($uri, '{?') !== false;
    }

    /**
     * Helper to inspect a given uri for uri params.
     *
     * @param string $uri /players/{name}
     * @return bool
     */
    private function hasUriParams($uri)
    {
        return strpos($uri, '{') !== false;
    }

    /**
     * Find test by resource Action name
     * @param string$actionName
     * @param object $tests [0 - prepend, 1 - tests by actions]
     * @return string
     */
    private function getTest($actionName, $tests)
    {

        return isset($tests[1][$actionName])
            ? $tests[0] . $tests[1][$actionName]
            : '';
    }

    /**
     * Parse Markdown with tests
     * @todo to parse class?
     * @param string $testsFile
     * @return array
     */
    private function parseTestsFile($testsFile)
    {
        if(!$markdown = file($testsFile, FILE_SKIP_EMPTY_LINES)) {
            return array();
        }

        $tests = array();
        $prepend = '';
        $mode = false;
        $head = false;
        $append = false;

        $heading = '/^(#+)\s+(.*)(\s*)$/';
        $code = '/^(```)(.*)/';

        foreach ($markdown as $line) {
            $matches = array();
            $head = $head ? $head : false;
            if(preg_match($heading, $line, $matches)) {
                $mode = $matches[1];
                $head = trim($matches[2]);
                $tests[$head] = '';
                continue;
            }
            if(preg_match($code, $line, $matches) && $head) {
                $append = !$append;
                continue;
            }
            if($head && $mode && $append) {
                switch ($mode) {
                    case '##':
                        $prepend .= $line;
                        break;
                    case '###':
                        $tests[$head] .= $line;
                        break;
                }
            }
        }

        foreach ($tests as $action => $test) {
            if(!$test) {
                unset($tests[$action]);
            }
        }

        return array(
            $prepend,
            $tests,
        );
    }
}
