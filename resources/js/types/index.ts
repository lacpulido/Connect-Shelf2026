export type { Activity } from './activity'
export type { AdminUsersProps } from './admin'
export type { Auth } from './auth'
export type { Role, Department } from './common'
export type { NavItem } from './navigation'
export type { PageProps, NotificationItem, ZiggyConfig } from './page'
export type { PaginationLink, PaginatedUsers } from './pagination'
export type { Project, ProjectSchedule } from './project'
export type { Schedule, Panelist } from './schedule'
export type { StatusSummary } from './status'
export type {
    Adviser,
    Comment,
    Submission,
    SubmissionVersion,
    SubmissionDetails,
} from './submission'
export type { User, Researcher } from './user'
export interface BreadcrumbItem {
    title: string;
    href?: string;
}
