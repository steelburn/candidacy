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
  <div class="container animate-in">
    <div v-if="loading" class="loading-container">
        <div class="spinner"></div>
        <p>Loading Portal...</p>
    </div>
    
    <div v-else-if="dashboardData" class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar card">
            <div class="profile-header">
                <div class="avatar-circle">{{ dashboardData.candidate.name.charAt(0) }}</div>
                <h3>{{ dashboardData.candidate.name }}</h3>
                <p class="email">{{ dashboardData.candidate.email }}</p>
                <div class="status-badge" :class="dashboardData.candidate.status.toLowerCase()">
                    {{ dashboardData.candidate.status }}
                </div>
            </div>
            
            <nav class="portal-nav">
                <button @click="activeTab = 'overview'" :class="{ active: activeTab === 'overview' }">
                    <span class="icon">📊</span> Overview
                </button>
                <button @click="activeTab = 'interviews'" :class="{ active: activeTab === 'interviews' }">
                    <span class="icon">📅</span> Interviews
                </button>
                <button @click="activeTab = 'offers'" :class="{ active: activeTab === 'offers' }">
                    <span class="icon">💎</span> Offers
                </button>
            </nav>

            <button @click="logout" class="btn-logout">
                <span class="icon">🚪</span> Logout
            </button>
        </aside>

        <!-- Main Content -->
        <main class="content-area">
            <!-- OVERVIEW TAB -->
            <div v-if="activeTab === 'overview'" class="tab-content animate-in">
                <h2 class="text-gradient">Application Overview</h2>
                <div class="card content-card">
                    <h3 class="section-title">My Applications</h3>
                    <div v-if="dashboardData.matches.length === 0" class="empty-state">
                        You have not applied to any positions yet.
                    </div>
                    <div v-else class="applications-list">
                        <div v-for="match in dashboardData.matches" :key="match.id" class="app-item">
                            <div class="app-info">
                                <strong>{{ match.vacancy_title }}</strong>
                                <span class="app-date">Applied recently</span>
                            </div>
                            <span class="status-pill" :class="match.status">{{ match.status }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INTERVIEWS TAB -->
            <div v-if="activeTab === 'interviews'" class="tab-content animate-in">
                <h2 class="text-gradient">Scheduled Interviews</h2>
                <div v-if="!dashboardData.interviews || dashboardData.interviews.length === 0" class="card empty-card">
                    <p>No interviews scheduled at the moment.</p>
                </div>
                <div v-else class="grid">
                    <div v-for="interview in dashboardData.interviews" :key="interview.id" class="card interview-card">
                        <div class="card-header-row">
                             <h3>{{ interview.type }} Interview</h3>
                             <span class="status-pill" :class="interview.status">{{ interview.status }}</span>
                        </div>
                        <div class="interview-details">
                            <p><strong>Date:</strong> {{ new Date(interview.scheduled_at).toLocaleString() }}</p>
                            <p v-if="interview.interviewer"><strong>Interviewer:</strong> {{ interview.interviewer }}</p>
                        </div>
                        <a v-if="interview.meeting_link" :href="interview.meeting_link" target="_blank" class="btn-primary btn-sm join-btn">
                            Join Meeting
                        </a>
                    </div>
                </div>
            </div>

            <!-- OFFERS TAB -->
            <div v-if="activeTab === 'offers'" class="tab-content animate-in">
                <h2 class="text-gradient">Job Offers</h2>
                 <div v-if="!dashboardData.offers || dashboardData.offers.length === 0" class="card empty-card">
                    <p>No offers received yet. Keep applying!</p>
                </div>
                <div v-else class="grid">
                    <div v-for="offer in dashboardData.offers" :key="offer.id" class="card offer-card">
                        <div class="offer-header">
                            <h3>Job Offer</h3>
                            <span class="status-pill" :class="offer.status">{{ offer.status }}</span>
                        </div>
                        <div class="offer-amount">
                            {{ offer.salary_currency }} {{ offer.salary_amount }}
                        </div>
                        <div class="offer-actions" v-if="offer.status === 'pending'">
                            <button class="btn-success full-width">Accept Offer</button>
                            <button class="btn-danger full-width">Decline</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
  </div>
</template>

<style scoped>
.container { margin-top: 2rem; padding-bottom: 4rem; }
.loading-container { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 50vh; color: var(--text-muted); }
.spinner { width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.1); border-top-color: var(--primary-start); border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 1rem; }
@keyframes spin { to { transform: rotate(360deg); } }

