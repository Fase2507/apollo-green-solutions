import { Link } from 'react-router-dom'
import type { Project } from '../types'

const STATUS_LABELS: Record<string, string> = {
  planned: 'Planned',
  in_progress: 'In progress',
  on_hold: 'On hold',
  completed: 'Completed',
}

export default function ProjectCard({ project }: { project: Project }) {
  return (
    <Link to={`/projects/${project.id}`} className="card project-card">
      <div className="project-card-top">
        <h3>{project.name}</h3>
        <span className={`badge badge-${project.status}`}>
          {STATUS_LABELS[project.status]}
        </span>
      </div>
      {project.description && <p className="muted">{project.description}</p>}
      <div className="project-card-footer">
        <span>
          {project.tasks_count} task{project.tasks_count === 1 ? '' : 's'}
        </span>
        <span className="arrow">→</span>
      </div>
    </Link>
  )
}