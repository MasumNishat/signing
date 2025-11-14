# Testing Infrastructure

This directory contains the testing infrastructure for the DocuSign API clone project.

## Requirements

- PHP 8.2+
- SQLite PDO extension (`pdo_sqlite`) - Required for in-memory database testing
- PostgreSQL (alternative for testing if SQLite not available)

## Installation

### SQLite PDO (Recommended)

For Ubuntu/Debian:
```bash
sudo apt-get install php-sqlite3
```

For MacOS:
```bash
# Usually included with PHP installation
```

Verify installation:
```bash
php -m | grep pdo_sqlite
```

## Running Tests

### All Tests
```bash
php artisan test
```

### Specific Test Suites
```bash
# Unit tests only
php artisan test --testsuite=Unit

# Feature tests only
php artisan test --testsuite=Feature

# Integration tests only
php artisan test --testsuite=Integration
```

### Without Code Coverage
```bash
php artisan test --no-coverage
```

### With Code Coverage (requires Xdebug or PCOV)
```bash
php artisan test --coverage
```

Coverage reports are generated in:
- HTML: `coverage/html/index.html`
- Text: `coverage/coverage.txt`
- Clover XML: `coverage/clover.xml`

## Test Structure

### Test Suites

- **Unit Tests** (`tests/Unit/`) - Test individual classes and methods in isolation
- **Feature Tests** (`tests/Feature/`) - Test API endpoints and feature functionality
- **Integration Tests** (`tests/Integration/`) - Test interactions between multiple components

### Base Test Classes

#### `tests/TestCase.php`
Base class for all tests with global setup/teardown hooks.

#### `tests/Feature/ApiTestCase.php`
Comprehensive base class for API testing with:
- Automatic database refresh and seeding
- Authentication helpers (`createAndAuthenticateUser()`, `actingAsUser()`)
- API request methods (`apiGet()`, `apiPost()`, `apiPut()`, `apiDelete()`)
- Response assertions (`assertSuccessResponse()`, `assertErrorResponse()`, `assertPaginatedResponse()`)

### Factories

Test data factories are available for:
- **Account** - `Account::factory()`
  - States: `suspended()`, `unlimited()`
- **User** - `User::factory()`
  - States: `admin()`, `inactive()`, `unverified()`
- **PermissionProfile** - `PermissionProfile::factory()`
  - States: `admin()`, `sender()`, `signer()`, `role(UserRole)`
- **ApiKey** - `ApiKey::factory()`
  - States: `revoked()`, `expired()`, `withScopes(array)`

### Usage Examples

#### Creating Test Users
```php
// Create default test user with account
$user = $this->createAndAuthenticateUser();

// Create admin user
$admin = $this->createAndAuthenticateUser(['is_admin' => true]);

// Create user with specific email
$user = $this->createAndAuthenticateUser(['email' => 'custom@example.com']);
```

#### Making Authenticated API Requests
```php
// GET request
$response = $this->apiGet('/api/v2.1/users');

// POST request
$response = $this->apiPost('/api/v2.1/envelopes', [
    'subject' => 'Test Envelope',
]);

// Response assertions
$response->assertStatus(200);
$this->assertSuccessResponse();
```

#### Using Factories
```php
// Create account with default data
$account = Account::factory()->create();

// Create suspended account
$account = Account::factory()->suspended()->create();

// Create admin user
$admin = User::factory()->admin()->create();

// Create multiple users
$users = User::factory()->count(10)->create();
```

## Configuration

### phpunit.xml

Key configuration:
- Database: SQLite in-memory (`:memory:`)
- Code coverage: HTML, Text, and Clover reports
- Test suites: Unit, Feature, Integration
- Strict testing: Enabled (fails on risky tests and warnings)

### Test Environment

The test environment uses:
- App environment: `testing`
- Database: SQLite in-memory
- Queue: `sync` (synchronous)
- Cache: `array` (in-memory)
- Session: `array` (in-memory)

## Best Practices

1. **Use RefreshDatabase trait** in feature/integration tests for clean database state
2. **Use factories** for test data generation instead of manual creation
3. **Extend ApiTestCase** for API endpoint tests to get authentication helpers
4. **Use descriptive test names** following convention: `test_<action>_<expected_result>`
5. **Test both success and failure cases** for each endpoint
6. **Use assertions provided by ApiTestCase** for consistent response validation
7. **Keep tests focused** - one test should verify one behavior
8. **Avoid test interdependencies** - each test should run independently

## CI/CD Integration

### GitHub Actions
```yaml
- name: Run Tests
  run: php artisan test --coverage --min=80
```

### Coverage Requirements
- Minimum coverage: 80% (can be configured)
- Unit tests: Target 95%+ coverage
- Feature tests: Target 90%+ coverage

## Troubleshooting

### "could not find driver" Error
This means SQLite PDO extension is not installed. Install `php-sqlite3` package.

### "No code coverage driver available"
Install Xdebug or PCOV extension for code coverage:
```bash
# Xdebug
sudo apt-get install php-xdebug

# PCOV (faster alternative)
sudo pecl install pcov
```

### Tests Running Slowly
- Use `--parallel` flag for parallel test execution
- Consider using PCOV instead of Xdebug for coverage
- Reduce database seeding in tests if possible

### Database Connection Issues in Tests
- Verify `DB_CONNECTION=sqlite` in `phpunit.xml`
- Ensure SQLite PDO extension is installed
- Check that `database.sqlite.database` is set to `:memory:`

## Resources

- [Laravel Testing Documentation](https://laravel.com/docs/testing)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Pest PHP](https://pestphp.com/) - Alternative testing framework (not currently used)
