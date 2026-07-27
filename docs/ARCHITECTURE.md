# Architecture

Dependency direction is one-way:

```text
Starter application -> Laravel package -> Framework-independent core
```

Core logic, credentials, protocol profiles, encryption, Protobuf, token acquisition, regions, and media policies live only in `packages/core`. Laravel contains adapters. Starter contains UI and application composition. No core file may be copied into Laravel or starter.


## Versioned protocol boundary

Every OB release has its own core profile, canonical schema directory and generated namespace. The selected profile supplies request constants, endpoints, response message class and media cache namespace. Laravel automatically merges core built-ins with optional custom overrides and selects a profile; the starter only consumes the Laravel API.
