import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import ProjectCard from '../components/ProjectCard'
import api, { errorMessage } from '../services/api'
import type { Project } from '../types'

export default function DashboardPage() {
  const [projects, setProjects] = useState<Project[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let active = true
    api
      .get<{ data: Project[] }>('/projects')
      .then(({ data }) => {
        if (active) setProjects(data.data)
      })
      .catch((err) => {
        if (active) setError(errorMessage(err))
      })
      .finally(() => {
        if (active) setLoading(false)
      })
    return () => {
      active = false
    }
  }, [])

  return (
    <div>
      <div className="page-head">
        <h1>Dashboard</h1>
        <Link to="/projects/new" className="btn btn-primary">
          + New Project
        </Link>
      </div>

      {loading && <p className="muted">Loading projects…</p>}
      {error && <div className="alert alert-error">{error}</div>}

      {!loading && !error && projects.length === 0 && (
        <div className="empty-state">
          <p>No projects yet.</p>
          <Link to="/projects/new" className="btn btn-primary">
            Create your first project
          </Link>
        </div>
      )}

      {projects.length > 0 && (
        <div className="project-grid">
          {projects.map((project) => (
            <ProjectCard key={project.id} project={project} />
          ))}
        </div>
      )}
    </div>
  )
}