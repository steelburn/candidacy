import axios from 'axios'

const API_GATEWAY_URL = import.meta.env.VITE_API_GATEWAY_URL || 'http://localhost:8080'
const AUTH_SERVICE_URL = import.meta.env.VITE_AUTH_SERVICE_URL || 'http://localhost:8081'
const CANDIDATE_SERVICE_URL = import.meta.env.VITE_CANDIDATE_SERVICE_URL || 'http://localhost:8082'
const VACANCY_SERVICE_URL = import.meta.env.VITE_VACANCY_SERVICE_URL || 'http://localhost:8083'
const AI_SERVICE_URL = import.meta.env.VITE_AI_SERVICE_URL || 'http://localhost:8084'
const MATCHING_SERVICE_URL = import.meta.env.VITE_MATCHING_SERVICE_URL || 'http://localhost:8085'
const INTERVIEW_SERVICE_URL = import.meta.env.VITE_INTERVIEW_SERVICE_URL || 'http://localhost:8086'
const OFFER_SERVICE_URL = import.meta.env.VITE_OFFER_SERVICE_URL || 'http://localhost:8087'
const REPORTING_SERVICE_URL = import.meta.env.VITE_REPORTING_SERVICE_URL || 'http://localhost:8089'
const ADMIN_SERVICE_URL = import.meta.env.VITE_ADMIN_SERVICE_URL || 'http://localhost:8090'

const api = axios.create({
    baseURL: API_GATEWAY_URL,
    timeout: 130000, // 130 seconds to accommodate long AI processing (backend has 120s timeout)
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
})

// Request interceptor
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token')
        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }
        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// Response interceptor
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401 && !error.config?._skipAuthRedirect) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// Auth API
export const authAPI = {
    login: (credentials) => axios.post(`${API_GATEWAY_URL}/auth/login`, credentials),
    register: (data) => axios.post(`${API_GATEWAY_URL}/auth/register`, data),
    logout: () => api.post('/auth/logout'),
    me: () => api.get('/auth/me'),
    refresh: () => api.post('/auth/refresh'),
    changePassword: (data) => api.post('/auth/change-password', data),
    switchTenant: (tenantId) => api.post('/auth/switch-tenant', { tenant_id: tenantId }),
    // First-time setup
    setupCheck: () => axios.get(`${API_GATEWAY_URL}/setup/check`),
    createAdmin: (data) => axios.post(`${API_GATEWAY_URL}/setup/create-admin`, data)
}

// Tenant API
export const tenantAPI = {
    list: () => api.get('/tenants', { _skipAuthRedirect: true }),
    get: (uuid) => api.get(`/tenants/${uuid}`),
    create: (data) => api.post('/tenants', data),
    update: (uuid, data) => api.put(`/tenants/${uuid}`, data),
    members: (uuid) => api.get(`/tenants/${uuid}/members`),
    invite: (uuid, data) => api.post(`/tenants/${uuid}/invitations`, data),
    acceptInvitation: (token) => api.post(`/invitations/${token}/accept`),
    getInvitation: (token) => api.get(`/invitations/${token}`)
}

