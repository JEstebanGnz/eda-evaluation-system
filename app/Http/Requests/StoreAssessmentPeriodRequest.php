<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentPeriodRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $user = auth()->user();
        return $user->hasRole('administrador');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|String',
            'assessment_start_date' => 'required|Date',
            'assessment_end_date' => 'required|Date',
            'commitment_start_date' => 'required|Date',
            'commitment_end_date' => 'required|Date',
        ];
    }
}
