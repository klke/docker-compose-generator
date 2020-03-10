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
    protected $twig, $fileSystem, $rootPath;

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

    protected function showTitle($output)
    {
        $output->writeln('<comment>
            ______           _             _____                                      
            |  _  \         | |           /  __ \                                     
            | | | |___   ___| | _____ _ __| /  \/ ___  _ __ ___  _ __   ___  ___  ___ 
            | | | / _ \ / __| |/ / _ \ \'__| |    / _ \| \'_ ` _ \| \'_ \ / _ \/ __|/ _ \
            | |/ / (_) | (__|   <  __/ |  | \__/\ (_) | | | | | | |_) | (_) \__ \  __/
            |___/ \___/ \___|_|\_\___|_|   \____/\___/|_| |_| |_| .__/ \___/|___/\___|  <options=bold,underscore>GENERATOR</>
                                                                | |                   
                        By Enrique Fernandez Reig               |_|    (For ❤Symfony❤)              
                                                     
         
                            
        ');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $services = [
            'mysql' => [
                'enabled' => false,
                'port' => 3306
            ],
            'redis' => [
                'enabled' => false,
                'port' => 6379
            ],
            'elasticsearch' => [
                'enabled' => false,
                'port' => 9200
            ],
            'rabbitmq' => [
                'enabled' => false,
                'port' => 5672
            ],
        ];

        $this->showTitle($output);
        $helper = $this->getHelper('question');
        foreach($services as $service => $data)
        {
            $question = new ConfirmationQuestion("[*] <question>Do you want to enable {$service}?</question>[<fg=black;bg=white>y</>/n]", true);
            if ($helper->ask($input, $output, $question)) {
                $services[$service]['enabled'] = true;
            } else {
                continue;
            }

            do {
                $question = new ConfirmationQuestion("  [-] <info>Use '{$services[$service]['port']}' port for '{$service}'?</info>[<fg=black;bg=white>y</>/n]", true);
                if ($helper->ask($input, $output, $question))
                {
                    break;
                }
                $services[$service]['port']++;
            }while(1);

            $output->writeln(null);
        }

        $fileContent = $this->twig->render('@DockerComposeGenerator\docker-compose.yml.twig', ['services' => $services]);
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
    }
}
