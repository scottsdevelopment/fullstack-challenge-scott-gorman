This document tracks current todos, improvements, and future enhancements for the Weather App project.

---

## Backend / Core
- [x] **Decouple cache from weather client** — move caching logic into a separate layer/service so the client only handles API calls.  
- [ ] **Add additional weather API providers** — implement more `WeatherProvider` classes (e.g., OpenWeather, AccuWeather) for redundancy/fallback.  
- [ ] **Better seeding of data** — seed database with valid geolocations and test users to avoid invalid lat/lon coordinates.  
- [x] **Improve queue + scheduler setup** — configure Docker sidecars for `queue:work` and `schedule:work`, verify cron jobs.  
- [ ] **Error/degraded-mode handling** — controllers should gracefully degrade when provider data is missing or stale.  
- [x] **Jobs for cache warm/sync** — queue background jobs to fetch and refresh weather data regularly.  
- [ ] **Investigate missing data points** — investigate why some data points are missing

---

## Frontend
- [x] **Improve UI for user/weather panels** — highlight selected user, improve list styling, show skeleton loaders, and better temperature formatting.  
- [ ] **Add websocket support** — replace polling with real-time updates to weather widgets.  
- [x] **More weather widget detail** — show condition icons, wind speed, humidity, and observation timestamp.  
- [x] **Better handling of empty/error states** — clear UX when no users are loaded or weather is unavailable.  
- [ ] **Mobile UX** — implement a mobile UX for smaller displays

---

## Testing & Dev Experience
- [ ] **Expand test coverage** — add more coverage tests for controllers, and unit tests for cache and DTOs.  
- [x] **Toggle integration tests via env** — standardize on `RUN_INTEGRATION_TESTS` flag.  
- [ ] **Improve developer seeding** — seed users, weather, and cache metadata for reliable local development.  
- [x] **Refactor weather data naming** — use descriptive names (e.g., `weatherData`) consistently instead of abbreviations.  

---

## Future Enhancements
- [ ] **User preferences** — allow users to set units (°F/°C), favorite locations, etc.  
- [ ] **Historical weather support** — fetch and display past observations.  
- [ ] **Forecast support** — integrate short-term forecast endpoints in addition to current conditions.  
- [ ] **Monitoring/metrics** — log provider latency, cache hit/miss, and queue performance.  

---
