# Maintainer checklist

## Protocol/OB

- [ ] New OB/build identity confirmed from a controlled client
- [ ] Capture record follows `packages/core/docs/OB_PROTOCOL_CAPTURE.md`
- [ ] Old profile retained; new profile added instead of overwritten
- [ ] New profile registered in core `BuiltInProtocolProfiles` and selectable through `FREEFIRE_PROTOCOL`
- [ ] Protocol constants, headers, endpoints and region gateways reviewed
- [ ] Descriptor/schema diff completed
- [ ] Protobuf package, PHP namespace and metadata namespace include the new OB version
- [ ] Profile points to its own generated player-response class
- [ ] Request and encryption golden fixtures updated
- [ ] Default credential groups health-checked
- [ ] Credential pairs are complete per scope and rotation follows `ACCOUNT_CREDENTIAL_CAPTURE.md`
- [ ] All supported regions tested
- [ ] Sanitized live evidence matrix completed from `LIVE_PROTOCOL_VERIFICATION.md`
- [ ] Player response normalization compatibility tested
- [ ] Official CDN, ASTC and OB-scoped media cache tested

## Quality/release

- [ ] Secret-redaction and unsafe-cache tests passed
- [ ] Core/Laravel/starter compatibility reviewed
- [ ] Documentation, progress and changelog updated
- [ ] `composer test:docs` passed and maintenance links resolve
- [ ] Correct semantic version selected
- [ ] Local split simulation passed
- [ ] Composer/generated Protobuf/Laravel/media CI passed
- [ ] Destination repositories and immutable tags verified
