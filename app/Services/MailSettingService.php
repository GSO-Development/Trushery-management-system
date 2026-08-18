<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\Stream\SocketStream;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

class MailSettingService
{
    /**
     * Dynamically apply stored database mail settings to Laravel config and extend the SMTP transport.
     */
    public static function applyConfig(): void
    {
        $settings = Setting::getMailSettings();

        $host       = $settings['mail_host'] ?? 'smtp.gmail.com';
        $port       = (int) ($settings['mail_port'] ?? 587);
        $encryption = $settings['mail_encryption'] ?? 'tls';
        $username   = $settings['mail_username'] ?? 'georgesteuartit@gmail.com';
        $password   = $settings['mail_password'] ?? '';
        $fromEmail  = $settings['mail_from_address'] ?? 'georgesteuartit@gmail.com';
        $fromName   = $settings['mail_from_name'] ?? 'George Steuart Treasury';

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.transport', 'smtp');
        Config::set('mail.mailers.smtp.host', $host);
        Config::set('mail.mailers.smtp.port', $port);
        Config::set('mail.mailers.smtp.encryption', $encryption === 'none' ? null : $encryption);
        Config::set('mail.mailers.smtp.username', $username);
        Config::set('mail.mailers.smtp.password', $password);
        Config::set('mail.from.address', $fromEmail);
        Config::set('mail.from.name', $fromName);

        // Extend the smtp mailer driver with SSL stream verification options
        Mail::extend('smtp', function (array $config = []) use ($host, $port, $encryption, $username, $password) {
            $tls = ($encryption === 'ssl' || $port === 465);

            $transport = new EsmtpTransport($host, $port, $tls);
            $transport->setUsername($username);
            $transport->setPassword($password);

            $stream = $transport->getStream();
            if ($stream instanceof SocketStream) {
                $stream->setStreamOptions([
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ]);
            }

            return $transport;
        });

        // Reset the mail manager singleton instance so changes take effect immediately
        app()->forgetInstance('mail.manager');
    }

    /**
     * Send a test email using specific or stored mail settings with SSL bypass and clean error handling.
     */
    public static function sendTestMail(
        string $to,
        string $subject,
        string $body,
        ?array $overrideSettings = null
    ): array {
        try {
            $settings = $overrideSettings ?: Setting::getMailSettings();

            $host       = $settings['mail_host'] ?? 'smtp.gmail.com';
            $port       = (int) ($settings['mail_port'] ?? 587);
            $encryption = $settings['mail_encryption'] ?? 'tls';
            $username   = $settings['mail_username'] ?? 'georgesteuartit@gmail.com';
            $password   = $settings['mail_password'] ?? '';
            $fromEmail  = $settings['mail_from_address'] ?? 'georgesteuartit@gmail.com';
            $fromName   = $settings['mail_from_name'] ?? 'George Steuart Treasury';

            $tls = ($encryption === 'ssl' || $port === 465);

            // Create direct transport with SSL verification bypass
            $transport = new EsmtpTransport($host, $port, $tls);
            $transport->setUsername($username);
            $transport->setPassword($password);

            $stream = $transport->getStream();
            if ($stream instanceof SocketStream) {
                $stream->setStreamOptions([
                    'ssl' => [
                        'allow_self_signed' => true,
                        'verify_peer'       => false,
                        'verify_peer_name'  => false,
                    ],
                ]);
            }

            $symfonyMailer = new SymfonyMailer($transport);

            $email = (new Email())
                ->from(new Address($fromEmail, $fromName))
                ->to($to)
                ->subject($subject)
                ->text($body);

            $symfonyMailer->send($email);

            return [
                'success' => true,
                'message' => "Test email successfully delivered to {$to} via {$host}:{$port}!",
            ];
        } catch (\Throwable $e) {
            $rawMsg = $e->getMessage();

            // Provide clear, actionable feedback if authentication fails (e.g. Gmail 535)
            if (str_contains($rawMsg, '535') || str_contains($rawMsg, 'BadCredentials') || str_contains($rawMsg, 'Username and Password not accepted')) {
                return [
                    'success' => false,
                    'message' => "Gmail Authentication Failed (Error 535): Google does not accept standard passwords for SMTP. Please generate a 16-character Google App Password (myaccount.google.com/apppasswords) and enter it in the SMTP Password field.",
                ];
            }

            return [
                'success' => false,
                'message' => "SMTP Connection Error: " . $rawMsg,
            ];
        }
    }
}
