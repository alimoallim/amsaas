/**
 * Resolved tenant label: display_name, else first + last name, else tenant_code.
 */
export function tenantDisplayName(tenant) {
  if (!tenant) return ''

  const display = String(tenant.display_name ?? '').trim()
  if (display) return display

  const first = String(tenant.name?.first_name ?? tenant.first_name ?? '').trim()
  const last = String(tenant.name?.last_name ?? tenant.last_name ?? '').trim()
  const combined = `${first} ${last}`.trim()
  if (combined) return combined

  return String(tenant.tenant_code ?? '').trim()
}
