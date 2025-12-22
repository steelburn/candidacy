import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import VacancyDetailView from '../views/VacancyDetailView.vue'
import ApplyView from '../views/ApplyView.vue'

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: '/',
            name: 'home',
            component: HomeView
        },
        {
            path: '/vacancies/:id',
            name: 'vacancy-detail',
            component: VacancyDetailView
        },
        {
            path: '/vacancies/:id/apply',
            name: 'apply',
            component: ApplyView
        },
        {
            path: '/match',
            name: 'match-cv',
            component: () => import('../views/MatchView.vue')
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('../views/LoginView.vue')
        },
        {
            path: '/portal',
            name: 'portal-dashboard',
            component: () => import('../views/portal/PortalDashboard.vue')
        }
    ]
})

export default router
