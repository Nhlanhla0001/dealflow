import { ShieldCheck } from 'lucide-react';
import { firstError, type StepProps } from './types';

export function NegotiationStep({ data, errors, updateField }: StepProps) {
    return (
        <div className="space-y-6">
            <label
                htmlFor="negotiation_enabled"
                className="flex cursor-pointer items-start gap-3 rounded-xl border border-border p-4 transition-colors hover:bg-muted/40"
            >
                <input
                    id="negotiation_enabled"
                    type="checkbox"
                    checked={Boolean(data.negotiation_enabled)}
                    onChange={(event) =>
                        updateField('negotiation_enabled', event.target.checked)
                    }
                    className="mt-0.5 size-4 rounded border-input accent-emerald-700"
                />
                <span>
                    <span className="block text-sm font-medium">
                        Allow buyers to negotiate
                    </span>
                    <span className="mt-1 block text-sm leading-6 text-muted-foreground">
                        Buyers can propose different terms. The final agreement
                        still requires your explicit approval, and your private
                        limits stay private.
                    </span>
                </span>
            </label>

            {firstError(errors, 'negotiation_enabled') && (
                <p className="text-sm text-destructive">
                    {firstError(errors, 'negotiation_enabled')}
                </p>
            )}

            <div className="flex items-start gap-3 rounded-xl border border-border bg-muted/40 p-4">
                <ShieldCheck
                    className="mt-0.5 size-4 shrink-0 text-emerald-700 dark:text-emerald-400"
                    aria-hidden
                />
                <div className="text-sm leading-6 text-muted-foreground">
                    <p className="font-medium text-foreground">
                        Negotiation is bounded by your rules.
                    </p>
                    <p className="mt-1">
                        Your asking price and delivery window are public. Any
                        private floor or agent budget is configured separately
                        and is never shown to the buyer.
                    </p>
                </div>
            </div>
        </div>
    );
}
