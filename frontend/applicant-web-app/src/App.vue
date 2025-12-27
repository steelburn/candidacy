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
  padding: 1.25rem 2rem;
  background: rgba(30, 41, 59, 0.6);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--glass-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo {
  font-size: 1.75rem;
  font-weight: 800;
  text-decoration: none;
  letter-spacing: -0.05em;
  background: var(--primary-gradient);
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
