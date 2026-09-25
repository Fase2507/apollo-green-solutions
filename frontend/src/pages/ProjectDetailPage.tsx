import { useCallback, useEffect, useState, type FormEvent } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import TaskItem from '../components/TaskItem'
import api, { errorMessage } from '../services/api'
import type { Project, ProjectStatus, Task, TaskPriority, TaskStatus } from '../types'
import { PROJECT_STATUSES, TASK_PRIORITIES } from '../types'

const PROJECT_STATUS_LABELS: Record<ProjectStatus, string> = {
  planned: 'Planned',
  in_progress: 'In progress',
  on_hold: 'On hold',
  completed: 'Completed',
}

export default function ProjectDetailPage() {
  const { id } = useParams<{ id: string }>()
  const navigate = useNavigate()
  const projectId = Number(id)

  const [project, setProject] = useState<Project | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  const [status, setStatus] = useState<ProjectStatus>('planned')
  const [savingStatus, setSavingStatus] = useState(false)

  const [taskTitle, setTaskTitle] = useState('')
  const [taskPriority, setTaskPriority] = useState<TaskPriority>('medium')
  const [taskDueDate, setTaskDueDate] = useState('')
  const [taskBusy, setTaskBusy] = useState(false)
  const [taskError, setTaskError] = useState('')

  const [taskActionId, setTaskActionId] = useState<number | null>(null)

  const loadProject = useCallback(async () => {
    try {
      const { data } = await api.get<{ data: Project }>(`/projects/${projectId}`)
      setProject(data.data)
      setStatus(data.data.status)
      setError('')
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setLoading(false)
    }
  }, [projectId])

  useEffect(() => {
    void loadProject()
  }, [loadProject])

  async function handleStatusChange(newStatus: ProjectStatus) {
    if (!project) return
    setSavingStatus(true)
    setError('')
    try {
      const { data } = await api.put<{ data: Project }>(`/projects/${project.id}`, {
        status: newStatus,
      })
      setStatus(data.data.status)
      setProject(data.data)
      await loadProject()
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setSavingStatus(false)
    }
  }

  async function handleAddTask(e: FormEvent) {
    e.preventDefault()
    if (!project) return
    setTaskError('')
    setTaskBusy(true)
    try {
      await api.post(`/projects/${project.id}/tasks`, {
        title: taskTitle,
        priority: taskPriority,
        due_date: taskDueDate || null,
      })
      setTaskTitle('')
      setTaskDueDate('')
      setTaskPriority('medium')
      await loadProject()
    } catch (err) {
      setTaskError(errorMessage(err))
    } finally {
      setTaskBusy(false)
    }
  }

  async function handleTaskStatusChange(task: Task, newStatus: TaskStatus) {
    setTaskActionId(task.id)
    setError('')
    try {
      await api.put(`/tasks/${task.id}`, { status: newStatus })
      await loadProject()
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setTaskActionId(null)
    }
  }

  async function handleDeleteTask(task: Task) {
    setTaskActionId(task.id)
    setError('')
    try {
      await api.delete(`/tasks/${task.id}`)
      await loadProject()
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setTaskActionId(null)
    }
  }

  async function handleDeleteProject() {
    if (!project) return
    if (!window.confirm(`Delete project "${project.name}" and all its tasks?`)) return
    setError('')
    try {
      await api.delete(`/projects/${project.id}`)
      navigate('/')
    } catch (err) {
      setError(errorMessage(err))
    }
  }

  if (loading) return <p className="muted">Loading project…</p>
  if (error && !project) return <div className="alert alert-error">{error}</div>
  if (!project) return null

  return (
    <div className="narrow">
      <Link to="/" className="back-link">
        ← Back to dashboard
      </Link>

      <div className="page-head">
        <h1>{project.name}</h1>
        <button type="button" className="btn btn-danger" onClick={handleDeleteProject}>
          Delete project
        </button>
      </div>

      {project.description && <p className="muted">{project.description}</p>}
      {error && <div className="alert alert-error">{error}</div>}

      <div className="form-group">
        <label htmlFor="project-status">Project status</label>
        <select
          id="project-status"
          className="select"
          value={status}
          disabled={savingStatus}
          onChange={(e) => handleStatusChange(e.target.value as ProjectStatus)}
        >
          {PROJECT_STATUSES.map((s) => (
            <option key={s} value={s}>
              {PROJECT_STATUS_LABELS[s]}
            </option>
          ))}
        </select>
      </div>

      <h2>Tasks ({project.tasks?.length ?? 0})</h2>

      <form className="card add-task-form" onSubmit={handleAddTask}>
        {taskError && <div className="alert alert-error">{taskError}</div>}
        <div className="add-task-row">
          <input
            type="text"
            className="input"
            placeholder="New task title"
            value={taskTitle}
            onChange={(e) => setTaskTitle(e.target.value)}
            required
          />
          <select
            className="select"
            value={taskPriority}
            onChange={(e) => setTaskPriority(e.target.value as TaskPriority)}
          >
            {TASK_PRIORITIES.map((p) => (
              <option key={p} value={p}>
                {p}
              </option>
            ))}
          </select>
          <input
            type="date"
            className="input"
            value={taskDueDate}
            onChange={(e) => setTaskDueDate(e.target.value)}
          />
          <button type="submit" className="btn btn-primary" disabled={taskBusy}>
            Add
          </button>
        </div>
      </form>

      {project.tasks && project.tasks.length > 0 ? (
        <ul className="task-list">
          {project.tasks.map((task) => (
            <TaskItem
              key={task.id}
              task={task}
              busy={taskActionId === task.id}
              onStatusChange={handleTaskStatusChange}
              onDelete={handleDeleteTask}
            />
          ))}
        </ul>
      ) : (
        <p className="muted">No tasks yet. Add the first one above.</p>
      )}
    </div>
  )
}