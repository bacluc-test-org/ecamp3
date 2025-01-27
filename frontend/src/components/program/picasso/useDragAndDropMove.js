import { toTime, roundTimeToNearestQuarterHour } from '@/helpers/vCalendarDragAndDrop.js'

/**
 * @param enabled {Ref<boolean>} drag & drop is disabled if enabled=false
 * @param threshold {number}     min. mouse movement needed to detect drag & drop
 * @param update {(scheduleEntry: object, startTime: number, endTime: number) => void} callback for update actions
 * @param minTimestamp {Ref<number>}  minimum allowed start timestamp (calendar start)
 * @param maxTimestamp {Ref<number>}  maximum allowed end timestamp (calendar end)
 * @returns
 */
export function useDragAndDropMove(
  enabled,
  threshold,
  update,
  minTimestamp,
  maxTimestamp
) {
  /**
   * internal data (not exposed)
   */

  // reference to dragged scheduleEntry (null = no drag & drop ongoing)
  let draggedEntry = null

  // time offset between (dragged) entry startTimestamp and mouse location
  let mouseOffset = null

  // coordinates of mouse down event
  let startX = null
  let startY = null

  // true while drag & drop action is ongoing (after mouse has moved more than threshold)
  let dragging = false

  /**
   * internal methods
   */

  // returns true if still within defined threshold
  function withinThreshold(nativeEvent) {
    return (
      Math.abs(nativeEvent.x - startX) < threshold &&
      Math.abs(nativeEvent.y - startY) < threshold
    )
  }

  // move an existing entry
  const moveEntry = (mouse) => {
    const start = draggedEntry.startTimestamp
    const end = draggedEntry.endTimestamp
    const duration = end - start

    const newStart = roundTimeToNearestQuarterHour(mouse - mouseOffset)
    const newEnd = newStart + duration

    const isWithinBounds = newStart >= minTimestamp.value && newEnd <= maxTimestamp.value
    if (isWithinBounds) {
      draggedEntry.startTimestamp = newStart
      draggedEntry.endTimestamp = newEnd
    }
    draggedEntry.isMoving = true
  }

  const clear = () => {
    if (draggedEntry) {
      draggedEntry.isMoving = false
    }

    mouseOffset = null
    draggedEntry = null
    startX = null
    startY = null
    dragging = false
  }

  /**
   * exposed methods
   */

  // triggered with MouseDown event on a calendar entry
  const entryMouseDown = ({ event: entry, timed, nativeEvent }) => {
    if (!enabled.value) {
      return
    }

    // cancel drag & drop if button is not left button
    if (nativeEvent.button !== 0) {
      return
    }

    // only move timed events and non-filtered entries
    if (!entry || !timed || !entry.filterMatch) {
      return
    }

    // start Drag & Drop
    startX = nativeEvent.x
    startY = nativeEvent.y
    draggedEntry = entry
    mouseOffset = null // not know yet: will be populated by timeMouseDown event
  }

  // triggered with MouseDown event anywhere on the calendar (independent of clicking on entry or not)
  const timeMouseDown = (tms, nativeEvent) => {
    if (!enabled.value) {
      return
    }

    // cancel drag & drop if button is not left button
    if (nativeEvent.button !== 0) {
      return
    }

    // Drag has just started on an entry ('mousedown:event' was detected before)
    if (draggedEntry) {
      const mouseTime = toTime(tms)
      const startTimestamp = draggedEntry.startTimestamp
      mouseOffset = mouseTime - startTimestamp
    }
  }

  // triggered when mouse is being moved in calendar (independent whether drag & drop is ongoing or not)
  const timeMouseMove = (tms, nativeEvent) => {
    if (!enabled.value) {
      return
    }

    const mouse = toTime(tms)

    if (draggedEntry) {
      // don't move if still within threshold
      if (withinThreshold(nativeEvent)) {
        return
      }

      // move existing
      dragging = true
      moveEntry(mouse)
    }
  }

  // triggered with MouseUp Event anywhere in the calendar
  const timeMouseUp = () => {
    if (!enabled.value) {
      return
    }

    // trigger update callback
    if (dragging) {
      update(draggedEntry, draggedEntry.startTimestamp, draggedEntry.endTimestamp)
    }

    clear()
  }

  // treat mouseleave as a mouseUp event (finish operation and save last known status)
  const nativeMouseLeave = () => {
    timeMouseUp()
  }

  return {
    vCalendarListeners: {
      'mousedown:event': entryMouseDown,
      'mousedown:time': timeMouseDown,
      'mousemove:time': timeMouseMove,
      'mouseup:time': timeMouseUp,
    },
    nativeMouseLeave,
  }
}
