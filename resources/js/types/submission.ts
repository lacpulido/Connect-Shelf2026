export type Adviser = {
    first_name: string
    last_name: string
}

export type Comment = {
    id: number
    comment: string | null
    adviser?: Adviser | null
}
export interface Submission {
    id: number
    slug: string
    title: string
    filename: string
    status: string | null
    version: number
    created_at: string
    folder_id?: number
    description?: string | null
    file_url?: string
    comments?: Comment[]
    is_current?: boolean
}
export type SubmissionVersion = {
    id: number
    version: number
    status: string | null
    created_at: string
    filename: string
    file_url: string
    comments: Comment[]
}

export type SubmissionDetails = {
   
    title: string
    project_slug: string
    versions: SubmissionVersion[]
}