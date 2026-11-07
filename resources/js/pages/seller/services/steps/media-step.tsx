import { ImagePlus, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type StepProps } from './types';

export function MediaStep({ data, errors, updateField }: StepProps) {
    const images = Array.isArray(data.image_urls)
        ? (data.image_urls as string[])
        : [];

    const updateImage = (index: number, value: string) => {
        const next = [...images];
        next[index] = value;
        updateField('image_urls', next);
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
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <p className="text-sm font-medium">Image URLs</p>
                        <p className="text-xs text-muted-foreground">
                            Optional. Add up to three public image URLs.
                        </p>
                    </div>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            updateField('image_urls', [...images, ''])
                        }
                        disabled={images.length >= 3}
                    >
                        <ImagePlus className="size-3.5" aria-hidden />
                        Add image
                    </Button>
                </div>

                <div className="space-y-3">
                    {images.length === 0 && (
                        <div className="rounded-lg border border-dashed p-5 text-center text-sm text-muted-foreground">
                            No images yet. Supporting information below is
                            enough to publish.
                        </div>
                    )}
                    {images.map((image, index) => (
                        <div key={index} className="space-y-2">
                            <div className="flex items-center gap-2">
                                <Input
                                    value={image}
                                    onChange={(event) =>
                                        updateImage(index, event.target.value)
                                    }
                                    placeholder="https://example.com/work.jpg"
                                    maxLength={2048}
                                    aria-label={`Image URL ${index + 1}`}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeImage(index)}
                                    aria-label={`Remove image ${index + 1}`}
                                >
                                    <X className="size-4" aria-hidden />
                                </Button>
                            </div>
                            {image.startsWith('http') && (
                                <img
                                    src={image}
                                    alt={`Listing image ${index + 1} preview`}
                                    className="h-32 w-full rounded-lg border border-border object-cover"
                                    loading="lazy"
                                />
                            )}
                        </div>
                    ))}
                </div>
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
