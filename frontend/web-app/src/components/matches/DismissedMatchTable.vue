<template>
  <div class="matches-table-container">
    <table class="matches-table dismissed-table">
        <thead>
        <tr>
            <th>Candidate</th>
            <th>Vacancy</th>
            <th>Score</th>
            <th>Dismissed Date</th>
            <th>Actions</th>
        </tr>
        </thead>
        <tbody>
            <tr v-for="match in matches" :key="match.id">
                <td>{{ getCandidateName(match.candidate_id) }}</td>
                <td>{{ getVacancyTitle(match.vacancy_id) }}</td>
                <td>{{ match.match_score }}%</td>
                <td>{{ formatDate(match.updated_at) }}</td>
                <td>
                    <button class="btn-icon restore-btn" @click="$emit('restore', match)" title="Restore Match">
                        ↩️
                    </button>
                </td>
            </tr>
        </tbody>
    </table>
  </div>
</template>

<script setup>
import { formatDate } from '../../composables/useMatchAnalysis'

const props = defineProps({
  matches: {
    type: Array,
    required: true
  },
  candidates: {
    type: Array,
    default: () => []
  },
  vacancies: {
    type: Array,
    default: () => []
  }
})

defineEmits(['restore'])

const getCandidateName = (id) => {
  if (!id) return 'Unknown Candidate'
  const candidate = props.candidates.find(c => c.id == id)
  return candidate ? candidate.name : `Candidate #${id}`
}

const getVacancyTitle = (id) => {
  if (!id) return 'Unknown Vacancy'
  const vacancy = props.vacancies.find(v => v.id == id)
  return vacancy ? vacancy.title : `Vacancy #${id}`
}
</script>

<style scoped>
.matches-table-container {
    overflow-x: auto;
}

.matches-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

.matches-table th {
  text-align: left;
  padding: 1rem;
  color: #64748b;
  font-weight: 600;
  border-bottom: 2px solid #f1f5f9;
}

.matches-table td {
  padding: 1rem;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
}

.matches-table tr:last-child td {
  border-bottom: none;
}

.matches-table tr:hover td {
  background: #f8fafc;
}

.btn-icon {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.2rem;
  padding: 0.5rem;
  border-radius: 8px;
  transition: all 0.2s;
}

.btn-icon:hover {
  background: #f1f5f9;
  transform: scale(1.1);
}

.restore-btn:hover {
  background: #f0fdf4;
}
</style>
