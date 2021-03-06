---
layout: default
permalink: /docs/events-and-commands/
title: Events and commands
---

# Events and commands

Events are the core of any event sourced system. They are the payload,
the message, they allow our system to communicate in a meaningful way.
Events (and commands) are also very simple. They are instantiated with
all the data they need and _only_ expose the data. An event should be
handled as an immutable object. They also have but a few technical
requirements:

1. They contain a reference to the aggregate root.
1. They must be persistable.
1. They must be valid.

The events and commands reference the aggregate root by ID. This ID
is commonly referred to as the `AggregateRootId`. Additionally an
event should contain a relevant payload.

Defining events and commands can be done in 2 ways.

* Defining them in YAML.
* Creating classes by pressing keys on your keyboard.


## Manually creating classes.

EventSauce provides interfaces for events and commands. You can create implementations of this. Here are minimal 
examples.

#### Command

```php
<?php

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Command;

class SomeCommand implements Command
{
    private $aggregateRootId;
    
    public function __construct(AggregateRootId $aggregateRootId)
    {
        $this->aggregateRootId = $aggregateRootId;
    }
    
    public function aggregateRootId(): AggregateRootId
    {
        return $this->aggregateRootId;
    }
}
```

### Event

```php
<?php

use EventSauce\EventSourcing\AggregateRootId;
use EventSauce\EventSourcing\Event;
use EventSauce\EventSourcing\PointInTime;

class SomeEvent implements Event
{
    private $aggregateRootId;
    private $timeOfRecording;

    public function __construct(AggregateRootId $aggregateRootId, PointInTime $timeOfRecording)
    {
        $this->aggregateRootId = $aggregateRootId;
        $this->timeOfRecording = $timeOfRecording;
    }

    public function aggregateRootId(): AggregateRootId
    {
        return $this->aggregateRootId;
    }

    public function timeOfRecording(): PointInTime
    {
        return $this->timeOfRecording;
    }

    public function toPayload(): array
    {
        return [];
    }

    public static function fromPayload(array $payload, AggregateRootId $aggregateRootId, PointInTime $timeOfRecording): Event
    {
        return new SomeEvent($aggregateRootId, $timeOfRecording);
    }
}
```

As you can see in the examples above, there are a handful of required methods. These methods help EventSauce to connect
events to aggregates using the AggregateRootId. The _from_ and _to_ payload methods are used in the serialization process.
This ensures the events can be properly stores, values returned in the `toPayload` method should be `json_encode`-able.

Additional required properties of an event should be injected into the constructor and properly formatted in the payload
methods.

## Defining commands and events using YAML.

Commands and events are mostly not very special, they're often just glorified arrays with accessors. Because of this
an easy way to declare them is made available. Here's an example YAML file containing some command and event definitions.

```yaml
namespace: Acme\BusinessProcess 
commands:
    SubscribeToMailingList:
        fields:
            username:
                type: string
                example: example-user
            mailingList:
                type: string
                example: list-name
    UnsubscribeFromMailingList:
        fields:
            username:
                type: string
                example: example-user
            mailingList:
                type: string
                example: list-name
            reason:
                type: string
                example: no-longer-interested
events:
    UserSubscribedFromMailingList:
            fields:
                username:
                    type: string
                    example: example-user
                mailingList:
                    type: string
                    example: list-name
        UserUnsubscribedFromMailingList:
            fields:
                username:
                    type: string
                    example: example-user
                mailingList:
                    type: string
                    example: list-name
                reason:
                    type: string
                    example: no-longer-interested
```

