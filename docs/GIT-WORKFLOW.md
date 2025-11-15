# Git Workflow & Branching Strategy

This document outlines the Git workflow and branching strategy for the DocuSign Signing API project.

## Table of Contents

1. [Branching Strategy](#branching-strategy)
2. [Branch Naming Convention](#branch-naming-convention)
3. [Workflow](#workflow)
4. [Commit Message Guidelines](#commit-message-guidelines)
5. [Pull Request Process](#pull-request-process)
6. [Release Process](#release-process)
7. [Hotfix Process](#hotfix-process)

---

## Branching Strategy

We follow a **Git Flow** branching strategy with the following main branches:

### Main Branches

#### `main`
- **Purpose:** Production-ready code only
- **Protection:** Protected branch, requires PR reviews
- **Direct Commits:** Never (except hotfixes with approval)
- **Deployment:** Auto-deploys to production (with manual approval)
- **Status:** Always stable and deployable

#### `develop`
- **Purpose:** Integration branch for features
- **Protection:** Protected branch, requires PR reviews
- **Direct Commits:** Never
- **Deployment:** Auto-deploys to staging environment
- **Status:** Should always be in a working state

### Supporting Branches

#### Feature Branches (`feature/*`)
- **Purpose:** Develop new features
- **Branch From:** `develop`
- **Merge Into:** `develop`
- **Naming:** `feature/<ticket-id>-<short-description>`
- **Example:** `feature/SIGN-123-oauth-authentication`
- **Lifetime:** Temporary (deleted after merge)

#### Bugfix Branches (`bugfix/*`)
- **Purpose:** Fix bugs in `develop`
- **Branch From:** `develop`
- **Merge Into:** `develop`
- **Naming:** `bugfix/<ticket-id>-<short-description>`
- **Example:** `bugfix/SIGN-456-fix-envelope-status`
- **Lifetime:** Temporary (deleted after merge)

#### Release Branches (`release/*`)
- **Purpose:** Prepare for production release
- **Branch From:** `develop`
- **Merge Into:** `main` AND `develop`
- **Naming:** `release/v<version>`
- **Example:** `release/v1.2.0`
- **Lifetime:** Short-lived (deleted after release)

#### Hotfix Branches (`hotfix/*`)
- **Purpose:** Emergency fixes for production
- **Branch From:** `main`
- **Merge Into:** `main` AND `develop`
- **Naming:** `hotfix/v<version>-<description>`
- **Example:** `hotfix/v1.2.1-security-patch`
- **Lifetime:** Very short (deleted after merge)

---

## Branch Naming Convention

### Pattern

```
<type>/<ticket-id>-<short-description>
```

### Types

- `feature/` - New features or enhancements
- `bugfix/` - Bug fixes
- `hotfix/` - Production emergency fixes
- `release/` - Release preparation
- `docs/` - Documentation updates
- `refactor/` - Code refactoring
- `test/` - Test additions or updates
- `chore/` - Maintenance tasks

### Examples

```
feature/SIGN-123-user-authentication
bugfix/SIGN-456-envelope-email
hotfix/v1.2.1-security-vulnerability
release/v2.0.0
docs/SIGN-789-api-documentation
refactor/SIGN-101-service-layer
test/SIGN-202-integration-tests
chore/SIGN-303-update-dependencies
```

### Rules

- Use lowercase only
- Use hyphens (`-`) to separate words
- Keep descriptions short (3-5 words max)
- Always include ticket ID (if applicable)
- Be descriptive but concise

---

## Workflow

### 1. Starting New Work

```bash
# Update local develop branch
git checkout develop
git pull origin develop

# Create new feature branch
git checkout -b feature/SIGN-123-new-feature

# Work on your feature
# ... make changes ...

# Commit regularly
git add .
git commit -m "feat: implement user authentication"
```

### 2. Keeping Your Branch Updated

```bash
# Regularly sync with develop
git checkout develop
git pull origin develop
git checkout feature/SIGN-123-new-feature
git rebase develop

# Or use merge (if team prefers)
git merge develop
```

### 3. Pushing Your Work

```bash
# First push
git push -u origin feature/SIGN-123-new-feature

# Subsequent pushes
git push
```

### 4. Creating Pull Request

1. Push your branch to remote
2. Create PR on GitHub/GitLab
3. Fill out PR template
4. Request reviews
5. Address feedback
6. Squash commits if needed
7. Merge when approved

### 5. After Merge

```bash
# Switch back to develop
git checkout develop
git pull origin develop

# Delete local branch
git branch -d feature/SIGN-123-new-feature

# Delete remote branch (if not auto-deleted)
git push origin --delete feature/SIGN-123-new-feature
```

---

## Commit Message Guidelines

We follow **Conventional Commits** specification.

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes (formatting, etc.)
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks
- `perf`: Performance improvements
- `ci`: CI/CD changes
- `build`: Build system changes
- `revert`: Revert previous commit

### Examples

```bash
# Feature
git commit -m "feat(auth): implement OAuth 2.0 authentication"

# Bug fix
git commit -m "fix(envelope): resolve status update issue"

# Documentation
git commit -m "docs(api): update authentication endpoints"

# With body
git commit -m "feat(billing): add Stripe payment integration

Implemented Stripe payment processing for subscription billing.
Added webhook handlers for payment events.

Closes SIGN-456"

# Breaking change
git commit -m "feat(api)!: change authentication response format

BREAKING CHANGE: Authentication endpoints now return JWT tokens
in a different structure. Update your API clients accordingly."
```

### Rules

- Use imperative mood ("add" not "added")
- Don't capitalize first letter
- No period at the end
- Keep subject under 50 characters
- Separate subject from body with blank line
- Wrap body at 72 characters
- Use body to explain what and why, not how

---

## Pull Request Process

### PR Title Format

```
<type>(<scope>): <description>
```

Example: `feat(auth): implement OAuth 2.0 authentication`

### PR Description Template

```markdown
## Description
Brief description of the changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Related Issues
Closes #123

## Changes Made
- Implemented OAuth 2.0 authentication
- Added JWT token management
- Created authentication middleware

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing completed

## Checklist
- [ ] Code follows project style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No new warnings generated
- [ ] Tests pass locally
- [ ] Dependent changes merged

## Screenshots (if applicable)
```

### Review Process

1. **Automated Checks**
   - All CI/CD tests must pass
   - Code coverage must meet threshold
   - No linting errors

2. **Code Review**
   - At least 1 approval required (develop)
   - At least 2 approvals required (main)
   - All conversations resolved

3. **Merge Strategy**
   - Squash and merge (for feature branches)
   - Merge commit (for release/hotfix)
   - Rebase and merge (optional, team preference)

---

## Release Process

### 1. Create Release Branch

```bash
# From updated develop branch
git checkout develop
git pull origin develop

# Create release branch
git checkout -b release/v1.2.0
```

### 2. Prepare Release

```bash
# Update version numbers
# - composer.json
# - package.json
# - README.md

# Update CHANGELOG.md
# - Document all changes since last release

# Commit changes
git add .
git commit -m "chore(release): prepare v1.2.0"

# Push release branch
git push -u origin release/v1.2.0
```

### 3. Testing

- Run full test suite
- Perform manual QA testing
- Deploy to staging for final verification
- Fix any critical bugs in release branch

### 4. Merge to Main

```bash
# Create PR: release/v1.2.0 → main
# Get approvals and merge

# Tag the release
git checkout main
git pull origin main
git tag -a v1.2.0 -m "Release version 1.2.0"
git push origin v1.2.0
```

### 5. Merge Back to Develop

```bash
# Create PR: release/v1.2.0 → develop
# Merge to keep develop updated

# Delete release branch
git branch -d release/v1.2.0
git push origin --delete release/v1.2.0
```

---

## Hotfix Process

### 1. Create Hotfix Branch

```bash
# From main
git checkout main
git pull origin main

# Create hotfix branch
git checkout -b hotfix/v1.2.1-security-patch
```

### 2. Fix the Issue

```bash
# Make necessary fixes
# ... code changes ...

# Commit fix
git add .
git commit -m "fix(security): patch XSS vulnerability

Security patch for CVE-2024-XXXXX"

# Push hotfix branch
git push -u origin hotfix/v1.2.1-security-patch
```

### 3. Merge to Main

```bash
# Create PR: hotfix/v1.2.1-security-patch → main
# Get emergency approval and merge

# Tag the hotfix
git checkout main
git pull origin main
git tag -a v1.2.1 -m "Hotfix version 1.2.1"
git push origin v1.2.1
```

### 4. Merge to Develop

```bash
# Create PR: hotfix/v1.2.1-security-patch → develop
# Merge to keep develop updated

# Delete hotfix branch
git branch -d hotfix/v1.2.1-security-patch
git push origin --delete hotfix/v1.2.1-security-patch
```

---

## Git Commands Cheat Sheet

### Common Operations

```bash
# Check status
git status

# View commit history
git log --oneline --graph --all

# Create and switch to new branch
git checkout -b feature/my-feature

# Switch branches
git checkout develop

# Update current branch
git pull

# Stage changes
git add .
git add <file>

# Commit changes
git commit -m "feat: add new feature"

# Push changes
git push

# Rebase interactive (squash commits)
git rebase -i HEAD~3

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Undo last commit (discard changes)
git reset --hard HEAD~1

# Stash changes
git stash
git stash pop

# View differences
git diff
git diff --staged
```

### Branch Management

```bash
# List branches
git branch
git branch -a  # including remote

# Delete local branch
git branch -d feature/my-feature

# Delete remote branch
git push origin --delete feature/my-feature

# Rename branch
git branch -m old-name new-name

# Track remote branch
git branch --set-upstream-to=origin/feature/my-feature
```

---

## Best Practices

### DO ✅

- Commit early and often
- Write descriptive commit messages
- Keep commits focused (one logical change per commit)
- Pull/rebase regularly to stay updated
- Run tests before pushing
- Review your own code before creating PR
- Keep branches short-lived
- Delete branches after merging

### DON'T ❌

- Commit directly to `main` or `develop`
- Push broken code
- Include secrets in commits
- Make huge commits with many unrelated changes
- Leave stale branches lying around
- Force push to shared branches (except after PR feedback)
- Ignore merge conflicts
- Skip code reviews

---

## Troubleshooting

### Merge Conflicts

```bash
# Update your branch
git checkout develop
git pull origin develop
git checkout feature/my-feature
git merge develop

# Fix conflicts in files
# Then:
git add .
git commit -m "chore: resolve merge conflicts"
git push
```

### Undo Pushed Commits

```bash
# Create new commit that reverts changes
git revert HEAD
git push
```

### Clean Up Local Branches

```bash
# Delete all local branches except main and develop
git branch | grep -v "main\|develop" | xargs git branch -D

# Prune deleted remote branches
git fetch --prune
```

---

## Additional Resources

- [Git Flow](https://nvie.com/posts/a-successful-git-branching-model/)
- [Conventional Commits](https://www.conventionalcommits.org/)
- [GitHub Flow](https://guides.github.com/introduction/flow/)
- [Git Documentation](https://git-scm.com/doc)
