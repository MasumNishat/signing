# Authentication Guide

## Two Authentication Systems

Your application has **two separate authentication systems** for different use cases:

### 1. 🌐 Web Authentication (Session-based) - For Login Interface
**Use for:** Users accessing your website through a browser

### 2. 🔌 API Authentication (OAuth 2.0) - For External Access
**Use for:** Mobile apps, third-party integrations, API clients

---

## 🌐 Web Authentication (Session-based)

### How It Works
```
User fills login form → Laravel validates credentials → Creates session cookie → User stays logged in
```

### When to Use
- ✅ Regular website login page
- ✅ User dashboard
- ✅ Web application interface
- ✅ Browser-based access

### Implementation

**1. Login Flow**
```html
<!-- Login Form -->
<form method="POST" action="/login">
    @csrf
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <input type="checkbox" name="remember"> Remember Me
    <button type="submit">Login</button>
</form>
```

**Controller Method:**
```php
public function login(Request $request)
{
    // Validates credentials and creates session
    if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        $request->session()->regenerate();
        return redirect()->intended('dashboard');
    }

    throw ValidationException::withMessages([
        'email' => ['The provided credentials do not match our records.'],
    ]);
}
```

**2. Registration Flow**
```html
<!-- Register Form -->
<form method="POST" action="/register">
    @csrf
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <input type="password" name="password_confirmation" required>
    <button type="submit">Register</button>
</form>
```

**3. Logout Flow**
```html
<!-- Logout Button -->
<form method="POST" action="/logout">
    @csrf
    <button type="submit">Logout</button>
</form>
```

**4. Protected Pages**
```php
// All routes in web.php with auth middleware are protected
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/envelopes', [EnvelopeController::class, 'index']);
    // etc...
});
```

### Available Routes

**Guest Routes (Unauthenticated):**
```
GET  /login                 → Show login form
POST /login                 → Handle login (creates session)
GET  /register              → Show registration form
POST /register              → Handle registration
GET  /forgot-password       → Show forgot password form
POST /forgot-password       → Send reset link
GET  /reset-password/{token} → Show reset password form
POST /reset-password        → Reset password
```

**Authenticated Routes:**
```
POST /logout                → Logout (destroy session)
GET  /dashboard             → Dashboard
GET  /envelopes             → All envelope pages
GET  /templates             → All template pages
... (all 56 web pages)
```

### How Session Works

1. **Login:** Server creates encrypted session cookie
2. **Requests:** Browser sends cookie automatically
3. **Validation:** Laravel checks cookie on each request
4. **Logout:** Session destroyed

**No tokens needed!** The browser handles everything automatically.

---

## 🔌 API Authentication (OAuth 2.0)

### How It Works
```
App requests token → OAuth server validates → Returns access token → App uses token for API calls
```

### When to Use
- ✅ Mobile applications (iOS, Android)
- ✅ Third-party integrations
- ✅ JavaScript SPAs (separate frontend)
- ✅ Server-to-server communication
- ✅ Postman/cURL testing

### Grant Types

#### 1. Client Credentials (Server-to-Server)
```bash
# Request token
curl -X POST http://localhost:8000/oauth/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=client_credentials" \
  -d "client_id=2" \
  -d "client_secret=YOUR_SECRET" \
  -d "scope=envelopes.read"

# Response
{
    "token_type": "Bearer",
    "expires_in": 3600,
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}

# Use token
curl -X GET http://localhost:8000/api/v2.1/accounts/1/envelopes \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

#### 2. Authorization Code (Web Apps)
```bash
# Step 1: Redirect user to authorize
https://localhost:8000/oauth/authorize?client_id=2&redirect_uri=YOUR_CALLBACK&response_type=code&scope=envelopes.read

# Step 2: User approves

# Step 3: Exchange code for token
curl -X POST http://localhost:8000/oauth/token \
  -d "grant_type=authorization_code" \
  -d "client_id=2" \
  -d "client_secret=YOUR_SECRET" \
  -d "redirect_uri=YOUR_CALLBACK" \
  -d "code=AUTH_CODE_FROM_STEP_2"
```

#### 3. Password Grant (First-party apps only)
```bash
curl -X POST http://localhost:8000/oauth/token \
  -d "grant_type=password" \
  -d "client_id=2" \
  -d "client_secret=YOUR_SECRET" \
  -d "username=user@example.com" \
  -d "password=password" \
  -d "scope=envelopes.read envelopes.write"
```

#### 4. Refresh Token (Renew expired token)
```bash
curl -X POST http://localhost:8000/oauth/token \
  -d "grant_type=refresh_token" \
  -d "refresh_token=YOUR_REFRESH_TOKEN" \
  -d "client_id=2" \
  -d "client_secret=YOUR_SECRET"
```

### API Endpoints

All API endpoints require the `Authorization` header:

```http
GET /api/v2.1/accounts/{accountId}/envelopes
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**358 API endpoints available** - see `docs/OAUTH-SETUP.md` for complete list.

---

## Comparison Table

| Feature | Web (Session) | API (OAuth) |
|---------|---------------|-------------|
| **Authentication** | Username + Password | Client ID + Secret |
| **Storage** | Server session | Access token (JWT) |
| **Expires** | Configurable (default 2 hours) | 1 hour (configurable) |
| **Remember Me** | ✅ Yes | ❌ Use refresh tokens |
| **Auto-refresh** | ✅ Automatic | ⚠️ Manual (refresh token) |
| **CSRF Protection** | ✅ Required | ❌ Not needed |
| **Use Case** | Human users | Programmatic access |
| **Setup Complexity** | Simple | Moderate |
| **Security** | Cookie-based | Token-based |

