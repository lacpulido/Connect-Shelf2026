export function parseSubmissionSlug(slug?: string) {
    if (!slug) {
        return { folder: '', document: '' };
    }

    const [folder = '', document = ''] = slug.split('/');
    return { folder, document };
}