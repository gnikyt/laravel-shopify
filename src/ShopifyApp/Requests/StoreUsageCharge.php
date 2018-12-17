<?php

namespace OhMyBrew\ShopifyApp\Requests;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

/**
 * Handles validating a usage charge.
 */
class StoreUsageCharge extends FormRequest
{
    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator)
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
            $signatureLocal = ShopifyApp::createHmac(['data' => $data, 'buildQuery' => true]);
            if (!hash_equals($signature, $signatureLocal)) {
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
    public function rules()
    {
        return [
            'signature'   => 'required|string',
            'price'       => 'required|numeric',
            'description' => 'required|string',
            'redirect'    => 'nullable|string',
        ];
    }
}
