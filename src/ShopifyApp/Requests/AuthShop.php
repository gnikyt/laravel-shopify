<?php

namespace OhMyBrew\ShopifyApp\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;

/**
 * Handles validating a shop trying to authenticate.
 */
class AuthShop extends FormRequest
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
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        // Determine if the HMAC is correct
        $validator->after(function (Validator $validator) {
            if (!$this->request->has('code')) {
                // No code, continue
                return;
            }

            // Determine if the HMAC is correct
            $authHandler = new AuthShopHandler($this->request->get('shop'));
            if (!$authHandler->verifyRequest($this->request->all())) {
                $validator->errors()->add('signature', 'Not a valid signature.');
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
            'shop'      => 'required|string',
            'code'      => 'nullable|string',
            'hmac'      => 'nullable|string',
            'timestamp' => 'nullable|numeric',
            'protocol'  => 'nullable|string',
        ];
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        return $this->redirector->getUrlGenerator()->route('login');
    }
}
