import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useThemeStore = defineStore('theme', () => {
  const currentTheme = ref(document.documentElement.getAttribute('data-theme') || 'light')

  function setTheme(theme) {
    currentTheme.value = theme
    document.documentElement.setAttribute('data-theme', theme)
    localStorage.setItem('jb_theme', theme)
  }

  function toggleTheme() {
    const nextTheme = currentTheme.value === 'dark' ? 'light' : 'dark'
    setTheme(nextTheme)
  }

  return {
    currentTheme,
    setTheme,
    toggleTheme
  }
})
