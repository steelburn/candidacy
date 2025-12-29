<template>
  <div class="detail-grid">
    <div class="info-card">
      <h3>Contact Information</h3>
      <p><strong>Email:</strong> {{ candidate.email }}</p>
      <p><strong>Phone:</strong> {{ displayPhone }}</p>
      <p v-if="candidate.years_of_experience"><strong>Years of Experience:</strong> {{ candidate.years_of_experience }} years</p>
      <p><strong>LinkedIn:</strong> <a v-if="candidate.linkedin_url" :href="candidate.linkedin_url" target="_blank">View Profile</a><span v-else>N/A</span></p>
      <p v-if="candidate.github_url"><strong>GitHub:</strong> <a :href="candidate.github_url" target="_blank">View Profile</a></p>
      <p v-if="candidate.portfolio_url"><strong>Portfolio:</strong> <a :href="candidate.portfolio_url" target="_blank">View Portfolio</a></p>
    </div>
    
    <div class="info-card">
      <h3>Summary</h3>
      <p>{{ candidate.summary || 'No summary available' }}</p>
    </div>
    
    <div class="info-card">
      <h3>Skills</h3>
      <div v-if="parsedSkills.length" class="skills">
        <span v-for="skill in parsedSkills" :key="skill" class="skill-tag">{{ skill }}</span>
      </div>
      <p v-else>No skills listed</p>
    </div>
    
    <div class="info-card">
      <h3>Work Experience</h3>
      <div v-if="parsedExperience.length">
        <div v-for="(exp, index) in parsedExperience" :key="index" class="experience-item">
          <h4>{{ exp.title }}</h4>
          <p class="company">{{ exp.company }} • {{ exp.duration }}</p>
          <div class="description">
            <ul v-if="Array.isArray(exp.description)">
              <li v-for="(descLine, i) in exp.description" :key="i">{{ descLine }}</li>
            </ul>
            <p v-else>{{ exp.description }}</p>
          </div>
        </div>
      </div>
      <p v-else>No work experience listed</p>
    </div>
    
    <div class="info-card">
      <h3>Education</h3>
      <div v-if="parsedEducation.length">
        <div v-for="(edu, index) in parsedEducation" :key="index" class="education-item">
          <h4>{{ edu.degree }}</h4>
          <p class="institution">{{ edu.institution }} • {{ edu.year }}</p>
        </div>
      </div>
      <p v-else>No education listed</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  candidate: {
    type: Object,
    required: true
  },
  parsedSkills: {
    type: Array,
    default: () => []
  },
  parsedExperience: {
    type: Array,
    default: () => []
  },
  parsedEducation: {
    type: Array,
    default: () => []
  }
})

// Get phone from candidate or from latest CV parsed data
const displayPhone = computed(() => {
  if (props.candidate.phone) return props.candidate.phone
  
  // Try to get from latest CV file
  const cvFiles = props.candidate.cv_files
  if (cvFiles && cvFiles.length > 0) {
    const latestCv = cvFiles[cvFiles.length - 1]
    const phone = latestCv?.parsed_data?.parsed_data?.phone
    if (phone) return phone
  }
  
  return 'N/A'
})
</script>

<style scoped>
.detail-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
}

@media (min-width: 1024px) {
  .detail-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.info-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-card h3 {
  margin-top: 0;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid #eee;
  color: #2c3e50;
}

.skills {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.skill-tag {
  background: #e3f2fd;
  color: #1976d2;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 0.9em;
}

.experience-item, .education-item {
  margin-bottom: 15px;
  padding-bottom: 15px;
  border-bottom: 1px solid #f5f5f5;
}

.experience-item:last-child, .education-item:last-child {
  border-bottom: none;
}

.company, .institution {
  color: #666;
  font-size: 0.9em;
  margin: 4px 0;
}

.description {
  margin-top: 8px;
  white-space: pre-line;
}

.description ul {
  padding-left: 20px;
  margin: 5px 0;
}

.description li {
  margin-bottom: 4px;
}
</style>
