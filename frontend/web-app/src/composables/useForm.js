import { ref, computed } from 'vue'
import { validateForm } from '../utils/validators'

/**
 * Composable for form state management and validation
 * 
 * @param {Object} initialData - Initial form data
 * @param {Object} validationRules - Validation rules for each field
 * @returns {Object} Form state and methods
 */
export function useForm(initialData = {}, validationRules = {}) {
    const formData = ref({ ...initialData })
    const errors = ref({})
    const touched = ref({})
    const isSubmitting = ref(false)

    /**
     * Check if form is valid
     */
    const isValid = computed(() => {
        const { isValid } = validateForm(formData.value, validationRules)
        return isValid
    })

    /**
     * Check if form has been modified
     */
    const isDirty = computed(() => {
        return JSON.stringify(formData.value) !== JSON.stringify(initialData)
    })

    /**
     * Update a field value
     */
    function setFieldValue(field, value) {
        formData.value[field] = value
        touched.value[field] = true
        validateField(field)
    }

    /**
     * Validate a single field
     */
    function validateField(field) {
        if (!validationRules[field]) {
            errors.value[field] = null
            return true
        }

        const rules = validationRules[field]
        for (const rule of rules) {
            const result = rule(formData.value[field])
            if (result !== true) {
                errors.value[field] = result
                return false
            }
        }

        errors.value[field] = null
        return true
    }

    /**
     * Validate all fields
     */
    function validate() {
        const { isValid: valid, errors: validationErrors } = validateForm(
            formData.value,
            validationRules
        )
        errors.value = validationErrors
        return valid
    }

    /**
     * Reset form to initial state
     */
    function reset() {
        formData.value = { ...initialData }
        errors.value = {}
        touched.value = {}
        isSubmitting.value = false
    }

    /**
     * Handle form submission
     */
    async function handleSubmit(onSubmit) {
        if (!validate()) {
            return false
        }

        isSubmitting.value = true
        try {
            await onSubmit(formData.value)
            return true
        } catch (error) {
            console.error('Form submission error:', error)
            return false
        } finally {
            isSubmitting.value = false
        }
    }

    /**
     * Set multiple errors (e.g., from API response)
     */
    function setErrors(newErrors) {
        errors.value = { ...errors.value, ...newErrors }
    }

    /**
     * Clear all errors
     */
    function clearErrors() {
        errors.value = {}
    }

    return {
        formData,
        errors,
        touched,
        isSubmitting,
        isValid,
        isDirty,
        setFieldValue,
        validateField,
        validate,
        reset,
        handleSubmit,
        setErrors,
        clearErrors
    }
}
