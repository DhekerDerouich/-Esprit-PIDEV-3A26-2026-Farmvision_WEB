# Weather Integration - Complete Guide

## 🎯 What Was Implemented

Your Parcelle module now displays real-time weather information from OpenWeather API based on GPS coordinates.

---

## 📁 Project Structure

```
farmvision/
├── .env                          # Placeholder values (committed to git)
├── .env.local                    # Real API keys (NOT in git)
├── .env.example                  # Template for team members
├── config/
│   └── services.yaml             # Service configuration with API key injection
├── src/
│   └── CultureParcelle/
│       ├── Controller/
│       │   └── ParcelleController.php    # Added weather endpoint
│       ├── Service/
│       │   └── WeatherService.php        # NEW - Fetches weather from API
│       └── templates/
│           └── parcelle/
│               └── index.html.twig       # Added weather widget UI
└── WEATHER_FEATURE_GUIDE.md      # This file
```

---

## 🔄 How It Works - Complete Flow

### 1. Environment Configuration

**File Priority** (later files override earlier ones):
```
.env          → OPENWEATHER_API_KEY=your_api_key_here (placeholder)
.env.local    → OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605 (real key)
Result: Uses the real key from .env.local ✅
```

### 2. Service Configuration

**File**: `config/services.yaml`
```yaml
parameters:
    openweather.api_key: '%env(OPENWEATHER_API_KEY)%'

services:
    App\CultureParcelle\Service\WeatherService:
        arguments:
            $apiKey: '%openweather.api_key%'
```

**What happens**:
- Symfony reads `OPENWEATHER_API_KEY` from environment (gets value from `.env.local`)
- Creates parameter `openweather.api_key` with the real API key
- Injects it into `WeatherService` constructor

### 3. Weather Service

**File**: `src/CultureParcelle/Service/WeatherService.php`
```php
class WeatherService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiKey  // ← Injected automatically by Symfony
    ) {}

    public function getWeatherByCoordinates(float $lat, float $lon): ?array
    {
        $response = $this->httpClient->request('GET', API_URL, [
            'query' => [
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $this->apiKey,  // ← Uses injected key
                'units' => 'metric',
                'lang' => 'fr'
            ]
        ]);
        
        // Returns: temperature, humidity, wind_speed, description, emoji, etc.
    }
}
```

### 4. Controller Endpoint

**File**: `src/CultureParcelle/Controller/ParcelleController.php`
```php
#[Route('/{idParcelle}/weather', name: 'front_parcelle_weather', methods: ['GET'])]
public function getWeather(
    int $idParcelle, 
    ParcelleRepository $repo, 
    WeatherService $weatherService  // ← Injected by Symfony
): JsonResponse
{
    $parcelle = $repo->find($idParcelle);
    
    if (!$parcelle->getLatitude() || !$parcelle->getLongitude()) {
        return $this->json(['error' => 'Coordonnées GPS manquantes'], 400);
    }

    $weatherData = $weatherService->getWeatherByCoordinates(
        $parcelle->getLatitude(),
        $parcelle->getLongitude()
    );

    return $this->json($weatherData);
}
```

### 5. Frontend Integration

**File**: `src/CultureParcelle/templates/parcelle/index.html.twig`

**HTML**: Weather widget container
```html
<div class="cp-weather-box" id="weather-{{ parcelle.idParcelle }}">
    <div class="cp-weather-loading">⏳ Chargement météo...</div>
</div>
```

**JavaScript**: AJAX call to fetch weather
```javascript
fetch('/parcelles/{{ parcelle.idParcelle }}/weather')
    .then(response => response.json())
    .then(data => {
        // Display: temperature, emoji, description, humidity, wind, visibility
        weatherBox.innerHTML = `
            <div class="cp-weather-icon">${data.emoji}</div>
            <div class="cp-weather-temp">${data.temperature}°C</div>
            <div class="cp-weather-desc">${data.description}</div>
            <div>💧 ${data.humidity}%  💨 ${data.wind_speed} km/h</div>
        `;
    });
```

---

## 🔐 Git Security - API Key Management

### Environment Files Explained

