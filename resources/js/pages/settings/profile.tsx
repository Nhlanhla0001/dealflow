import { Form, Head, Link, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/delete-user';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { uploadAttachment } from '@/lib/attachments';
import { edit } from '@/routes/profile';
import type { Auth } from '@/types';
import { send } from '@/routes/verification';

type PageProps = {
    auth: Auth;
};

export default function Profile({
    mustVerifyEmail,
    status,
}: {
    mustVerifyEmail: boolean;
    status?: string;
}) {
    const { auth } = usePage<PageProps>().props;
    const [uploadingAvatar, setUploadingAvatar] = useState(false);
    const [avatarError, setAvatarError] = useState<string | null>(null);
    const avatarInput = useRef<HTMLInputElement>(null);

    const uploadAvatar = async (file: File) => {
        setUploadingAvatar(true);
        setAvatarError(null);

        try {
            await uploadAttachment(file, 'avatar');
            router.reload({ only: ['auth'] });
        } catch (error) {
            setAvatarError(
                error instanceof Error ? error.message : 'Upload failed.',
            );
        } finally {
            setUploadingAvatar(false);

            if (avatarInput.current) {
                avatarInput.current.value = '';
            }
        }
    };

    return (
        <>
            <Head title="Profile settings" />

            <h1 className="sr-only">Profile settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Profile"
                    description="Update your name, photo, and email address"
                />

                <div className="flex flex-wrap items-center gap-4">
                    <Avatar className="size-16 overflow-hidden rounded-full">
                        <AvatarImage
                            src={auth.user.avatar_url ?? undefined}
                            alt={auth.user.name}
                        />
                        <AvatarFallback className="rounded-full bg-neutral-200 text-base text-black dark:bg-neutral-700 dark:text-white">
                            {auth.user.name.slice(0, 2).toUpperCase()}
                        </AvatarFallback>
                    </Avatar>

                    <div className="space-y-2">
                        <input
                            ref={avatarInput}
                            type="file"
                            className="sr-only"
                            accept="image/jpeg,image/png,image/webp"
                            onChange={(event) => {
                                const file = event.target.files?.[0];

                                if (file) {
                                    void uploadAvatar(file);
                                }
                            }}
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={() => avatarInput.current?.click()}
                            disabled={uploadingAvatar}
                        >
                            {uploadingAvatar ? 'Uploading…' : 'Change photo'}
                        </Button>
                        <p className="text-xs text-muted-foreground">
                            JPG, PNG, or WebP up to 10 MB.
                        </p>
                        {avatarError !== null && (
                            <p
                                className="text-sm text-destructive"
                                role="alert"
                            >
                                {avatarError}
                            </p>
                        )}
                    </div>
                </div>

                <Form
                    {...ProfileController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.name}
                                    name="name"
                                    required
                                    autoComplete="name"
                                    placeholder="Full name"
                                    aria-invalid={Boolean(errors.name)}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.name}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    defaultValue={auth.user.email}
                                    name="email"
                                    required
                                    autoComplete="username"
                                    placeholder="Email address"
                                    aria-invalid={Boolean(errors.email)}
                                />

                                <InputError
                                    className="mt-2"
                                    message={errors.email}
                                />
                            </div>

                            {mustVerifyEmail &&
                                auth.user.email_verified_at === null && (
                                    <div>
                                        <p className="-mt-4 text-sm text-muted-foreground">
                                            Your email address is unverified.{' '}
                                            <Link
                                                href={send()}
                                                as="button"
                                                className="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                            >
                                                Click here to re-send the
                                                verification email.
                                            </Link>
                                        </p>

                                        {status ===
                                            'verification-link-sent' && (
                                            <div className="mt-2 text-sm font-medium text-green-600">
                                                A new verification link has been
                                                sent to your email address.
                                            </div>
                                        )}
                                    </div>
                                )}

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-profile-button"
                                >
                                    Save
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <DeleteUser />
        </>
    );
}

Profile.layout = {
    breadcrumbs: [
        {
            title: 'Profile settings',
            href: edit(),
        },
    ],
};
