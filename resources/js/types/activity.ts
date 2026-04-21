export interface Activity {
  id: string
  type: 'submission' | 'comment' | 'approved' | 'revision'
  title: string
  description?: string
  created_at: string
}
