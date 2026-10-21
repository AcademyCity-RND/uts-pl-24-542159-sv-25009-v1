import { createRouter, createWebHistory } from 'vue-router'
import PublicDashboard from '../views/PublicDashboard.vue'

const routes = [
  {
    path: '/',
    name: 'home',
    component: PublicDashboard,
    meta: { title: 'Pusat Informasi Barang Hilang & Ditemukan' }
  },
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { guestOnly: true, title: 'Masuk Akun' }
  },
  {
    path: '/register',
    name: 'register',
    component: () => import('../views/RegisterView.vue'),
    meta: { guestOnly: true, title: 'Daftar Akun Baru' }
  },
  {
    path: '/report',
    name: 'report-item',
    component: () => import('../views/ReportItemView.vue'),
    meta: { requiresAuth: true, title: 'Laporkan Barang' }
  },
  {
    path: '/items/:id',
    name: 'item-detail',
    component: () => import('../views/ItemDetailView.vue'),
    meta: { title: 'Detail Barang' }
  },
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('../views/AdminDashboard.vue'),
    meta: { requiresAuth: true, requiresAdmin: true, title: 'Dashboard Moderasi Admin' }
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior() {
    return { top: 0 }
  }
})

// Route Guards
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('jb_token')
  const user = JSON.parse(localStorage.getItem('jb_user') || 'null')

  if (to.meta.title) {
    document.title = `${to.meta.title} — JejakBarang`
  }

  if (to.meta.requiresAuth && !token) {
    return next({ name: 'login', query: { redirect: to.fullPath } })
  }

  if (to.meta.requiresAdmin && (!user || user.role !== 'admin')) {
    return next({ name: 'home' })
  }

  if (to.meta.guestOnly && token) {
    return next({ name: 'home' })
  }

  next()
})

export default router
