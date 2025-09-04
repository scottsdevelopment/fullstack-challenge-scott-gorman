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
</script>

<template>
  <!-- Full viewport height; the inner list area scrolls -->
  <div class="relative h-[100vh] flex flex-col">
    <!-- Tiny loading spinner in the top-right (no title bar) -->
    <div class="absolute top-2 right-3" v-if="users.loading">
      <n-icon size="18" class="animate-spin text-gray-500">
        <ReloadOutline />
      </n-icon>
    </div>

    <!-- Scrollable list area -->
    <div class="flex-1 overflow-hidden">
      <div class="h-full overflow-y-auto">
        <template v-if="isEmpty">
          <div class="p-6">
            <n-empty description="No users found" />
          </div>
        </template>

        <template v-else>
          <n-list hoverable clickable>
            <n-list-item
              v-for="u in users.list"
              :key="u.id"
              @click="onSelect(u.id)"
            >
              <n-thing>
                <template #avatar>
                  <!-- Prefer weather icon if present -->
                  <n-avatar
                    v-if="(u as any).weather?.iconUrl"
                    round size="large"
                    :src="(u as any).weather.iconUrl"
                  />
                  <n-avatar v-else round size="large">
                    <n-icon><PersonCircleOutline /></n-icon>
                  </n-avatar>
                </template>

                <template #header>
                  <div class="flex items-center justify-between">
                    <span
                      class="font-medium"
                      :class="{'text-black': selectedId === u.id, 'text-gray-800': selectedId !== u.id}"
                    >
                      {{ u.name }}
                    </span>
                    <n-tag size="small" :type="selectedId === u.id ? 'success' : 'default'">
                      ID {{ u.id }}
                    </n-tag>
                  </div>
                </template>

                <template #description>
                  <div class="text-gray-600 text-sm">
                    {{ u.email }}
                  </div>
                  <div class="text-gray-500 text-xs mt-1 flex items-center gap-2">
                    <span>{{ u.city }}, {{ u.state }}</span>
                    <span>•</span>
                    <span>{{ fmtTempF((u as any).weather?.temperatureFahrenheit) }}</span>
                    <n-avatar
                      v-if="(u as any).weather?.iconUrl"
                      :src="(u as any).weather.iconUrl"
                      size="small"
                      class="!h-5 !w-5"
                    />
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