---

## Common Scenarios

### Scenario 1: User Logs into Website ✅ Use Web Auth
```php
// AuthController.php
public function login(Request $request)
{
    if (Auth::attempt($request->only('email', 'password'))) {
        return redirect('dashboard'); // Session created automatically
    }

    return back()->withErrors(['email' => 'Invalid credentials']);
}
```

**Result:** User gets session cookie, stays logged in until logout/expiration

---

### Scenario 2: Mobile App Needs API Access ✅ Use OAuth
```javascript
// Mobile App (React Native / Flutter)
const response = await fetch('https://api.example.com/oauth/token', {
  method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
  body: new URLSearchParams({
    grant_type: 'password',
    client_id: '2',
    client_secret: 'secret',
    username: 'user@example.com',
    password: 'password',
    scope: 'envelopes.read'
  })
});

const { access_token } = await response.json();

// Store token
localStorage.setItem('access_token', access_token);

// Use token for API calls
const envelopes = await fetch('https://api.example.com/api/v2.1/accounts/1/envelopes', {
  headers: { 'Authorization': `Bearer ${access_token}` }
});
```

---

### Scenario 3: JavaScript SPA (Vue/React) on Same Domain ✅ Use Web Auth

If your SPA is on the **same domain**, you can use session auth:

```javascript
// Login via session
await axios.post('/login', {
  email: 'user@example.com',
  password: 'password'
});

// Session cookie is set automatically
// No need to manage tokens!

// Future requests work automatically
const envelopes = await axios.get('/api/v2.1/accounts/1/envelopes');
// Laravel uses session cookie automatically
```

---

### Scenario 4: Third-party Integration ✅ Use OAuth (Client Credentials)
```bash
# Integration partner gets client credentials
CLIENT_ID=2
CLIENT_SECRET=abc123...

# They request token
curl -X POST https://api.yourapp.com/oauth/token \
  -d "grant_type=client_credentials" \
  -d "client_id=$CLIENT_ID" \
  -d "client_secret=$CLIENT_SECRET" \
  -d "scope=envelopes.read"

# Use token for all API requests
curl -X GET https://api.yourapp.com/api/v2.1/accounts/1/envelopes \
  -H "Authorization: Bearer $ACCESS_TOKEN"
```

---

## Configuration

### Session Configuration (config/session.php)
```php
return [
    'driver' => 'file',            // Session storage
    'lifetime' => 120,             // 2 hours
    'expire_on_close' => false,    // Keep session after browser close
    'encrypt' => true,             // Encrypt session data
    'same_site' => 'lax',          // CSRF protection
];
```

### OAuth Configuration (bootstrap/app.php)
```php
use Laravel\Passport\Passport;

Passport::tokensExpireIn(now()->addHours(1));              // 1 hour
Passport::refreshTokensExpireIn(now()->addDays(14));       // 14 days
Passport::personalAccessTokensExpireIn(now()->addMonths(6)); // 6 months
```

---

## Security Best Practices

### Web Authentication
1. ✅ Always use HTTPS in production
2. ✅ Enable CSRF protection (automatic in Laravel)
3. ✅ Use `@csrf` directive in all forms
4. ✅ Set secure session cookies
5. ✅ Implement rate limiting on login
6. ✅ Use strong password requirements

### API Authentication
1. ✅ Always use HTTPS in production
2. ✅ Store client secrets securely (never in frontend)
3. ✅ Use short token lifetimes
4. ✅ Implement token refresh flow
5. ✅ Use specific scopes (principle of least privilege)
6. ✅ Revoke tokens when no longer needed

---

## Troubleshooting

### "401 Unauthenticated" on Web Pages
**Problem:** User not logged in or session expired
**Solution:** Redirect to `/login` page

### "unsupported_grant_type" on OAuth
**Problem:** Database not set up or wrong grant type
**Solution:** Run `php artisan migrate && php artisan passport:install`

### "invalid_client" on OAuth
**Problem:** Wrong client_id or client_secret
**Solution:** Check credentials from `passport:install` output

### Session not persisting
**Problem:** Cookie not being set
**Solution:**
- Check `SESSION_DOMAIN` in `.env`
- Ensure `session.php` configuration is correct
- Clear browser cookies

### CORS errors on API
**Problem:** Frontend on different domain
**Solution:** Configure CORS in `config/cors.php`

---

## Quick Reference

### Login via Web Interface
```html
<form method="POST" action="/login">
    @csrf
    <input name="email" type="email">
    <input name="password" type="password">
    <button>Login</button>
</form>
```
**Result:** Session cookie created, user redirected to dashboard

### Login via API
```bash
curl -X POST /oauth/token \
  -d "grant_type=password" \
  -d "client_id=2" \
  -d "client_secret=secret" \
  -d "username=user@example.com" \
  -d "password=password"
```
**Result:** Access token returned, use in `Authorization` header

---

## Summary

- 🌐 **Web Login Interface** → Use **Session Authentication** (AuthController)
- 🔌 **API Access** → Use **OAuth 2.0** (Passport)
- 📱 **Mobile Apps** → Use **OAuth 2.0**
- 🔗 **Same-domain SPA** → Use **Session Authentication**
- 🤝 **Third-party Integration** → Use **OAuth 2.0 (Client Credentials)**

**Your login page uses sessions - no OAuth tokens needed!**
