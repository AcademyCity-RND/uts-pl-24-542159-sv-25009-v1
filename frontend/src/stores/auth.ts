import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api, { type ApiErrorDetail } from '../services/api'
import type { User, ApiResponse } from '../types/api'

// Helper aman membaca dan mem-parse JSON dari localStorage
function safeGetStoredUser(): User | null {
  try {
    const raw = localStorage.getItem('jb_user')
    if (!raw) return null
    return JSON.parse(raw) as User
  } catch (e) {
    console.warn('Gagal mem-parse data jb_user dari localStorage, membersihkan sesi...', e)
    try {
      localStorage.removeItem('jb_user')
      localStorage.removeItem('jb_token')
    } catch (_) {}
    return null
  }
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('jb_token') || null)
  const user = ref<User | null>(safeGetStoredUser())
  const loading = ref<boolean>(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.role === 'admin')
  const currentUser = computed(() => user.value)

  function setAuth(newToken: string, newUser: User) {
    token.value = newToken
    user.value = newUser
    try {
      localStorage.setItem('jb_token', newToken)
      localStorage.setItem('jb_user', JSON.stringify(newUser))
    } catch (e) {
      console.warn('Gagal menyimpan sesi ke localStorage', e)
    }
  }

  function clearAuth() {
    token.value = null
    user.value = null
    try {
      localStorage.removeItem('jb_token')
      localStorage.removeItem('jb_user')
    } catch (e) {}
  }

  async function login(email: string, password: string) {
    loading.value = true
    try {
      // Backend POST /api/login mengembalikan: { message: "...", data: { user: {...}, token: "..." } }
      const response = await api.post<ApiResponse<{ user: User; token: string }>>('/login', { email, password })
      const { user: userData, token: userToken } = response.data.data
      setAuth(userToken, userData)
      return { success: true, user: userData }
    } catch (error: any) {
      const err = error as ApiErrorDetail
      return {
        success: false,
        error: err.message || 'Login gagal. Periksa kredensial Anda.',
        errors: err.errors || null
      }
    } finally {
      loading.value = false
    }
  }

  async function register(payload: Record<string, any>) {
    loading.value = true
    try {
      // Backend POST /api/register mengembalikan: { message: "...", data: { user: {...}, token: "..." } }
      const response = await api.post<ApiResponse<{ user: User; token: string }>>('/register', payload)
      const { user: userData, token: userToken } = response.data.data
      setAuth(userToken, userData)
      return { success: true, user: userData }
    } catch (error: any) {
      const err = error as ApiErrorDetail
      return {
        success: false,
        error: err.message || 'Pendaftaran gagal.',
        errors: err.errors || null
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
      // Abaikan error jaringan saat revokasi token jika server sudah menghapus sesi
    } finally {
      clearAuth()
    }
  }

  async function fetchCurrentUser() {
    if (!token.value) return null
    try {
      // Backend GET /api/user mengembalikan langsung: { data: { id: ..., name: ..., email: ..., role: ... } }
      const response = await api.get<ApiResponse<User>>('/user')
      const userData = response.data.data
      user.value = userData
      try {
        localStorage.setItem('jb_user', JSON.stringify(userData))
      } catch (e) {}
      return userData
    } catch (e: any) {
      if (e.status === 401) {
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
