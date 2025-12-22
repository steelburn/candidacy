<template>
  <div class="markdown-content" v-html="renderedHtml"></div>
</template>

<script setup>
import { computed } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const props = defineProps({
  content: {
    type: String,
    default: ''
  }
})

// Configure marked options
marked.setOptions({
  breaks: true,
  gfm: true,
  headerIds: true,
  mangle: false
})

const renderedHtml = computed(() => {
  if (!props.content) return ''
  
  // Parse markdown to HTML
  const rawHtml = marked.parse(props.content)
  
  // Sanitize HTML to prevent XSS
  return DOMPurify.sanitize(rawHtml)
})
</script>

<style scoped>
.markdown-content {
  line-height: 1.6;
  color: #333;
}

.markdown-content :deep(h1) {
  font-size: 2em;
  font-weight: 700;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  color: #2c3e50;
  border-bottom: 2px solid #42b983;
  padding-bottom: 0.3em;
}

.markdown-content :deep(h2) {
  font-size: 1.5em;
  font-weight: 600;
  margin-top: 1.2em;
  margin-bottom: 0.5em;
  color: #2c3e50;
}

.markdown-content :deep(h3) {
  font-size: 1.25em;
  font-weight: 600;
  margin-top: 1em;
  margin-bottom: 0.5em;
  color: #34495e;
}

.markdown-content :deep(p) {
  margin-bottom: 1em;
}

.markdown-content :deep(ul),
.markdown-content :deep(ol) {
  margin-bottom: 1em;
  padding-left: 2em;
}

.markdown-content :deep(li) {
  margin-bottom: 0.5em;
}

.markdown-content :deep(code) {
  background-color: #f5f5f5;
  padding: 0.2em 0.4em;
  border-radius: 3px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
  color: #e83e8c;
}

.markdown-content :deep(pre) {
  background-color: #f5f5f5;
  padding: 1em;
  border-radius: 5px;
  overflow-x: auto;
  margin-bottom: 1em;
}

.markdown-content :deep(pre code) {
  background-color: transparent;
  padding: 0;
  color: #333;
}

.markdown-content :deep(blockquote) {
  border-left: 4px solid #42b983;
  padding-left: 1em;
  margin-left: 0;
  color: #666;
  font-style: italic;
}

.markdown-content :deep(a) {
  color: #42b983;
  text-decoration: none;
}

.markdown-content :deep(a:hover) {
  text-decoration: underline;
}

.markdown-content :deep(strong) {
  font-weight: 600;
  color: #2c3e50;
}

.markdown-content :deep(em) {
  font-style: italic;
}

.markdown-content :deep(hr) {
  border: none;
  border-top: 1px solid #e0e0e0;
  margin: 2em 0;
}

.markdown-content :deep(table) {
  border-collapse: collapse;
  width: 100%;
  margin-bottom: 1em;
}

.markdown-content :deep(th),
.markdown-content :deep(td) {
  border: 1px solid #ddd;
  padding: 0.75em;
  text-align: left;
}

.markdown-content :deep(th) {
  background-color: #f5f5f5;
  font-weight: 600;
}

.markdown-content :deep(tr:nth-child(even)) {
  background-color: #fafafa;
}
</style>
