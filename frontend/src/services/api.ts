import axios, {
  type AxiosInstance,
  type InternalAxiosRequestConfig,
  type AxiosResponse,
  type AxiosError
} from 'axios'
import type { ApiResponse } from '../types/api'

export interface ApiErrorDetail {
  isNetworkError?: boolean
  status: number
  message: string
  errors?: Record<string, string[]>
}

// Inisialisasi instance Axios membaca base URL dari VITE_API_URL
const api: AxiosInstance = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
  headers: {
    'Accept': 'application/json'
  }
})

// Request Interceptor: Otomatis sisipkan Bearer token jika ada di localStorage
api.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    try {
      const token = localStorage.getItem('jb_token')
      if (token && config.headers) {
        config.headers.Authorization = `Bearer ${token}`
      }
    } catch (e) {
      // Mengatasi environment yang membatasi akses localStorage
    }
    return config
  },
  (error: AxiosError) => {
    return Promise.reject(error)
  }
)

// Response Interceptor: Tangani error semantik (401, 403, 404, 409, 422, 429, 5xx, Network Error)
api.interceptors.response.use(
  (response: AxiosResponse) => {
    return response
  },
  (error: AxiosError<any>) => {
    // 1. Kasus Network Error / Server offline / Connection Refused
    if (!error.response) {
      const networkError: ApiErrorDetail = {
        isNetworkError: true,
        status: 0,
        message: 'Layanan sedang dalam pemeliharaan atau koneksi ke server terputus. Silakan coba lagi.'
      }
      return Promise.reject(networkError)
    }

    const { status, data } = error.response

    // 2. HTTP 401 Unauthorized: Sesi berakhir / token tidak sah
    if (status === 401) {
      try {
        localStorage.removeItem('jb_token')
        localStorage.removeItem('jb_user')
      } catch (e) {}

      const currentPath = window.location.pathname
      const isAuthPage = currentPath.includes('/login') || currentPath.includes('/register')
      if (!isAuthPage) {
        window.location.href = '/login?expired=1'
      }
    }

    // 3. Bangun objek error terstandarisasi untuk konsumsi UI
    const normalizedError: ApiErrorDetail = {
      isNetworkError: false,
      status,
      message: data?.message || getDefaultErrorMessage(status),
      errors: data?.errors || undefined
    }

    return Promise.reject(normalizedError)
  }
)

// Helper pesan ramah pengguna per status kode semantik
function getDefaultErrorMessage(status: number): string {
  switch (status) {
    case 401:
      return 'Sesi Anda telah berakhir. Silakan masuk kembali.'
    case 403:
      return 'Akses ditolak: Anda tidak memiliki izin untuk melakukan tindakan ini.'
    case 404:
      return 'Data yang Anda cari tidak ditemukan atau telah dihapus.'
    case 409:
      return 'Terjadi konflik status data. Silakan periksa kembali kondisi laporan Anda.'
    case 422:
      return 'Data yang Anda masukkan tidak valid. Silakan periksa formulir.'
    case 429:
      return 'Terlalu banyak permintaan. Silakan tunggu beberapa saat lagi.'
    default:
      if (status >= 500) {
        return 'Terjadi kesalahan pada server. Layanan sedang dalam pemeliharaan.'
      }
      return 'Terjadi kesalahan saat memproses permintaan Anda.'
  }
}

// Helper resolver URL gambar (sesuai kontrak PRD & Design Guideline)
export function buildImageUrl(imagePath?: string | null): string | null {
  if (!imagePath) return null
  if (imagePath.startsWith('http://') || imagePath.startsWith('https://')) {
    return imagePath
  }
  const storageBase = import.meta.env.VITE_STORAGE_URL || 'http://localhost:8000/storage'
  const cleanPath = imagePath.replace(/^\/?(storage\/)?/, '')
  return `${storageBase.replace(/\/$/, '')}/${cleanPath}`
}

export default api
