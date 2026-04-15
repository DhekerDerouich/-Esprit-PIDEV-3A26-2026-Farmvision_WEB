# Git Security Guide - API Keys

## ✅ Current Setup (Secure)

Your project is now configured to keep API keys out of git:

```
.env              → Committed to git (placeholder only)
.env.local        → NOT committed (your real API key)
.env.example      → Committed to git (template)
```

---

## What's Safe to Push to GitHub

### ✅ Safe to Commit:

**`.env`** - Contains placeholder:
```env
OPENWEATHER_API_KEY=your_api_key_here
```

**`.env.example`** - Template for team:
```env
OPENWEATHER_API_KEY=your_api_key_here
```

**All code files**:
- `src/CultureParcelle/Service/WeatherService.php`
- `src/CultureParcelle/Controller/ParcelleController.php`
- `config/services.yaml`
- Templates, etc.

### ❌ Never Commit:

**`.env.local`** - Contains real API key:
```env
OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605
```

This file is automatically ignored by `.gitignore`

---

## How It Works

### Symfony Environment File Priority:

```
1. .env                  (lowest priority - committed)
2. .env.local            (overrides .env - NOT committed)
3. .env.{APP_ENV}        (e.g., .env.prod)
4. .env.{APP_ENV}.local  (highest priority)
```

**Result**: 
- `.env` has placeholder → committed to git ✅
- `.env.local` has real key → overrides placeholder → NOT in git ❌

---

## Before Pushing to GitHub

### Check What You're Committing:

```bash
git status
```

**Should see**:
```
modified:   .env
new file:   .env.example
new file:   SETUP_FOR_DEVELOPERS.md
```

**Should NOT see**:
```
.env.local  ← If you see this, STOP!
```

### If .env.local Appears:

```bash
# Make sure it's in .gitignore
echo "/.env.local" >> .gitignore

# Remove from git if accidentally added
git rm --cached .env.local

# Commit the fix
git add .gitignore
git commit -m "Ensure .env.local is ignored"
```

---

## Safe Git Workflow

### 1. Check Status
```bash
git status
```

### 2. Review Changes
```bash
git diff .env
```

Make sure `.env` only has placeholder:
```diff
+OPENWEATHER_API_KEY=your_api_key_here
```

NOT your real key:
```diff
-OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605  ← WRONG!
```

### 3. Add Files
```bash
git add .env
git add .env.example
git add SETUP_FOR_DEVELOPERS.md
git add src/
git add config/
```

### 4. Commit
```bash
git commit -m "Add weather integration with OpenWeather API"
```

### 5. Push
```bash
git push origin your-branch
```

---

## Team Collaboration

### When You Push:
- ✅ Code is pushed
- ✅ `.env` with placeholder is pushed
- ✅ `.env.example` template is pushed
- ❌ Your real API key stays local

### When Team Member Clones:
```bash
git clone <repo>
cd farmvision

# They create their own .env.local
cp .env.example .env.local

# They add their own API key
# Edit .env.local:
# OPENWEATHER_API_KEY=their_own_key_here

composer install
php bin/console cache:clear
```

**Result**: Everyone has their own API key, none in git!

---

## Verify Your Setup

### Test 1: Check .gitignore
```bash
cat .gitignore | grep env
```

Should show:
```
/.env.local
/.env.local.php
/.env.*.local
```

### Test 2: Check Git Status
```bash
git status
```

Should NOT show `.env.local`

### Test 3: Check .env Content
```bash
cat .env | grep OPENWEATHER
```

Should show:
```
OPENWEATHER_API_KEY=your_api_key_here
```

NOT your real key!

### Test 4: Check .env.local Content
```bash
cat .env.local | grep OPENWEATHER
```

Should show:
```
OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605
```

Your real key (this file is NOT in git)

---

## If You Accidentally Committed API Key

### Remove from Git History:

```bash
# Remove the file from git (keeps local copy)
git rm --cached .env.local

# Or if you committed real key in .env:
# 1. Replace with placeholder in .env
# 2. Move real key to .env.local
# 3. Commit the fix

git add .env .env.local
git commit -m "Security: Move API key to .env.local"
git push origin your-branch
```

### If Already Pushed to GitHub:

1. **Revoke the exposed API key** at https://home.openweathermap.org/api_keys
2. Generate a new API key
3. Update `.env.local` with new key
4. Fix the git history (above steps)
5. Force push (if needed): `git push --force`

**Important**: Once an API key is on GitHub, consider it compromised!

---

## Production Deployment

### Option 1: Server Environment Variables
```bash
export OPENWEATHER_API_KEY=production_key_here
```

### Option 2: .env.local on Server
Create `.env.local` directly on production server:
```bash
ssh user@production-server
cd /var/www/farmvision
nano .env.local
# Add: OPENWEATHER_API_KEY=production_key
```

**Never commit production keys to git!**

---

## Quick Checklist

Before pushing to GitHub:

- [ ] `.env` contains only placeholder: `your_api_key_here`
- [ ] `.env.local` contains real API key
- [ ] `.env.local` is in `.gitignore`
- [ ] `git status` does NOT show `.env.local`
- [ ] `.env.example` is created as template
- [ ] `SETUP_FOR_DEVELOPERS.md` is created
- [ ] Tested: `git diff .env` shows no real API key

---

## Summary

✅ **What you did right**:
- Asked before pushing API key to GitHub
- Now using `.env.local` for secrets
- `.env` has only placeholders
- `.env.local` is in `.gitignore`

✅ **Safe to push**:
- All code files
- `.env` (with placeholder)
- `.env.example`
- Documentation

❌ **Never push**:
- `.env.local` (real API keys)
- Any file with real secrets

---

**You're now ready to safely push to GitHub!** 🎉
