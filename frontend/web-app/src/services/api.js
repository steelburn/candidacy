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
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            window.location.href = '/login'
        }
        return Promise.reject(error)
    }
)

// Auth API
export const authAPI = {
    login: (credentials) => axios.post(`${API_GATEWAY_URL}/api/auth/login`, credentials),
    register: (data) => axios.post(`${API_GATEWAY_URL}/api/auth/register`, data),
    logout: () => api.post('/api/auth/logout'),
    me: () => api.get('/api/auth/me'),
    refresh: () => api.post('/api/auth/refresh'),
    changePassword: (data) => api.post('/api/auth/change-password', data),
    // First-time setup
    setupCheck: () => axios.get(`${API_GATEWAY_URL}/api/setup/check`),
    createAdmin: (data) => axios.post(`${API_GATEWAY_URL}/api/setup/create-admin`, data)
}

// Candidate API
export const candidateAPI = {
    list: (params) => api.get('/api/candidates', { params }),
    get: (id) => api.get(`/api/candidates/${id}`),
    create: (data) => {
        const config = data instanceof FormData ? { headers: { 'Content-Type': 'multipart/form-data' } } : {}
        return api.post('/api/candidates', data, config)
    },
    update: (id, data) => {
        if (data instanceof FormData) {
            data.append('_method', 'PUT')
            return api.post(`/api/candidates/${id}`, data, {
                headers: { 'Content-Type': 'multipart/form-data' }
            })
        }
        return api.put(`/api/candidates/${id}`, data)
    },
    delete: (id) => api.delete(`/api/candidates/${id}`),
    uploadCV: (id, file) => {
        const formData = new FormData()
        formData.append('cv_file', file)
        return api.post(`/api/candidates/${id}/cv`, formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    },
    getCv: (id) => api.get(`/api/candidates/${id}/cv`),
    parseCv: (formData) => {
        return api.post('/api/candidates/parse-cv', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
    },
    downloadCv: (id) => api.get(`/api/candidates/${id}/cv/download`, { responseType: 'blob' }),
    bulkUpload: (files, onProgress) => {
        const formData = new FormData()
        files.forEach((file) => {
            formData.append('files[]', file)
        })
        return api.post('/api/candidates/bulk-upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
            onUploadProgress: onProgress
        })
    },
    generateToken: (id, vacancyId) => api.post(`/api/candidates/${id}/generate-token`, { vacancy_id: vacancyId }),
    validateToken: (token) => api.get(`/api/portal/validate-token/${token}`),
    validateToken: (token) => api.get(`/api/portal/validate-token/${token}`),
    submitAnswers: (token, data) => api.post(`/api/portal/submit-answers/${token}`, data),
    // Admin CV Jobs
    listCvJobs: (params) => api.get('/api/candidates/cv-jobs', { params }),
    retryCvJob: (id) => api.post(`/api/candidates/cv-jobs/${id}/retry`),
    deleteCvJob: (id) => api.delete(`/api/candidates/cv-jobs/${id}`),
    // Parsing Details
    getParsingDetails: (id) => api.get(`/api/candidates/${id}/parsing-details`)
}

// Vacancy API
export const vacancyAPI = {
    list: (params) => api.get('/api/vacancies', { params }),
    get: (id) => api.get(`http://localhost:8083/api/vacancies/${id}`),
    create: (data) => api.post('/api/vacancies', data),
    update: (id, data) => api.put(`http://localhost:8083/api/vacancies/${id}`, data),
    delete: (id) => api.delete(`http://localhost:8083/api/vacancies/${id}`),
    generateJD: (id) => api.post(`http://localhost:8083/api/vacancies/${id}/generate-description`),
    addQuestion: (id, data) => api.post(`http://localhost:8083/api/vacancies/${id}/questions`, data),
    getQuestions: (id) => api.get(`http://localhost:8083/api/vacancies/${id}/questions`)
}

// AI API (direct calls)
export const aiAPI = {
    parseCV: (text) => api.post('/api/parse-cv', { text }),
    generateJD: (data) => api.post('/api/generate-jd', data),
    match: (candidateProfile, jobRequirements) => api.post('/api/match', {
        candidate_profile: candidateProfile,
        job_requirements: jobRequirements
    }),
    discussQuestion: (data) => api.post('/api/discuss-question', data)
}

// Matching API
export const matchingAPI = {
    forCandidate: (id) => api.get(`http://localhost:8085/api/candidates/${id}/matches`),
    forVacancy: (id) => api.get(`http://localhost:8085/api/vacancies/${id}/matches`),
    matchCandidate: (id, params) => api.get(`http://localhost:8085/api/candidates/${id}/matches`, { params }),
    matchVacancy: (id, params) => api.get(`http://localhost:8085/api/vacancies/${id}/matches`, { params }),
    list: (params) => api.get('/api/matches', { params }),
    clear: () => api.delete('/api/matches/clear'),
    getJobStatus: (id) => api.get(`http://localhost:8085/api/matches/jobs/${id}`),
    generateQuestions: (candidateId, vacancyId) => api.post(`http://localhost:8085/api/matches/${candidateId}/${vacancyId}/questions`),
    saveDiscussion: (candidateId, vacancyId, questionIndex, discussion) =>
        api.post(`http://localhost:8085/api/matches/${candidateId}/${vacancyId}/questions/${questionIndex}/discussion`, { discussion }),
    dismiss: (candidateId, vacancyId) => api.post(`http://localhost:8085/api/matches/${candidateId}/${vacancyId}/dismiss`),
    restore: (candidateId, vacancyId) => api.post(`http://localhost:8085/api/matches/${candidateId}/${vacancyId}/restore`)
}

// Interview API
export const interviewAPI = {
    list: (params) => api.get('/api/interviews', { params }),
    get: (id) => api.get(`http://localhost:8086/api/interviews/${id}`),
    create: (data) => api.post('/api/interviews', data),
    update: (id, data) => api.put(`http://localhost:8086/api/interviews/${id}`, data),
    delete: (id) => api.delete(`http://localhost:8086/api/interviews/${id}`),
    addFeedback: (id, feedback) => api.post(`http://localhost:8086/api/interviews/${id}/feedback`, feedback),
    upcoming: () => api.get('/api/interviews/upcoming/all')
}

// Offer API
export const offerAPI = {
    list: (params) => api.get('/api/offers', { params }),
    get: (id) => api.get(`http://localhost:8087/api/offers/${id}`),
    create: (data) => api.post('/api/offers', data),
    update: (id, data) => api.put(`http://localhost:8087/api/offers/${id}`, data),
    delete: (id) => api.delete(`http://localhost:8087/api/offers/${id}`),
    respond: (id, response) => api.post(`http://localhost:8087/api/offers/${id}/respond`, response)
}

// Reporting API
export const reportAPI = {
    candidateMetrics: () => api.get('/api/reports/candidates'),
    vacancyMetrics: () => api.get('/api/reports/vacancies'),
    pipeline: () => api.get('/api/reports/pipeline'),
    performance: () => api.get('/api/reports/performance')
}

// Admin API
export const adminAPI = {
    getSettings: () => api.get('/api/settings'),
    updateSettings: (settings) => api.put('/api/settings', settings),
    getSystemHealth: () => api.get('/api/system-health'),
    // Configuration Management
    getDetailedSettings: () => api.get('/api/settings/detailed'),
    getSettingsByCategory: (category) => api.get(`/api/settings/category/${category}`),
    getSettingsByScope: (scope) => api.get(`/api/settings/scope/${scope}`),
    updateSetting: (key, value) => api.put('/api/settings', { [key]: value }),
    getSettingHistory: (key) => api.get(`/api/settings/${key}/history`),
    exportSettings: () => api.get('/api/settings/export'),
    importSettings: (data) => api.post('/api/settings/import', data)
}

// Role API
export const roleAPI = {
    list: () => api.get('/api/roles'),
    get: (id) => api.get(`http://localhost:8081/api/roles/${id}`)
}

// User Management API (via auth service)
export const userAPI = {
    list: () => api.get('/api/users'),
    get: (id) => api.get(`http://localhost:8081/api/users/${id}`),
    create: (data) => api.post('/api/users', data),
    update: (id, data) => api.put(`http://localhost:8081/api/users/${id}`, data),
    delete: (id) => api.delete(`http://localhost:8081/api/users/${id}`),
    assignRole: (userId, roleId) => api.post(`http://localhost:8081/api/users/${userId}/roles`, { role_id: roleId }),
    removeRole: (userId, roleId) => api.delete(`http://localhost:8081/api/users/${userId}/roles/${roleId}`)
}

export default api
