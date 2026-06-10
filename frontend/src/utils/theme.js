const STORAGE_KEY = 'theme'

/** @typedef {'light' | 'dark' | 'system'} ThemeMode */

/**
 * @param {ThemeMode} mode
 * @returns {'light' | 'dark'}
 */
export function resolveTheme(mode) {
  if (mode === 'system') {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  }
  return mode === 'dark' ? 'dark' : 'light'
}

/**
 * @param {'light' | 'dark'} resolved
 */
export function applyTheme(resolved) {
  const root = document.documentElement
  root.setAttribute('data-theme', resolved)
  root.classList.toggle('dark', resolved === 'dark')
  root.style.colorScheme = resolved
}

/**
 * @returns {ThemeMode}
 */
export function readStoredThemeMode() {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored === 'light' || stored === 'dark' || stored === 'system') {
    return stored
  }
  return 'system'
}

/**
 * Apply persisted (or system) theme before Vue mounts — avoids flash.
 */
export function initTheme() {
  const mode = readStoredThemeMode()
  applyTheme(resolveTheme(mode))
  return mode
}

export { STORAGE_KEY }
