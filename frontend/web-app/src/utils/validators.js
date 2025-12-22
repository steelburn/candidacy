/**
 * Validation utility functions
 */

export const validators = {
    /**
     * Check if value is required (not empty)
     */
    required: (value) => {
        if (Array.isArray(value)) {
            return value.length > 0 || 'This field is required'
        }
        return !!value || 'This field is required'
    },

    /**
     * Validate email format
     */
    email: (value) => {
        if (!value) return true
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        return emailRegex.test(value) || 'Please enter a valid email address'
    },

    /**
     * Validate minimum length
     */
    minLength: (min) => (value) => {
        if (!value) return true
        return value.length >= min || `Must be at least ${min} characters`
    },

    /**
     * Validate maximum length
     */
    maxLength: (max) => (value) => {
        if (!value) return true
        return value.length <= max || `Must be at most ${max} characters`
    },

    /**
     * Validate minimum value (for numbers)
     */
    min: (min) => (value) => {
        if (!value) return true
        return Number(value) >= min || `Must be at least ${min}`
    },

    /**
     * Validate maximum value (for numbers)
     */
    max: (max) => (value) => {
        if (!value) return true
        return Number(value) <= max || `Must be at most ${max}`
    },

    /**
     * Validate URL format
     */
    url: (value) => {
        if (!value) return true
        try {
            new URL(value)
            return true
        } catch {
            return 'Please enter a valid URL'
        }
    },

    /**
     * Validate phone number (basic)
     */
    phone: (value) => {
        if (!value) return true
        const phoneRegex = /^[\d\s\-\+\(\)]+$/
        return phoneRegex.test(value) || 'Please enter a valid phone number'
    },

    /**
     * Validate that value matches another field
     */
    sameAs: (otherValue, fieldName = 'field') => (value) => {
        return value === otherValue || `Must match ${fieldName}`
    },

    /**
     * Custom regex validation
     */
    pattern: (regex, message = 'Invalid format') => (value) => {
        if (!value) return true
        return regex.test(value) || message
    }
}

/**
 * Run multiple validators on a value
 */
export function validateField(value, rules) {
    if (!rules || rules.length === 0) return null

    for (const rule of rules) {
        const result = rule(value)
        if (result !== true) {
            return result
        }
    }

    return null
}

/**
 * Validate entire form
 */
export function validateForm(formData, validationRules) {
    const errors = {}
    let isValid = true

    for (const [field, rules] of Object.entries(validationRules)) {
        const error = validateField(formData[field], rules)
        if (error) {
            errors[field] = error
            isValid = false
        }
    }

    return { isValid, errors }
}
