import { ref, computed } from 'vue'

/**
 * Composable for table/list state management
 * 
 * @param {Object} options - Configuration options
 * @returns {Object} Table state and methods
 */
export function useTable(options = {}) {
    const {
        initialSortBy = null,
        initialSortOrder = 'asc',
        initialFilters = {},
        perPage = 20
    } = options

    const sortBy = ref(initialSortBy)
    const sortOrder = ref(initialSortOrder)
    const filters = ref({ ...initialFilters })
    const currentPage = ref(1)
    const searchQuery = ref('')

    /**
     * Update sort column
     */
    function updateSort(column) {
        if (sortBy.value === column) {
            // Toggle sort order
            sortOrder.value = sortOrder.value === 'asc' ? 'desc' : 'asc'
        } else {
            sortBy.value = column
            sortOrder.value = 'asc'
        }
    }

    /**
     * Update filter
     */
    function updateFilter(key, value) {
        filters.value[key] = value
        currentPage.value = 1 // Reset to first page when filtering
    }

    /**
     * Clear all filters
     */
    function clearFilters() {
        filters.value = { ...initialFilters }
        searchQuery.value = ''
        currentPage.value = 1
    }

    /**
     * Update search query
     */
    function updateSearch(query) {
        searchQuery.value = query
        currentPage.value = 1 // Reset to first page when searching
    }

    /**
     * Get query parameters for API call
     */
    const queryParams = computed(() => {
        return {
            page: currentPage.value,
            per_page: perPage,
            sort_by: sortBy.value,
            sort_order: sortOrder.value,
            search: searchQuery.value,
            ...filters.value
        }
    })

    /**
     * Reset table state
     */
    function reset() {
        sortBy.value = initialSortBy
        sortOrder.value = initialSortOrder
        filters.value = { ...initialFilters }
        currentPage.value = 1
        searchQuery.value = ''
    }

    return {
        sortBy,
        sortOrder,
        filters,
        currentPage,
        searchQuery,
        queryParams,
        updateSort,
        updateFilter,
        clearFilters,
        updateSearch,
        reset
    }
}
