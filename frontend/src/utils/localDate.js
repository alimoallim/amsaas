/** Format a Date as YYYY-MM-DD in local timezone (avoids UTC shift from toISOString). */
export function localDateString(date = new Date()) {
  const y = date.getFullYear()
  const m = String(date.getMonth() + 1).padStart(2, '0')
  const d = String(date.getDate()).padStart(2, '0')
  return `${y}-${m}-${d}`
}

/** First and last day of the calendar month containing `date`. */
export function calendarMonthPeriod(date = new Date()) {
  const from = new Date(date.getFullYear(), date.getMonth(), 1)
  const to = new Date(date.getFullYear(), date.getMonth() + 1, 0)
  return {
    from: localDateString(from),
    to: localDateString(to),
  }
}
