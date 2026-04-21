import { ref } from 'vue'

export function useFileUpload() {
    const file = ref<File | null>(null)

    const handleFileChange = (event: Event) => {
        const input = event.target as HTMLInputElement
        if (input.files?.length) {
            file.value = input.files[0]
        }
    }

    const resetFile = () => {
        file.value = null
    }

    return { file, handleFileChange, resetFile }
}