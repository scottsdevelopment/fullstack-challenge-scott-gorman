<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useUsersStore } from '@/stores/users'
import UserListPanel from '@/components/UserListPanel.vue'
import WeatherWidget from '@/components/WeatherWidget.vue'

const users = useUsersStore()
const selectedId = ref<number | null>(null)
const loadingWeather = ref(false)

/** Consider weather stale if observed > 60 min ago (fallback to fetchedAt if you track it in the store) */
function isStale(weather: any | null | undefined) {
  if (!weather) return true
  const iso = weather.observedAtIso8601 as string | null | undefined
  if (!iso) return true
  const t = new Date(iso).getTime()
  if (Number.isNaN(t)) return true
  const ageMs = Date.now() - t
  return ageMs > 60 * 60 * 1000 // 60 minutes
}

onMounted(async () => {
  try {
    await users.fetchAll()

    if (users.list.length) {
      const firstId = users.list[0].id
      selectedId.value = firstId

      // Prefetch only if missing or stale to make the first render instant when possible
      const cached = users.getWeather(firstId).value
      if (isStale(cached)) {
        loadingWeather.value = true
        try {
          await users.fetchWeather(firstId)
        } finally {
          loadingWeather.value = false
        }
      }
    }
  } catch {
    // store handles its own error state
  }
})

const selectedUser = computed(() =>
  selectedId.value ? users.byId.get(selectedId.value) ?? null : null
)

const selectedWeather = computed(() =>
  selectedId.value ? users.getWeather(selectedId.value).value : null
)

watch(
  selectedId,
  async (id) => {
    if (!id) return
    const cached = users.getWeather(id).value
    if (isStale(cached)) {
      loadingWeather.value = true
      try {
        await users.fetchWeather(id)
      } finally {
        loadingWeather.value = false
      }
    } else {
      // cached & fresh; ensure no spinner
      loadingWeather.value = false
    }
  },
  { immediate: false }
)

function handleSelect(id: number) {
  selectedId.value = id
}
</script>

<template>
  <n-layout style="height: 100vh">
    <n-layout has-sider>
      <n-layout-sider
        bordered
        :width="300"
        class="bg-white h-full flex flex-col overflow-hidden"
      >
        <!-- Make only the list area scroll -->
        <div class="flex-1 min-h-0 overflow-y-auto">
          <user-list-panel
            :selected-id="selectedId"
            @select="handleSelect"
          />
        </div>
      </n-layout-sider>

      <n-layout-content>
        <div class="h-full flex flex-col min-h-0 p-6">
          <div class="flex-1 min-h-0 overflow-hidden">
            <weather-widget
              :weather="selectedWeather"
              :loading="loadingWeather"
            />
          </div>
        </div>
      </n-layout-content>
    </n-layout>
  </n-layout>
</template>
