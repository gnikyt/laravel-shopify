<?php

namespace Osiset\ShopifyApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Osiset\ShopifyApp\Objects\Values\Hmac;
use Osiset\ShopifyApp\Util;

/**
 * Handles validating a usage charge.
 */
class StoreUsageCharge extends FormRequest
{
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
                'price' => $this->request->get('price'),
                'description' => $this->request->get('description'),
                'signature' => $this->request->get('signature'),
            ];
            if ($this->request->has('redirect')) {
                $data['redirect'] = $this->request->get('redirect');
            }

            $signature = Hmac::fromNative($data['signature']);
            unset($data['signature']);

            // Confirm the charge hasn't been tampered with
            $signatureLocal = Util::createHmac(
                [
                    'data' => $data,
                    'buildQuery' => true,
                ],
                Util::getShopifyConfig('api_secret')
            );
            if (! $signature->isSame($signatureLocal)) {
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
            'signature' => 'required|string',
            'price' => 'required|numeric',
            'description' => 'required|string',
            'redirect' => 'nullable|string',
        ];
    }
}