| File | Content | Git Status | Purpose |
|------|---------|------------|---------|
| `.env` | `OPENWEATHER_API_KEY=your_api_key_here` | ✅ Committed | Placeholder for team |
| `.env.local` | `OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605` | ❌ NOT committed | Your real API key |
| `.env.example` | `OPENWEATHER_API_KEY=your_api_key_here` | ✅ Committed | Setup template |

### Why This Works

**Symfony's DotEnv Priority**:
1. Loads `.env` first (placeholder)
2. Loads `.env.local` second (overrides with real key)
3. Result: Real key is used, but never in git!

**Verification**:
```bash
# Check what Symfony sees
php bin/console debug:container --env-var=OPENWEATHER_API_KEY
# Output: Real value = "10f4b8f78ff1854d402feade0f123605"

# Check git status
git status
# .env.local should NOT appear (it's in .gitignore)
```

### Safe to Push to GitHub

✅ **Commit these**:
- `.env` (placeholder only)
- `.env.example` (template)
- All code files (Service, Controller, Templates)
- `config/services.yaml`

❌ **Never commit**:
- `.env.local` (real API key)

---

## 🚀 Setup Instructions

### For You (First Time):

1. **Get API Key**:
   - Visit: https://openweathermap.org/api
   - Sign up and get free API key
   - Copy the key

2. **Configure .env.local**:
   ```bash
   # Create .env.local (if not exists)
   cp .env.example .env.local
   
   # Edit .env.local and add your real key
   OPENWEATHER_API_KEY=your_actual_key_here
   ```

3. **Wait for Activation**:
   - New API keys take 10-30 minutes to activate
   - Check activation: Visit `/parcelles/{id}/weather` in browser

4. **Clear Cache**:
   ```bash
   php bin/console cache:clear
   ```

5. **Test**:
   - Visit `/parcelles`
   - Weather should display on parcelle cards with GPS coordinates

### For Team Members:

```bash
# 1. Clone repository
git clone <repo-url>
cd farmvision

# 2. Install dependencies
composer install

# 3. Create .env.local from template
cp .env.example .env.local

# 4. Edit .env.local and add their own API key
# OPENWEATHER_API_KEY=their_key_here

# 5. Setup database
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Clear cache
php bin/console cache:clear

# 7. Start server
symfony server:start
```

---

## 🎨 Features

### Weather Data Displayed:
- 🌡️ Temperature (°C)
- ☀️ Weather condition with emoji (☀️ 🌧️ ☁️ ⛈️ ❄️ 🌫️)
- 💧 Humidity (%)
- 💨 Wind speed (km/h)
- 👁️ Visibility (km)
- 📝 Description in French

### API Endpoint:
```
GET /parcelles/{idParcelle}/weather

Response:
{
  "temperature": 22.5,
  "feels_like": 21.8,
  "humidity": 65,
  "pressure": 1013,
  "description": "ciel dégagé",
  "icon": "01d",
  "wind_speed": 12.6,
  "wind_deg": 180,
  "clouds": 10,
  "visibility": 10.0,
  "emoji": "☀️"
}
```

### UI Widget:
- Blue gradient background
- Large temperature display
- Weather emoji icon
- Compact details row
- Loading state: "⏳ Chargement météo..."
- Error state: "⚠️ Impossible de récupérer les données météo"

---

## 🧪 Testing

### Test API Key:
```bash
# Check what Symfony sees
php bin/console debug:container --env-var=OPENWEATHER_API_KEY

# Check service configuration
php bin/console debug:container WeatherService --show-arguments
```

### Test Endpoint:
```bash
# Using curl
curl http://localhost:8000/parcelles/1/weather

# Or visit in browser
http://localhost:8000/parcelles/1/weather
```

### Test UI:
1. Visit `/parcelles`
2. Check browser console (F12) for errors
3. Weather should display on cards with GPS coordinates

### Check Logs:
```bash
tail -f var/log/dev.log
```

---

## 🐛 Troubleshooting

### Issue: "⚠️ Impossible de récupérer les données météo"

**Cause**: API key not activated yet  
**Solution**: Wait 10-30 minutes after creating the key

**Check logs**:
```bash
tail -f var/log/dev.log | grep -i weather
```

