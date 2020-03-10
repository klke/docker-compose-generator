# docker-compose-generator

Command-line Symfony tool to generate 'docker-compose.yml' with pre-configurated services:

* Elastic Search (6.8.6)
* Kibana (6.8.6)
* Mysql (5.7)
* Redis (3.2)
* RabbitMQ (latest)
* MongoDB (latest)

##### ¿Why not Apache/Nginx and PHP? ##### 

Symfony recommends to use their local web server. You can find included in their binary:
https://symfony.com/download

Once you have installed just run:
```
symfony server:start 
```

As an alternative you can also use php:
```
php -S 127.0.0.1:8009 -t public
```
I prefer to use symfony client, with php-cli you can experience several problems; for example with urls containing dot on parameters or parallel requests (due to single-threading).

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


### Prerequisites

To be able to run and use docker-compose generator, you should have installed:

* Docker: https://www.docker.com/
* Docker-compose tool: https://github.com/docker/compose

### Authorization

Use next credentials in order to get access to services.

####Mysql

User: root

Password: root

Database: symfony

####MongoDB

User: guest

Password: guest

####RabbitMQ

User: guest

Password: guest

Default vhost: mainrabbit

### Database files

Remember that you can find database files outside of docker container for services:

* Mysql: /docker/mysql/data
* MongoDB: /docker/mysql/data

