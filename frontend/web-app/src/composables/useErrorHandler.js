import { parseApiError, logError } from '../utils/errorHandler'
import { useNotification } from './useNotification'

/**
 * Composable for error handling
 */
export function useErrorHandler() {
    const { error: showError } = useNotification()

    /**
     * Handle API error
     */
    function handleError(error, options = {}) {
        const {
            showNotification = true,
            logToConsole = true,
            context = {}
        } = options

        const errorMessage = parseApiError(error)

        if (logToConsole) {
            logError(error, context)
        }

        if (showNotification) {
            showError(errorMessage)
        }

        return errorMessage
    }

    /**
     * Handle error with custom message
     */
    function handleErrorWithMessage(error, customMessage, options = {}) {
        const { error: showError } = useNotification()
        const { logToConsole = true, context = {} } = options

        if (logToConsole) {
            logError(error, { ...context, customMessage })
        }

        showError(customMessage)

        return customMessage
    }

    /**
     * Try-catch wrapper with error handling
     */
    async function tryCatch(fn, errorMessage = null) {
        try {
            return await fn()
        } catch (error) {
            if (errorMessage) {
                handleErrorWithMessage(error, errorMessage)
            } else {
                handleError(error)
            }
            throw error
        }
    }

    return {
        handleError,
        handleErrorWithMessage,
        tryCatch
    }
}
