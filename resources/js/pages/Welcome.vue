<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import Header from '@/components/Navbar.vue'
import Search from '@/components/Search.vue'
import FeaturedProjectsList from '@/components/FeaturedProjectsList.vue'
// import FilterProject from '@/components/FilterProject.vue'
import NavFooter from '@/components/NavFooter.vue'

const props = defineProps<{
    projects: any[]
    years: string[]
    projectTypes: string[]
    filters: {
        year: string
        project_type: string
    }
}>()
</script>

<template>
    <Head title="Welcome" />

    <Header />

    <div class="bg-[#FDFDFC] px-6 py-6">
        <div class="flex flex-col items-start w-full">
          
            <div class="w-full">
                <Search />
            </div>

       
            <div class="w-full">
                <FeaturedProjectsList :projects="props.projects" />
            </div>

          
            <div class="w-full">
                <NavFooter />
            </div>
        </div>
    </div>
</template>