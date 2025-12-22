<script setup>
</script>

<template>
  <header>
    <nav class="navbar">
      <router-link to="/" class="logo">Career Portal</router-link>
      <div class="nav-links">
        <router-link to="/match">Find Matches</router-link>
        <router-link to="/login" class="btn-login">Portal Login</router-link>
      </div>
    </nav>
  </header>

  <main>
    <router-view />
  </main>
</template>

<style scoped>
.navbar {
  padding: 1.5rem 2rem;
  background-color: var(--card-bg);
  border-bottom: 1px solid var(--border-color);
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.logo {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--text-color);
  text-decoration: none;
  background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.nav-links {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.nav-links a {
  color: #94a3b8;
  text-decoration: none;
  font-weight: 500;
  transition: color 0.2s;
}

.nav-links a:hover {
  color: white;
}

.btn-login {
  background: rgba(255,255,255,0.1);
  padding: 0.5rem 1rem;
  border-radius: 0.5rem;
  color: white !important;
}
</style>
