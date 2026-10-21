import axios from 'axios'

// Inisialisasi instance Axios membaca base URL dari VITE_API_URL
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Accept': 'application/json'
  }
})

// Request Interceptor: Otomatis sisipkan Bearer token jika ada di localStorage
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('jb_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Tangani error semantik (401, 403, 404, 409, 422, 429, 5xx)
api.interceptors.response.use(
  (response) => {
    return response
  },
  (error) => {
    if (!error.response) {
      // Network Error / Server offline / Connection Refused
      return Promise.reject({
        isNetworkError: true,
        message: 'Layanan sedang dalam pemeliharaan atau koneksi terputus. Silakan coba lagi.'
      })
    }

    const { status, data } = error.response

    if (status === 401) {
      // Sesi berakhir / token tidak valid: bersihkan token lokal
      localStorage.removeItem('jb_token')
      localStorage.removeItem('jb_user')
      if (window.location.pathname !== '/login' && window.location.pathname !== '/register') {
        window.location.href = '/login?expired=1'
      }
    }

    return Promise.reject(error)
  }
)

// Helper resolver URL gambar (sesuai kontrak PRD & Design Guideline)
export function buildImageUrl(imagePath) {
  if (!imagePath) return null
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath
  }
  const storageBase = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage'
  const cleanPath = imagePath.replace(/^\/?(storage\/)?/, '')
  return `${storageBase.replace(/\/$/, '')}/${cleanPath}`
}

export default api
