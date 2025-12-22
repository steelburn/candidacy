<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../../services/api'

const router = useRouter()
const loading = ref(true)
const dashboardData = ref(null)
const activeTab = ref('overview')

const logout = () => {
    localStorage.removeItem('candidate_token')
    router.push({ name: 'login' })
}

onMounted(async () => {
    const token = localStorage.getItem('candidate_token')
    if (!token) {
        router.push({ name: 'login' })
        return
    }
    
    try {
        const res = await api.get('/portal/dashboard', {
            headers: { 'X-Candidate-Token': token }
        })
        dashboardData.value = res.data
    } catch (error) {
        console.error("Failed to load dashboard", error)
        if (error.response && error.response.status === 401) {
            router.push({ name: 'login' })
        }
    } finally {
        loading.value = false
    }
})
</script>

<template>
  <div class="container margin-top">
    <div v-if="loading" class="loading">Loading Portal...</div>
    
    <div v-else-if="dashboardData" class="dashboard-layout">
        <aside class="sidebar card">
            <div class="profile-summary">
                <div class="avatar-circle">{{ dashboardData.candidate.name.charAt(0) }}</div>
                <h3>{{ dashboardData.candidate.name }}</h3>
                <p class="email">{{ dashboardData.candidate.email }}</p>
                <div class="status-badge">{{ dashboardData.candidate.status }}</div>
            </div>
            
            <nav class="portal-nav">
                <button @click="activeTab = 'overview'" :class="{ active: activeTab === 'overview' }">Overview</button>
                <button @click="activeTab = 'interviews'" :class="{ active: activeTab === 'interviews' }">Interviews</button>
                <button @click="activeTab = 'offers'" :class="{ active: activeTab === 'offers' }">Offers</button>
            </nav>

            <button @click="logout" class="btn-logout">Logout</button>
        </aside>

        <main class="content-area">
            <!-- OVERVIEW TAB -->
            <div v-if="activeTab === 'overview'" class="tab-content">
                <h2>Overview</h2>
                <div class="card">
                    <h3>Applications</h3>
                    <div v-if="dashboardData.matches.length === 0">No active applications.</div>
                    <ul v-else class="list">
                        <li v-for="match in dashboardData.matches" :key="match.id">
                            <strong>{{ match.vacancy_title }}</strong> - Status: {{ match.status }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- INTERVIEWS TAB -->
            <div v-if="activeTab === 'interviews'" class="tab-content">
                <h2>Interviews</h2>
                <div v-if="!dashboardData.interviews || dashboardData.interviews.length === 0" class="card">
                    No interviews scheduled.
                </div>
                <div v-else class="grid">
                    <div v-for="interview in dashboardData.interviews" :key="interview.id" class="card">
                        <h3>{{ interview.type }} Interview</h3>
                        <p>Date: {{ new Date(interview.scheduled_at).toLocaleString() }}</p>
                        <p>Status: {{ interview.status }}</p>
                        <a v-if="interview.meeting_link" :href="interview.meeting_link" target="_blank" class="link">Join Meeting</a>
                    </div>
                </div>
            </div>

            <!-- OFFERS TAB -->
            <div v-if="activeTab === 'offers'" class="tab-content">
                <h2>Offers</h2>
                 <div v-if="!dashboardData.offers || dashboardData.offers.length === 0" class="card">
                    No offers received yet.
                </div>
                <div v-else class="grid">
                    <div v-for="offer in dashboardData.offers" :key="offer.id" class="card offer-card">
                        <h3>Job Offer</h3>
                        <p class="salary">{{ offer.salary_currency }} {{ offer.salary_amount }}</p>
                        <p>Status: <span :class="offer.status">{{ offer.status }}</span></p>
                        <div class="offer-actions" v-if="offer.status === 'pending'">
                            <button class="btn-success">Accept</button>
                            <button class="btn-danger">Reject</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
  </div>
</template>

<style scoped>
.margin-top { margin-top: 2rem; }
.dashboard-layout { display: grid; grid-template-columns: 250px 1fr; gap: 2rem; }
.sidebar { height: fit-content; text-align: center; }
.profile-summary { margin-bottom: 2rem; }
.avatar-circle { width: 60px; height: 60px; background: var(--primary-color); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: bold; margin: 0 auto 1rem; }
.portal-nav { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 2rem; }
.portal-nav button { background: transparent; color: #94a3b8; text-align: left; padding: 0.75rem; border-radius: 0.5rem; }
.portal-nav button:hover, .portal-nav button.active { background: rgba(255,255,255,0.05); color: white; }
.btn-logout { width: 100%; background: rgba(239,68,68,0.1); color: var(--error-color); }
.list { list-style: none; padding: 0; }
.list li { padding: 0.75rem 0; border-bottom: 1px solid var(--border-color); }
.link { color: var(--primary-color); }
.status-badge { display: inline-block; padding: 0.25rem 0.5rem; background: rgba(255,255,255,0.1); border-radius: 4px; font-size: 0.8rem; margin-top: 0.5rem; text-transform: uppercase; }
@media (max-width: 768px) { .dashboard-layout { grid-template-columns: 1fr; } }
</style>
