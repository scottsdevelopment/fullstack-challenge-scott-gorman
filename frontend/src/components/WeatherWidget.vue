<script setup lang="ts">
import { NCard, NDivider, NSkeleton, NGrid, NGi, NStatistic } from 'naive-ui'
import type { WeatherBrief } from '@/types'

defineProps<{
  weather: WeatherBrief | null
  loading?: boolean
}>()

function toNum(n: unknown) {
  return typeof n === 'number' && Number.isFinite(n) ? n : null
}
function fmtTempF(t: unknown) {
  const n = toNum(t as number)
  return n == null ? '—' : `${Math.round(n)}°F`
}
function fmtTempC(t: unknown) {
  const n = toNum(t as number)
  return n == null ? '—' : `${Math.round(n)}°C`
}
function fmtTime(s: string | null | undefined) {
  if (!s) return '—'
  const d = new Date(s)
  return Number.isNaN(d.getTime()) ? s : d.toLocaleTimeString()
}
function fmtPct(p: unknown) {
  const n = toNum(p as number)
  return n == null ? '—' : `${Math.round(n)}%`
}
function fmtPressureMb(v: unknown) {
  const n = toNum(v as number)
  return n == null ? '—' : `${n.toFixed(0)} mb`
}
function fmtWind(mph?: number | null, kph?: number | null) {
  const m = toNum(mph ?? null)
  if (m != null) return `${Math.round(m)} mph`
  const k = toNum(kph ?? null)
  return k != null ? `${Math.round(k)} km/h` : '—'
}
</script>

<template>
  <div class="h-full w-full flex items-center justify-center p-6">
    <n-card class="max-w-2xl w-full elevated-card relative overflow-hidden">
      <!-- Header (icon left, text right) -->
      <div class="flex items-start gap-4 p-6 pt-5 pb-3">
        <div v-if="loading" class="space-y-3 p-4">
          <n-skeleton text :repeat="2" :width="'80%'" />
        </div>

        <!-- Make this a flex row so icon sits left of city/state -->
        <div v-else class="flex items-center gap-4">
          <div v-if="weather?.iconUrl" class="icon-tile" :style="{ backgroundImage: `url(${weather.iconUrl})` }" />
          <div class="leading-tight">
            <div class="text-xl font-semibold">
              {{ weather?.city || '—' }}<span v-if="weather?.state">, {{ weather?.state }}</span>
            </div>
            <div class="text-xs text-gray-600">
              {{ fmtTime(weather?.observedAtIso8601) }}
            </div>
          </div>
        </div>
      </div>

      <n-divider class="mt-0" />

      <!-- Loading -->
      <div v-if="loading" class="space-y-3 p-4">
        <n-skeleton text :repeat="4" />
      </div>

      <!-- Content -->
      <div v-else class="space-y-6 p-6 pt-4">
        <!-- Headline -->
        <div class="flex items-end gap-6">
          <div class="flex-1">
            <div class="text-2xl font-bold">
              {{ weather?.conditionSummary ?? '—' }}
            </div>
            <div class="text-5xl font-extrabold leading-none">
              {{ fmtTempF(weather?.temperatureFahrenheit) }}
            </div>
            <div class="text-sm text-gray-500 mt-1">
              {{ fmtTempC(weather?.temperatureCelsius) }}
            </div>
          </div>
        </div>

        <!-- Stats -->
        <n-grid :cols="4" :x-gap="12" :y-gap="12" responsive="screen">
          <n-gi :span="2">
            <n-statistic
              label="Wind"
              :value="fmtWind(weather?.windSpeedMilesPerHour, weather?.windSpeedKilometersPerHour)"
            />
          </n-gi>
          <n-gi :span="2">
            <n-statistic label="Humidity" :value="fmtPct(weather?.relativeHumidityPercent)" />
          </n-gi>
          <n-gi :span="2">
            <n-statistic label="Pressure" :value="fmtPressureMb(weather?.pressureMillibars)" />
          </n-gi>
        </n-grid>
      </div>
    </n-card>
  </div>
</template>

<style scoped>
.elevated-card {
  border-radius: 14px;
  box-shadow:
    0 1px 3px rgba(0,0,0,0.06),
    0 10px 28px rgba(0,0,0,0.14); /* a touch stronger */
}

/* Square weather icon with subtle depth */
.icon-tile {
  width: 64px;
  height: 64px;
  border-radius: 12px;          /* square with soft corners */
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  box-shadow:
    inset 0 0 0 1px rgba(255,255,255,0.35),
    0 2px 6px rgba(0,0,0,0.1),
    0 10px 18px rgba(0,0,0,0.08);
  background-color: #f5f7fb;    /* fallback tint */
  position: relative;
}

/* soft top highlight & bottom shade for depth */
.icon-tile::before {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(180deg, rgba(255,255,255,0.65), rgba(255,255,255,0) 40%);
  pointer-events: none;
}

.icon-tile::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: inherit;
  background: linear-gradient(0deg, rgba(0,0,0,0.06), rgba(0,0,0,0) 55%);
  pointer-events: none;
}
</style>
