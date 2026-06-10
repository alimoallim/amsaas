import { defineStore } from 'pinia'
import { applyTheme, readStoredThemeMode, resolveTheme, STORAGE_KEY } from '@/utils/theme'

export const useThemeStore = defineStore('theme', {
  state: () => ({
    /** @type {'light' | 'dark' | 'system'} */
    mode: readStoredThemeMode(),
    /** @type {'light' | 'dark'} */
    resolved: resolveTheme(readStoredThemeMode()),
  }),

  getters: {
    isDark: (state) => state.resolved === 'dark',
    label: (state) => {
      if (state.mode === 'system') return `System (${state.resolved})`
      return state.mode === 'dark' ? 'Dark' : 'Light'
    },
  },

  actions: {
    init() {
      this.mode = readStoredThemeMode()
      this.sync()

      if (this._mediaListener) return

      const mq = window.matchMedia('(prefers-color-scheme: dark)')
      this._mediaListener = () => {
        if (this.mode === 'system') this.sync()
      }
      mq.addEventListener('change', this._mediaListener)
    },

    sync() {
      this.resolved = resolveTheme(this.mode)
      applyTheme(this.resolved)
    },

    setMode(mode) {
      this.mode = mode
      localStorage.setItem(STORAGE_KEY, mode)
      this.sync()
    },

    toggle() {
      const next = this.resolved === 'dark' ? 'light' : 'dark'
      this.setMode(next)
    },
  },
})
