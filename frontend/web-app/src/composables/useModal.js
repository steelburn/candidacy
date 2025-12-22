import { ref } from 'vue'

/**
 * Composable for modal state management
 * 
 * @param {Boolean} initialState - Initial open/closed state
 * @returns {Object} Modal state and methods
 */
export function useModal(initialState = false) {
    const isOpen = ref(initialState)
    const data = ref(null)

    /**
     * Open the modal
     */
    function open(modalData = null) {
        data.value = modalData
        isOpen.value = true
    }

    /**
     * Close the modal
     */
    function close() {
        isOpen.value = false
        // Clear data after animation completes
        setTimeout(() => {
            data.value = null
        }, 300)
    }

    /**
     * Toggle modal state
     */
    function toggle() {
        if (isOpen.value) {
            close()
        } else {
            open()
        }
    }

    return {
        isOpen,
        data,
        open,
        close,
        toggle
    }
}
