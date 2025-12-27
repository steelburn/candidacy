<template>
  <div class="tab-content">
    <div class="header">
      <h2>User Management</h2>
      <button @click="showCreateUser = true" class="btn-primary">Add User</button>
    </div>
    
    <div v-if="loading" class="loading">Loading users...</div>
    <div v-else-if="error" class="error">{{ error }}</div>
    
    <table v-else class="users-table">
      <thead>
        <tr>
          <th>Name</th>
          <th>Email</th>
          <th>Role</th>
          <th>Department</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="user in users" :key="user.id">
          <td><strong>{{ user.name }}</strong></td>
          <td>{{ user.email }}</td>
          <td>
            <span v-for="role in (user.roles || [])" :key="role.id" class="role-badge">
              {{ role.display_name || role.name }}
            </span>
            <span v-if="!user.roles || user.roles.length === 0" class="no-role">No role</span>
          </td>
          <td>{{ user.department || 'N/A' }}</td>
          <td>
            <span :class="'status-badge status-' + (user.is_active ? 'active' : 'inactive')">
              {{ user.is_active ? 'Active' : 'Inactive' }}
            </span>
          </td>
          <td>
            <button @click="editUser(user)" class="btn-sm">Edit</button>
            <button @click="deleteUser(user.id)" class="btn-sm btn-danger">Delete</button>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Create/Edit User Modal -->
    <div v-if="showCreateUser" class="modal-overlay" @click="showCreateUser = false">
      <div class="modal-content" @click.stop>
        <h3>{{ editingUser ? 'Edit User' : 'Create New User' }}</h3>
        <form @submit.prevent="submitUser" class="user-form">
          <div class="form-group">
            <label>Name *</label>
            <input v-model="userForm.name" type="text" required />
          </div>
          
          <div class="form-group">
            <label>Email *</label>
            <input v-model="userForm.email" type="email" required />
          </div>
          
          <div class="form-group" v-if="!editingUser">
            <label>Password *</label>
            <input v-model="userForm.password" type="password" required />
          </div>
          
          <div class="form-group">
            <label>Department</label>
            <input v-model="userForm.department" type="text" />
          </div>
          
          <div class="form-group">
            <label>Position</label>
            <input v-model="userForm.position" type="text" />
          </div>
          
          <div class="form-group">
            <label>
              <input v-model="userForm.is_active" type="checkbox" />
              Active
            </label>
          </div>
          
          <div class="form-group">
            <label>Role</label>
            <select v-model="userForm.role_id" class="select-input">
              <option value="">Select a role...</option>
              <option v-for="role in roles" :key="role.id" :value="role.id">
                {{ role.display_name }}
              </option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="submit" class="btn-primary">{{ editingUser ? 'Update' : 'Create' }}</button>
            <button type="button" @click="showCreateUser = false" class="btn-secondary">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { userAPI, roleAPI } from '../../services/api'

const loading = ref(false)
const error = ref('')
const users = ref([])
const roles = ref([])
const showCreateUser = ref(false)
const editingUser = ref(null)

const userForm = ref({
  name: '',
  email: '',
  password: '',
  department: '',
  position: '',
  is_active: true,
  role_id: ''
})

const loadUsers = async () => {
  loading.value = true
  error.value = ''
  try {
    const [usersRes, rolesRes] = await Promise.all([
      userAPI.list(),
      roleAPI.list()
    ])
    users.value = usersRes.data.data || usersRes.data || []
    roles.value = rolesRes.data || []
  } catch (err) {
    console.error('Failed to load users:', err)
    error.value = 'Failed to load users'
    users.value = []
  } finally {
    loading.value = false
  }
}

const editUser = (user) => {
  editingUser.value = user
  userForm.value = { 
    ...user, 
    password: '',
    role_id: user.roles && user.roles.length > 0 ? user.roles[0].id : ''
  }
  showCreateUser.value = true
}

const submitUser = async () => {
  try {
    let userId
    if (editingUser.value) {
      await userAPI.update(editingUser.value.id, userForm.value)
      userId = editingUser.value.id
    } else {
      const response = await userAPI.create(userForm.value)
      userId = response.data.id
    }
    
    // Handle role assignment
    if (userForm.value.role_id && userId) {
      try {
        await userAPI.assignRole(userId, userForm.value.role_id)
      } catch (roleErr) {
        console.error('Failed to assign role:', roleErr)
      }
    }
    
    showCreateUser.value = false
    editingUser.value = null
    userForm.value = {
      name: '',
      email: '',
      password: '',
      department: '',
      position: '',
      is_active: true,
      role_id: ''
    }
    loadUsers()
  } catch (err) {
    console.error('Failed to save user:', err)
    alert('Failed to save user')
  }
}

const deleteUser = async (id) => {
  if (!confirm('Are you sure you want to delete this user?')) return
  
  try {
    await userAPI.delete(id)
    loadUsers()
  } catch (err) {
    console.error('Failed to delete user:', err)
    alert('Failed to delete user')
  }
}

onMounted(() => {
  loadUsers()
})
</script>

<style scoped>
/* Reusing shared styles - ideally these should be global or shared css, but scoping them here for now */
.tab-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th {
  background: #f8f9fa;
  padding: 1rem;
  text-align: left;
  font-weight: 600;
}

.users-table td {
  padding: 1rem;
  border-top: 1px solid #eee;
}

.status-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge.status-active { background: #e8f5e9; color: #388e3c; }
.status-badge.status-inactive { background: #ffebee; color: #c62828; }

.role-badge {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  margin-right: 0.25rem;
}
.no-role { color: #999; font-style: italic; font-size: 0.875rem; }

.btn-primary {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
}

.btn-secondary {
  background: #6c757d;
  color: white;
  padding: 0.75rem 2rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
}

.btn-sm {
  padding: 0.5rem 1rem;
  border-radius: 4px;
  font-size: 0.875rem;
  margin-right: 0.5rem;
  background: #667eea;
  color: white;
  border: none;
  cursor: pointer;
}

.btn-danger { background: #dc3545; color: white; }

.modal-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="password"] {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
}
.select-input {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 6px;
  background: white;
}

.loading { text-align: center; padding: 3rem; color: #666; }
.error { background: #fee; color: #c33; padding: 0.75rem; border-radius: 6px; margin: 1rem 0; }
</style>
