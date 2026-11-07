import { Textarea } from '@/components/ui/textarea';
import { WizardField } from '@/components/wizard/wizard-field';
import { asString, firstError, type AgentStepProps } from './types';

export function ObjectiveStep({ data, errors, updateField }: AgentStepProps) {
    return (
        <div className="space-y-5">
            <WizardField
                label="Objective"
                htmlFor="objective"
                required
                error={firstError(errors, 'objective')}
                hint="Describe the outcome the agent should optimise for. Do not include private numbers here."
            >
                <Textarea
                    id="objective"
                    value={asString(data.objective)}
                    onChange={(event) =>
                        updateField('objective', event.target.value)
                    }
                    rows={6}
                    maxLength={500}
                    placeholder="Negotiate a clear scope and a fair price while protecting my private limit."
                />
            </WizardField>

            <div className="rounded-lg border border-border bg-muted/40 p-4 text-sm leading-6 text-muted-foreground">
                The objective is guidance, not a permission slip. Budget, floor,
                turn limits, and approval rules are enforced by the server.
            </div>
        </div>
    );
}
