import type { Auth } from './auth'

export interface NotificationItem {
    id: number
    title: string
    message: string
    status: 'READ' | 'UNREAD'
}

export interface ZiggyConfig {
    location: string
    url: string
    port: number | null
    defaults: Record<string, unknown>
    routes: Record<string, string>
}

export interface PageProps {
    auth: Auth
    notifications?: NotificationItem[]
    showOthersMenu?: boolean
    ziggy?: ZiggyConfig
    [key: string]: unknown
}