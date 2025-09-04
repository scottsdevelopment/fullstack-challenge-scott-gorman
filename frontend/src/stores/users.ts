import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { ID, User, UsersIndexResponse, UserShowResponse, WeatherBrief } from '../types'
import { api } from '../lib/api'

export const useUsersStore = defineStore('users', () => {
  // state
  const list       = ref<User[]>([])
  const byId       = ref<Map<ID, User>>(new Map())
  const weather    = ref<Map<ID, WeatherBrief | null>>(new Map())
  const loading    = ref(false)
  const loadingIds = ref<Set<ID>>(new Set())
  const error      = ref<string | null>(null)

  // getters
  const hasData = computed(() => list.value.length > 0)
  const getUser = (id: ID) => computed(() => byId.value.get(id) ?? null)
  const getWeather = (id: ID) => computed(() => weather.value.get(id) ?? null)

  // actions
  async function fetchAll(force = false) {
    if (hasData.value && !force) return
    loading.value = true
    error.value = null
    try {
      const data = await api<UsersIndexResponse>('/users')
      list.value = data.users ?? []
      byId.value = new Map(list.value.map(u => [u.id, u]))
    } catch (e: any) {
      error.value = e?.message ?? 'Failed to load users'
      throw e
    } finally {
      loading.value = false
    }
  }

  async function fetchOne(id: ID, force = false) {
    if (!force && byId.value.has(id)) return byId.value.get(id)!
    // hit the show endpoint and refresh this user specifically
    const data = await api<UserShowResponse>(`/users/${id}`)
    const u = data.user
    // update user map
    byId.value.set(u.id, u)
    // also refresh list if it already exists
    const idx = list.value.findIndex(x => x.id === u.id)
    if (idx >= 0) list.value[idx] = u
    // store weather if present
    weather.value.set(u.id, (u as any).weather ?? null)
    return u
  }

  async function fetchWeather(id: ID, { force = false } = {}) {
    if (weather.value.has(id) && !force) return weather.value.get(id) ?? null
    loadingIds.value.add(id)
    try {
      const data = await api<UserShowResponse>(`/users/${id}`)
      const u = data.user
      // keep user fresh
      byId.value.set(u.id, u)
      // and stash weather
      weather.value.set(u.id, (u as any).weather ?? null)
      return (u as any).weather ?? null
    } finally {
      loadingIds.value.delete(id)
    }
  }

  function isLoadingUser(id: ID) {
    return loadingIds.value.has(id)
  }

  return {
    // state
    list, byId, weather, loading, error,
    // getters
    hasData, getUser, getWeather, isLoadingUser,
    // actions
    fetchAll, fetchOne, fetchWeather,
  }
})
