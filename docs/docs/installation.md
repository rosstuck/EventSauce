---
layout: default
permalink: /docs/installation/
title: Installation
---

# Installation

EvenSauce consists of multiple parts. Besides the main package you'll need _persistence_ and a _dispatcher_.

First you'll need to install the main package. This package provides the base functionality, some interfaces and
test-tooling.

```bash
composer require eventsauce/eventsauce:@dev
```

At the time of writing a Doctrine implementation of the `MessageRepository` is provided separately:

```bash
composer require eventsauce/doctrine-message-repository:@dev
```

There's also a RabbitMQ dispatcher available. This package is an extension to the php-amqplib/rabbitmq-bundle package.
This is a Symfony specific package which offers a solid integration with the framework. This package provides an
implementation of the `EventSauce\EventSourcing\Cosumer` interface, binding it to the
`OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface` which ties into the bundle.

```bash
composer require eventsauce/rabbitmq-bundle-bindings:@dev
```

## Bootstrap

```php
<?php

use Doctrine\DBAL\Connection;
use EventSauce\DoctrineMessageRepository\MysqlDoctrineMessageRepository;
use EventSauce\EventSourcing\UuidAggregateRootId;
use EventSauce\EventSourcing\AggregateRootRepository;
use EventSauce\EventSourcing\Serialization\ConstructingMessageSerializer;
use EventSauce\RabbitMQ\RabbitMQMessageDispatcher;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;

include __DIR__.'/vendor/autoload.php';

/** @var Connection $connection */
$connection = doctrine_connection();
/** @var ProducerInterface $producer */
$producer = rabbbitmq_producer();

$messageSerializer = new ConstructingMessageSerializer(UuidAggregateRootId::class);
$messageDispatcher = new RabbitMQMessageDispatcher($producer, $messageSerializer);
$messageRepository = new MysqlDoctrineMessageRepository($connection, $messageDispatcher, $messageSerializer, 'domain_messages');
$aggregateRootRepository = new AggregateRootRepository(MyAggregateRoot::class, $messageRepository);

$aggregateRootId = new UuidAggregateRootId('4ea45435-aee8-43f2-aad8-2309bcd2aaab');
$myAggregate = $aggregateRootRepository->retrieve($aggregateRootId);

// Perform actions.

$aggregateRootRepository->persist(...$myAggregate->releaseEvents());
```
