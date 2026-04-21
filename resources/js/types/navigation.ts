import type { LucideIcon } from 'lucide-vue-next'

export interface NavItem {
  title: string
  href?: string
  icon?: LucideIcon
  isActive?: boolean
  children?: NavItem[]
}