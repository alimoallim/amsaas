/**
 * Normalize Laravel API list payloads.
 * Handles both flat `{ data: [...] }` and nested `{ data: { data: [...] } }` from Resource::collection().
 */
export function unwrapApiList(body) {
  const root = body?.data ?? body
  if (Array.isArray(root)) {
    return root
  }
  if (root && Array.isArray(root.data)) {
    return root.data
  }
  return []
}

export function unwrapApiMeta(body, fallback = {}) {
  if (body?.meta && typeof body.meta === 'object') {
    return body.meta
  }
  const nested = body?.data?.meta
  if (nested && typeof nested === 'object') {
    return nested
  }
  return fallback
}

export function unwrapApiRecord(body) {
  const root = body?.data ?? body
  if (root && typeof root === 'object' && !Array.isArray(root) && root.data && typeof root.data === 'object') {
    return root.data
  }
  return root
}
