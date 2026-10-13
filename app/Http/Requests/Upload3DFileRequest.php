<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class Upload3DFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:102400', // 100MB
                'mimes:stl,3mf,zip,bin,txt', // Common extensions/mimes for STL and 3MF
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => '請選擇要上傳的 3D 模型檔案',
            'file.file' => '上傳內容必須為有效檔案',
            'file.max' => '檔案大小不能超過 100MB',
            'file.mimes' => '只支援 STL 或 3MF 格式的 3D 檔案',
        ];
    }
}
