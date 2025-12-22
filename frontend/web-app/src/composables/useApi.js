import { ref } from 'vue'
import api from '../services/api'

/**
 * Composable for API calls with loading and error states
 * 
 * @param {Function} apiFunction - The API function to call
 * @param {Object} options - Options for the API call
 * @returns {Object} API state and execute function
 */
export function useApi(apiFunction, options = {}) {
    const {
        immediate = false,
        onSuccess = null,
        onError = null,
        initialData = null
    } = options

    const data = ref(initialData)
    const loading = ref(false)
    const error = ref(null)

    /**
     * Execute the API call
     */
    async function execute(...args) {
        loading.value = true
        error.value = null

        try {
            const response = await apiFunction(...args)
            data.value = response.data

            if (onSuccess) {
                onSuccess(response.data)
            }

            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'An error occurred'

            if (onError) {
                onError(err)
            }

            throw err
        } finally {
            loading.value = false
        }
    }

    /**
     * Reset state
     */
    function reset() {
        data.value = initialData
        loading.value = false
        error.value = null
    }

    // Execute immediately if requested
    if (immediate) {
        execute()
    }

    return {
        data,
        loading,
        error,
        execute,
        reset
    }
}

/**
 * Composable for paginated API calls
 */
export function usePaginatedApi(apiFunction, options = {}) {
    const {
        perPage = 20,
        onSuccess = null,
        onError = null
    } = options

    const data = ref([])
    const loading = ref(false)
    const error = ref(null)
    const currentPage = ref(1)
    const totalPages = ref(1)
    const total = ref(0)

    async function fetchPage(page = 1) {
        loading.value = true
        error.value = null

        try {
            const response = await apiFunction({ page, per_page: perPage })

            data.value = response.data.data || response.data
            currentPage.value = response.data.current_page || page
            totalPages.value = response.data.last_page || 1
            total.value = response.data.total || data.value.length

            if (onSuccess) {
                onSuccess(response.data)
            }

            return response.data
        } catch (err) {
            error.value = err.response?.data?.message || err.message || 'An error occurred'

            if (onError) {
                onError(err)
            }

            throw err
        } finally {
            loading.value = false
        }
    }

    function nextPage() {
        if (currentPage.value < totalPages.value) {
            return fetchPage(currentPage.value + 1)
        }
    }

    function prevPage() {
        if (currentPage.value > 1) {
            return fetchPage(currentPage.value - 1)
        }
    }

    function goToPage(page) {
        if (page >= 1 && page <= totalPages.value) {
            return fetchPage(page)
        }
    }

    return {
        data,
        loading,
        error,
        currentPage,
        totalPages,
        total,
        fetchPage,
        nextPage,
        prevPage,
        goToPage
    }
}
