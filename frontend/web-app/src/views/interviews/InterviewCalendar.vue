<template>
  <div class="interview-calendar premium-container">
    <header class="calendar-header">
      <div class="calendar-controls">
        <button class="btn-icon" @click="changeMonth(-1)">&lt;</button>
        <h2>{{ currentMonthName }} {{ currentYear }}</h2>
        <button class="btn-icon" @click="changeMonth(1)">&gt;</button>
        <button class="btn-secondary today-btn" @click="goToToday">Today</button>
      </div>
      <div class="view-controls">
        <router-link to="/interviews" class="btn-secondary">List View</router-link>
        <button class="btn-primary" @click="showModal = true">+ Schedule Interview</button>
      </div>
    </header>

    <div class="calendar-grid">
      <div class="weekday-header" v-for="day in weekDays" :key="day">{{ day }}</div>
      
      <div 
        v-for="(day, index) in calendarDays" 
        :key="index" 
        class="calendar-day"
        :class="{ 
          'is-today': day.isToday, 
          'is-other-month': !day.isCurrentMonth 
        }"
      >
        <div class="day-number">{{ day.date.getDate() }}</div>
        
  <div class="events-list">
          <div 
            v-for="event in day.events" 
            :key="event.id" 
            class="event-pill"
            :class="getEventClass(event)"
            @click="openEvent(event)"
          >
            <div class="event-time">{{ formatTime(event.scheduled_at) }}</div>
            <div class="event-details">
                <div class="event-title"><strong>{{ event.stage }}</strong></div>
                <div class="event-row">👤 {{ getCandidateName(event) }}</div>
                <div class="event-row">👥 {{ getInterviewerNames(event) }}</div>
                <div class="event-row">💼 {{ getVacancyTitle(event) }}</div>
                <div class="event-row">📍 {{ getLocation(event) }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Re-use Interview Modal for details if needed, or emit event -->
    <InterviewModal 
      :show="showModal" 
      :interview="selectedInterview"
      :candidate-name="selectedInterview ? getCandidateName(selectedInterview) : ''"
      :vacancy-title="selectedInterview ? getVacancyTitle(selectedInterview) : ''"
      @close="closeModal" 
      @created="handleCreated"
      @updated="handleCreated"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { interviewAPI, candidateAPI, vacancyAPI, userAPI } from '../../services/api'
import InterviewModal from './InterviewModal.vue'

const props = defineProps({
  refreshTrigger: Number
})

const emit = defineEmits(['schedule', 'edit'])

const currentDate = ref(new Date())
const interviews = ref([])
const candidates = ref({}) // Cache for names
const vacancies = ref({}) // Cache for titles
const users = ref({}) // Cache for interviewer names
const loading = ref(false)
const showModal = ref(false)

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']

// Helper to get days in month
const getDaysInMonth = (year, month) => new Date(year, month + 1, 0).getDate()
const getFirstDayOfMonth = (year, month) => new Date(year, month, 1).getDay()

const currentMonthName = computed(() => currentDate.value.toLocaleString('default', { month: 'long' }))
const currentYear = computed(() => currentDate.value.getFullYear())

const calendarDays = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const daysInMonth = getDaysInMonth(year, month)
  const firstDay = getFirstDayOfMonth(year, month)
  
  const days = []
  
  // Previous month filler
  const prevMonthDays = getDaysInMonth(year, month - 1)
  for (let i = firstDay - 1; i >= 0; i--) {
    days.push({
      date: new Date(year, month - 1, prevMonthDays - i),
      isCurrentMonth: false,
      isToday: false,
      events: []
    })
  }
  
  // Current month
  const today = new Date()
  for (let i = 1; i <= daysInMonth; i++) {
    const date = new Date(year, month, i)
    days.push({
      date: date,
      isCurrentMonth: true,
      isToday: date.toDateString() === today.toDateString(),
      events: getEventsForDate(date)
    })
  }
  
  // Next month filler
  const remainingCells = 42 - days.length // 6 rows * 7 cols
  for (let i = 1; i <= remainingCells; i++) {
    days.push({
      date: new Date(year, month + 1, i),
      isCurrentMonth: false,
      isToday: false,
      events: [] // We typically only load current month events, extend logic if needed
    })
  }
  
  return days
})

const fetchEvents = async () => {
  loading.value = true
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  
  const startDate = new Date(year, month, 1).toISOString().slice(0, 10)
  const endDate = new Date(year, month + 1, 0).toISOString().slice(0, 10)
  
  try {
    const res = await interviewAPI.list({ 
        start_date: startDate, 
        end_date: endDate,
        per_page: 1000 
    })
    const allInterviews = res.data.data || res.data || []
    interviews.value = allInterviews.filter(i => i.status !== 'cancelled')
    
    // Fetch Candidates
    const uniqueCandidateIds = [...new Set(interviews.value.map(i => i.candidate_id))]
    if (uniqueCandidateIds.length > 0) { // Fetch all potentially for simplicity or optimize
        // Determine if we need to fetch. Simple strategy: check if ANY missing or just fetch recent page
        const cRes = await candidateAPI.list({ per_page: 1000 }) 
        cRes.data.data.forEach(c => {
            candidates.value[c.id] = c.name || `${c.first_name} ${c.last_name}`
        })
    }

    // Fetch Vacancies
    const uniqueVacancyIds = [...new Set(interviews.value.map(i => i.vacancy_id))]
    if (uniqueVacancyIds.length > 0) {
        const vRes = await vacancyAPI.list({ per_page: 1000 })
        const vData = vRes.data.data || vRes.data || []
        vData.forEach(v => {
            vacancies.value[v.id] = v.title
        })
    }
    
    // Fetch Users (Interviewers)
    // Flatten all interviewer_ids arrays
    const allInterviewerIds = interviews.value.flatMap(i => i.interviewer_ids || [])
    const uniqueUserIds = [...new Set(allInterviewerIds)]
    
    if (uniqueUserIds.length > 0) {
        try {
             // Assuming userAPI.list() returns all users or paginated. 
             // Ideally we filter, but for now fetch list.
             const uRes = await userAPI.list()
             const uData = uRes.data.data || uRes.data || []
             uData.forEach(u => {
                 users.value[u.id] = u.name
             })
        } catch (uErr) {
             console.error("Failed to fetch users", uErr)
        }
    }
    
  } catch (err) {
    console.error("Failed to fetch calendar events", err)
  } finally {
    loading.value = false
  }
}

