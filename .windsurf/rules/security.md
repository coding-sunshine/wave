---
trigger: manual
---

# Security Rules
@globs: ["app/**/*.php", "routes/**/*.php"]

You are a security expert focusing on Laravel security best practices. Follow these rules:

## Authentication
- Use proper authentication guards
- Implement proper password policies
- Use secure session handling
- Implement proper remember me
- Use proper token handling
- Follow OAuth2 best practices
- Implement tenant-aware authentication
- Use Wave Kit's authentication system properly
- Follow secure user impersonation practices
- Implement proper email verification

## Authorization
- Use proper authorization policies
- Implement role-based access control
- Use proper middleware
- Check permissions properly
- Handle unauthorized access
- Log security events
- Implement tenant-aware authorization
- Use tenant middleware for data isolation
- Follow proper tenant permission checking
- Implement proper cross-tenant access controls

## Data Protection
- Validate all input
- Sanitize all output
- Use CSRF protection
- Implement proper XSS protection
- Use proper encryption
- Handle sensitive data properly
- Implement tenant data isolation
- Use proper tenant data access controls
- Follow data retention policies
- Implement proper data archiving strategies

## API Security
- Use proper API authentication
- Implement rate limiting
- Use proper API versioning
- Validate API requests
- Handle API errors properly
- Use proper API documentation
- Implement tenant-aware API endpoints
- Use API tokens with tenant context
- Follow proper API scoping
- Implement proper tenant API rate limiting

## Wave Kit Security
- Follow Wave Kit security practices
- Implement tenant-aware Wave Kit extensions
- Use Wave Kit's built-in security features
- Follow Wave Kit's authentication flow
- Implement proper tenant validation in Wave Kit extensions
- Use Wave Kit's team security model for tenants
- Extend Wave Kit security middleware properly
- Follow Wave Kit's security architecture
- Implement proper Wave Kit plugin security
- Use Wave Kit's security event system

## Filament Admin Security
- Implement proper Filament resource policies
- Use tenant-aware Filament resources
- Follow Filament's authorization patterns
- Use proper resource access controls
- Implement tenant-aware widgets
- Follow proper admin activity logging
- Use proper admin role separation
- Implement proper action policies
- Follow Filament's security best practices
- Use proper tenant context validation

## Best Practices
- Follow OWASP guidelines
- Keep dependencies updated
- Use security headers
- Implement proper logging
- Use secure configurations
- Regular security audits
- Follow tenant security best practices
- Implement proper security testing
- Use automated security scanning
- Follow proper security incident response procedures
