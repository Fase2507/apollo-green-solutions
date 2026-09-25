import type { Task, TaskStatus } from '../types'

const STATUS_LABELS: Record<TaskStatus, string> = {
  todo: 'Todo',
  in_progress: 'In progress',
  done: 'Done',
}

export default function TaskItem({
  task,
  onStatusChange,
  onDelete,
  busy,
}: {
  task: Task
  onStatusChange: (task: Task, status: TaskStatus) => void
  onDelete: (task: Task) => void
  busy: boolean
}) {
  return (
    <li className={`task-item task-${task.status}`}>
      <div className="task-main">
        <div className="task-title-row">
          <strong>{task.title}</strong>
          <span className={`badge badge-priority badge-${task.priority}`}>
            {task.priority}
          </span>
        </div>
        {task.description && <p className="muted">{task.description}</p>}
        <div className="task-meta">
          <span>{STATUS_LABELS[task.status]}</span>
          {task.due_date && <span>Due {task.due_date}</span>}
        </div>
      </div>
      <div className="task-actions">
        <select
          className="select"
          value={task.status}
          disabled={busy}
          onChange={(e) => onStatusChange(task, e.target.value as TaskStatus)}
        >
          {(['todo', 'in_progress', 'done'] as TaskStatus[]).map((s) => (
            <option key={s} value={s}>
              {STATUS_LABELS[s]}
            </option>
          ))}
        </select>
        <button
          type="button"
          className="btn btn-danger btn-sm"
          disabled={busy}
          onClick={() => onDelete(task)}
        >
          Delete
        </button>
      </div>
    </li>
  )
}