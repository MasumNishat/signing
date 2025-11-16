# OAuth 2.0 Setup Guide

## Current Status

✅ **Passport Keys Generated**
- `storage/oauth-private.key` - Encryption key created
- `storage/oauth-public.key` - Public key created

⚠️ **Database Required**
- PostgreSQL must be running before OAuth can work
- OAuth clients are stored in the database

## Error: "unsupported_grant_type"

This error means:
1. Database tables don't exist yet (run migrations first)
2. OAuth clients haven't been created
3. The requested grant type isn't configured

## Setup Steps

### 1. Start PostgreSQL
```bash
# Make sure PostgreSQL is running on localhost:5432
# Database: signing_api
# User: postgres
```

### 2. Run Migrations
```bash
php artisan migrate --force
```

This creates the required Passport tables:
- `oauth_access_tokens`
- `oauth_auth_codes`
- `oauth_clients`
- `oauth_personal_access_clients`
- `oauth_refresh_tokens`

### 3. Install Passport
```bash
php artisan passport:install --force
```

This creates:
- Personal Access Client (for `password` grant)
- Password Grant Client (for `client_credentials` grant)

### 4. (Optional) Create Additional Clients
```bash
# Create a personal access token client
php artisan passport:client --personal --name="Mobile App"

# Create a password grant client
php artisan passport:client --password --name="Web Application"

# Create a client credentials client
php artisan passport:client --client
```

## Supported Grant Types

According to your OpenAPI spec (`docs/openapi.json`), the following grant types are supported:

### 1. Authorization Code (`authorization_code`)
**Best for:** Web applications with backend

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&redirect_uri=YOUR_REDIRECT_URI
&code=AUTHORIZATION_CODE
```

### 2. Client Credentials (`client_credentials`)
**Best for:** Server-to-server authentication

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&scope=OPTIONAL_SCOPES
```

### 3. Refresh Token (`refresh_token`)
**Best for:** Refreshing expired access tokens

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token
&refresh_token=YOUR_REFRESH_TOKEN
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
```

### 4. Password Grant (⚠️ Deprecated in OAuth 2.1)
**Use only for first-party apps**

```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=password
&client_id=YOUR_CLIENT_ID
&client_secret=YOUR_CLIENT_SECRET
&username=user@example.com
&password=user_password
&scope=OPTIONAL_SCOPES
```

## Token Lifetimes

Configured in `bootstrap/app.php`:

```php
Passport::tokensExpireIn(now()->addHours(1));      // 1 hour
Passport::refreshTokensExpireIn(now()->addDays(14)); // 14 days
Passport::personalAccessTokensExpireIn(now()->addMonths(6)); // 6 months
```

## Available Scopes

Your application supports 26 OAuth scopes:

**Envelope Operations:**
- `envelopes.read` - View envelopes
- `envelopes.write` - Create/update envelopes
- `envelopes.delete` - Delete envelopes

**Template Operations:**
- `templates.read` - View templates
- `templates.write` - Create/update templates

**Document Operations:**
- `documents.read` - View documents
- `documents.write` - Upload/modify documents

**Account Management:**
- `accounts.read` - View account information
- `accounts.write` - Modify account settings

**User Management:**
- `users.read` - View users
- `users.write` - Create/update users

**Billing:**
- `billing.read` - View billing information
- `billing.write` - Process payments

**Recipients:**
- `recipients.read` - View recipients
- `recipients.write` - Manage recipients

**Branding:**
- `branding.read` - View branding
- `branding.write` - Modify branding

**Connect/Webhooks:**
- `connect.read` - View webhooks
- `connect.write` - Configure webhooks

**Signatures:**
- `signatures.read` - View signatures
- `signatures.write` - Create signatures

**Admin:**
- `admin` - Full administrative access

## Example: Complete OAuth Flow

### Step 1: Get Authorization Code
```http
GET /oauth/authorize?client_id=CLIENT_ID&redirect_uri=REDIRECT_URI&response_type=code&scope=envelopes.read+envelopes.write
```

### Step 2: Exchange Code for Token
```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=authorization_code&client_id=CLIENT_ID&client_secret=CLIENT_SECRET&redirect_uri=REDIRECT_URI&code=AUTH_CODE
```

### Step 3: Use Access Token
```http
GET /api/v2.1/accounts/{accountId}/envelopes
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Step 4: Refresh Token When Expired
```http
POST /oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=refresh_token&refresh_token=YOUR_REFRESH_TOKEN&client_id=CLIENT_ID&client_secret=CLIENT_SECRET
```

## Testing with cURL

### Get Client Credentials Token
```bash
curl -X POST http://localhost:8000/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "scope=envelopes.read"
```

### Get Password Grant Token
```bash
curl -X POST http://localhost:8000/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password" \
  -d "client_id=YOUR_CLIENT_ID" \
  -d "client_secret=YOUR_CLIENT_SECRET" \
  -d "username=user@example.com" \
  -d "password=password" \
  -d "scope=envelopes.read envelopes.write"
```

### Use Token to Access API
```bash
curl -X GET http://localhost:8000/api/v2.1/accounts/1/envelopes \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN"
```

## Troubleshooting

### "unsupported_grant_type"
**Cause:** Database not set up or client doesn't exist
**Solution:** Run migrations and `passport:install`

### "invalid_client"
**Cause:** Wrong client_id or client_secret
**Solution:** Check credentials from `passport:install` output or database

### "invalid_grant"
**Cause:** Wrong username/password or expired authorization code
**Solution:** Verify credentials or request new authorization code

### "invalid_scope"
**Cause:** Requested scope not available
**Solution:** Check available scopes list above

### "unauthenticated"
**Cause:** Missing or invalid access token
**Solution:** Include `Authorization: Bearer TOKEN` header

## Security Best Practices

1. ✅ **Keys Generated** - Unique per environment
2. ⚠️ **HTTPS Required** - Use TLS in production
3. ⚠️ **Secure Storage** - Never commit client secrets to git
4. ⚠️ **Short Lifetimes** - Access tokens expire in 1 hour
5. ⚠️ **Scope Limitation** - Request minimum required scopes
6. ⚠️ **PKCE Recommended** - Use PKCE for public clients

## Next Steps

1. Start PostgreSQL server
2. Run `php artisan migrate`
3. Run `php artisan passport:install`
4. Save the client credentials
5. Test with cURL or Postman
6. Update your frontend to use OAuth flow

## References

- [Laravel Passport Documentation](https://laravel.com/docs/12.x/passport)
- [OAuth 2.0 Specification](https://oauth.net/2/)
- [OAuth 2.1 Draft](https://oauth.net/2.1/)
