export type UploadPurpose = 'message' | 'service_image' | 'avatar';

export type UploadedAttachment = {
    id: number;
    url: string;
    name: string;
    mime: string;
    size: number;
    collection?: string;
};

export function csrfToken(): string {
    if (typeof document === 'undefined') {
        return '';
    }

    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export async function uploadAttachment(
    file: File,
    purpose: UploadPurpose,
): Promise<UploadedAttachment> {
    const form = new FormData();
    form.append('file', file);
    form.append('purpose', purpose);

    const response = await fetch('/attachments', {
        method: 'POST',
        body: form,
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
    });

    if (!response.ok) {
        const payload = await response.json().catch(() => null);

        throw new Error(
            payload?.errors?.file?.[0] ??
                payload?.message ??
                'Upload failed. Try a smaller file.',
        );
    }

    const data = (await response.json()) as { attachment: UploadedAttachment };

    return data.attachment;
}