// Candidate API
export const candidateAPI = {
    list: (params) => api.get('/candidates', { params }),
    get: (id) => api.get(`/candidates/${id}`),
    create: (data) => {
        const config = data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : {}
        return api.post('/candidates', data, config)
    },
    update: (id, data) => {
        if (data instanceof FormData) {
            data.append('_method', 'PUT')
            return api.post(`/candidates/${id}`, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
        }
        return api.put(`/candidates/${id}`, data)
    },
    delete: (id) => api.delete(`/candidates/${id}`),
    uploadCV: (id, file) => {
        const formData = new FormData()
        formData.append('cv_file', file)
        return api.post(`/candidates/${id}/cv`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    },
    getCv: (id) => api.get(`/candidates/${id}/cv`),
    parseCv: (formData) => {
        return api.post('/candidates/parse-cv', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    },
    downloadCv: (id) => api.get(`/candidates/${id}/cv/download`, { responseType: 'blob' }),
    bulkUpload: (files, onProgress) => {
        const formData = new FormData()
        files.forEach((file) => {
            formData.append('files[]', file)
        })
        return api.post('/candidates/bulk-upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: onProgress
        })
    },
    generateToken: (id, vacancyId) => api.post(`/candidates/${id}/generate-token`, { vacancy_id: vacancyId }),
    validateToken: (token) => api.get(`/portal/validate-token/${token}`),
    submitAnswers: (token, data) => api.post(`/portal/submit-answers/${token}`, data),
    // Admin CV Jobs
    listCvJobs: (params) => api.get('/candidates/cv-jobs', { params }),
    retryCvJob: (id) => api.post(`/candidates/cv-jobs/${id}/retry`),
    deleteCvJob: (id) => api.delete(`/candidates/cv-jobs/${id}`),
    // Parsing Details
    getParsingDetails: (id) => api.get(`/candidates/${id}/parsing-details`)
}

// Vacancy API
export const vacancyAPI = {
    list: (params) => api.get('/vacancies', { params }),
    get: (id) => api.get(`/vacancies/${id}`),
    create: (data) => api.post('/vacancies', data),
    update: (id, data) => api.put(`/vacancies/${id}`, data),
    delete: (id) => api.delete(`/vacancies/${id}`),
    generateJD: (id) => api.post(`/vacancies/${id}/generate-description`),
    addQuestion: (id, data) => api.post(`/vacancies/${id}/questions`, data),
    getQuestions: (id) => api.get(`/vacancies/${id}/questions`),
    updateQuestion: (vacancyId, questionId, data) => api.put(`/vacancies/${vacancyId}/questions/${questionId}`, data),
    deleteQuestion: (vacancyId, questionId) => api.delete(`/vacancies/${vacancyId}/questions/${questionId}`)
}

// AI API (direct calls)
export const aiAPI = {
    parseCV: (text) => api.post('/parse-cv', { text }),
    generateJD: (data) => api.post('/generate-jd', data),
    match: (candidateProfile, jobRequirements) => api.post('/match', {
        candidate_profile: candidateProfile,
        job_requirements: jobRequirements
    }),
    discussQuestion: (data) => api.post('/discuss-question', data),
    generateScreeningQuestions: (data) => api.post('/generate-questions-screening', data),
    // Provider Management
    getProviders: () => api.get('/providers'),
    createProvider: (data) => api.post('/providers', data),
    updateProvider: (id, data) => api.put(`/providers/${id}`, data),
    deleteProvider: (id) => api.delete(`/providers/${id}`),
    getModels: (data) => api.post('/providers/models', data),
    saveChains: (chains) => api.post('/providers/chains', { chains })
}

// Matching API
export const matchingAPI = {
    forCandidate: (id) => api.get(`/matches/candidates/${id}`),
    forVacancy: (id) => api.get(`/matches/vacancies/${id}`),
    matchCandidate: (id, params) => api.get(`/matches/candidates/${id}`, { params }),
    matchVacancy: (id, params) => api.get(`/matches/vacancies/${id}`, { params }),
    getMatches: (params) => api.get('/matches', { params }),
    list: (params) => api.get('/matches', { params }),
    clear: () => api.delete('/matches/clear'),
    getJobStatus: (id) => api.get(`/matches/jobs/${id}`),
    generateQuestions: (candidateId, vacancyId) => api.post(`/matches/${candidateId}/${vacancyId}/questions`),
    saveDiscussion: (candidateId, vacancyId, questionIndex, discussion) =>
        api.post(`/matches/${candidateId}/${vacancyId}/questions/${questionIndex}/discussion`, { discussion }),
    dismiss: (candidateId, vacancyId) => api.post(`/matches/${candidateId}/${vacancyId}/dismiss`),
    restore: (candidateId, vacancyId) => api.post(`/matches/${candidateId}/${vacancyId}/restore`)
}

// Interview API
export const interviewAPI = {
    list: (params) => api.get('/interviews', { params }),
    get: (id) => api.get(`/interviews/${id}`),
    create: (data) => api.post('/interviews', data),
    update: (id, data) => api.put(`/interviews/${id}`, data),
    delete: (id) => api.delete(`/interviews/${id}`),
    addFeedback: (id, feedback) => api.post(`/interviews/${id}/feedback`, feedback),
    upcoming: () => api.get('/interviews/upcoming/all')
}

// Offer API
export const offerAPI = {
    list: (params) => api.get('/offers', { params }),
    get: (id) => api.get(`/offers/${id}`),
    create: (data) => api.post('/offers', data),
    update: (id, data) => api.put(`/offers/${id}`, data),
    delete: (id) => api.delete(`/offers/${id}`),
    respond: (id, response) => api.post(`/offers/${id}/respond`, response)
}

// Reporting API
export const reportAPI = {
    candidateMetrics: () => api.get('/reports/candidates'),
    vacancyMetrics: () => api.get('/reports/vacancies'),
    pipeline: () => api.get('/reports/pipeline'),
    performance: () => api.get('/reports/performance')
}

// Admin API
export const adminAPI = {
    getSettings: () => api.get('/settings'),
    updateSettings: (settings) => api.put('/settings', settings),
    getSystemHealth: () => api.get('/system-health'),
    // Configuration Management
    getDetailedSettings: () => api.get('/settings/detailed'),
    getSettingsByCategory: (category) => api.get(`/settings/category/${category}`),
    getSettingsByScope: (scope) => api.get(`/settings/scope/${scope}`),
    updateSetting: (key, value) => api.put('/settings', { [key]: value }),
    getSettingHistory: (key) => api.get(`/settings/${key}/history`),
    exportSettings: () => api.get('/settings/export'),
    importSettings: (data) => api.post('/settings/import', data)
}

// Role API
export const roleAPI = {
    list: () => api.get('/roles'),
    get: (id) => api.get(`/roles/${id}`)
}

// User Management API (via auth service)
export const userAPI = {
    list: () => api.get('/users'),
    get: (id) => api.get(`/users/${id}`),
    create: (data) => api.post('/users', data),
    update: (id, data) => api.put(`/users/${id}`, data),
    delete: (id) => api.delete(`/users/${id}`),
    assignRole: (userId, roleId) => api.post(`/users/${userId}/roles`, { role_id: roleId }),
    removeRole: (userId, roleId) => api.delete(`/users/${userId}/roles/${roleId}`)
}

export default api
