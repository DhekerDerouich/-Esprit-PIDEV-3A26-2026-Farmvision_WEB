# 🌤️ Weather Integration - Complete Guide

## What Was Implemented

Your Parcelle module now displays real-time weather information from OpenWeather API based on GPS coordinates.

---

## Files Created/Modified

### ✅ Created:
- `src/CultureParcelle/Service/WeatherService.php` - Service to fetch weather data

### ✅ Modified:
- `.env` - Added OPENWEATHER_API_KEY
- `config/services.yaml` - Configured WeatherService
- `src/CultureParcelle/Controller/ParcelleController.php` - Added weather endpoint
- `src/CultureParcelle/templates/parcelle/index.html.twig` - Added weather widget

---

## Quick Setup (3 Steps)

### 1. Get API Key
- Visit: https://openweathermap.org/api
- Sign up and get your free API key
- Copy the key

### 2. Configure .env
```env
OPENWEATHER_API_KEY=your_api_key_here
```

### 3. Wait & Clear Cache
New API keys take 10-30 minutes to activate. After waiting, clear cache:
```bash
php bin/console cache:clear
```
Then refresh your browser and visit `/parcelles`

---

## API Key Activation

**Important**: New API keys take 10-30 minutes to activate after creation.

**Steps**:
1. Wait 10-30 minutes after creating your API key
2. Clear Symfony cache: `php bin/console cache:clear`
3. Refresh browser (Ctrl+F5)
4. Visit `/parcelles` to see weather

---

## Features

### Weather Data Displayed:
- 🌡️ Temperature (°C)
- ☀️ Weather condition with emoji
- 💧 Humidity (%)
- 💨 Wind speed (km/h)
- 👁️ Visibility (km)

### API Endpoint:
```
GET /parcelles/{idParcelle}/weather
```

Returns JSON:
```json
{
  "temperature": 22.5,
  "humidity": 65,
  "wind_speed": 12.6,
  "description": "ciel dégagé",
  "emoji": "☀️"
}
```

---

## Architecture

```
Browser (AJAX)
    ↓
ParcelleController::getWeather()
    ↓
WeatherService::getWeatherByCoordinates()
    ↓
OpenWeather API
    ↓
Returns weather data
    ↓
Displays in weather widget
```

---

## Testing

### Clear Cache:
```bash
php bin/console cache:clear
```

### Test Endpoint:
Visit in browser or use curl:
```
http://localhost:8000/parcelles/1/weather
```

Expected response:
```json
{
  "temperature": 22.5,
  "humidity": 65,
  "wind_speed": 12.6,
  "description": "ciel dégagé",
  "emoji": "☀️"
}
```

---

## Troubleshooting

### 401 Unauthorized Error
**Cause**: API key not activated yet  
**Solution**: Wait 10-30 minutes after creating the key

### 503 Service Unavailable
**Cause**: WeatherService can't reach API (usually 401)  
**Solution**: Check API key activation

### Weather Not Showing
**Causes**:
- Parcelle has no GPS coordinates → Edit parcelle and click on map
- API key not activated → Wait and test
- Cache not cleared → Run `php bin/console cache:clear`

### Check Logs:
```bash
tail -f var/log/dev.log
```

---

## API Limits (Free Tier)

- 1,000 calls per day
- 60 calls per minute
- Current weather only

---

## Next Steps After Activation

1. ✅ Wait 10-30 minutes for API key activation
2. ✅ Clear cache: `php bin/console cache:clear`
3. ✅ Refresh browser: Ctrl+F5
4. ✅ Visit: `/parcelles`
5. ✅ See weather on parcelle cards!

---

## Configuration Details

### Environment Variable:
```env
OPENWEATHER_API_KEY=your_key_here
```

### Service Configuration (services.yaml):
```yaml
parameters:
    openweather.api_key: '%env(OPENWEATHER_API_KEY)%'

services:
    App\CultureParcelle\Service\WeatherService:
        arguments:
            $apiKey: '%openweather.api_key%'
```

### Weather Service:
- Fetches data from OpenWeather API
- Returns temperature, humidity, wind, visibility
- Handles errors gracefully
- 5-second timeout
- French language support

---

## UI Integration

Weather widget appears on each parcelle card with:
- Blue gradient background
- Large temperature display
- Weather emoji icon
- Humidity, wind speed, visibility
- Loading state: "⏳ Chargement météo..."
- Error state: "⚠️ Impossible de récupérer les données météo"

---

## Commands Reference

```bash
# Clear Symfony cache
php bin/console cache:clear

# View logs
tail -f var/log/dev.log

# Test endpoint with curl
curl http://localhost:8000/parcelles/1/weather
```

---

## Important URLs

- **API Keys**: https://home.openweathermap.org/api_keys
- **API Docs**: https://openweathermap.org/current
- **API Status**: https://status.openweathermap.org/

---

## Timeline

| Time | Status |
|------|--------|
| 0 min | Create API key |
| 0-10 min | Key shows "Active" but API returns 401 |
| 10-30 min | Key usually becomes active |
| 30-120 min | Maximum activation time |

---

## Success Indicators

You'll know it's working when:
- ✅ Browser console has no 503 errors
- ✅ Weather widgets display temperature and info
- ✅ Symfony logs show HTTP 200 responses
- ✅ `/parcelles/{id}/weather` endpoint returns weather data

---

## Support

If issues persist after 2 hours:
1. Generate a new API key
2. Verify email is confirmed
3. Check OpenWeather status page
4. Review Symfony logs

---

**Current Status**: Implementation complete. Waiting for API key activation (10-30 minutes).

**Next Action**: Wait 10-30 minutes, then clear cache and refresh browser.