Which compiles to the following PHP file:
 
 ```php
 <?php
 
 namespace Acme\BusinessProcess;
 
 use EventSauce\EventSourcing\AggregateRootId;
 use EventSauce\EventSourcing\Command;
 use EventSauce\EventSourcing\Event;
 use EventSauce\EventSourcing\PointInTime;
 
 final class UserSubscribedFromMailingList implements Event
 {
     /**
      * @var AggregateRootId
      */
     private $aggregateRootId;
 
     /**
      * @var string
      */
     private $username;
 
     /**
      * @var string
      */
     private $mailingList;
 
     /**
      * @var PointInTime
      */
     private $timeOfRecording;
 
     public function __construct(
         AggregateRootId $aggregateRootId,
         PointInTime $timeOfRecording,
         string $username,
         string $mailingList
     ) {
         $this->aggregateRootId = $aggregateRootId;
         $this->timeOfRecording = $timeOfRecording;
         $this->username = $username;
         $this->mailingList = $mailingList;
     }
 
     public function aggregateRootId(): AggregateRootId
     {
         return $this->aggregateRootId;
     }
 
     public function username(): string
     {
         return $this->username;
     }
 
     public function mailingList(): string
     {
         return $this->mailingList;
     }
     
     public function timeOfRecording(): PointInTime
     {
         return $this->timeOfRecording;
     }
 
     public static function fromPayload(
         array $payload,
         AggregateRootId $aggregateRootId,
         PointInTime $timeOfRecording): Event
     {
         return new UserSubscribedFromMailingList(
             $aggregateRootId,
             $timeOfRecording,
             (string) $payload['username'],
             (string) $payload['mailingList']
         );
     }
 
     public function toPayload(): array
     {
         return [
             'username' => (string) $this->username,
             'mailingList' => (string) $this->mailingList
         ];
     }
 
     public function withUsername(string $username): UserSubscribedFromMailingList
     {
         $this->username = $username;
         
         return $this;
     }
 
     public function withMailingList(string $mailingList): UserSubscribedFromMailingList
     {
         $this->mailingList = $mailingList;
         
         return $this;
     }
 
     public static function with(AggregateRootId $aggregateRootId, PointInTime $timeOfRecording): UserSubscribedFromMailingList
     {
         return new UserSubscribedFromMailingList(
             $aggregateRootId,
             $timeOfRecording,
             (string) 'example-user',
             (string) 'list-name'
         );
     }
 
 }
 
 
 final class SubscribeToMailingList implements Command
 {
     /**
      * @var PointInTime
      */
     private $timeOfRequest;
 
     /**
      * @var AggregateRootId
      */
     private $aggregateRootId;
 
     /**
      * @var string
      */
     private $username;
 
     /**
      * @var string
      */
     private $mailingList;
 
     public function __construct(
         AggregateRootId $aggregateRootId,
         PointInTime $timeOfRequest,
         string $username,
         string $mailingList
     ) {
         $this->aggregateRootId = $aggregateRootId;
         $this->timeOfRequest = $timeOfRequest;
         $this->username = $username;
         $this->mailingList = $mailingList;
     }
 
     public function timeOfRequest(): PointInTime
     {
         return $this->timeOfRequest;
     }
 
     public function aggregateRootId(): AggregateRootId
     {
         return $this->aggregateRootId;
     }
 
     public function username(): string
     {
         return $this->username;
     }
 
     public function mailingList(): string
     {
         return $this->mailingList;
     }
 
 }
 
 final class UnsubscribeFromMailingList implements Command
 {
     /**
      * @var PointInTime
      */
     private $timeOfRequest;
 
     /**
      * @var AggregateRootId
      */
     private $aggregateRootId;
 
     /**
      * @var string
      */
     private $username;
 
     /**
      * @var string
      */
     private $mailingList;
 
     /**
      * @var string
      */
     private $reason;
 
     public function __construct(
         AggregateRootId $aggregateRootId,
         PointInTime $timeOfRequest,
         string $username,
         string $mailingList,
         string $reason
     ) {
         $this->aggregateRootId = $aggregateRootId;
         $this->timeOfRequest = $timeOfRequest;
         $this->username = $username;
         $this->mailingList = $mailingList;
         $this->reason = $reason;
     }
 
     public function timeOfRequest(): PointInTime
     {
         return $this->timeOfRequest;
     }
 
     public function aggregateRootId(): AggregateRootId
     {
         return $this->aggregateRootId;
     }
 
     public function username(): string
     {
         return $this->username;
     }
 
     public function mailingList(): string
     {
         return $this->mailingList;
     }
 
     public function reason(): string
     {
         return $this->reason;
     }
 
 }
 ```
 
 The code required to generate events is pretty simple:
 
```php
<?php

use EventSauce\EventSourcing\CodeGeneration\CodeDumper;
use EventSauce\EventSourcing\CodeGeneration\YamlDefinitionLoader;

$loader = new YamlDefinitionLoader();
$dumper = new CodeDumper();
$phpCode = $dumper->dump($loader->load('path/to/definition.yml'));
file_put_contents($destination, $phpCode);
```
