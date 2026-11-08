export type WizardStepDefinition = {
    key: string;
    label: string;
    description: string;
    review: boolean;
};

export type WizardDraft = {
    uuid: string;
    wizard_type: string;
    title: string;
    description: string;
    current_step: string;
    data: Record<string, unknown>;
    completed_steps: string[];
    version: number;
    status: string;
    expires_at: string | null;
    context_type: string | null;
    context_id: number | null;
    steps: WizardStepDefinition[];
};

export type WizardErrors = Record<string, string[] | string>;
