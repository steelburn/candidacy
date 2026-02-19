<template>
  <div class="docx-viewer">
    <div v-if="loading" class="loading">Loading document...</div>
    <div v-if="error" class="error">{{ error }}</div>
    <div ref="container" class="docx-container"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, toRefs } from 'vue'
import { renderAsync } from 'docx-preview'

const props = defineProps({
  src: {
    type: String,
    default: ''
  },
  blob: {
    type: Object, // File or Blob
    default: null
  },
  height: {
      type: String,
      default: '800px'
  }
})

const { src, blob } = toRefs(props)
const container = ref(null)
const loading = ref(false)
const error = ref(null)

const renderDoc = async () => {
    if (!container.value) return
    
    // Clear previous
    container.value.innerHTML = ''
    error.value = null
    
    let fileData = blob.value
    
    if (!fileData && src.value) {
        loading.value = true
        try {
            const response = await fetch(src.value)
            if (!response.ok) throw new Error(`Failed to load file: ${response.statusText}`)
            fileData = await response.blob()
        } catch (e) {
            error.value = "Failed to load document. " + e.message
            loading.value = false
            return
        }
    }
    
    if (!fileData) return

    loading.value = true
    try {
        await renderAsync(fileData, container.value, container.value, {
            className: 'docx', // class name/prefix for default styles
            inWrapper: true, // enables rendering of wrapper around document content
            ignoreWidth: false, // disables rendering width of page
            ignoreHeight: false, // disables rendering height of page
            ignoreFonts: false, // disables fonts rendering
            breakPages: true, // enables page breaking on page breaks
            ignoreLastRenderedPageBreak: true, // disables page breaking on lastRenderedPageBreak elements
            experimental: false, // enables experimental features (may be unstable)
            trimXmlDeclaration: true, // if true, xml declaration will be removed from xml documents before parsing
            useBase64URL: false, // if true, images, fonts, etc. will be converted to base64 URL, otherwise URL.createObjectURL is used
            useMathMLPolyfill: false, // includes MathML polyfills for chrome, edge, etc.
            showChanges: false, // enables experimental changes rendering
            debug: false, // enables additional logging
        })
    } catch (e) {
        console.error("Docx rendering error", e)
        error.value = "Failed to render document. The file might be corrupted or not a valid DOCX."
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    renderDoc()
})

watch([src, blob], () => {
    renderDoc()
})
</script>

<style scoped>
.docx-viewer {
    width: 100%;
    height: 100%;
    overflow: auto;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}

.docx-container {
    padding: 20px;
}

.loading, .error {
    text-align: center;
    padding: 20px;
    color: #666;
}

.error {
    color: #dc3545;
}
</style>