const getEventsForDate = (date) => {
  const dateStr = date.toISOString().slice(0, 10)
  return interviews.value.filter(i => i.scheduled_at.startsWith(dateStr))
}

const getCandidateName = (event) => {
    if (candidates.value[event.candidate_id]) {
        return candidates.value[event.candidate_id]
    }
    return `Candidate #${event.candidate_id}`
}

const getVacancyTitle = (event) => {
    if (vacancies.value[event.vacancy_id]) {
        return vacancies.value[event.vacancy_id]
    }
    return `Vacancy #${event.vacancy_id}`
}

const getLocation = (event) => {
    return event.location || event.type || 'Remote'
}

const getInterviewerNames = (event) => {
    if (!event.interviewer_ids || event.interviewer_ids.length === 0) return 'TBD'
    
    return event.interviewer_ids.map(id => users.value[id] || `User #${id}`).join(', ')
}

const changeMonth = (delta) => {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + delta, 1)
  fetchEvents()
}

const goToToday = () => {
  currentDate.value = new Date()
  fetchEvents()
}

const formatTime = (isoString) => {
  return new Date(isoString).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const getEventClass = (event) => {
  return {
    'stage-screening': event.stage === 'screening',
    'stage-technical': event.stage === 'technical',
    'stage-behavioral': event.stage === 'behavioral',
    'stage-final': event.stage === 'final'
  }
}

const selectedInterview = ref(null)

const openEvent = (event) => {
    selectedInterview.value = event
    showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  selectedInterview.value = null
}

const handleCreated = () => {
  fetchEvents()
}

onMounted(() => {
  fetchEvents()
})
watch(() => props.refreshTrigger, () => {
  fetchEvents()
})
</script>

<style scoped>
.premium-container {
  background: white;
  border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  padding: 1.5rem;
  height: calc(100vh - 120px);
  display: flex;
  flex-direction: column;
}

.calendar-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.calendar-controls {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.calendar-controls h2 {
  min-width: 200px;
  text-align: center;
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1e293b;
}

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  grid-template-rows: auto repeat(6, 1fr); /* Header + 6 weeks */
  gap: 1px;
  background: #e2e8f0; /* Grid lines */
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  flex: 1;
  overflow: hidden;
}

.weekday-header {
  background: #f8fafc;
  padding: 0.75rem;
  text-align: center;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  font-size: 0.75rem;
  letter-spacing: 0.05em;
}

.calendar-day {
  background: white;
  min-height: 100px;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  transition: background-color 0.2s;
}

.calendar-day:hover {
  background-color: #fcfcfc;
}

.calendar-day.is-other-month {
  background-color: #f8fafc;
  color: #cbd5e1;
}

.calendar-day.is-today {
  background-color: #f0f9ff;
}

.calendar-day.is-today .day-number {
  background-color: #3b82f6;
  color: white;
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.day-number {
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #475569;
  align-self: flex-start;
}

.events-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow-y: auto;
}

.event-pill {
  font-size: 0.7rem;
  padding: 4px 6px;
  border-radius: 4px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  gap: 2px;
  transition: transform 0.1s;
  margin-bottom: 4px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

.event-time {
    font-weight: bold;
    font-size: 0.7rem;
    opacity: 0.8;
}

.event-details {
    display: flex;
    flex-direction: column;
    gap: 1px;
}

.event-row {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.event-pill:hover {
  transform: scale(1.02);
  z-index: 10;
}

/* Event Stage Colors */
.stage-screening {
  background-color: #dbeafe;
  color: #1e40af;
  border-left: 3px solid #2563eb;
}
.stage-technical {
  background-color: #fef3c7;
  color: #92400e;
  border-left: 3px solid #d97706;
}
.stage-behavioral {
  background-color: #e0e7ff;
  color: #3730a3;
  border-left: 3px solid #4f46e5;
}
.stage-final {
  background-color: #d1fae5;
  color: #065f46;
  border-left: 3px solid #059669;
}

.btn-icon {
  background: none;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #64748b;
  font-weight: bold;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f1f5f9;
  color: #0f172a;
  border-color: #cbd5e1;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: opacity 0.2s;
}

.btn-secondary {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  text-decoration: none;
  font-weight: 500;
  font-size: 0.875rem;
  cursor: pointer;
}
</style>