.dashboard-layout { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; }
.sidebar { height: fit-content; text-align: center; padding: 2.5rem 1.5rem; position: sticky; top: 100px; }

.profile-header { margin-bottom: 2.5rem; }
.avatar-circle { width: 80px; height: 80px; background: var(--primary-gradient); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; margin: 0 auto 1rem; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
.profile-header h3 { margin-bottom: 0.25rem; }
.email { color: var(--text-muted); font-size: 0.9rem; margin: 0 0 1rem; }

.status-badge { display: inline-block; padding: 0.25rem 0.75rem; background: rgba(255,255,255,0.1); border-radius: 99px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
.status-badge.active { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }

.portal-nav { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2.5rem; }
.portal-nav button { background: transparent; color: var(--text-muted); text-align: left; padding: 0.875rem 1rem; border-radius: 12px; font-weight: 500; display: flex; align-items: center; gap: 0.75rem; transition: all 0.2s; }
.portal-nav button:hover { background: rgba(255,255,255,0.05); color: white; transform: translateX(4px); }
.portal-nav button.active { background: rgba(99, 102, 241, 0.15); color: white; font-weight: 600; border: 1px solid rgba(99, 102, 241, 0.3); }
.icon { font-size: 1.1rem; }

.btn-logout { width: 100%; background: rgba(239,68,68,0.1); color: var(--error-color); justify-content: center; }
.btn-logout:hover { background: rgba(239,68,68,0.2); }

.content-area h2 { margin-bottom: 1.5rem; font-size: 2rem; }
.content-card { padding: 0; overflow: hidden; }
.section-title { padding: 1.5rem; border-bottom: 1px solid var(--glass-border); margin: 0; font-size: 1.1rem; color: #cbd5e1; }

.empty-state, .empty-card { padding: 3rem; text-align: center; color: var(--text-muted); font-style: italic; }

.applications-list { display: flex; flex-direction: column; }
.app-item { padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--glass-border); display: flex; justify-content: space-between; align-items: center; transition: background 0.2s; }
.app-item:last-child { border-bottom: none; }
.app-item:hover { background: rgba(255,255,255,0.02); }
.app-info { display: flex; flex-direction: column; gap: 0.25rem; }
.app-date { font-size: 0.8rem; color: var(--text-muted); }

.status-pill { padding: 0.25rem 0.75rem; border-radius: 99px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; background: rgba(255,255,255,0.1); }
.status-pill.applied { background: rgba(59, 130, 246, 0.2); color: #93c5fd; }
.status-pill.interviewing { background: rgba(168, 85, 247, 0.2); color: #e9d5ff; }
.status-pill.offer { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; }
.status-pill.rejected { background: rgba(239, 68, 68, 0.2); color: #fca5a5; }

.grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; }
.interview-card { display: flex; flex-direction: column; gap: 1rem; }
.card-header-row { display: flex; justify-content: space-between; align-items: start; }
.interview-details { color: var(--text-muted); font-size: 0.9rem; flex-grow: 1; }
.join-btn { width: 100%; margin-top: auto; }

.offer-card { text-align: center; border: 1px solid rgba(16, 185, 129, 0.3); background: linear-gradient(145deg, rgba(16, 185, 129, 0.05), transparent); }
.offer-header { display: flex; justify-content: space-between; margin-bottom: 1rem; }
.offer-amount { font-size: 2rem; font-weight: 800; color: white; margin: 1.5rem 0; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }
.offer-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.btn-success { background: var(--success-color); color: white; }
.btn-danger { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }

@media (max-width: 900px) { .dashboard-layout { grid-template-columns: 1fr; } .sidebar { position: static; margin-bottom: 2rem; } }
</style>

