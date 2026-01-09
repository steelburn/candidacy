import { defineStore } from 'pinia'
import { ref } from 'vue'
import { adminAPI } from '../services/api'

export const useThemeStore = defineStore('theme', () => {
    // State
    const primaryColor = ref('#4F46E5')
    const darkMode = ref(false)
    const sidebarWidth = ref('250px')
    const maxContentWidth = ref('1200px')
    const dateFormat = ref('YYYY-MM-DD')
    const timeFormat = ref('HH:mm')
    const itemsPerPage = ref(10)

    const loading = ref(false)

    // Actions
    const initializeTheme = async () => {
        loading.value = true
        try {
            // Fetch public settings or specific UI settings
            // Assuming we have an endpoint for public config or we filter locally
            const response = await adminAPI.getDetailedSettings()
            const settings = response.data.settings || []

            settings.forEach(setting => {
                if (setting.category !== 'ui') return

                switch (setting.key) {
                    case 'ui.primary_color':
                        primaryColor.value = setting.value
                        break
                    case 'ui.enable_dark_mode':
                        darkMode.value = setting.value === true || setting.value === 'true'
                        break
                    case 'ui.sidebar_width':
                        // Validate: if 0, empty, or invalid, use default 250px
                        const sideVal = parseInt(String(setting.value).replace('px', ''), 10)
                        if (!sideVal || sideVal <= 0) {
                            sidebarWidth.value = '250px'
                        } else {
                            sidebarWidth.value = sideVal + 'px'
                        }
                        break
                    case 'ui.max_content_width':
                        // Validate: if 0, empty, or invalid, use default 1200px
                        const widthVal = parseInt(String(setting.value).replace('px', ''), 10)
                        if (!widthVal || widthVal <= 0) {
                            maxContentWidth.value = '1200px'
                        } else {
                            maxContentWidth.value = widthVal + 'px'
                        }
                        break
                    case 'ui.date_format':
                        dateFormat.value = setting.value
                        break
                    case 'ui.time_format':
                        timeFormat.value = setting.value
                        break
                    case 'ui.items_per_page':
                        itemsPerPage.value = Number(setting.value)
                        break
                }
            })

            applyTheme()
        } catch (error) {
            console.error('Failed to initialize theme:', error)
        } finally {
            loading.value = false
        }
    }

    const applyTheme = () => {
        const root = document.documentElement

        // Apply Colors
        root.style.setProperty('--primary-color', primaryColor.value)

        // Apply Layout
        root.style.setProperty('--sidebar-width', sidebarWidth.value)
        root.style.setProperty('--max-content-width', maxContentWidth.value)

        // Apply Dark Mode
        if (darkMode.value) {
            document.body.classList.add('dark-mode')
        } else {
            document.body.classList.remove('dark-mode')
        }
    }

    return {
        primaryColor,
        darkMode,
        sidebarWidth,
        maxContentWidth,
        dateFormat,
        timeFormat,
        itemsPerPage,
        loading,
        initializeTheme,
        applyTheme
    }
})
