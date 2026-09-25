import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from 'react'
import api, { TOKEN_KEY } from '../services/api'
import type { AuthResponse, User } from '../types'

interface AuthContextValue {
  user: User | null
  loading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (name: string, email: string, password: string) => Promise<void>
  verify: (email: string, code: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [loading, setLoading] = useState(true)

  useEffect(() => {
    const token = localStorage.getItem(TOKEN_KEY)
    if (!token) {
      setLoading(false)
      return
    }
    api
      .get<{ data: User }>('/user')
      .then(({ data }) => setUser(data.data))
      .finally(() => setLoading(false))
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    const { data } = await api.post<AuthResponse>('/login', { email, password })
    localStorage.setItem(TOKEN_KEY, data.token)
    setUser(data.data)
  }, [])

  const register = useCallback(async (name: string, email: string, password: string) => {
    await api.post('/register', {
      name,
      email,
      password,
      password_confirmation: password,
    })
  }, [])

  const verify = useCallback(async (email: string, code: string) => {
    const { data } = await api.post<AuthResponse>('/verify', {
      email,
      verification_code: code,
    })
    localStorage.setItem(TOKEN_KEY, data.token)
    setUser(data.data)
  }, [])

  const logout = useCallback(async () => {
    try {
      await api.post('/logout')
    } finally {
      localStorage.removeItem(TOKEN_KEY)
      setUser(null)
    }
  }, [])

  const value = useMemo(
    () => ({ user, loading, login, register, verify, logout }),
    [user, loading, login, register, verify, logout],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext)
  if (!ctx) {
    throw new Error('useAuth must be used within an <AuthProvider>')
  }
  return ctx
}