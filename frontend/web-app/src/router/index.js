import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
    {
        path: '/',
        redirect: '/login'
    },
    {
        path: (import.meta.env.VITE_PORTAL_PATH || '/portal') + '/:token',
        name: 'ApplicantPortal',
        component: () => import('../views/portal/ApplicantPortal.vue'),
        // No meta required, accessible by all
    },
    {
        path: '/login',
        name: 'Login',
        component: () => import('../views/auth/Login.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/setup',
        name: 'Setup',
        component: () => import('../views/auth/Setup.vue'),
        meta: { requiresGuest: true }
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../views/Dashboard.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/candidates',
        name: 'Candidates',
        component: () => import('../views/candidates/CandidateList.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/candidates/create',
        name: 'CreateCandidate',
        component: () => import('../views/candidates/CandidateForm.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/candidates/:id/edit',
        name: 'EditCandidate',
        component: () => import('../views/candidates/CandidateForm.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/candidates/:id',
        name: 'CandidateDetail',
        component: () => import('../views/candidates/CandidateDetail.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/vacancies',
        name: 'Vacancies',
        component: () => import('../views/vacancies/VacancyList.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/vacancies/create',
        name: 'CreateVacancy',
        component: () => import('../views/vacancies/VacancyForm.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/vacancies/:id/edit',
        name: 'EditVacancy',
        component: () => import('../views/vacancies/VacancyForm.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/vacancies/:id',
        name: 'VacancyDetail',
        component: () => import('../views/vacancies/VacancyDetail.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/interviews',
        name: 'Interviews',
        component: () => import('../views/interviews/InterviewList.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/interviews/calendar',
        name: 'InterviewCalendar',
        component: () => import('../views/interviews/InterviewCalendar.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/matches',
        name: 'Matches',
        component: () => import('../views/matches/MatchList.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/offers',
        name: 'Offers',
        component: () => import('../views/offers/OfferList.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/reports',
        name: 'Reports',
        component: () => import('../views/Reports.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/admin',
        name: 'Admin',
        component: () => import('../views/Admin.vue'),
        meta: { requiresAuth: true },
        redirect: '/admin/system',
        children: [
            {
                path: 'system',
                name: 'AdminSystemHealth',
                component: () => import('../components/admin/AdminSystemHealth.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'configuration',
                name: 'AdminConfiguration',
                component: () => import('../components/admin/AdminConfiguration.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'ai-providers',
                name: 'AdminAIProviders',
                component: () => import('../components/admin/AdminAIProviders.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'users',
                name: 'AdminUserManagement',
                component: () => import('../components/admin/AdminUserManagement.vue'),
                meta: { requiresAuth: true }
            },
            {
                path: 'cv-jobs',
                name: 'AdminCvJobs',
                component: () => import('../components/admin/AdminCvJobs.vue'),
                meta: { requiresAuth: true }
            }
        ]
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore()

    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login')
    } else if (to.meta.requiresGuest && authStore.isAuthenticated) {
        next('/dashboard')
    } else {
        next()
    }
})

export default router
