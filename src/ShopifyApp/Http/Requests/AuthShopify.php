<?php

namespace Osiset\ShopifyApp\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;

/**
 * Handles validating a shop trying to authenticate.
 * TODO: Inject iApiHelper somehow.
 */
class AuthShopify extends FormRequest
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
     * TODO: Adjust to use actions.
     *
     * @param \Illuminate\Validation\Validator $validator
     *
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        // Determine if the HMAC is correct
        $validator->after(function (Validator $validator) {
            if (!$this->request->has('code')) {
                // No code, continue...
                return;
            }

            // Hacky, but not sure what else to do here...
            $apiHelper = app(IApiHelper::class);
            $apiHelper->make();

            // Determine if the HMAC is correct
            if (!$apiHelper->verifyRequest($this->request->all())) {
                $validator->errors()->add('signature', 'Not a valid signature.');
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
            'shop'      => 'required|string',
            'code'      => 'nullable|string',
            'hmac'      => 'nullable|string',
            'timestamp' => 'nullable|numeric',
            'protocol'  => 'nullable|string',
            'locale'    => 'nullable|string',
        ];
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     *
     * @codeCoverageIgnore
     */
    protected function getRedirectUrl(): string
    {
        return $this->redirector->getUrlGenerator()->route('login');
    }
}