If you see `401 Unauthorized`: API key not active yet

### Issue: Weather not showing

**Possible causes**:
1. Parcelle has no GPS coordinates
   - **Fix**: Edit parcelle, click on map to set coordinates
   
2. API key not configured
   - **Fix**: Check `.env.local` has `OPENWEATHER_API_KEY=your_key`
   
3. Cache not cleared
   - **Fix**: Run `php bin/console cache:clear`

### Issue: 503 Service Unavailable

**Cause**: WeatherService can't reach OpenWeather API  
**Common reasons**:
- API key not activated (401)
- Network issues
- OpenWeather API down

**Check**: https://status.openweathermap.org/

---

## 📊 API Limits (Free Tier)

- **Calls per day**: 1,000
- **Calls per minute**: 60
- **Data**: Current weather only
- **Cost**: Free

**Recommendation**: For production, implement caching (30-minute TTL) to reduce API calls.

---

## 🔄 Complete Request Flow

```
1. User visits /parcelles
   ↓
2. Page loads with parcelle cards
   ↓
3. JavaScript: fetch('/parcelles/6/weather')
   ↓
4. Symfony Router → ParcelleController::getWeather()
   ↓
5. Controller gets WeatherService (with API key injected)
   ↓
6. WeatherService::getWeatherByCoordinates(lat, lon)
   ↓
7. HTTP request to OpenWeather API with API key
   ↓
8. OpenWeather returns weather data
   ↓
9. WeatherService parses and returns data
   ↓
10. Controller returns JSON to browser
    ↓
11. JavaScript updates weather widget
    ↓
12. User sees: ☀️ 22.5°C, ciel dégagé, 💧 65%, 💨 12.6 km/h
```

---

## 🎓 Key Concepts

### Dependency Injection
- Symfony automatically injects `WeatherService` into controller
- Symfony automatically injects API key into `WeatherService`
- No manual instantiation needed

### Environment Variables
- `.env` = base configuration (committed)
- `.env.local` = local overrides (NOT committed)
- Later files override earlier files
- Each developer has their own `.env.local`

### Service Layer
- Business logic in `WeatherService`
- Controller stays thin
- Easy to test and reuse
- Clean separation of concerns

---

## 📝 Commands Reference

```bash
# Clear cache
php bin/console cache:clear

# Check environment variable
php bin/console debug:container --env-var=OPENWEATHER_API_KEY

# Check service configuration
php bin/console debug:container WeatherService

# View logs
tail -f var/log/dev.log

# Test endpoint
curl http://localhost:8000/parcelles/1/weather

# Check git status (verify .env.local not tracked)
git status
```

---

## 🌐 Important URLs

- **API Keys**: https://home.openweathermap.org/api_keys
- **API Docs**: https://openweathermap.org/current
- **API Status**: https://status.openweathermap.org/

---

## ✅ Success Checklist

- [ ] `.env` has placeholder: `OPENWEATHER_API_KEY=your_api_key_here`
- [ ] `.env.local` has real API key
- [ ] `.env.local` is in `.gitignore`
- [ ] `git status` does NOT show `.env.local`
- [ ] API key is activated (waited 10-30 minutes)
- [ ] Cache cleared: `php bin/console cache:clear`
- [ ] Parcelles have GPS coordinates (latitude/longitude)
- [ ] Weather displays on `/parcelles` page
- [ ] No 503 errors in browser console
- [ ] Symfony logs show HTTP 200 responses

---

## 🎉 Summary

**What you have**:
- ✅ Real-time weather integration
- ✅ Clean Symfony architecture
- ✅ Secure API key management (not in git)
- ✅ Beautiful weather widget UI
- ✅ RESTful API endpoint
- ✅ Error handling and logging
- ✅ Team-friendly setup

**How it works**:
1. Symfony loads API key from `.env.local` (overrides `.env`)
2. Injects API key into `WeatherService` via dependency injection
3. Controller calls `WeatherService` to fetch weather
4. JavaScript displays weather in widget
5. API key never appears in git (`.env.local` is ignored)

**Ready to push to GitHub**: Yes! Your API key is safe in `.env.local` which is not tracked by git.
