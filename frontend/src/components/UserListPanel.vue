<script setup lang="ts">
import { computed } from 'vue'
import { useUsersStore } from '@/stores/users'
import { PersonCircleOutline, ReloadOutline } from '@vicons/ionicons5'

defineProps<{ selectedId: number | null }>()
const emit = defineEmits<{ (e: 'select', id: number): void }>()

const users = useUsersStore()
const isEmpty = computed(() => !users.loading && users.list.length === 0)

function onSelect(id: number) {
  emit('select', id)
}

function fmtTempF(n: number | null | undefined) {
  return typeof n === 'number' && Number.isFinite(n) ? `${Math.round(n)}°F` : '—'
}
function userTempF(id: number) {
  const w = users.getWeather(id).value
  return fmtTempF(w?.temperatureFahrenheit ?? null)
}
function userCondition(id: number) {
  return users.getWeather(id).value?.conditionSummary || 'Current temperature'
}
</script>

<template>
  <div class="relative h-[100vh] flex flex-col">
    <!-- Global spinner centered -->
    <div class="absolute inset-0 flex items-center justify-center" v-if="users.loading">
      <n-icon size="32" class="animate-spin text-gray-500" :component="ReloadOutline" />
    </div>

    <div class="flex-1 overflow-hidden">
      <div class="h-full overflow-y-auto">
        <template v-if="isEmpty">
          <div class="p-6">
            <n-empty description="No users found" />
          </div>
        </template>

        <template v-else>
          <n-list hoverable clickable>
            <n-list-item v-for="u in users.list" :key="u.id" @click="onSelect(u.id)"
              class="relative rounded-md transition-colors" :class="{
                'bg-gray-50 ring-1 ring-gray-200': selectedId === u.id
              }" :aria-selected="selectedId === u.id" role="button" tabindex="0">

              <div v-if="selectedId === u.id" class="absolute inset-y-0 left-0 w-1 bg-blue-500 rounded-r" />

              <div
                class="absolute top-2 right-3 px-2 py-0.5 text-xs rounded-md bg-gray-100 text-gray-800 border border-gray-200"
                :title="userCondition(u.id)">
                {{ userTempF(u.id) }}
              </div>

              <n-thing>
                <template #avatar>
                  <n-avatar round size="large">
                    <n-icon>
                      <PersonCircleOutline />
                    </n-icon>
                  </n-avatar>
                </template>

                <template #header>
                  <span class="font-medium"
                    :class="{ 'text-black': selectedId === u.id, 'text-gray-800': selectedId !== u.id }">
                    {{ u.name }}
                  </span>
                </template>

                <template #description>
                  <div class="text-gray-600 text-sm">
                    {{ u.email }}
                  </div>
                </template>
              </n-thing>
            </n-list-item>
          </n-list>
        </template>
      </div>
    </div>
  </div>
</template>
