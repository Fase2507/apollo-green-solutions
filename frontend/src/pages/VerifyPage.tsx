import { useState, type FormEvent } from 'react'
import { Link, useLocation, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import { errorMessage } from '../services/api'

interface LocationState {
  email?: string
}

export default function VerifyPage() {
  const { verify } = useAuth()
  const navigate = useNavigate()
  const location = useLocation()
  const state = (location.state as LocationState | null) ?? {}
  const [email, setEmail] = useState(state.email ?? '')
  const [code, setCode] = useState('')
  const [error, setError] = useState('')
  const [submitting, setSubmitting] = useState(false)

  async function handleSubmit(e: FormEvent) {
    e.preventDefault()
    setError('')
    setSubmitting(true)
    try {
      await verify(email, code)
      navigate('/')
    } catch (err) {
      setError(errorMessage(err))
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <div className="auth-screen">
      <form className="card auth-card" onSubmit={handleSubmit}>
        <div className="auth-heading">
          <span className="brand-dot" />
          <h1>Verify your email</h1>
          <p className="muted">
            Enter the 6-digit code we sent to <strong>{email}</strong>
          </p>
        </div>

        {error && <div className="alert alert-error">{error}</div>}

        <div className="form-group">
          <label htmlFor="verify-email">Email</label>
          <input
            id="verify-email"
            type="email"
            className="input"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            autoComplete="email"
            required
          />
        </div>

        <div className="form-group">
          <label htmlFor="verify-code">Verification code</label>
          <input
            id="verify-code"
            type="text"
            className="input"
            inputMode="numeric"
            pattern="[0-9]{6}"
            maxLength={6}
            value={code}
            onChange={(e) => setCode(e.target.value.replace(/\D/g, ''))}
            placeholder="123456"
            required
          />
        </div>

        <button type="submit" className="btn btn-primary" disabled={submitting}>
          {submitting ? 'Verifying…' : 'Verify & continue'}
        </button>

        <p className="auth-alt">
          Back to <Link to="/register">registration</Link> · Already verified?{' '}
          <Link to="/login">Log in</Link>
        </p>
      </form>
    </div>
  )
}