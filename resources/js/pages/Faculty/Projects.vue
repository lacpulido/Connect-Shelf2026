<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue'
import { Breadcrumb, BreadcrumbList, BreadcrumbItem as CrumbItem } from '@/components/ui/breadcrumb'
import { Separator } from '@/components/ui/separator'
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar'
import { useDisplayFormatters } from '@/composables/useDisplayFormatter'
import type { Project, User } from '@/types'
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import { FolderOpen } from 'lucide-vue-next'
import {
    Empty,
    EmptyContent,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
} from '@/components/ui/empty'

type PageProps = {
    projects?: Project[]
}

type ProjectCard = {
    key: string
    slug: string | null
    title: string
    description: string
    project_type: string
    student: User | null
    student_name: string
    student_department: string
    initials: string
}

const page = usePage<PageProps>()
const projects = computed<Project[]>(() => page.props.projects ?? [])

const { initials, fullName, departmentName } = useDisplayFormatters()

const projectCards = computed<ProjectCard[]>(() => {
    const grouped: Record<string, Project[]> = {}

    for (const project of projects.value) {
        const studentKey = String(project.student?.id ?? `unknown-${project.id}`)

        if (!grouped[studentKey]) {
            grouped[studentKey] = []
        }

        grouped[studentKey].push(project)
    }

    return Object.entries(grouped).map(([studentKey, studentProjects]) => {
        const project = studentProjects[0]
        const student = project?.student ?? null

        return {
            key: studentKey,
            slug: project?.slug ?? null,
            title: project?.title ?? 'Untitled Project',
            description: project?.description?.trim() || 'No project description provided.',
            project_type: project?.project_type ?? 'No project type',
            student,
            student_name: fullName(student, 'No student assigned'),
            student_department: departmentName(student, 'No department'),
            initials: initials(student, 'NA'),
        }
    })
})

function canViewProject(project: ProjectCard): boolean {
    return !!project.slug
}

function projectUrl(project: ProjectCard): string {
    if (!project.slug) return '#'

    return route('faculty.projects.show', { project: project.slug })
}
</script>

<template>
    <Head title="Advisee Projects" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <CrumbItem>Projects</CrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <div class="space-y-5 p-6">
                <div class="rounded-xl border border-gray-200 bg-white px-6 py-5 shadow-sm">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">Your Projects</h1>
                            <p class="mt-1 text-sm text-gray-500">
                                View and manage all assigned projects.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    v-if="projectCards.length === 0"
                    class="flex min-h-[60vh] items-center justify-center"
                >
                    <Empty class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <EmptyHeader>
                            <EmptyMedia variant="icon">
                                <FolderOpen />
                            </EmptyMedia>
                        </EmptyHeader>

                        <EmptyTitle>No Projects Yet</EmptyTitle>

                        <EmptyDescription>
                            There are currently no projects assigned to you.
                        </EmptyDescription>

                        <EmptyContent />
                    </Empty>
                </div>

                <div v-else class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    <div
                        v-for="project in projectCards"
                        :key="project.key"
                        class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                    >
                        <div class="min-h-[128px] border-b border-gray-100 bg-gradient-to-r from-[#0C4B05] to-[#146b0c] px-5 py-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-white/80">
                                        {{ project.project_type }}
                                    </p>
                                    <h2 class="line-clamp-3 text-lg font-bold leading-snug text-white">
                                        {{ project.title }}
                                    </h2>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-1 flex-col p-5">
                            <div class="pt-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                                    Student
                                </p>

                                <div class="mt-3 flex items-center gap-3">
                                    <div
                                        class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-[#0C4B05]/10 text-sm font-bold uppercase text-[#0C4B05]"
                                    >
                                        {{ project.initials }}
                                    </div>

                                    <div class="min-w-0">
                                        <p class="truncate text-base font-semibold text-gray-900">
                                            {{ project.student_name }}
                                        </p>
                                        <p class="truncate text-sm text-gray-500">
                                            {{ project.student_department }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-auto pt-6">
                                <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                                    <span class="text-xs font-medium text-gray-400">
                                        Assigned Project
                                    </span>

                                    <Link
                                        v-if="canViewProject(project)"
                                        :href="projectUrl(project)"
                                        class="inline-flex min-w-[140px] items-center justify-center rounded-md bg-[#0C4B05] px-4 py-2.5 text-sm font-medium text-white transition hover:opacity-90"
                                    >
                                        View Project
                                    </Link>

                                    <span
                                        v-else
                                        class="inline-flex min-w-[140px] items-center justify-center rounded-lg bg-red-50 px-3 py-2 text-xs font-semibold text-red-500"
                                    >
                                        Missing project slug
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>