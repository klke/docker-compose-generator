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
    protected $twig, $fileSystem, $conf, $rootPath, $services;

    public function __construct($rootPath, Twig $twig)
    {
        $this->twig = $twig;
        $this->rootPath = $rootPath;
        $this->fileSystem = new Filesystem();

        parent::__construct();
    }

    protected function configure(){

        $this->setName('docker-compose:generate')
            ->setDescription('Generador de entornos docker')
        ;
    }

    protected function normalizeConfig()
    {
        foreach($this->conf['services'] as $key => $service)
        {
            if(!isset($service['extra_ports']))
            {
                continue;
            }

            $tmp = [];
            foreach($service['extra_ports'] as $port)
            {
                $tmp[$port['name']] = $port['port'];
            }
            unset($this->conf['services'][$key]['extra_ports']);
            $this->conf['services'][$key]['extra_ports'] = $tmp;
        }

        foreach($this->conf['services'] as $key => $service)
        {
            if(!isset($service['options']))
            {
                continue;
            }

            $tmp = [];
            foreach($service['options'] as $option)
            {
                $tmp[$option['name']] = $option['value'];
            }
            unset($this->conf['services'][$key]['options']);
            $this->conf['services'][$key]['options'] = $tmp;
        }
    }

    public function setConfig($conf)
    {
        $this->conf = $conf;
        $this->services = [
            'nginx' => [
                'enabled' => false,
                'version' => 'latest',
                'port' => 80,
                'extra_ports' => [],
                'options' => [
                    'HOST_NAME' => 'localhost',
                ]
            ],
            'php' => [
                'enabled' => false,
                'version' => '7.4-fpm',
                'port' => 9000,
                'extra_ports' => [],
                'options' => []
            ],
            'mysql' => [
                'enabled' => false,
                'version' => '5.7',
                'port' => 3306,
                'extra_ports' => [],
                'options' => [
                    'MYSQL_USER' => 'symfony',
                    'MYSQL_PASSWORD' => 'root',
                    'MYSQL_ALLOW_EMPTY_PASSWORD' => 1,
                    'MYSQL_ROOT_PASSWORD' => 'root',
                    'MYSQL_DATABASE' => 'symfony',
                    'MYSQL_ROOT_HOST' => '%',
                ]
            ],
            'redis' => [
                'enabled' => false,
                'version' => '3.2',
                'port' => 6379,
                'extra_ports' => [],
                'options' => []
            ],
            'mongodb' => [
                'enabled' => false,
                'version' => 'latest',
                'port' => 27017,
                'extra_ports' => [],
                'options' => [
                    'MONGO_DATA_DIR' => '/data/db',
                    'MONGO_LOG_DIR' => '/dev/null',
                    'MONGODB_USER' => 'symfony',
                    'MONGODB_PASS' => 'root',
                ]
            ],
            'elasticsearch' => [
                'enabled' => false,
                'version' => '6.8.6',
                'port' => 9200,
                'extra_ports' => [],
                'options' => []
            ],
            'kibana' => [
                'enabled' => false,
                'version' => '6.8.6',
                'port' => 5601,
                'extra_ports' => [],
                'options' => [
                    'SERVER_HOST' => 'localhost',
                ]
            ],
            'rabbitmq' => [
                'enabled' => false,
                'version' => 'latest',
                'port' => 5672,
                'extra_ports' => [
                    'rabbitmq_manager' => 15672
                ],
                'options' => [
                    'RABBITMQ_ERLANG_COOKIE' => 'SWQOKODSQALRPCLNMEQG',
                    'RABBITMQ_DEFAULT_USER' => 'guest',
                    'RABBITMQ_DEFAULT_PASS' => 'guest',
                    'RABBITMQ_DEFAULT_VHOST' => 'mainrabbit',
                ]
            ],
            'mailcatcher' => [
                'enabled' => false,
                'version' => 'latest',
                'port' => 1025,
                'extra_ports' => [
                    'webmail' => 1080
                ]
            ],
        ];

        if(isset($this->conf['services']))
        {
            $this->normalizeConfig();
            $this->services = array_replace_recursive($this->services, $this->conf['services']);
        }
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

    protected function gitIgnoreFolders(array $files)
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
            $content .= "\r\n{$tags[0]}\r\n{$text}\r\n$tags[1]\r\n";
            $this->fileSystem->dumpFile($gitignore, $content); //No using appendToFile method due to retrocompatibility
        }
    }

    protected function getEntryPoint()
    {
        if($this->fileSystem->exists("{$this->rootPath}/public/index.php"))
        {
            return ['folder' => 'public', 'file' => 'index', 'extension' => 'php'];
        }

        return ['folder' => 'web', 'file' => 'app', 'extension' => 'php'];
    }

    protected function createConfigDirs()
    {
        $ignores = [
            '/docker/'
        ];

        foreach($this->services as $serviceName => $data)
        {
            if($data['enabled'] === false)
            {
                continue;
            }

            switch ($serviceName)
            {
                case 'nginx':
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/nginx/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/nginx/logs");
                    $this->fileSystem->dumpFile(
                        "{$this->rootPath}/docker/nginx/default.conf",
                        $this->twig->render('@DockerComposeGenerator\config\nginx.default.conf.twig', [
                            'data' => $data,
                            'entrypoint' =>  $this->getEntryPoint()
                        ])
                    );
                    break;

                case 'php':
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/php/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/php/logs");
                    break;

                case 'mysql':
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mysql/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mysql/data/");
                    $this->fileSystem->dumpFile(
                        "{$this->rootPath}/docker/mysql/mysqld.cnf",
                        $this->twig->render('@DockerComposeGenerator\config\mysqld.conf.twig')
                    );
                    break;

                case 'mongodb':
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mongodb/", 0775);
                    $this->fileSystem->mkdir("{$this->rootPath}/docker/mongodb/data/");
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

        $this->gitIgnoreFolders($ignores);
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
