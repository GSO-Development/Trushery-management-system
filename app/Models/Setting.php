<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'group',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    /**
     * Get a setting by key, auto-decrypting if encrypted.
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        if (!$setting) {
            return $default;
        }

        if ($setting->is_encrypted && !empty($setting->value)) {
            try {
                return Crypt::decryptString($setting->value);
            } catch (\Exception $e) {
                return $setting->value;
            }
        }

        return $setting->value ?? $default;
    }

    /**
     * Set a setting value, auto-encrypting if specified.
     */
    public static function set(string $key, $value, bool $isEncrypted = false, string $group = 'general'): self
    {
        $storedValue = $value;
        if ($isEncrypted && !empty($value)) {
            $storedValue = Crypt::encryptString((string) $value);
        }

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value'        => $storedValue,
                'group'        => $group,
                'is_encrypted' => $isEncrypted,
            ]
        );
    }

    /**
     * Retrieve all mail settings as an array.
     */
    public static function getMailSettings(): array
    {
        return [
            'mail_mailer'       => static::get('mail_mailer', 'smtp'),
            'mail_host'         => static::get('mail_host', 'smtp.gmail.com'),
            'mail_port'         => (int) static::get('mail_port', 587),
            'mail_encryption'   => static::get('mail_encryption', 'tls'),
            'mail_username'     => static::get('mail_username', 'georgesteuartit@gmail.com'),
            'mail_password'     => static::get('mail_password', 'Geo@2026'),
            'mail_from_address' => static::get('mail_from_address', 'georgesteuartit@gmail.com'),
            'mail_from_name'    => static::get('mail_from_name', 'George Steuart Treasury'),
        ];
    }
}
