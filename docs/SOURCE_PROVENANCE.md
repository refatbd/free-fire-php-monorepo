# Source provenance

The conversion was based on the public Python project:

```text
https://github.com/refatbd/FreeFireInfoSite
```

The legacy generated modules `FreeFire_pb2.py`, `main_pb2.py` and `AccountPersonalShow_pb2.py` were used as protocol-recovery inputs. Their recovered canonical schemas now live in `packages/core/protocol/ob54/` and are guarded by source/golden tests.

Credential-bearing legacy Python copies are intentionally not retained in this monorepo. Required bundled accounts exist only in `packages/core/src/Credentials/BundledCredentialProvider.php`; OB protocol constants exist only in versioned core profiles. Community/reference schemas remain under `packages/core/protocol/reference/` and are excluded from generation.
