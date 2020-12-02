<?php

namespace Osiset\ShopifyApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;
use Illuminate\Validation\Validator;
use function Osiset\ShopifyApp\createHmac;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Handles validating a usage charge.
 */
class StoreUsageCharge extends FormRequest
{
    use ConfigAccessible;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Configure the validator instance.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        // Determine if the HMAC is correct
        $validator->after(function (Validator $validator) {
            // Get the input values needed
            $data = [
                'price'       => $this->request->get('price'),
                'description' => $this->request->get('description'),
                'signature'   => $this->request->get('signature'),
            ];
            if ($this->request->has('redirect')) {
                $data['redirect'] = $this->request->get('redirect');
            }

            $signature = $data['signature'];
            unset($data['signature']);

            // Confirm the charge hasn't been tampered with
            $signatureLocal = createHmac(
                [
                    'data'       => $data,
                    'buildQuery' => true,
                ],
                $this->getConfig('api_secret')
            );
            if (! hash_equals($signature, $signatureLocal)) {
                // Possible tampering
                $validator->errors()->add('signature', 'Signature does not match.');
            }
        });
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'signature'   => 'required|string',
            'price'       => 'required|numeric',
            'description' => 'required|string',
            'redirect'    => 'nullable|string',
        ];
    }
}
