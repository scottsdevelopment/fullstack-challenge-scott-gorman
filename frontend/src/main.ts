import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import './style.css'
import { create, NLayout, NLayoutSider, NLayoutContent, NSplit,
  NList, NListItem, NThing, NAvatar, NCard, NEmpty, NButton, NDivider,
  NSpin, NSkeleton, NTag, NIcon, NGrid, NGi, NStatistic } from 'naive-ui'

const naive = create({
  components: [
    NLayout, NLayoutSider, NLayoutContent, NSplit,
    NList, NListItem, NThing, NAvatar, NCard, NEmpty, NButton, NDivider,
    NSpin, NSkeleton, NTag, NIcon, NGrid, NGi, NStatistic
  ]
})

const app = createApp(App)
app.use(createPinia())
app.use(naive)
app.mount('#app')
