import { computed } from 'vue'
import { useThemeStore } from '../stores/useThemeStore'
import dayjs from 'dayjs'

export function useFormat() {
    const themeStore = useThemeStore()

    const formatDate = (date) => {
        if (!date) return ''
        return dayjs(date).format(themeStore.dateFormat)
    }

    const formatTime = (date) => {
        if (!date) return ''
        return dayjs(date).format(themeStore.timeFormat)
    }

    const formatDateTime = (date) => {
        if (!date) return ''
        return dayjs(date).format(`${themeStore.dateFormat} ${themeStore.timeFormat}`)
    }

    return {
        formatDate,
        formatTime,
        formatDateTime,
        dateFormat: computed(() => themeStore.dateFormat),
        timeFormat: computed(() => themeStore.timeFormat)
    }
}
