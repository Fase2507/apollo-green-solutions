export type ProjectStatus = 'planned' | 'in_progress' | 'on_hold' | 'completed'

export type TaskStatus = 'todo' | 'in_progress' | 'done'

export type TaskPriority = 'low' | 'medium' | 'high'

export interface User {
  id: number
  name: string
  email: string
  created_at: string
}

export interface Project {
  id: number
  name: string
  description: string | null
  status: ProjectStatus
  tasks_count: number
  created_at: string
  updated_at: string
  tasks?: Task[]
}

export interface Task {
  id: number
  project_id: number
  title: string
  description: string | null
  status: TaskStatus
  priority: TaskPriority
  due_date: string | null
  created_at: string
  updated_at: string
}

export interface AuthResponse {
  data: User
  token: string
}

export const PROJECT_STATUSES: ProjectStatus[] = [
  'planned',
  'in_progress',
  'on_hold',
  'completed',
]

export const TASK_STATUSES: TaskStatus[] = ['todo', 'in_progress', 'done']

export const TASK_PRIORITIES: TaskPriority[] = ['low', 'medium', 'high']