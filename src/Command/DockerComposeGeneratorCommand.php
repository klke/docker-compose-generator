<?php

namespace Klke\DockerComposeGeneratorBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment as Twig;

/**
 * Class DockerComposeGeneratorCommand
 * @package Klke\DockerComposeGeneratorBundle\Command
 */
class DockerComposeGeneratorCommand extends Command
{
    protected $twig, $fileSystem, $rootPath, $services;

    public function __construct($rootPath, Twig $twig)
    {
        $this->twig = $twig;
        $this->rootPath = $rootPath;
        $this->fileSystem = new Filesystem();
        $this->services = [
            'mysql' => [
                'enabled' => false,
                'port' => 3306,
                'extra_ports' => []
            ],
            'redis' => [
                'enabled' => false,
                'port' => 6379,
                'extra_ports' => []
            ],
            'mongodb' => [
                'enabled' => false,
                'port' => 27017,
                'extra_ports' => []
            ],
            'elasticsearch' => [
                'enabled' => false,
                'port' => 9200,
                'extra_ports' => []
            ],
            'kibana' => [
                'enabled' => false,
                'port' => 5601,
                'extra_ports' => []
            ],
            'rabbitmq' => [
                'enabled' => false,
                'port' => 5672,
                'extra_ports' => [
                    'rabbitmq_manager' => 15672
                ]
            ],
        ];

        parent::__construct();
    }

    protected function configure(){

        $this->setName('docker-compose:generate')
            ->setDescription('Generador de entornos docker')
        ;
    }

    protected function showTitle($output)
    {
        $output->writeln('<comment>
    ______           _             _____                                      
    |  _  \         | |           /  __ \                                     
    | | | |___   ___| | _____ _ __| /  \/ ___  _ __ ___  _ __   ___  ___  ___ 
    | | | / _ \ / __| |/ / _ \ \'__| |    / _ \| \'_ ` _ \| \'_ \ / _ \/ __|/ _ \
    | |/ / (_) | (__|   <  __/ |  | \__/\ (_) | | | | | | |_) | (_) \__ \  __/
    |___/ \___/ \___|_|\_\___|_|   \____/\___/|_| |_| |_| .__/ \___/|___/\___|                                         
                 _____                           _      | |
                |  __ \                         | |     |_|
                | |  \/ ___ _ __   ___ _ __ __ _| |_ ___  _ __ 
                | | __ / _ \ \'_ \ / _ \ \'__/ _` | __/ _ \| \'__|
                | |_\ \  __/ | | |  __/ | | (_| | || (_) | |   
                 \____/\___|_| |_|\___|_|  \__,_|\__\___/|_|
                                                                                                
         
        ');
    }

    protected function askQuestions($input, $output)
    {
        $helper = $this->getHelper('question');
        foreach($this->services as $service => $data)
        {
            $question = new ConfirmationQuestion("[*] <question>Do you want to enable {$service}?</question>[<fg=black;bg=white>y</>/n]", true);
            if ($helper->ask($input, $output, $question)) {
                $this->services[$service]['enabled'] = true;
            } else {
                continue;
            }

            do {
                $question = new ConfirmationQuestion("  [-] <info>Use '{$this->services[$service]['port']}' port for '{$service}'?</info>[<fg=black;bg=white>y</>/n]", true);
                if ($helper->ask($input, $output, $question))
                {
                    break;
                }
                $this->services[$service]['port']++;
            }while(1);

            foreach($this->services[$service]['extra_ports'] as $extraPortName => $extraPort) {

                do {
                    $question = new ConfirmationQuestion("  [-] <info>Use '{$this->services[$service]['extra_ports'][$extraPortName]}' port for '{$extraPortName}'?</info>[<fg=black;bg=white>y</>/n]", true);
                    if ($helper->ask($input, $output, $question))
                    {
                        break;
                    }
                    $this->services[$service]['extra_ports'][$extraPortName]++;
                }while(1);
            }


            $output->writeln(null);
        }
    }

    protected function ignoreFolders(array $files)
    {
        $tags = array(
            '###> klke/docker-compose-generator-bundle ###',
            '###< klke/docker-compose-generator-bundle ###'
        );

        $gitignore = "{$this->rootPath}/.gitignore";

        if(!$this->fileSystem->exists($gitignore))
        {
            $this->fileSystem->dumpFile($gitignore, "{$tags[0]}{$tags[1]}");
        }

        $text = implode(PHP_EOL,$files);
        $content = file_get_contents($gitignore);
        if(strpos($content, $tags[0]) !== false)
        {
            $content_processed = preg_replace_callback('#\#\#\#\> klke\/docker-compose-generator-bundle \#\#\#(.+?)\#\#\#\< klke\/docker-compose-generator-bundle \#\#\##s',
                static function () use ($text, $tags)
                {
                    return "{$tags[0]}\r\n{$text}\r\n{$tags[1]}\r\n";
                },
                $content
            );

            $this->fileSystem->dumpFile($gitignore, $content_processed);
        }else{
            $this->fileSystem->appendToFile($gitignore, "\r\n{$tags[0]}\r\n{$text}\r\n$tags[1]\r\n");
        }

    }

    protected function createConfigDirs()
    {
        $ignores = [];

        foreach($this->services as $serviceName => $data)
        {
            if($data['enabled'] === false)
            {
                continue;
            }

            switch ($serviceName)
            {
                case 'mysql':
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mysql/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mysql/data/");
                    $ignores[] = '/docker/mysql/data/*';
                    $this->fileSystem->dumpFile(
                        "{$this->rootPath}/docker/mysql/mysqld.cnf",
                        $this->twig->render('@DockerComposeGenerator\mysqld.cnf.twig')
                    );
                    break;

                case 'mongodb':
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mongodb/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mongodb/data/");
                    $ignores[] = '/docker/mongodb/data/*';
                    break;

                case 'rabbitmq':

                    $this->fileSystem->mkdir("{$this->rootPath}/docker/rabbitmq", 0775);

                    $this->fileSystem->dumpFile(
                        "{$this->rootPath}/docker/rabbitmq/enabled_plugins",
                        '[rabbitmq_management, rabbitmq_management_visualiser].'
                    );
                    break;
            }
        }

        $this->ignoreFolders($ignores);
    }

    protected function dumpConfig($input, $output)
    {
        $helper = $this->getHelper('question');

        $fileContent = $this->twig->render('@DockerComposeGenerator\docker-compose.yml.twig', [
            'mutex' => substr(md5(basename($this->rootPath)),0,6),
            'services' => $this->services
        ]);

        $filePath = "{$this->rootPath}/docker-compose.yml";

        if($this->fileSystem->exists($filePath))
        {
            $question = new ConfirmationQuestion(PHP_EOL."<error>File 'docker-compose' already exists, do you want to override?</error>[y/<fg=black;bg=white>n</>]", false);
            if ($helper->ask($input, $output, $question)) {
                $this->fileSystem->dumpFile($filePath, $fileContent);
                $output->writeln(PHP_EOL.'<info>[*] Updated! Run it with "docker-compose up" :)</info>');
                return 0;
            }

            $output->writeln(PHP_EOL.'[!] <fg=red>"docker-compose.yml" not updated!</>');
            return -1;
        }

        $this->fileSystem->dumpFile($filePath, $fileContent);
        $output->writeln(PHP_EOL.'<info>[*] Created! Run it with "docker-compose up" :)</info>');

        return 0;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->showTitle($output);
        $this->askQuestions($input, $output);
        $this->createConfigDirs();
        return $this->dumpConfig($input, $output);
    }
}
