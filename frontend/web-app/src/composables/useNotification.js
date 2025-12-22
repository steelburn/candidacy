import { ref } from 'vue'

const notifications = ref([])
let notificationId = 0

/**
 * Composable for toast notifications
 */
export function useNotification() {
    /**
     * Show a notification
     */
    function notify(message, type = 'info', duration = 3000) {
        const id = ++notificationId
        const notification = {
            id,
            message,
            type, // 'success', 'error', 'warning', 'info'
            duration
        }

        notifications.value.push(notification)

        if (duration > 0) {
            setTimeout(() => {
                remove(id)
            }, duration)
        }

        return id
    }

    /**
     * Show success notification
     */
    function success(message, duration = 3000) {
        return notify(message, 'success', duration)
    }

    /**
     * Show error notification
     */
    function error(message, duration = 5000) {
        return notify(message, 'error', duration)
    }

    /**
     * Show warning notification
     */
    function warning(message, duration = 4000) {
        return notify(message, 'warning', duration)
    }

    /**
     * Show info notification
     */
    function info(message, duration = 3000) {
        return notify(message, 'info', duration)
    }

    /**
     * Remove a notification
     */
    function remove(id) {
        const index = notifications.value.findIndex(n => n.id === id)
        if (index > -1) {
            notifications.value.splice(index, 1)
        }
    }

    /**
     * Clear all notifications
     */
    function clear() {
        notifications.value = []
    }

    return {
        notifications,
        notify,
        success,
        error,
        warning,
        info,
        remove,
        clear
    }
}
