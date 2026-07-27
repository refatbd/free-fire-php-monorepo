# OB54 protocol recovery notes

The canonical schemas in this directory were reconstructed from the generated modules in the original `refatbd/FreeFireInfoSite` repository:

- `FreeFire_pb2.py` → `LegacyLogin.proto`
- `main_pb2.py` → `PlayerRequest.proto`
- `AccountPersonalShow_pb2.py` → `AccountPersonalShow.proto`

`AccountPersonalShow.proto` intentionally follows the original descriptor rather than the newer community reference schema. Important differences include:

- `GuildWarTitleInfo.clan_name` is field **8** in the original descriptor.
- `AvatarProfile.equiped_skills` is field **5** in the original descriptor.
- `AccountInfoBasic` ends at field **61** in the original descriptor.
- `AccountPersonalShowInfo` ends at field **13** in the original descriptor.

The community files under `protocol/reference/` are research inputs only and are excluded from `composer proto:generate`.

The remaining release gate is runtime validation against sanitized real OB54 response bytes. Field-map recovery does not by itself prove that current upstream servers still speak OB54.


## Canonical versioning note

The original descriptors used unversioned Protobuf package/file identities. The canonical PHP schemas intentionally use OB54-versioned Protobuf packages, PHP namespaces and metadata namespaces. This prevents generated descriptor/class collisions when OB55 or later schemas are installed beside OB54. Protobuf package/name metadata does not change the encoded wire payload; the recovered field numbers, labels and wire types remain the compatibility source of truth.
