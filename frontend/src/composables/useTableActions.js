/**
 * Build row action menus for DataTable / RowActionsMenu.
 * Filter with compactActions() — omit falsy entries.
 */

export function compactActions(items) {
  return items.filter(Boolean)
}

export function viewAction(routeName, id, label = 'View') {
  return { key: 'view', label, to: { name: routeName, params: { id } } }
}

export function editAction(onClick, label = 'Edit') {
  return { key: 'edit', label, onClick }
}

export function deleteAction(onClick, label = 'Delete') {
  return { key: 'delete', label, variant: 'danger', onClick }
}
