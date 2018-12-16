<?php

namespace OhMyBrew\ShopifyApp\Requests;

use Illuminate\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

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
    public function authorize()
    {
        return true;
    }

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
            $data = $this->request->only(['price', 'description', 'redirect', 'signature']);
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
