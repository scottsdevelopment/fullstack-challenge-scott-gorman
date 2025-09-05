<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useUsersStore } from '@/stores/users'
import UserListPanel from '@/components/UserListPanel.vue'
import WeatherWidget from '@/components/WeatherWidget.vue'

const users = useUsersStore()
const selectedId = ref<number | null>(null)

let pollHandle: number | null = null
const POLL_MS = 15 * 60 * 1000 // 15 minutes

onMounted(async () => {
  try {
    await users.fetchAll()

    if (users.list.length) {
      selectedId.value = users.list[0].id
    }

    // Poll the entire dataset (users + weather) every 15 minutes
    pollHandle = window.setInterval(() => {
      users.fetchAll(true) // drop "true" if your store doesn't use it
    }, POLL_MS)
  } catch {
    // store handles its own error state
  }
})

onUnmounted(() => {
  if (pollHandle != null) {
    clearInterval(pollHandle)
    pollHandle = null
  }
})

const selectedUser = computed(() =>
  selectedId.value ? users.byId.get(selectedId.value) ?? null : null
)

const selectedWeather = computed(() =>
  selectedId.value ? users.getWeather(selectedId.value).value : null
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
        <div class="flex-1 min-h-0 overflow-y-auto">
          <user-list-panel
            :selected-id="selectedId"
            @select="handleSelect"
          />
        </div>
      </n-layout-sider>

      <n-layout-content>
        <div class="h-full flex flex-col min-h-0 p-6 relative">
          <div class="flex-1 min-h-0 overflow-hidden">
            <weather-widget
              :weather="selectedWeather"
              :loading="users.loading"
            />
          </div>
        </div>
      </n-layout-content>
    </n-layout>
  </n-layout>
</template>
