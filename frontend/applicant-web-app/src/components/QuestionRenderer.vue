<script setup>
import { computed } from 'vue'

const props = defineProps({
  questions: {
    type: Array,
    required: true
  },
  modelValue: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['update:modelValue'])

const answers = computed({
  get: () => props.modelValue,
  set: (val) => emit('update:modelValue', val)
})
</script>

<template>
  <div class="questions-container">
    <div v-for="q in questions" :key="q.id" class="question-block card">
      <label class="question-label">
        {{ q.question_text }}
        <span class="required">*</span>
      </label>

      <!-- Text Answer -->
      <div v-if="q.question_type === 'text'" class="input-wrapper">
        <textarea 
          v-model="answers[q.id]" 
          rows="3" 
          placeholder="Type your answer here..."
          required
        ></textarea>
      </div>

      <!-- Boolean Answer -->
      <div v-else-if="q.question_type === 'boolean'" class="input-wrapper">
        <select v-model="answers[q.id]" required>
          <option :value="null" disabled>Select an option</option>
          <option value="Yes">Yes</option>
          <option value="No">No</option>
        </select>
      </div>

       <!-- Multiple Choice Answer -->
       <div v-else-if="q.question_type === 'multiple_choice'" class="input-wrapper">
        <!-- Assuming simple text input for now as the data structure for options wasn't clear in previous context, 
             but if options exist they should be rendered. 
             Falling back to text input or simplistic dropdown if options available. 
             Based on ApplicantPortal.vue, it treated it as text input. -->
         <input 
            v-model="answers[q.id]" 
            type="text" 
            placeholder="Type your answer" 
            required 
         />
      </div>
    </div>
  </div>
</template>

<style scoped>
.questions-container {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-top: 2rem;
}

.question-block {
  padding: 1.5rem;
  background: rgba(30, 41, 59, 0.4); /* Slightly lighter/different than main card for contrast */
}

.question-label {
  display: block;
  font-weight: 600;
  margin-bottom: 1rem;
  color: #e2e8f0;
  font-size: 1.1rem;
}

.required {
  color: var(--error-color);
  margin-left: 0.25rem;
}

.input-wrapper {
  position: relative;
}

textarea, select, input {
  background: rgba(15, 23, 42, 0.8);
  border-color: rgba(255, 255, 255, 0.1);
}

textarea:focus, select:focus, input:focus {
  border-color: var(--primary-start);
  background: rgba(15, 23, 42, 0.95);
}
</style>
