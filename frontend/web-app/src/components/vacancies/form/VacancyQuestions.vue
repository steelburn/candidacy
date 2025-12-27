<template>
  <div class="questions-section">
    <h3>Screening Questions</h3>
    
    <div v-if="loading" class="text-center text-muted">Loading questions...</div>
    
    <div v-else class="question-list-wrapper">
        <div class="question-list" v-if="questions.length > 0">
          <div v-for="q in questions" :key="q.id" class="question-item">
            <div class="question-content">
                <p><strong>{{ q.question_text }}</strong> <span class="badge">{{ q.question_type }}</span></p>
            </div>
            <div class="question-actions">
                <button @click="editQuestion(q)" class="btn-icon btn-edit" title="Edit Question">✎</button>
                <button @click="deleteQuestion(q.id)" class="btn-icon btn-delete" title="Delete Question">&times;</button>
            </div>
          </div>
        </div>

        <p v-else class="text-muted">No screening questions added yet.</p>
    </div>

    <!-- Add/Edit Form -->
    <div class="add-question-form">
      <h4>{{ editingQuestionId ? 'Edit Question' : 'Add New Question' }}</h4>
      
      <div class="ai-question-actions" v-if="!editingQuestionId">
         <button type="button" @click="generateAiQuestions" :disabled="aiQuestionGenerating" class="btn-ai-questions">
             {{ aiQuestionGenerating ? '✨ Generating Questions...' : '✨ Generate with AI' }}
         </button>
      </div>
      <div class="divider" v-if="!editingQuestionId"><span>OR MANUAL ADD</span></div>
      
      <div class="form-group">
        <label>Question</label>
        <input v-model="newQuestion.text" type="text" placeholder="e.g. Why do you want to join us?" />
      </div>
      <div class="form-group">
          <label>Type</label>
          <select v-model="newQuestion.type">
              <option value="text">Text Answer</option>
              <option value="boolean">Yes/No</option>
              <option value="multiple_choice">Multiple Choice</option>
          </select>
      </div>
      <div class="form-actions-small">
          <button type="button" @click="saveQuestion" :disabled="addingQuestion || !newQuestion.text" class="btn-secondary">
              {{ addingQuestion ? 'Saving...' : (editingQuestionId ? 'Update Question' : 'Add Question') }}
          </button>
          <button v-if="editingQuestionId" type="button" @click="cancelEdit" class="btn-text">
              Cancel
          </button>
      </div>
      <div v-if="error" class="error-msg">{{ error }}</div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { vacancyAPI, aiAPI } from '../../../services/api'

const props = defineProps({
  vacancyId: {
    type: [String, Number],
    required: true
  },
  vacancyTitle: {
      type: String,
      default: ''
  },
  vacancyDescription: {
      type: String,
      default: ''
  }
})

const questions = ref([])
const loading = ref(false)
const error = ref('')

const addingQuestion = ref(false)
const aiQuestionGenerating = ref(false)
const editingQuestionId = ref(null)
const newQuestion = ref({ text: '', type: 'text' })

const loadQuestions = async () => {
    loading.value = true
    try {
        const res = await vacancyAPI.getQuestions(props.vacancyId)
        questions.value = res.data
    } catch (e) {
        console.error("Failed to load questions", e)
        error.value = "Failed to load questions"
    } finally {
        loading.value = false
    }
}

const editQuestion = (q) => {
    newQuestion.value = { text: q.question_text, type: q.question_type }
    editingQuestionId.value = q.id
    error.value = ''
}

const cancelEdit = () => {
    newQuestion.value = { text: '', type: 'text' }
    editingQuestionId.value = null
    error.value = ''
}

const saveQuestion = async () => {
    if (!newQuestion.value.text) return
    addingQuestion.value = true
    error.value = ''
    try {
        if (editingQuestionId.value) {
            await vacancyAPI.updateQuestion(props.vacancyId, editingQuestionId.value, {
                question_text: newQuestion.value.text,
                question_type: newQuestion.value.type
            })
        } else {
            await vacancyAPI.addQuestion(props.vacancyId, {
                question_text: newQuestion.value.text,
                question_type: newQuestion.value.type
            })
        }
        
        cancelEdit()
        await loadQuestions()
    } catch (e) {
        console.error(e)
        error.value = editingQuestionId.value ? "Failed to update question" : "Failed to add question"
    } finally {
        addingQuestion.value = false
    }
}

const deleteQuestion = async (questionId) => {
    if (!confirm("Are you sure you want to delete this question?")) return
    
    try {
        await vacancyAPI.deleteQuestion(props.vacancyId, questionId)
        questions.value = questions.value.filter(q => q.id !== questionId)
    } catch (e) {
        console.error("Failed to delete question", e)
        error.value = "Failed to delete question"
    }
}

const generateAiQuestions = async () => {
    if (!props.vacancyTitle) {
        alert("Please ensure the vacancy has a title.")
        return
    }
    
    aiQuestionGenerating.value = true
    error.value = ''
    try {
        const res = await aiAPI.generateScreeningQuestions({
            title: props.vacancyTitle,
            description: props.vacancyDescription
        })
        
        const aiQuestions = res.data
        if (Array.isArray(aiQuestions)) {
            for (const q of aiQuestions) {
                await vacancyAPI.addQuestion(props.vacancyId, {
                    question_text: q.question_text,
                    question_type: q.question_type || 'text'
                })
            }
            await loadQuestions()
        }
    } catch (e) {
        console.error("Failed to generate questions", e)
        error.value = "Failed to generate screening questions."
    } finally {
        aiQuestionGenerating.value = false
    }
}

onMounted(() => {
    if (props.vacancyId) {
        loadQuestions()
    }
})
</script>

<style scoped>
.questions-section {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #eee;
}

.question-list-wrapper {
    margin-bottom: 2rem;
}

.question-item {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 0.5rem;
    border: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.question-content {
    flex: 1;
}

.question-content p { margin: 0; }

.question-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-icon {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    cursor: pointer;
    font-size: 1rem;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    transition: background 0.2s;
}

.btn-delete { background: #fee2e2; color: #ef4444; }
.btn-delete:hover { background: #fecaca; }

.btn-edit { background: #e0e7ff; color: #4f46e5; font-size: 0.9rem; }
.btn-edit:hover { background: #c7d2fe; }

.badge {
    display: inline-block;
    background: #e9ecef;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #495057;
    margin-left: 0.5rem;
}

.add-question-form {
    margin-top: 1.5rem;
    background: #f1f3f5;
    padding: 1.5rem;
    border-radius: 8px;
}

.form-group {
    margin-bottom: 1rem;
}

label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

input, select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 1rem;
}

.form-actions-small {
    display: flex;
    gap: 1rem;
    align-items: center;
    margin-top: 1rem;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 6px;
    border: none;
    cursor: pointer;
}

.btn-secondary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-text {
    background: none;
    border: none;
    color: #6c757d;
    cursor: pointer;
    text-decoration: underline;
    font-size: 0.9rem;
}

.btn-ai-questions {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    padding: 0.6rem 1.2rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    width: 100%;
    transition: opacity 0.2s;
}

.btn-ai-questions:disabled { opacity: 0.7; cursor: not-allowed; }

.divider {
    display: flex;
    align-items: center;
    text-align: center;
    margin: 1.5rem 0;
    color: #adb5bd;
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.divider::before, .divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #dee2e6;
}

.divider::before { margin-right: .5em; }
.divider::after { margin-left: .5em; }

.error-msg {
    color: #c33;
    margin-top: 0.5rem;
}

.text-muted { color: #6c757d; }
.text-center { text-align: center; }
</style>
