import { useRef, useState } from 'react';
import { ImagePlus, Loader2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { uploadAttachment } from '@/lib/attachments';
import { asString, firstError, type StepProps } from './types';

const MAX_IMAGES = 6;

export function MediaStep({ data, errors, updateField }: StepProps) {
    const images = Array.isArray(data.image_urls)
        ? (data.image_urls as string[])
        : [];
    const [uploading, setUploading] = useState(false);
    const [uploadError, setUploadError] = useState<string | null>(null);
    const fileInput = useRef<HTMLInputElement>(null);

    const upload = async (file: File) => {
        setUploading(true);
        setUploadError(null);

        try {
            const attachment = await uploadAttachment(file, 'service_image');

            updateField('image_urls', [...images, attachment.url]);
        } catch (error) {
            setUploadError(
                error instanceof Error ? error.message : 'Upload failed.',
            );
        } finally {
            setUploading(false);

            if (fileInput.current) {
                fileInput.current.value = '';
            }
        }
    };

    const removeImage = (index: number) => {
        updateField(
            'image_urls',
            images.filter((_, itemIndex) => itemIndex !== index),
        );
    };

    return (
        <div className="space-y-6">
            <div className="space-y-2">
                <div className="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-medium">Work images</p>
                        <p className="text-xs text-muted-foreground">
                            Up to {MAX_IMAGES} images, 10 MB each. JPG, PNG, or
                            WebP.
                        </p>
                    </div>
                    <input
                        ref={fileInput}
                        type="file"
                        className="sr-only"
                        accept="image/jpeg,image/png,image/webp"
                        onChange={(event) => {
                            const file = event.target.files?.[0];

                            if (file) {
                                void upload(file);
                            }
                        }}
                    />
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => fileInput.current?.click()}
                        disabled={uploading || images.length >= MAX_IMAGES}
                    >
                        {uploading ? (
                            <Loader2 className="size-3.5 animate-spin" />
                        ) : (
                            <ImagePlus className="size-3.5" aria-hidden />
                        )}
                        {uploading ? 'Uploading…' : 'Upload image'}
                    </Button>
                </div>

                {uploadError !== null && (
                    <p className="text-sm text-destructive" role="alert">
                        {uploadError}
                    </p>
                )}

                {images.length === 0 ? (
                    <div className="rounded-lg border border-dashed p-5 text-center text-sm text-muted-foreground">
                        No images yet. Buyers trust listings that show the work.
                    </div>
                ) : (
                    <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        {images.map((image, index) => (
                            <li
                                key={`${image}-${index}`}
                                className="group relative overflow-hidden rounded-lg border"
                            >
                                <img
                                    src={image}
                                    alt={`Listing image ${index + 1}`}
                                    className="h-32 w-full object-cover"
                                    loading="lazy"
                                />
                                <button
                                    type="button"
                                    onClick={() => removeImage(index)}
                                    className="absolute top-1 right-1 rounded-full bg-background/90 p-1 shadow-sm ring-1 ring-border"
                                    aria-label={`Remove image ${index + 1}`}
                                >
                                    <X className="size-3.5" aria-hidden />
                                </button>
                            </li>
                        ))}
                    </ul>
                )}

                {firstError(errors, 'image_urls', 'image_urls.0') && (
                    <p className="text-sm text-destructive">
                        {firstError(errors, 'image_urls', 'image_urls.0')}
                    </p>
                )}
            </div>

            <WizardField
                label="Supporting information"
                htmlFor="supporting_info"
                error={firstError(errors, 'supporting_info')}
                hint="Optional. Tools, process, examples, or anything a buyer should know."
            >
                <Textarea
                    id="supporting_info"
                    value={asString(data.supporting_info)}
                    onChange={(event) =>
                        updateField('supporting_info', event.target.value)
                    }
                    rows={6}
                    maxLength={3000}
                    placeholder="Built with Laravel, Inertia, React, and PostgreSQL-compatible migrations."
                />
            </WizardField>
        </div>
    );
}
