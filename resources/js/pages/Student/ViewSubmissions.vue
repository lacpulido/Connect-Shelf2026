<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList } from '@/components/ui/breadcrumb';
import { Separator } from '@/components/ui/separator';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { useSubmissionStatus } from '@/composables/useSubmissionStatus';
import { getSectionKeyFromTitle } from '@/constants/submissionSections';
import type { SubmissionDetails } from '@/types/submission';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const { formatDateTime } = useDateFormatter();
const { statusClasses, statusLabel } = useSubmissionStatus();

const page = usePage<{ submission: SubmissionDetails }>();
const submission = computed(() => page.props.submission);
const backSection = computed(() => getSectionKeyFromTitle(submission.value?.title));
</script>

<template>
    <Head :title="submission.title" />

    <SidebarProvider>
        <AppSidebar />

        <SidebarInset>
            <!-- HEADER -->
            <header class="flex h-16 items-center gap-2 border-b px-6">
                <SidebarTrigger />
                <Separator orientation="vertical" class="h-4" />

                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <Link
                                :href="backSection ? route('student.submissions', { open: backSection }) : route('student.submissions')"
                                class="text-gray-500 hover:text-gray-900 transition"
                            >
                                Submissions
                            </Link>
                        </BreadcrumbItem>

                        <BreadcrumbItem>
                            <span class="truncate max-w-[220px]">
                                {{ submission.title }}
                            </span>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </header>

            <!-- CONTENT -->
            <div class="space-y-6 p-6">
                <!-- TITLE -->
                <div class="space-y-1">
                    <h1 class="text-2xl font-semibold text-gray-900">
                        {{ submission.title }}
                    </h1>

                    <Link
                        :href="backSection ? route('student.submissions', { open: backSection }) : route('student.submissions')"
                        class="text-sm text-gray-500 hover:text-gray-900 transition"
                    >
                        ← Back to Submissions
                    </Link>
                </div>

                <!-- CARD -->
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="mb-6 flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Revision History
                        </h2>

                        <span class="text-sm text-gray-500">
                            {{ submission.versions.length }} version(s)
                        </span>
                    </div>

                    <!-- CLEAN LIST (NO TIMELINE) -->
                    <div
                        v-if="submission.versions.length > 0"
                        class="space-y-4"
                    >
                        <div
                            v-for="version in submission.versions"
                            :key="version.id"
                            class="rounded-2xl border border-gray-200 p-5 transition hover:shadow-sm"
                        >
                            <!-- TOP -->
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900">
                                        Version {{ version.version }}
                                    </span>

                                    <span
                                        :class="[
                                            'rounded-full px-3 py-1 text-xs font-medium capitalize',
                                            statusClasses(version.status),
                                        ]"
                                    >
                                        {{ statusLabel(version.status) }}
                                    </span>
                                </div>

                                <span class="text-xs text-gray-400">
                                    {{ formatDateTime(version.created_at) }}
                                </span>
                            </div>

                            <!-- FILE -->
                            <div class="mt-3">
                                <a
                                    :href="version.file_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-sm font-medium text-black-600 hover:underline"
                                >
                                    {{ version.filename }}
                                </a>
                            </div>

                            <!-- COMMENTS -->
                            <div class="mt-4 border-t pt-3">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    Comments
                                </p>

                                <div
                                    v-if="version.comments?.length && version.comments[0]?.comment?.trim()"
                                    class="mt-2"
                                >
                                    <p class="text-sm leading-relaxed text-gray-700">
                                        {{ version.comments[0].comment }}
                                    </p>
                                </div>

                                <div v-else class="mt-2">
                                    <p class="text-sm italic text-gray-400">
                                        No comment provided
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EMPTY -->
                    <div
                        v-else
                        class="flex min-h-[40vh] items-center justify-center"
                    >
                        <div class="w-full max-w-md rounded-2xl border border-gray-200 px-8 py-12 text-center">
                            <h2 class="text-xl font-semibold text-black">
                                No Revision History
                            </h2>
                            <p class="mt-2 text-sm text-gray-500">
                                No revision history available for this submission.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </SidebarInset>
    </SidebarProvider>
</template>