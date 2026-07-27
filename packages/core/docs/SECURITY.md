# Security

Public endpoints accept only validated UID and region values. The media layer accepts item IDs, not arbitrary URLs. Credentials/tokens remain server-side. CORS must be configured by the host application rather than globally enabled by the core. The HTTP transport rejects redirects by default. Production controllers return generic upstream errors and report details only to protected server logs.

Report vulnerabilities privately to the maintainer instead of opening a public issue containing credentials or live tokens.
