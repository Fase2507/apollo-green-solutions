import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { useState } from 'react'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()
  const [busy, setBusy] = useState(false)

  async function handleLogout() {
    setBusy(true)
    await logout()
    navigate('/login')
  }

  return (
    <header className="navbar">
      <Link to="/" className="navbar-brand">
        <span className="brand-dot" />
        Apollo Green Solutions
      </Link>
      <nav className="navbar-links">
        <Link to="/">Dashboard</Link>
        <Link to="/projects/new" className="btn btn-primary btn-sm">
          + New Project
        </Link>
        <span className="navbar-user">
          {user?.name}
          <button
            type="button"
            className="btn btn-ghost btn-sm"
            onClick={handleLogout}
            disabled={busy}
          >
            Logout
          </button>
        </span>
      </nav>
    </header>
  )
}