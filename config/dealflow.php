<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI negotiation
    |--------------------------------------------------------------------------
    |
    | The buyer and seller agents propose and counter. The deterministic
    | boundary engine still enforces the buyer budget, seller floor, and
    | delivery window. When AI is disabled or unavailable, a safe boundary
    | proposal is used and the limits still apply.
    |
    */
    'ai_negotiation' => env('DEALFLOW_AI_NEGOTIATION', true),
    'ai_model' => env('DEALFLOW_AI_MODEL'),
    'ai_cost_per_call_minor' => (int) env('DEALFLOW_AI_COST_PER_CALL_MINOR', 1),
];
