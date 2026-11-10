<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** BRD PAY-01/PAY-05: one or more payment legs (a split paid at once, or one partial-amount leg) against an open checkout. */
final class CollectCheckoutPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', Rule::in(['cash', 'pos_terminal', 'bank_transfer', 'in_app'])],
            'payments.*.amount_minor' => ['required', 'integer', 'min:1'],
            'payments.*.reference' => ['nullable', 'string', 'max:255'],
            'existing_payment_transaction_id' => ['nullable', 'integer'],
        ];
    }

    /** Each leg's own reference requirement depends on ITS OWN method (not any sibling leg's) — a wildcard rule can't express that, so it's checked by hand instead. */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->array('payments') as $index => $leg) {
                if (($leg['method'] ?? null) !== 'cash' && empty($leg['reference'])) {
                    $validator->errors()->add("payments.{$index}.reference", 'A reference is required for this payment method.');
                }
            }
        });
    }

    /** @return array<int, array{method: string, amount_minor: int, reference: ?string}> */
    public function payments(): array
    {
        return array_map(static fn (array $leg): array => [
            'method' => $leg['method'],
            'amount_minor' => (int) $leg['amount_minor'],
            'reference' => $leg['reference'] ?? null,
        ], $this->array('payments'));
    }
}
