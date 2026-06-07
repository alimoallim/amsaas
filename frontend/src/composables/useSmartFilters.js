import { computed, reactive, watch } from 'vue'

/**
 * Fiori Smart Filter Bar — immediate apply, chips, optional URL sync.
 *
 * @param {object} options
 * @param {Record<string, string>} options.defaults
 * @param {Record<string, { label?: string, format?: (v: string) => string }>} [options.labels]
 */
export function useSmartFilters({ defaults, labels = {} }) {
  const filters = reactive({ ...defaults })

  const chips = computed(() =>
    Object.entries(filters)
      .filter(([key, value]) => {
        const def = defaults[key]
        return value !== '' && value != null && String(value) !== String(def ?? '')
      })
      .map(([key, value]) => {
        const meta = labels[key] || {}
        const formatted = meta.format ? meta.format(value) : value
        return {
          key,
          label: meta.label || key,
          value: String(formatted),
        }
      })
  )

  function clearAll() {
    Object.keys(defaults).forEach((key) => {
      filters[key] = defaults[key]
    })
  }

  function removeChip(key) {
    if (key in defaults) {
      filters[key] = defaults[key]
    }
  }

  /**
   * Sync filters ↔ route.query (bookmarkable lists).
   */
  function bindRoute(route, router, { debounceMs = 0 } = {}) {
    const keys = Object.keys(defaults)

    function applyFromQuery() {
      keys.forEach((key) => {
        const q = route.query[key]
        filters[key] = q != null && q !== '' ? String(q) : defaults[key]
      })
    }

    applyFromQuery()

    let timer = null
    watch(
      () => ({ ...filters }),
      () => {
        const push = () => {
          const query = { ...route.query }
          keys.forEach((key) => {
            const v = filters[key]
            if (v !== '' && v != null && String(v) !== String(defaults[key] ?? '')) {
              query[key] = String(v)
            } else {
              delete query[key]
            }
          })
          router.replace({ query })
        }
        if (debounceMs > 0) {
          clearTimeout(timer)
          timer = setTimeout(push, debounceMs)
        } else {
          push()
        }
      },
      { deep: true }
    )

    watch(
      () => route.query,
      () => applyFromQuery(),
      { deep: true }
    )
  }

  return {
    filters,
    chips,
    clearAll,
    removeChip,
    bindRoute,
  }
}
