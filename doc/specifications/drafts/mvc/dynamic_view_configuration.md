# Dynamic view configuration

As view configuration is based on container configuration, any change will recompile
the container. This operation takes several seconds, and is painful for developer
experience.

A second view provider, that instead of reading from the container, reads from a more
restricted set of files on runtime instead of compilation time.

## Usage context
It would only be enabled in 'dev' mode, using container configuration:

```yaml
ezplatform:
    dynamic_view_configuration:
        enabled: true
        dynamic_files:
            'app/config/views.yml'
```

Files listed in `dynamic_files` would not be tracked for changes by the container.

Instead, when view configuration is matched, the contents of the dynamic files will
be merged with the configuration loaded from the container.

### Implementation
A DynamicConfigurable ViewMatcher is added, that inherits from `eZ\Publish\Core\MVC\Symfony\Matcher\ClassNameMatcherFactory`.
It uses a new service, that loads the YAML configuration from the dynamic files. It uses the same API that is used
to parse siteaccess aware config.

Controller defined by convention as well as templates should not need to recompile the container.
