## A few design decisions

### Dependencies explained

The project relies on several key dependencies:

- **PHP**: `^8.3` - The latest PHP version compatible with the project.
- **cuyz/valinor**: `^1.12` - Facilitates writing strongly-typed DTOs from loosely-typed sources.
- **delolmo/valinor-console**: `^1.7.1` - Allows using console inputs as sources for cuyz/valinor.
- **doctrine/orm**: `^3.2` - The Object-Relational Mapper (ORM) of the library.
- **giggsey/libphonenumber-for-php**: `^8.13` - To validate phone numbers in Doctrine embeddables.
- **nikic/php-parser**: `^4.19` - To parse PHP files within some library tools.
- **pragmarx/google2fa**: `^8.0` - To generate secret key for 2FA authentication.
- **psr/container**: `^2.0` - Used for a basic ContainerInterface implementation that ensures reusability.
- **ramsey/uuid**: `^4.7` - Enables creation of UUIDs as primary keys for entities.
- **ramsey/uuid-doctrine**: `^2.1` - Adds 'uuid' and 'uuid_binary' Doctrine types.
- **simpod/doctrine-utcdatetime**: `^0.3` - Converts all DateTime objects to the UTC timezone.
- **symfony/cache**: `^7.0` - Required by doctrine/orm.
- **symfony/console**: `^7.0` - Used to manage the command-line interface.
- **webmozart/assert**: `^1.11` - To perform basic assertions in Doctrine embeddables.

### Entities

- **Standalone Objects**: All entities are single, standalone objects, valid from the moment of instantiation.
- **UUID Primary Keys**: Primary keys are UUIDs for consistency and performance.
- **Unidirectional Associations**: No bidirectional associations are allowed to enhance performance.
- **Unique Fields**: Each entity must have a unique field in addition to the primary key to facilitate querying.
- **Date/Time Fields**: Should always be of type `\DateTimeImmutable` and shifted to UTC. This is automatically managed by overriding the default 'datetime_immutable' field.
- **Class and Method Constraints**: Entity classes cannot be `final`, but, by default, all entity methods should be `final` and `private`, and entity properties should be `readonly`. Deviations must be justified by business logic.

### Commands

- **Data Transfer Objects (DTOs)**: Commands are DTOs processed by Handlers. All Commands should extend `DelOlmoPro\Command\Command` and be declared `final` and `readonly`.
- **Entity Passing**: If an entity is passed through a query, the Command object should pass one of the entity's primary keys.
- **Attributes**: console commands, arguments and options are configured through the `Attribute\AsCommand`, `Attribute\AsArgument` and `Attribute\AsOption` annotation classes, respectively. The `CommandReader` is able to read through these attributes and configure the root console `Command` with this information.

### Handlers

- **Single Responsibility**: Handlers should perform only one function and accept a single Command in the __invoke function.
- **Dependencies**: Handler dependencies should be loaded using dependency injection.
- **Final Class**: Handlers should be declared `final`.

### Exceptions

- **Root Exception**: All exceptions inherit from `DelOlmoPro\Exception\Exception`, which extends `\Exception`.
- **Specialized Exceptions**: `EntityNotFound` and `EntityAlreadyExists` exceptions are defined for common use cases.
- **Generic Commands**: Exception querys are kept generic to ensure the library's role in preserving data integrity.

### Dependency injection

This library includes a very simple implementation of the `Psr\Container\ContainerInterface` to manage dependencies. Basically, services get registered through the `set` method - and that is that.

The whole idea is to **not** boostrap the project through a custom `Psr\Container\ContainerInterface`, but to allow others to integrate the library into other projects that use their own dependency injection components.

Internally, some utility classes have been provided to facilitate the task of defining services:

- `CommandLoader`: Loads console commands from a directory and adds them to the Symfony Console Application.
- `DescriptorLoader`: Loads descriptors from a directory and registers them in the dependency container. Assumes descriptors don't have any dependencies.
- `DocumentLoader`: Nothing more than a DocumentManaget factory that allows adding several entity paths and adding document prefixes on a per namespace basis.
- `EntityLoader`: Nothing more than an EntityManager factory that allows adding several entity paths and adding table prefixes on a per namespace basis.
- `HandlerLoader`: Loads query handlers from a directory and registers them in the dependency container. Handler arguments are loaded from the container using simple autowiring.

Unfortunately, the `HandlerLoader` and `DescriptorLoader` classes uses the specific implementation of the Container because it requires that `set` method. However, both handlers and descriptors may be easily autowired when using other dependency injection libraries.

The `CommandLoader`, however, is more flexible. To load the library's commands to your own app:

1) The CommandLoader reads attributes from the Commands to configure a one-stop command for the entire library.

```php

$reader = new CommandReader($container); // pass on the PSR-11 compliant container of your choosing
$reader->load('./src'); // specify the directory on which to look for Command objects

```

2) Make sure the PSR-11 compliant container includes the definition for every handler and descriptor. The one-stop command will try to load them from the container.
3) Check the command execution workflow using the verbosity modifiers (`-vvv`).