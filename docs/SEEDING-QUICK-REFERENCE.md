# Seeding Quick Reference Guide

**Quick access guide for database seeding tasks**

---

## Quick Commands

```bash
# Refresh database and seed everything
php artisan migrate:fresh --seed

# Seed without refreshing
php artisan db:seed

# Seed specific seeder
php artisan db:seed --class=EnvelopeSeeder

# Create new factory
php artisan make:factory EnvelopeFactory --model=Envelope

# Create new seeder
php artisan make:seeder EnvelopeSeeder

# Test in Tinker
php artisan tinker
>>> Envelope::factory()->count(5)->create()
>>> Envelope::factory()->withDocuments()->create()
```

---

## Current Status

### ✅ Completed (12 items)

**Seeders (8):**
1. FileTypeSeeder - 23 file types
2. SupportedLanguageSeeder - 20 languages
3. SignatureProviderSeeder - 3 providers
4. PlanSeeder - 4 subscription plans
5. AccountSeeder - 2 demo accounts
6. PermissionProfileSeeder - 3 permission profiles
7. UserSeeder - 3 users
8. DatabaseSeeder - Main orchestrator

**Factories (4):**
1. AccountFactory
2. UserFactory
3. PermissionProfileFactory
4. ApiKeyFactory

### ⏳ To Create (109 items)

- **62 Factories** (66 total - 4 existing)
- **47 Seeders** (55 total - 8 existing)

---

## Priority Order

### 🔴 CRITICAL (Must do first)
1. **Phase S2**: Core Infrastructure (12 models) - 16h
   - Users, Accounts, API Keys, Permissions
2. **Phase S3**: Envelopes Module (14 models) - 24h
   - Envelopes, Documents, Recipients, Tabs

### 🟡 HIGH (Do next)
3. **Phase S4**: Templates & Documents (6 models) - 10h
4. **Phase S5**: Recipients & Routing (5 models) - 8h
5. **Phase S1**: Reference Data (8 models) - 8h

### 🟢 MEDIUM (Important but can wait)
6. **Phase S6**: Billing & Payments (5 models) - 8h
7. **Phase S7**: Branding & Customization (8 models) - 10h
8. **Phase S8**: Bulk Operations (3 models) - 6h
9. **Phase S12**: Signatures & Seals (4 models) - 6h

### ⚪ LOW (Nice to have)
10. **Phase S9**: Connect & Webhooks (4 models) - 6h
11. **Phase S10**: Workspaces & Folders (3 models) - 4h
12. **Phase S11**: PowerForms (2 models) - 3h
13. **Phase S13**: Logging & Diagnostics (2 models) - 3h

---

## Dependency Quick Reference

### Level 1 - No Dependencies
Start here first:
- FileType, SupportedLanguage, SignatureProvider
- IdentityVerificationWorkflow, TabSetting
- NotificationDefault, PasswordRule

### Level 2 - Depends on Level 1
- Plan, BillingPlan, AccountSettings

### Level 3 - Depends on Level 2
- Account, PermissionProfile

### Level 4 - Depends on Level 3
- User, Brand, Folder, SigningGroup, UserGroup, Workspace

### Level 5+ - Depends on Level 4
Everything else (Envelopes, Templates, etc.)

---

## Factory Template (Copy & Paste)

```php
<?php

namespace Database\Factories;

use App\Models\ModelName;
use Illuminate\Database\Eloquent\Factories\Factory;

class ModelNameFactory extends Factory
{
    protected $model = ModelName::class;

    public function definition(): array
    {
        return [
            // UUID (if model uses UUIDs)
            'uuid' => $this->faker->uuid(),

            // Foreign keys
            'account_id' => Account::factory(),
            'user_id' => User::factory(),

            // Basic fields
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'status' => $this->faker->randomElement(['active', 'inactive']),

            // JSON fields
            'metadata' => [],

            // Timestamps (auto-handled by Laravel)
        ];
    }

    /**
     * State: Active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Relationship: For specific account
     */
    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }
}
```

---

## Seeder Template (Copy & Paste)

```php
<?php

namespace Database\Seeders;

use App\Models\ModelName;
use App\Models\Account;
use Illuminate\Database\Seeder;

class ModelNameSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Seeding ModelName...');

        // Option 1: Simple seeding
        ModelName::factory()->count(10)->create();

        // Option 2: Relationship-based seeding
        Account::all()->each(function ($account) {
            ModelName::factory()
                ->count(5)
                ->forAccount($account)
                ->create();
        });

        // Option 3: Specific records
        ModelName::create([
            'name' => 'Specific Record',
            'status' => 'active',
        ]);

        $this->command->info('✅ ModelName seeded successfully!');
    }
}
```

