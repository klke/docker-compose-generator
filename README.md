# docker-compose-generator

Command-line Symfony tool to generate 'docker-compose.yml' with pre-configurated services:

* Elastic Search (6.8.6)
* Mysql (5.7)
* Redis (3.2)
* RabbitMQ (latest)

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

In >= Symfony 4 with Symfony Flex this will be done automatically.


### Prerequisites

To be able to run docker-compose tool you might have:

* Docker
* Docker-compose tool
