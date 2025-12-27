<template>
  <div class="filters">
    <select 
        :value="candidateFilter" 
        @input="$emit('update:candidateFilter', $event.target.value)" 
        @change="$emit('refresh')" 
        class="filter-select"
    >
      <option value="">All Candidates</option>
      <option v-for="c in candidates" :key="c.id" :value="c.id">{{ c.name }}</option>
    </select>
    
    <select 
        :value="vacancyFilter" 
        @input="$emit('update:vacancyFilter', $event.target.value)" 
        @change="$emit('refresh')" 
        class="filter-select"
    >
      <option value="">All Vacancies</option>
      <option v-for="v in vacancies" :key="v.id" :value="v.id">{{ v.title }}</option>
    </select>
    
    <select 
        :value="minScore" 
        @input="$emit('update:minScore', $event.target.value)" 
        @change="$emit('refresh')" 
        class="filter-select"
    >
      <option value="">Any Score</option>
      <option value="80">80%+</option>
      <option value="60">60%+</option>
      <option value="40">40%+</option>
    </select>
    
    <select 
        :value="sortOption" 
        @input="$emit('update:sortOption', $event.target.value)" 
        @change="$emit('refresh')" 
        class="filter-select"
    >
      <option value="match_score:desc">Score (High to Low)</option>
      <option value="match_score:asc">Score (Low to High)</option>
      <option value="created_at:desc">Newest First</option>
      <option value="created_at:asc">Oldest First</option>
    </select>
  </div>
</template>

<script setup>
defineProps({
  candidates: {
    type: Array,
    default: () => []
  },
  vacancies: {
    type: Array,
    default: () => []
  },
  candidateFilter: {
    type: [String, Number],
    default: ''
  },
  vacancyFilter: {
    type: [String, Number],
    default: ''
  },
  minScore: {
    type: [String, Number],
    default: ''
  },
  sortOption: {
    type: String,
    default: 'match_score:desc'
  }
})

defineEmits(['update:candidateFilter', 'update:vacancyFilter', 'update:minScore', 'update:sortOption', 'refresh'])
</script>

<style scoped>
.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-size: 0.95rem;
  min-width: 200px;
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
  transition: all 0.2s;
}

.filter-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}
</style>
