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
    protected function configure() {
        $this->setName("convert")
            ->setDescription("Converts an API Blueprint JSON file into a Postman collection")
            ->addArgument(
                'input_file',
                InputArgument::REQUIRED,
                'The JSON file to convert'
            )
            ->addOption(
               'path',
               getcwd(),
               InputOption::VALUE_REQUIRED,
               'The absolute path pointing to the location of your JSON file. Defaults to the current directory.'
            )
            ->addOption(
               'host',
               null,
               InputOption::VALUE_REQUIRED,
               'The base host of your API (e.g. https://api.example.com/v1).'
            )
            ->setHelp(<<<EOT
The <info>convert</info> command converts an API Blueprint JSON file into a Postman collection.
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filePath = $input->getOption('path');

        $file = is_null($filePath) ? $input->getArgument('input_file') : $filePath.DIRECTORY_SEPARATOR.$input->getArgument('input_file');

        if (!file_exists($file)) {
            $output->writeln(sprintf("\n<error>Error: API Blueprint file [%s] not found.</error>\n", $file));
            exit();
        }

        $blueprint = json_decode(file_get_contents($file));

        $version = (float)$blueprint->_version;
        if ($version < 2.0) {
            $output->writeln(sprintf("\n<error>Your API Blueprint needs to be build with Snow Crash 0.9.0 or higher.</error>"));
            exit();
        }

        $collection = array();
        $collection['id'] = (string)Uuid::uuid4();
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
                "\n<info>Please enter the base uri of your API</info> [<comment>https://api.example.com/v1</comment>]: ",
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
                            $request['data'] = (string)$exampleRequest->body;
                            $request['dataMode'] = 'raw';
                            $request['collectionId'] = $collection['id'];
                        }
                        $request['url'] = $host.$this->parseUri($resource, $action);
                        $request['name'] = $resource->uriTemplate;
                        $request['method'] = $action->method;
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

        $collection['requests'] = $requests;

        $output->writeln(sprintf("\n<info>Done.</info>"));

        file_put_contents(getcwd().'/collection.json', json_encode($collection));
    }

    /**
     * Parses the URI to make sure any parameters are replaced with actual values
     *
     * @param  stdObject $resource The current resource
     * @param  stdObject $action   The current action
     * @return string              The parsed URI
     */
    private function parseUri($resource, $action)
    {
        $uriTemplate = $resource->uriTemplate;

        if (strpos($uriTemplate, '{?') !== false) {
            $resultString = preg_match('/{(.*?)}/', $uriTemplate, $matches);
            $resultString = $matches[1];
            if (strpos($resultString, '?') !== false) {
                $resultString = str_replace(',', '&', substr($resultString, 1));
                $urlParameters = explode('&', $resultString);
            }

            foreach ($urlParameters as $key => $urlParameter) {
                $urlParameters[$key] = $urlParameter . '=' . $action->parameters[$key]->example;
            }

            $start = strpos($uriTemplate, '{');
            $convertedUri = substr_replace($uriTemplate, '', $start);

            foreach ($urlParameters as $key => $urlParameter) {
                $convertedUri .= ($key === 0 ? '?' : '&') . $urlParameter;
            }
        } else if (strpos($uriTemplate, '{') !== false) {
            foreach ($action->parameters as $parameter) {
                $convertedUri = str_replace('{'.$parameter->name.'}', $parameter->example, $uriTemplate);
            }
        } else {
            $convertedUri = $uriTemplate;
        }

        return $convertedUri;
    }
}
