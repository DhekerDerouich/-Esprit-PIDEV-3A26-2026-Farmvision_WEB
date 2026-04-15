# How Symfony Loads API Key from .env.local

## Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ Step 1: Application Starts                                     │
│ (User visits /parcelles)                                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 2: Symfony Loads Environment Files (in order)             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Load .env                                                   │
│     OPENWEATHER_API_KEY=your_api_key_here                      │
│                                                                 │
│  2. Load .env.local (OVERRIDES .env)                           │
│     OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605       │
│                                                                 │
│  Result: OPENWEATHER_API_KEY = 10f4b8f78ff1854d402feade0f123605│
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 3: Symfony Processes config/services.yaml                 │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  parameters:                                                    │
│    openweather.api_key: '%env(OPENWEATHER_API_KEY)%'          │
│                                                                 │
│  This reads the environment variable and creates a parameter   │
│  openweather.api_key = "10f4b8f78ff1854d402feade0f123605"     │
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 4: Dependency Injection Container                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  services:                                                      │
│    App\CultureParcelle\Service\WeatherService:                 │
│      arguments:                                                 │
│        $apiKey: '%openweather.api_key%'                        │
│                                                                 │
│  Container creates WeatherService with:                        │
│  $apiKey = "10f4b8f78ff1854d402feade0f123605"                 │
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 5: WeatherService Constructor                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  public function __construct(                                   │
│      private HttpClientInterface $httpClient,                   │
│      private LoggerInterface $logger,                           │
│      private string $apiKey  ← Injected here!                  │
│  ) {}                                                           │
│                                                                 │
│  $this->apiKey = "10f4b8f78ff1854d402feade0f123605"           │
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 6: Controller Calls WeatherService                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ParcelleController::getWeather()                              │
│  {                                                              │
│      $weatherData = $weatherService->getWeatherByCoordinates(  │
│          $parcelle->getLatitude(),                             │
│          $parcelle->getLongitude()                             │
│      );                                                         │
│  }                                                              │
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 7: WeatherService Makes API Call                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  $response = $this->httpClient->request('GET', API_URL, [      │
│      'query' => [                                               │
│          'lat' => $latitude,                                    │
│          'lon' => $longitude,                                   │
│          'appid' => $this->apiKey,  ← Uses injected key!      │
│          'units' => 'metric',                                   │
│          'lang' => 'fr'                                         │
│      ]                                                          │
│  ]);                                                            │
│                                                                 │
│  URL: https://api.openweathermap.org/data/2.5/weather?         │
│       lat=36.138&lon=10.394&                                   │
│       appid=10f4b8f78ff1854d402feade0f123605&                  │
│       units=metric&lang=fr                                      │
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 8: OpenWeather API Response                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Returns weather data JSON                                      │
│  {                                                              │
│    "main": {"temp": 22.5, "humidity": 65},                    │
│    "weather": [{"description": "ciel dégagé"}],               │
│    "wind": {"speed": 3.5}                                      │
│  }                                                              │
│                                                                 │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│ Step 9: Display in Browser                                     │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  Weather widget shows:                                          │
│  ☀️ 22.5°C                                                     │
│  ciel dégagé                                                    │
│  💧 65%  💨 12.6 km/h                                          │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

---

## Detailed Step-by-Step

### Step 1: Environment File Loading

When Symfony starts, it loads environment files in this order:

```php
// Symfony's DotEnv component does this automatically:

1. .env                    // Base file (committed to git)
   OPENWEATHER_API_KEY=your_api_key_here

2. .env.local              // Overrides .env (NOT in git)
   OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605

3. .env.{APP_ENV}          // e.g., .env.prod
4. .env.{APP_ENV}.local    // e.g., .env.prod.local
```

**Result**: The value from `.env.local` WINS!

```
Final value: OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605
```

---

### Step 2: Parameter Resolution

**File**: `config/services.yaml`

```yaml
parameters:
    openweather.api_key: '%env(OPENWEATHER_API_KEY)%'
```

**What happens**:
1. `%env(OPENWEATHER_API_KEY)%` tells Symfony to read the environment variable
2. Symfony finds: `OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605`
3. Creates parameter: `openweather.api_key = "10f4b8f78ff1854d402feade0f123605"`

---

### Step 3: Service Configuration

**File**: `config/services.yaml`

```yaml
services:
    App\CultureParcelle\Service\WeatherService:
        arguments:
            $apiKey: '%openweather.api_key%'
```

**What happens**:
1. Symfony's Dependency Injection Container prepares to create WeatherService
2. It sees it needs to inject `$apiKey` parameter
3. It resolves `%openweather.api_key%` to `"10f4b8f78ff1854d402feade0f123605"`
4. Stores this for when WeatherService is needed

---

