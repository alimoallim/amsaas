import { onMounted, onUnmounted } from 'vue'

/**
 * Toggle a class on <html> and <body> for route-scoped mobile CSS (avoids :has() — weak on Android).
 */
export function useDocumentRouteClass(className) {
  onMounted(() => {
    document.documentElement.classList.add(className)
    document.body.classList.add(className)
  })

  onUnmounted(() => {
    document.documentElement.classList.remove(className)
    document.body.classList.remove(className)
  })
}
