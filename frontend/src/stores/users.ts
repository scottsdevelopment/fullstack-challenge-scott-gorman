import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { ID, User, UsersIndexResponse, UserShowResponse, WeatherBrief } from '../types'
import { api } from '../lib/api'

const WEATHER_TTL_MS = 5 * 60 * 1000 // 5 minutes

export const useUsersStore = defineStore('users', () => {
  // state
  const list = ref<User[]>([])
  const byId = ref<Map<ID, User>>(new Map())
  const weather = ref<Map<ID, WeatherBrief | null>>(new Map())
  const weatherAt = ref<Map<ID, number>>(new Map()) // timestamp per user
  const loading = ref(false)
  const loadingIds = ref<Set<ID>>(new Set())
  const error = ref<string | null>(null)
  const hasLoadedOnce = ref(false)

  // getters
  const hasData = computed(() => list.value.length > 0)
  const getUser = (id: ID) => computed(() => byId.value.get(id) ?? null)
  const getWeather = (id: ID) => computed(() => weather.value.get(id) ?? null)
  function isLoadingUser(id: ID) { return loadingIds.value.has(id) }

  // helpers
  function isWeatherFresh(id: ID): boolean {
    const ts = weatherAt.value.get(id)
    if (!ts) return false
    return Date.now() - ts < WEATHER_TTL_MS
  }

  // actions
  async function fetchAll() {
    const shouldSpin = !hasLoadedOnce.value
    if (shouldSpin) loading.value = true

    error.value = null
    try {
      const data = await api<UsersIndexResponse>('/users')
      list.value = data.users ?? []
      byId.value = new Map(list.value.map(u => [u.id, u]))

      const now = Date.now()
      for (const u of list.value) {
        const weatherData = (u as any).weather ?? null
        if (weatherData !== undefined) {
          weather.value.set(u.id, weatherData)
          weatherAt.value.set(u.id, now)
        }
      }

      hasLoadedOnce.value = true
    } catch (e: any) {
      error.value = e?.message ?? 'Failed to load users'
      throw e
    } finally {
      if (shouldSpin) loading.value = false
    }
  }


  async function fetchOne(id: ID, force = false) {
    if (!force && byId.value.has(id)) return byId.value.get(id)!
    const data = await api<UserShowResponse>(`/users/${id}`)
    const u = data.user
    byId.value.set(u.id, u)
    const idx = list.value.findIndex(x => x.id === u.id)
    if (idx >= 0) list.value[idx] = u

    const weatherData = (u as any).weather ?? null
    if (weatherData !== undefined) {
      weather.value.set(u.id, weatherData)
      weatherAt.value.set(u.id, Date.now())
    }
    return u
  }

  async function fetchWeather(id: ID, { force = false } = {}) {
    if (!force && weather.value.has(id) && isWeatherFresh(id)) {
      return weather.value.get(id) ?? null
    }

    loadingIds.value.add(id)
    try {
      const data = await api<UserShowResponse>(`/users/${id}`)
      const u = data.user
      byId.value.set(u.id, u)
      const weatherData = (u as any).weather ?? null
      weather.value.set(u.id, weatherData)
      weatherAt.value.set(u.id, Date.now())
      return weatherData
    } finally {
      loadingIds.value.delete(id)
    }
  }

  return {
    // state
    list, byId, weather, loading, error,
    // getters
    hasData, getUser, getWeather, isLoadingUser,
    // actions
    fetchAll, fetchOne, fetchWeather,
    // optional helper
    isWeatherFresh,
  }
})
