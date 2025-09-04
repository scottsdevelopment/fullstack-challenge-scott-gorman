<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import { useUsersStore } from '@/stores/users'
import UserListPanel from '@/components/UserListPanel.vue'
import WeatherWidget from '@/components/WeatherWidget.vue'

const users = useUsersStore()
const selectedId = ref<number | null>(null)
const loadingWeather = ref(false)

onMounted(async () => {
  try {
    await users.fetchAll()
    if (users.list.length) {
      selectedId.value = users.list[0].id
    }
  } catch (e) {
    // already handled in store's error
  }
})

const selectedUser = computed(() =>
  selectedId.value ? users.byId.get(selectedId.value) ?? null : null
)

const selectedWeather = computed(() =>
  selectedId.value ? users.getWeather(selectedId.value).value : null
)

watch(selectedId, async (id) => {
  if (!id) return
  loadingWeather.value = true
  try {
    await users.fetchWeather(id)
  } finally {
    loadingWeather.value = false
  }
})

function handleSelect(id: number) {
  selectedId.value = id
}
</script>

<template>
  <n-layout style="height: 100vh"> <!-- was min-h-screen -->
    <n-layout has-sider>
      <n-layout-sider
        bordered
        :width="300"
        class="bg-white h-full flex flex-col overflow-hidden"
      >
        <!-- make the list area scroll, not the whole page -->
        <div class="flex-1 min-h-0 overflow-y-auto">
          <user-list-panel
            :selected-id="selectedId"
            @select="handleSelect"
          />
        </div>
      </n-layout-sider>

      <n-layout-content>
        <!-- allow this column to shrink/scroll correctly -->
        <div class="h-full flex flex-col min-h-0 p-6">
          <!-- main area must be allowed to grow but not force page height -->
          <div class="flex-1 min-h-0 overflow-hidden">
            <weather-widget
              :title="selectedUser ? `Current Weather` : 'Current Weather'"
              :weather="selectedWeather"
              :loading="loadingWeather"
            />
          </div>
        </div>
      </n-layout-content>
    </n-layout>
  </n-layout>
</template>
