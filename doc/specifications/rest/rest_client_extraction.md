# PHP REST Client

Make the PHP REST Client standalone. It must be usable, with or without Symfony2, in any PHP app that matches the requirements.

## Requires

### Files
- `eZ/Publish/Core/FieldType/*/Type`
- `eZ/Publish/Core/REST/Common`
- `eZ/Publish/Core/REST/Client`
- `eZ/Publish/Core/REST/common.php`

### Libraries
- `ezsystems/ezpublish-api`
- `ezsystems/ezpublish-rest-common` (see below)
- `symfony/dependency-injection` (if we decide to use it)
- `symfony/config` + `symfony/yaml` OR we use a PHP container definition ?
- `symfony/http-foundation` (used in `ezpublish-rest-common`)
- `guzzle/guzzle` (REST client)

## Done

### Package for ezpublish-rest-common
Created a split export, with no dependencies, for the `eZ\Publish\Core\REST\Common` namespace.

### Guzzle REST client
Added a REST Client implementation based on Guzzle.

### RepositoryFactory
Added `eZ\Publish\Core\REST\Client\RepositoryFactory`, with a `create()` method, to instanciate a Repository with an URL and HTTP auth credentials. Takes care of bootstrapping and DI.

### Ideas to follow-up on
- split `eZ/Publish/Core/REST/Common` into a distinct repository (ezpublish-rest-common), so that ezpublish-rest-client can depend on it => DONE
- `ezpublish-rest-client-bundle` and `ezpublish-rest-common-bundle`, with their own definitions for services.
  - should we split out `EzPublishRestBundle` into Client, Server and Common as well ? Probably. In that case they can also be mirrored/split
- How do we handle dependency injection/bootstrap of repository when not in a bundle ? Taken care of by a factory ? Works, but dual maintenance with the bundle's services definitions, is it not ?
- The REST client seems to depend on `eZ\Publish\Core\FieldType\*\Type`, presumably in regards to the `fromHash()` method. How do we handle this ? Anything in `eZ/Publish` we could export as a sane, reusable dependency ?
- prefix... IMHO, should NOT be exposed. We should use `loadContent(59)`, not `loadContent('/content/objects/59')`
