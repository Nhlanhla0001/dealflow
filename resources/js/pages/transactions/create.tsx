import { Form, Head, Link } from '@inertiajs/react';
import { ArrowLeft, Sparkles } from 'lucide-react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { dashboard } from '@/routes';
import {
    index as transactionsIndex,
    store as transactionsStore,
} from '@/routes/transactions';
import type { DealParty } from '@/types';

type Prefill = {
    service_id: number;
    seller_id: number;
    title: string;
    description: string;
    budget: number;
    delivery_days: number;
};

export default function TransactionsCreate({
    sellers,
    prefill = null,
}: {
    sellers: DealParty[];
    prefill?: Prefill | null;
}) {
    return (
        <>
            <Head title="New transaction" />

            <div className="flex flex-1 flex-col gap-6 p-4">
                <div>
                    <p className="text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase">
                        DealFlow AI
                    </p>
                    <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                        New transaction
                    </h1>
                    <p className="mt-1 max-w-2xl text-sm text-muted-foreground">
                        Describe what you need and set the maximum you are
                        willing to pay. The agent will never negotiate past that
                        limit.
                    </p>
                </div>

                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>Buyer request</CardTitle>
                        <CardDescription>
                            The seller will respond with an offer, then both
                            agents negotiate inside the limits you set.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {prefill && (
                            <div className="mb-6 rounded-lg border border-border bg-muted/40 p-4">
                                <p className="text-xs font-medium text-muted-foreground">
                                    Requesting a published service
                                </p>
                                <p className="mt-1 text-sm font-medium">
                                    {prefill.title}
                                </p>
                                <p className="mt-1 text-xs text-muted-foreground">
                                    The listing details are prefilled. Adjust
                                    the scope and budget before creating the
                                    transaction.
                                </p>
                            </div>
                        )}

                        <Form
                            {...transactionsStore.form()}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {prefill && (
                                        <input
                                            type="hidden"
                                            name="service_id"
                                            value={prefill.service_id}
                                        />
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="title">
                                            What do you need?
                                        </Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            defaultValue={prefill?.title}
                                            placeholder="Business website build"
                                            required
                                            autoFocus
                                            aria-invalid={Boolean(errors.title)}
                                        />
                                        <InputError message={errors.title} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            Scope and requirements
                                        </Label>
                                        <textarea
                                            id="description"
                                            name="description"
                                            defaultValue={prefill?.description}
                                            required
                                            rows={5}
                                            aria-invalid={Boolean(
                                                errors.description,
                                            )}
                                            placeholder="A seven-page marketing site with a contact form, CMS, and launch support."
                                            className="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40"
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="seller_id">
                                                Seller
                                            </Label>
                                            <select
                                                id="seller_id"
                                                name="seller_id"
                                                required
                                                defaultValue={
                                                    prefill?.seller_id ??
                                                    sellers[0]?.id ??
                                                    ''
                                                }
                                                aria-invalid={Boolean(
                                                    errors.seller_id,
                                                )}
                                                className="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            >
                                                {sellers.map((seller) => (
                                                    <option
                                                        key={seller.id}
                                                        value={seller.id}
                                                    >
                                                        {seller.name}
                                                        {seller.email
                                                            ? ` · ${seller.email}`
                                                            : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                message={errors.seller_id}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="budget">
                                                Maximum budget (USD)
                                            </Label>
                                            <Input
                                                id="budget"
                                                name="budget"
                                                type="number"
                                                min="1"
                                                step="0.01"
                                                defaultValue={prefill?.budget}
                                                placeholder="500"
                                                required
                                            />
                                            <p className="text-xs text-muted-foreground">
                                                The buyer agent cannot exceed
                                                this amount.
                                            </p>
                                            <InputError
                                                message={errors.budget}
                                            />
                                        </div>
                                    </div>

                                    <div className="grid gap-2 sm:max-w-xs">
                                        <Label htmlFor="delivery_days">
                                            Preferred delivery (days)
                                        </Label>
                                        <Input
                                            id="delivery_days"
                                            name="delivery_days"
                                            type="number"
                                            min="1"
                                            max="365"
                                            defaultValue={
                                                prefill?.delivery_days
                                            }
                                            placeholder="7"
                                            required
                                        />
                                        <InputError
                                            message={errors.delivery_days}
                                        />
                                    </div>

                                    <div className="flex flex-wrap items-center gap-3 border-t pt-4">
                                        <Button disabled={processing}>
                                            <Sparkles className="size-4" />
                                            {processing
                                                ? 'Creating…'
                                                : 'Create transaction'}
                                        </Button>

                                        <Button asChild variant="ghost">
                                            <Link href={transactionsIndex()}>
                                                <ArrowLeft className="size-4" />
                                                Cancel
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

TransactionsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
        {
            title: 'Transactions',
            href: transactionsIndex(),
        },
        {
            title: 'New transaction',
            href: '/transactions/create',
        },
    ],
};
