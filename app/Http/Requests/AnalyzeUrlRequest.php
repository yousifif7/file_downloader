<?php

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AnalyzeUrlRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'url', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (Setting::getBool('maintenance_mode', false)) {
                $validator->errors()->add('url', Setting::getValue('maintenance_message', 'Downloads are temporarily unavailable.'));

                return;
            }

            $url = (string) $this->input('url');
            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                $validator->errors()->add('url', 'Invalid URL host.');

                return;
            }

            if ($this->isBlockedHost($host)) {
                $validator->errors()->add('url', 'This URL is not allowed.');
            }
        });
    }

    private function isBlockedHost(string $host): bool
    {
        if (in_array(strtolower($host), ['localhost', '127.0.0.1', '0.0.0.0'], true)) {
            return true;
        }

        $ips = gethostbynamel($host);

        if ($ips === false) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return true;
            }
        }

        return false;
    }
}
