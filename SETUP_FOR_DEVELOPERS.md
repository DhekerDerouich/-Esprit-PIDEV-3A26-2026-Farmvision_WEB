# Setup Guide for Developers

## Initial Setup

### 1. Clone the Repository
```bash
git clone <repository-url>
cd farmvision
```

### 2. Install Dependencies
```bash
composer install
```

### 3. Configure Environment Variables

Create `.env.local` file (this file is NOT committed to git):
```bash
cp .env.example .env.local
```

Edit `.env.local` and add your configurations:
```env
###> openweather api ###
OPENWEATHER_API_KEY=your_actual_api_key_here
###< openweather api ###
```

### 4. Get OpenWeather API Key

1. Visit https://openweathermap.org/api
2. Sign up for a free account
3. Go to "API keys" section
4. Copy your API key
5. Paste it in `.env.local`

**Important**: Wait 10-30 minutes for the API key to activate!

### 5. Setup Database
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Clear Cache
```bash
php bin/console cache:clear
```

### 7. Start Development Server
```bash
symfony server:start
# or
php -S localhost:8000 -t public
```

---

## Environment Files Explained

| File | Purpose | Committed to Git? |
|------|---------|-------------------|
| `.env` | Default values, placeholders | ✅ YES |
| `.env.local` | Your real secrets (API keys, passwords) | ❌ NO |
| `.env.example` | Template for new developers | ✅ YES |

**Rule**: Never commit real API keys or passwords to git!

---

## Weather Integration

The weather feature requires an OpenWeather API key.

**Setup**:
1. Get API key from https://openweathermap.org/api
2. Add to `.env.local`:
   ```env
   OPENWEATHER_API_KEY=your_key_here
   ```
3. Wait 10-30 minutes for activation
4. Clear cache: `php bin/console cache:clear`
5. Visit `/parcelles` to see weather

**Testing**:
- Visit: `http://localhost:8000/parcelles/{id}/weather`
- Should return JSON with weather data

---

## Common Issues

### Weather shows "Impossible de récupérer les données météo"
- **Cause**: API key not activated yet
- **Solution**: Wait 10-30 minutes after creating the key

### 401 Unauthorized in logs
- **Cause**: Invalid or inactive API key
- **Solution**: Check your API key at https://home.openweathermap.org/api_keys

### Changes not reflecting
- **Solution**: Clear cache
  ```bash
  php bin/console cache:clear
  ```

---

## Git Workflow

### Before Committing
Make sure you're NOT committing secrets:
```bash
git status
```

Should NOT see:
- `.env.local` (contains real API keys)

Should see:
- `.env` (with placeholders only)
- `.env.example` (template)

### Safe to Commit
```bash
git add .
git commit -m "Add weather integration"
git push origin your-branch
```

---

## Team Collaboration

When a team member clones the repo:

1. They copy `.env.example` to `.env.local`
2. They add their own API key to `.env.local`
3. They never commit `.env.local`
4. Everyone has their own API key

This way:
- ✅ No secrets in git
- ✅ Each developer has their own API key
- ✅ Easy to setup for new team members

---

## Production Deployment

For production, set environment variables directly on the server:

**Option 1: Server Environment Variables**
```bash
export OPENWEATHER_API_KEY=your_production_key
```

**Option 2: .env.local on Server**
Create `.env.local` on the production server (not in git):
```env
APP_ENV=prod
OPENWEATHER_API_KEY=your_production_key
```

**Never commit production secrets to git!**

---

## Quick Reference

```bash
# Install dependencies
composer install

# Create .env.local
cp .env.example .env.local

# Clear cache
php bin/console cache:clear

# Check logs
tail -f var/log/dev.log

# Test weather endpoint
curl http://localhost:8000/parcelles/1/weather
```

---

## Need Help?

- Read: `WEATHER_INTEGRATION_README.md`
- Check logs: `var/log/dev.log`
- OpenWeather docs: https://openweathermap.org/current
