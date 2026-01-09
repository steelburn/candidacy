<script>
import { defineComponent, h } from 'vue'

export default defineComponent({
  name: 'SettingCard',
  props: {
    setting: { type: Object, required: true },
    editingSetting: { type: String, default: null },
    editValue: { type: [String, Number, Boolean, Object, Array], default: null },
    showSensitive: { type: Object, default: () => ({}) }
  },
  emits: ['startEdit', 'save', 'cancel', 'updateEditValue', 'toggleSensitive', 'viewHistory'],
  setup(props, { emit }) {
    // Helper to emit update
    const onInput = (val) => emit('updateEditValue', val)

    // Render select helper
    const renderSelect = (value, onInput, options) => {
      return h('select', { value, onChange: (e) => onInput(e.target.value), class: 'select-input' }, 
        options.map(o => h('option', { value: o.val }, o.label))
      )
    }

    // Render input control based on setting type
    const renderInputControl = (setting, value, onInput) => {
      // Boolean
      if (setting.type === 'boolean') {
        return h('label', { class: 'toggle-switch' }, [
          h('input', { type: 'checkbox', checked: value, onChange: (e) => onInput(e.target.checked) }),
          h('span', { class: 'toggle-slider' }),
          h('span', { class: 'toggle-label' }, value ? 'Enabled' : 'Disabled')
        ])
      }
      // Color
      if (setting.key.includes('color')) {
        return h('div', { class: 'color-input-group' }, [
          h('input', { type: 'color', value: value, onInput: (e) => onInput(e.target.value) }),
          h('input', { type: 'text', value: value, onInput: (e) => onInput(e.target.value), class: 'text-input' })
        ])
      }
      // Selects
      if (setting.key === 'ai.provider') {
        return renderSelect(value, onInput, [
          { val: 'ollama', label: 'Ollama (Local)' },
          { val: 'openrouter', label: 'OpenRouter' },
          { val: 'openai', label: 'OpenAI' },
          { val: 'gemini', label: 'Google Gemini' },
          { val: 'azure', label: 'Azure OpenAI' },
          { val: 'litellm', label: 'LiteLLM Proxy' },
          { val: 'llamacpp', label: 'Llama.cpp Server' }
        ])
      }
      if (setting.key === 'ui.date_format') {
        return renderSelect(value, onInput, [
          { val: 'YYYY-MM-DD', label: 'YYYY-MM-DD' },
          { val: 'DD/MM/YYYY', label: 'DD/MM/YYYY' },
          { val: 'MM/DD/YYYY', label: 'MM/DD/YYYY' },
          { val: 'DD MMM YYYY', label: 'DD MMM YYYY' },
          { val: 'MMM DD, YYYY', label: 'MMM DD, YYYY' }
        ])
      }
      if (setting.key === 'ui.time_format') {
        return renderSelect(value, onInput, [
          { val: 'HH:mm', label: '24-hour' },
          { val: 'hh:mm A', label: '12-hour' }
        ])
      }
      // Timezone
      if (setting.key === 'app.timezone') {
        const timezones = Intl.supportedValuesOf('timeZone').map(tz => ({ val: tz, label: tz }))
        return renderSelect(value, onInput, timezones)
      }
      // Document Parser: Supported Types (Checkbox Group)
      if (setting.key === 'document_parser.supported_types') {
        const types = value || { pdf: true, docx: true, doc: true, txt: true }
        return h('div', { class: 'checkbox-group' }, 
          Object.keys(types).map(type => 
            h('label', { class: 'checkbox-item' }, [
              h('input', { 
                type: 'checkbox', 
                checked: types[type], 
                onChange: (e) => {
                  const newVal = { ...types, [type]: e.target.checked }
                  onInput(newVal)
                } 
              }),
              h('span', { class: 'checkbox-label' }, type.toUpperCase())
            ])
          )
        )
      }
      // Document Parser: Pipeline (Reorderable List)
      if (setting.key === 'document_parser.pipeline' || setting.key === 'document_parser.pdf_pipeline') {
        const pipeline = Array.isArray(value) ? value : []
        return h('div', { class: 'pipeline-list' }, pipeline.map((item, index) => 
          h('div', { class: 'pipeline-item' }, [
            h('span', { class: 'pipeline-name' }, `${index + 1}. ${item}`),
            h('div', { class: 'pipeline-controls' }, [
              h('button', { 
                class: 'btn-icon-sm', 
                disabled: index === 0,
                onClick: () => {
                  const newPipeline = [...pipeline]
                  ;[newPipeline[index - 1], newPipeline[index]] = [newPipeline[index], newPipeline[index - 1]]
                  onInput(newPipeline)
                }
              }, '↑'),
              h('button', { 
                class: 'btn-icon-sm', 
                disabled: index === pipeline.length - 1,
                onClick: () => {
                  const newPipeline = [...pipeline]
                  ;[newPipeline[index + 1], newPipeline[index]] = [newPipeline[index], newPipeline[index + 1]]
                  onInput(newPipeline)
                }
              }, '↓')
            ])
          ])
        ))
      }
      // Range
      if (setting.type === 'integer' && (setting.key.includes('threshold') || setting.key.includes('score'))) {
        return h('div', { class: 'range-group' }, [
          h('input', { type: 'range', min: 0, max: 100, value: value, onInput: (e) => onInput(parseInt(e.target.value)) }),
          h('span', { class: 'range-val' }, value + '%')
        ])
      }
      // Default Input
      return h('input', { 
        type: setting.type === 'integer' ? 'number' : 'text',
        value: value,
        onInput: (e) => onInput(setting.type === 'integer' ? parseInt(e.target.value) : e.target.value),
        class: 'text-input full-width'
      })
    }

    // Render value display (view mode)
    const renderValueDisplay = (setting, showSensitive, toggle) => {
      if (setting.type === 'boolean') {
        return h('span', { class: setting.value ? 'val-true' : 'val-false' }, setting.value ? '✓ Enabled' : '✗ Disabled')
      }
      if (setting.is_sensitive) {
        const visible = showSensitive[setting.key]
        return h('div', { class: 'sensitive-row' }, [
          h('span', { class: 'val-text' }, visible ? (setting.value || '(empty)') : '••••••••'),
          h('button', { class: 'btn-icon-sm', onClick: () => toggle(setting.key) }, visible ? '👁️' : '👁️‍🗨️')
        ])
      }
      if (setting.key.includes('color')) {
        return h('div', { class: 'color-preview-row' }, [
          h('div', { class: 'color-dot', style: { backgroundColor: setting.value } }),
          h('span', { class: 'val-text' }, setting.value)
        ])
      }
      if (setting.type === 'json') {
        let val = setting.value
        if (typeof val === 'string') {
          try { val = JSON.parse(val) } catch (e) { /* ignore */ }
        }
        if (Array.isArray(val)) {
          return h('span', { class: 'val-text' }, val.join(', ') || '(empty list)')
        } else if (typeof val === 'object' && val !== null) {
          const keys = Object.keys(val).filter(k => val[k]).join(', ').toUpperCase()
          return h('span', { class: 'val-text' }, keys || '(none)')
        }
      }
      return h('span', { class: 'val-text' }, setting.value || '(empty)')
    }

    // Main render function
    return () => {
      const { setting, editingSetting, editValue, showSensitive } = props
      const isEditing = editingSetting === setting.key

      // Edit Mode
      if (isEditing) {
        return h('div', { class: 'setting-card setting-edit-mode' }, [
          h('div', { class: 'setting-meta-edit' }, [
            h('code', { class: 'setting-key' }, setting.key),
            h('p', { class: 'setting-desc' }, setting.description)
          ]),
          h('div', { class: 'edit-controls' }, [
            renderInputControl(setting, editValue, onInput),
            h('div', { class: 'edit-actions' }, [
              h('button', { class: 'btn-sm btn-save', onClick: () => emit('save', setting) }, '✓ Save'),
              h('button', { class: 'btn-sm btn-cancel', onClick: () => emit('cancel') }, 'Cancel')
            ])
          ])
        ])
      }

      // View Mode
      return h('div', { class: 'setting-card setting-view-mode' }, [
        // Row 1: Key and Actions
        h('div', { class: 'setting-header' }, [
          h('code', { class: 'setting-key' }, setting.key),
          h('div', { class: 'action-row' }, [
            h('button', { class: 'btn-icon', title: 'Edit', onClick: () => emit('startEdit', setting) }, '✏️'),
            h('button', { class: 'btn-icon', title: 'History', onClick: () => emit('viewHistory', setting.key) }, '📜')
          ])
        ]),
        // Row 2: Badges (Meta)
        (setting.is_sensitive || (setting.service_scope && setting.service_scope !== 'all')) 
          ? h('div', { class: 'setting-meta-row' }, [
              setting.is_sensitive ? h('span', { class: 'badge badge-sensitive' }, 'Sensitive') : null,
              setting.service_scope && setting.service_scope !== 'all' ? h('span', { class: 'badge badge-scope' }, setting.service_scope) : null
            ]) 
          : null,
        // Row 3: Description
        h('p', { class: 'setting-desc' }, setting.description),
        // Row 4: Value
        h('div', { class: 'setting-value-display' }, [
          renderValueDisplay(setting, showSensitive, (k) => emit('toggleSensitive', k))
        ])
      ])
    }
  }
})
</script>
