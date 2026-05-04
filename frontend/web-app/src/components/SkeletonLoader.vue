<template>
  <!-- Table skeleton (for list views) -->
  <div v-if="type === 'table'" class="skeleton-table">
    <!-- Header -->
    <div class="skeleton-thead">
      <div
        v-for="col in columns"
        :key="col"
        class="skeleton-th"
        :style="{ width: colWidth || 'auto' }"
      ></div>
    </div>
    <!-- Rows -->
    <div
      v-for="row in rows"
      :key="row"
      class="skeleton-tr"
    >
      <div
        v-for="(col, ci) in columns"
        :key="ci"
        class="skeleton-td"
      >
        <div
          class="skeleton-cell"
          :class="{ 'skeleton-avatar': ci === 0 }"
          :style="getCellStyle(ci)"
        ></div>
      </div>
    </div>
  </div>

  <!-- Card skeleton (for dashboard stats / card grids) -->
  <div v-else-if="type === 'card'" class="skeleton-cards">
    <div
      v-for="row in rows"
      :key="row"
      class="skeleton-card"
    >
      <div class="skeleton-card-icon"></div>
      <div class="skeleton-card-text"></div>
      <div class="skeleton-card-value"></div>
    </div>
  </div>

  <!-- Detail skeleton (for form/detail pages) -->
  <div v-else-if="type === 'detail'" class="skeleton-detail">
    <div v-for="row in rows" :key="row" class="skeleton-detail-row">
      <div class="skeleton-label"></div>
      <div class="skeleton-input"></div>
    </div>
  </div>

  <!-- Default: paragraph lines -->
  <div v-else class="skeleton-paragraph">
    <div
      v-for="row in rows"
      :key="row"
      class="skeleton-line"
      :style="{ width: row === rows ? '60%' : '100%' }"
    ></div>
  </div>
</template>

<script setup>
const props = defineProps({
  type: {
    type: String,
    default: 'paragraph' // 'table' | 'card' | 'detail' | 'paragraph'
  },
  rows: {
    type: Number,
    default: 5
  },
  columns: {
    type: Number,
    default: 4
  },
  colWidth: {
    type: String,
    default: ''
  }
})

function getCellStyle(index) {
  const widths = ['40px', '80%', '120px', '100px']
  return { width: widths[index % widths.length] }
}
</script>

<style scoped>
/* Shared shimmer animation */
.skeleton-cell,
.skeleton-line,
.skeleton-label,
.skeleton-input,
.seleton-card-icon,
.skeleton-card-text,
.skeleton-card-value,
.skeleton-th,
.skeleton-td > div {
  background: linear-gradient(
    90deg,
    #f0f0f0 25%,
    #e0e0e0 37%,
    #f0f0f0 63%
  );
  background-size: 400% 100%;
  animation: shimmer 1.4s ease infinite;
  border-radius: 6px;
}

@keyframes shimmer {
  0% { background-position: 100% 50%; }
  100% { background-position: 0% 50%; }
}

/* Table skeleton */
.skeleton-table {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #f0f0f0;
}

.skeleton-thead {
  display: flex;
  gap: 12px;
  padding: 14px 20px;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
}

.skeleton-th {
  flex: 1;
  height: 14px;
  border-radius: 4px;
}

.skeleton-tr {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  border-bottom: 1px solid #f9f9f9;
}

.skeleton-tr:last-child {
  border-bottom: none;
}

.skeleton-td {
  flex: 1;
}

.skeleton-cell {
  height: 14px;
  border-radius: 4px;
}

.skeleton-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  flex-shrink: 0;
}

/* Card skeleton */
.skeleton-cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 16px;
}

.skeleton-card {
  background: white;
  border-radius: 12px;
  padding: 20px;
  border: 1px solid #f0f0f0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.skeleton-card-icon {
  width: 40px;
  height: 40px;
  border-radius: 10px;
}

.skeleton-card-text {
  height: 12px;
  width: 60%;
}

.skeleton-card-value {
  height: 28px;
  width: 80%;
}

/* Detail skeleton */
.skeleton-detail {
  display: flex;
  flex-direction: column;
  gap: 20px;
  background: white;
  border-radius: 12px;
  padding: 24px;
  border: 1px solid #f0f0f0;
}

.skeleton-detail-row {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skeleton-label {
  height: 12px;
  width: 120px;
}

.skeleton-input {
  height: 40px;
  width: 100%;
  border-radius: 8px;
}

/* Paragraph skeleton */
.skeleton-paragraph {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 24px;
  background: white;
  border-radius: 12px;
}

.skeleton-line {
  height: 14px;
}

/* Dark mode overrides */
@media (prefers-color-scheme: dark) {
  .skeleton-cell,
  .skeleton-line,
  .skeleton-label,
  .skeleton-input,
  .skeleton-card-icon,
  .skeleton-card-text,
  .skeleton-card-value,
  .skeleton-th,
  .skeleton-td > div {
    background: linear-gradient(
      90deg,
      #2a2a2a 25%,
      #383838 37%,
      #2a2a2a 63%
    );
    background-size: 400% 100%;
  }
}
</style>
