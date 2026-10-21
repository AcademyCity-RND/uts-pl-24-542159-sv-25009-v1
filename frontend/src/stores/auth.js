import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('jb_token') || null)
  const user = ref(JSON.parse(localStorage.getItem('jb_user') || 'null'))
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const currentUser = computed(() => user.value)

  function setAuth(newToken, newUser) {
    token.value = newToken
    user.value = newUser
    localStorage.setItem('jb_token', newToken)
    localStorage.setItem('jb_user', JSON.stringify(newUser))
  }

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('jb_token')
    localStorage.removeItem('jb_user')
  }

  async function login(email, password) {
    loading.value = true
    try {
      const response = await api.post('/login', { email, password })
      const { user: userData, token: userToken } = response.data.data
      setAuth(userToken, userData)
      return { success: true, user: userData }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Login gagal. Periksa kredensial Anda.',
        errors: error.response?.data?.errors || null
      }
    } finally {
      loading.value = false
    }
  }

  async function register(payload) {
    loading.value = true
    try {
      const response = await api.post('/register', payload)
      const { user: userData, token: userToken } = response.data.data
      setAuth(userToken, userData)
      return { success: true, user: userData }
    } catch (error) {
      return {
        success: false,
        error: error.response?.data?.message || 'Pendaftaran gagal.',
        errors: error.response?.data?.errors || null
      }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      if (token.value) {
        await api.post('/logout')
      }
    } catch (e) {
      // Abaikan error saat proses revoke di server jika token sudah expired
    } finally {
      clearAuth()
    }
  }

  async function fetchCurrentUser() {
    if (!token.value) return null
    try {
      const response = await api.get('/user')
      user.value = response.data.data.user
      localStorage.setItem('jb_user', JSON.stringify(user.value))
      return user.value
    } catch (e) {
      if (e.response?.status === 401) {
        clearAuth()
      }
      return null
    }
  }

  return {
    token,
    user,
    loading,
    isAuthenticated,
    isAdmin,
    currentUser,
    login,
    register,
    logout,
    fetchCurrentUser,
    clearAuth
  }
})
