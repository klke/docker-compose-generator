# docker-compose-generator

Command-line Symfony tool to generate 'docker-compose.yml' with pre-configurated services:

![alt text](https://i.imgur.com/r257GPD.gif)

## Services

* Nginx
* Php-fpm
* Elastic Search
* Kibana
* Mysql
* Redis
* RabbitMQ
* MongoDB
* Mailcatcher

##### ¿Why not to use Symfony-cli server? ##### 

To test different versions of php on Symfony-cli server, you must have all installed on your pc so is not comfortable.
With docker-compose-generator you can change version easily from "docker_compose_generator.yaml".

## Getting Started

Install it with:

```
php composer.phar require klke/docker-compose-generator
```

Add this bundle to your Symfony application (in Symfony < 4):
```
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Klke\DockerComposeGeneratorBundle\DockerComposeGeneratorBundle(),
        // ...
    );
}
```

If you are using Symfony 2.8, you will need to add next line to 'app/config/config.yml' file:

```
kernel.project_dir: '%kernel.root_dir%/..'
```

In >= Symfony 4 with Symfony Flex everything will be done automatically.

### Configuration

You can customize image versions, passwords and many other options placing next configuration on 'config/packages/docker_compose_generator.yaml':

```
docker_compose_generator:
    services:
        nginx:
            version: latest
            port: 8087
            options:
                - { name: HOST_NAME, value: localhost }

        php:
            version: 7.2-fpm
            port: 9011
    
        mysql:
            version: 8
            port: 3306
            options:
                - { name: MYSQL_USER, value: symfony }
                - { name: MYSQL_PASSWORD, value: root }

        redis:
            version: 3.2
            port: 6379

        mongodb:
            version: latest
            port: 27017
            options:
                - { name: MONGODB_USER, value: guest }
                - { name: MONGODB_PASS, value: guest }

        elasticsearch:
            version: 6.8.5
            port: 9200

        kibana:
            version: 6.8.6
            port: 5601

        rabbitmq:
            version: latest
            port: 5672
            extra_ports:
                - {name: rabbitmq_manager, port: 15672}
            options:
                - { name: RABBITMQ_DEFAULT_USER, value: guest }
                - { name: RABBITMQ_DEFAULT_PASS, value: guest }
                - { name: RABBITMQ_DEFAULT_VHOST, value: mainrabbit }

        mailcatcher:
            version: latest
            port: 1025
            extra_ports:
                - {name: webmail, port: 1080}
```

### Prerequisites

To be able to run and use docker-compose generator, you should have installed:

* Docker: https://www.docker.com/
* Docker-compose tool: https://github.com/docker/compose

### Database files

Remember that you can find database files outside of docker container for services:

* Mysql: /docker/mysql/data
* MongoDB: /docker/mysql/data

