<script setup lang="ts">
import { NCard, NDivider, NSkeleton, NGrid, NGi, NStatistic } from 'naive-ui'
import type { User, WeatherBrief } from '@/types'

withDefaults(defineProps<{
  weather: WeatherBrief | null
  loading?: boolean
  selectedUser?: User | null
}>(), {
  loading: false,
  selectedId: null,
})

function toNum(n: unknown) { return typeof n === 'number' && Number.isFinite(n) ? n : null }
function fmtTempF(t: unknown) { const n = toNum(t as number); return n == null ? '—' : `${Math.round(n)}°F` }
function fmtTempC(t: unknown) { const n = toNum(t as number); return n == null ? '—' : `${Math.round(n)}°C` }
function fmtTime(s: string | null | undefined) { if (!s) return '—'; const d = new Date(s); return Number.isNaN(d.getTime()) ? s : d.toLocaleTimeString() }
function fmtPct(p: unknown) { const n = toNum(p as number); return n == null ? '—' : `${Math.round(n)}%` }
function fmtPressureMb(v: unknown) { const n = toNum(v as number); return n == null ? '—' : `${n.toFixed(0)} mb` }
function fmtWind(mph?: number | null, kph?: number | null) { const m = toNum(mph ?? null); if (m != null) return `${Math.round(m)} mph`; const k = toNum(kph ?? null); return k != null ? `${Math.round(k)} km/h` : '—' }
</script>

<template>
  <div class="h-full w-full flex items-center justify-center p-6">
    <n-card class="max-w-2xl w-full elevated-card relative overflow-hidden">
      <template v-if="loading">
        <div class="space-y-3 p-4">
          <n-skeleton text :repeat="2" />
        </div>
        <n-divider class="mt-0" />
        <div class="space-y-3 p-4">
          <n-skeleton text :repeat="4" />
        </div>
      </template>

      <template v-else-if="!weather">
        <div class="text-center py-12 text-gray-500">
          <div class="text-lg font-semibold">Weather data unavailable</div>
          <div class="text-sm mt-1">We couldn't retrieve the latest observation. Please try again later.</div>
        </div>
      </template>

      <template v-else>
        <!-- Header: left (icon + city/time), right (user card pulled via getUser(selectedId)) -->
        <div class="flex items-start justify-between gap-4 p-6 pt-5 pb-3">
          <div class="flex items-start gap-4">
            <div class="icon-tile flex items-center justify-center text-gray-400"
              :style="weather.iconUrl ? { backgroundImage: `url(${weather.iconUrl})` } : {}">
              <svg v-if="!weather.iconUrl" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                class="w-8 h-8">
                <path d="M6 19a6 6 0 1 1 2.24-11.59A7.5 7.5 0 0 1 20.5 13a5.5 5.5 0 0 1-5.5 6H6z" />
              </svg>
            </div>
            <div class="leading-tight">
              <div class="text-xl font-semibold">
                {{ weather.city || '—' }}<span v-if="weather.state">, {{ weather.state }}</span>
              </div>
              <div class="text-xs text-gray-600">{{ fmtTime(weather.observedAtIso8601) }}</div>
            </div>
          </div>

          <div v-if="selectedUser" class="shrink-0 min-w-[200px]">
            <div class="font-semibold truncate">{{ selectedUser.name }}</div>
            <div class="text-xs text-gray-500 truncate">{{ selectedUser.email }}</div>
          </div>
        </div>

        <n-divider class="mt-0" />

        <div v-if="toNum(weather?.temperatureFahrenheit) == null && toNum(weather?.temperatureCelsius) == null"
          class="mx-6 mt-4 rounded-md bg-yellow-50 text-yellow-800 text-sm px-4 py-2">
          Some weather details are missing. Live temperature isn't available right now, so values may be incomplete.
        </div>

        <div class="space-y-6 p-6 pt-4">
          <div class="flex items-end gap-6">
            <div class="flex-1 space-y-1">
              <div v-if="weather.conditionSummary" class="text-2xl font-bold">{{ weather.conditionSummary }}</div>
              <div v-if="toNum(weather.temperatureFahrenheit) != null" class="text-5xl font-extrabold leading-none">
                {{ fmtTempF(weather.temperatureFahrenheit) }}
              </div>
              <div v-if="toNum(weather.temperatureCelsius) != null" class="text-sm text-gray-500 mt-1">
                {{ fmtTempC(weather.temperatureCelsius) }}
              </div>
            </div>
          </div>

          <n-grid v-if="
            (toNum(weather?.windSpeedMilesPerHour) != null || toNum(weather?.windSpeedKilometersPerHour) != null)
            || toNum(weather?.relativeHumidityPercent) != null
            || toNum(weather?.pressureMillibars) != null
          " :cols="4" :x-gap="12" :y-gap="12" responsive="screen" class="mt-6">
            <n-gi
              v-if="toNum(weather?.windSpeedMilesPerHour) != null || toNum(weather?.windSpeedKilometersPerHour) != null"
              :span="2">
              <n-statistic label="Wind"
                :value="fmtWind(weather.windSpeedMilesPerHour, weather.windSpeedKilometersPerHour)" />
            </n-gi>
            <n-gi v-if="toNum(weather?.relativeHumidityPercent) != null" :span="2">
              <n-statistic label="Humidity" :value="fmtPct(weather.relativeHumidityPercent)" />
            </n-gi>
            <n-gi v-if="toNum(weather?.pressureMillibars) != null" :span="2">
              <n-statistic label="Pressure" :value="fmtPressureMb(weather.pressureMillibars)" />
            </n-gi>
          </n-grid>
        </div>
      </template>
    </n-card>
  </div>
</template>

<style scoped>
.elevated-card {
  border-radius: 14px;
  box-shadow:
    0 1px 3px rgba(0, 0, 0, 0.06),
    0 10px 28px rgba(0, 0, 0, 0.14);
}

.icon-tile {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  box-shadow:
    inset 0 0 0 1px rgba(255, 255, 255, 0.35),
    0 2px 6px rgba(0, 0, 0, 0.1),
    0 10px 18px rgba(0, 0, 0, 0.08);
  background-color: #f5f7fb;
  position: relative;
}

.icon-tile::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.65), rgba(255, 255, 255, 0) 40%);
  pointer-events: none;
}

.icon-tile::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(0deg, rgba(0, 0, 0, 0.06), rgba(0, 0, 0, 0) 55%);
  pointer-events: none;
}
</style>