### Step 4: Service Instantiation

**File**: `src/CultureParcelle/Service/WeatherService.php`

```php
class WeatherService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        private string $apiKey  // ← Injected here!
    ) {}
}
```

**What happens**:
1. When WeatherService is first needed, Symfony creates it
2. Symfony injects the API key: `$apiKey = "10f4b8f78ff1854d402feade0f123605"`
3. The private property `$this->apiKey` now contains your real API key
4. This happens automatically - no manual instantiation needed!

---

### Step 5: Using the API Key

**File**: `src/CultureParcelle/Service/WeatherService.php`

```php
public function getWeatherByCoordinates(float $latitude, float $longitude): ?array
{
    $response = $this->httpClient->request('GET', self::API_URL, [
        'query' => [
            'lat' => $latitude,
            'lon' => $longitude,
            'appid' => $this->apiKey,  // ← Uses the injected key!
            'units' => 'metric',
            'lang' => 'fr'
        ]
    ]);
}
```

**What happens**:
1. Method uses `$this->apiKey` which was injected in constructor
2. Builds API URL with the real API key
3. Makes HTTP request to OpenWeather API
4. API key is sent in the query string

---

## Code Flow Example

Let's trace a real request:

### 1. User visits: `http://localhost:8000/parcelles`

### 2. JavaScript makes AJAX call:
```javascript
fetch('/parcelles/6/weather')
```

### 3. Symfony Router matches route:
```php
#[Route('/{idParcelle}/weather', name: 'front_parcelle_weather')]
public function getWeather(int $idParcelle, ..., WeatherService $weatherService)
```

### 4. Symfony injects WeatherService:
```php
// Symfony automatically does this:
$weatherService = new WeatherService(
    $httpClient,
    $logger,
    "10f4b8f78ff1854d402feade0f123605"  // From .env.local!
);
```

### 5. Controller calls service:
```php
$weatherData = $weatherService->getWeatherByCoordinates(
    36.138784,
    10.394076
);
```

### 6. Service makes API call:
```
GET https://api.openweathermap.org/data/2.5/weather?
    lat=36.138784&
    lon=10.394076&
    appid=10f4b8f78ff1854d402feade0f123605&  ← Your real key!
    units=metric&
    lang=fr
```

### 7. Returns weather data to browser

---

## Why This Works

### Environment Variable Priority:

```
.env          (priority: 1 - lowest)
  ↓ overridden by
.env.local    (priority: 2 - higher)
  ↓ overridden by
.env.prod     (priority: 3)
  ↓ overridden by
.env.prod.local (priority: 4 - highest)
```

**In your case**:
- `.env` has: `OPENWEATHER_API_KEY=your_api_key_here`
- `.env.local` has: `OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605`
- **Result**: `.env.local` wins! ✅

---

## Verification

### Check what Symfony sees:

```bash
# Dump all environment variables
php bin/console debug:container --env-vars

# Check specific variable
php bin/console debug:container --env-var=OPENWEATHER_API_KEY
```

**Output**:
```
OPENWEATHER_API_KEY
-------------------
10f4b8f78ff1854d402feade0f123605
```

### Check service configuration:

```bash
php bin/console debug:container WeatherService
```

**Output**:
```
Service ID: App\CultureParcelle\Service\WeatherService
Class: App\CultureParcelle\Service\WeatherService
Arguments:
  - @http_client
  - @logger
  - "10f4b8f78ff1854d402feade0f123605"  ← Your API key!
```

---

## What Happens in Different Environments

### Development (your machine):
```
.env          → OPENWEATHER_API_KEY=your_api_key_here
.env.local    → OPENWEATHER_API_KEY=10f4b8f78ff1854d402feade0f123605
Result: Uses 10f4b8f78ff1854d402feade0f123605 ✅
```

### Team Member's Machine:
```
.env          → OPENWEATHER_API_KEY=your_api_key_here (from git)
.env.local    → OPENWEATHER_API_KEY=their_own_key_abc123 (they create)
Result: Uses their_own_key_abc123 ✅
```

### Production Server:
```
.env          → OPENWEATHER_API_KEY=your_api_key_here (from git)
.env.local    → OPENWEATHER_API_KEY=production_key_xyz789 (on server)
Result: Uses production_key_xyz789 ✅
```

---

## Summary

1. **Symfony loads** `.env` first (placeholder)
2. **Symfony loads** `.env.local` second (your real key) - OVERRIDES step 1
3. **services.yaml** reads the environment variable
4. **Dependency Injection** injects the key into WeatherService
5. **WeatherService** uses the key to call OpenWeather API
6. **API key never appears** in your code - only in config files
7. **`.env.local` never goes to git** - each developer has their own

**Result**: Your real API key is used, but never committed to GitHub! 🔒
