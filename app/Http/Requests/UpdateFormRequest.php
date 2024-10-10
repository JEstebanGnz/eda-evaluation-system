<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'String',
            'assessment_period_id' => 'Integer',
            'dependencies' => 'Array|min:1',
            'dependency_role' => [Rule::in([null, 'jefe', 'par', 'autoevaluación', 'cliente interno', 'cliente externo'])],
        ];
    }
}
