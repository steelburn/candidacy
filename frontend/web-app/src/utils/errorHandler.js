/**
 * Error handling utilities
 */

/**
 * Parse API error response
 */
export function parseApiError(error) {
    if (!error) return 'An unknown error occurred'

    // Check for response data
    if (error.response?.data) {
        const data = error.response.data

        // Laravel validation errors
        if (data.errors && typeof data.errors === 'object') {
            const errorMessages = Object.values(data.errors).flat()
            return errorMessages.join(', ')
        }

        // Standard error message
        if (data.message) {
            return data.message
        }

        // Error string
        if (typeof data === 'string') {
            return data
        }
    }

    // Network errors
    if (error.message === 'Network Error') {
        return 'Network error. Please check your connection.'
    }

    // Timeout errors
    if (error.code === 'ECONNABORTED') {
        return 'Request timeout. Please try again.'
    }

    // HTTP status errors
    if (error.response?.status) {
        const status = error.response.status
        switch (status) {
            case 400:
                return 'Bad request. Please check your input.'
            case 401:
                return 'Unauthorized. Please log in again.'
            case 403:
                return 'Access forbidden.'
            case 404:
                return 'Resource not found.'
            case 422:
                return 'Validation error. Please check your input.'
            case 500:
                return 'Server error. Please try again later.'
            case 503:
                return 'Service unavailable. Please try again later.'
            default:
                return `Error ${status}: ${error.message || 'Unknown error'}`
        }
    }

    // Fallback
    return error.message || 'An error occurred'
}

/**
 * Log error to console (can be extended to send to logging service)
 */
export function logError(error, context = {}) {
    console.error('Error:', {
        message: parseApiError(error),
        error,
        context,
        timestamp: new Date().toISOString()
    })

    // TODO: Send to logging service (e.g., Sentry, LogRocket)
}

/**
 * Handle form validation errors from API
 */
export function handleValidationErrors(error) {
    if (error.response?.data?.errors) {
        return error.response.data.errors
    }
    return {}
}