---

## Common Faker Methods

```php
// Text
$this->faker->name()
$this->faker->firstName()
$this->faker->lastName()
$this->faker->company()
$this->faker->jobTitle()
$this->faker->sentence()
$this->faker->paragraph()
$this->faker->text(200)

// Numbers
$this->faker->randomNumber()
$this->faker->numberBetween(1, 100)
$this->faker->randomFloat(2, 0, 1000) // 2 decimals

// Email & Internet
$this->faker->email()
$this->faker->safeEmail()
$this->faker->unique()->safeEmail()
$this->faker->url()
$this->faker->domainName()
$this->faker->ipv4()

// Dates
$this->faker->dateTime()
$this->faker->dateTimeBetween('-1 year', 'now')
$this->faker->date()
$this->faker->time()

// Address
$this->faker->address()
$this->faker->streetAddress()
$this->faker->city()
$this->faker->state()
$this->faker->postcode()
$this->faker->country()

// Phone
$this->faker->phoneNumber()

// Random Selection
$this->faker->randomElement(['option1', 'option2', 'option3'])
$this->faker->randomElements(['a', 'b', 'c'], 2) // Returns array of 2

// Boolean
$this->faker->boolean() // 50% true, 50% false
$this->faker->boolean(70) // 70% true, 30% false

// UUID
$this->faker->uuid()

// File paths (not real files)
$this->faker->filePath()
$this->faker->imageUrl(640, 480)

// Lorem Ipsum
$this->faker->word()
$this->faker->words(3, true) // Returns string of 3 words
$this->faker->sentence()
$this->faker->sentences(3, true)
```

---

## Testing Checklist

### Before Seeding
- [ ] Database is backed up (if production)
- [ ] Migrations are up to date
- [ ] .env is configured correctly

### After Seeding
- [ ] All seeders completed without errors
- [ ] Record counts match expectations
- [ ] Relationships are properly linked
- [ ] No foreign key constraint errors
- [ ] Test data is realistic
- [ ] No duplicate unique values

### Verification Queries

```sql
-- Check record counts
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL
SELECT 'accounts', COUNT(*) FROM accounts
UNION ALL
SELECT 'envelopes', COUNT(*) FROM envelopes;

-- Check relationships
SELECT e.id, e.subject, COUNT(d.id) as doc_count, COUNT(r.id) as recipient_count
FROM envelopes e
LEFT JOIN envelope_documents d ON e.id = d.envelope_id
LEFT JOIN envelope_recipients r ON e.id = r.envelope_id
GROUP BY e.id, e.subject;

-- Check orphaned records
SELECT * FROM envelope_documents WHERE envelope_id NOT IN (SELECT id FROM envelopes);
```

---

## Common Issues & Solutions

### Issue: Foreign key constraint errors
**Solution:** Seed parent tables first (check dependency graph)

### Issue: Unique constraint violations
**Solution:** Use `$this->faker->unique()->email()` or clear table first

### Issue: Model not found
**Solution:** Check namespace in factory: `use App\Models\ModelName;`

### Issue: Seeder doesn't run
**Solution:** Add to DatabaseSeeder::run() method

### Issue: Too slow
**Solution:** Use `withoutModelEvents()` or batch inserts

### Issue: Out of memory
**Solution:** Seed in chunks, reduce record count, or use DB::table() instead

---

## Maintenance

### Regular Updates
- Update seed data when schema changes
- Add new seeders when new models are created
- Keep factories in sync with model changes

### Documentation
- Document any special seeding requirements
- Note dependencies between seeders
- Keep SEEDING-TASK-LIST.md updated

### Version Control
- Commit factories and seeders to git
- Don't commit generated seed data files (unless reference data)
- Use .gitignore for large seed files

---

## Useful Aliases (Optional)

Add to `~/.bashrc` or `~/.zshrc`:

```bash
alias seed='php artisan db:seed'
alias seed-fresh='php artisan migrate:fresh --seed'
alias make-factory='php artisan make:factory'
alias make-seeder='php artisan make:seeder'
```

---

**Document Version:** 1.0
**Last Updated:** 2025-11-17
**See Also:** docs/SEEDING-TASK-LIST.md (full task breakdown)
