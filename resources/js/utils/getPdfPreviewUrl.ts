export function getPdfPreviewUrl(fileUrl?: string | null): string {
    if (!fileUrl) return '';

    const trimmedUrl = fileUrl.trim();
    if (!trimmedUrl) return '';

    const isAbsolute =
        trimmedUrl.startsWith('http://') ||
        trimmedUrl.startsWith('https://');

    const normalizedUrl = isAbsolute
        ? trimmedUrl
        : trimmedUrl.startsWith('/')
          ? trimmedUrl
          : `/${trimmedUrl}`;

    return `${normalizedUrl}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;
}