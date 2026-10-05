<?php

declare(strict_types=1);

namespace Osmium\Services\Brevo\Models;

use Osmium\Core\Library\MailerInterface;

/**
 * Sends mail via Brevo's transactional email API (JSON POST with an api-key
 * header). Plain curl, so there is no SDK to install.
 *
 * Built by MailerFactory with the site config, which supplies the shared
 * From address and name; the API key comes from this service's own config.
 * Brevo only sends from a verified sender, so the From address on the Email
 * settings page must be one.
 */
class BrevoMailer implements MailerInterface
{
    private const SEND_URL = 'https://api.brevo.com/v3/smtp/email';

    public function __construct(private object $config) {}

    /**
     * @param array<int, array{email: string, name?: string}> $recipients
     * @return array{success: bool, error?: string}
     */
    public function send(array $recipients, string $subject, string $htmlBody): array
    {
        try {
            $this->postMessage($recipients, $subject, $htmlBody);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @param array<int, array{email: string, name?: string}> $recipients
     */
    private function postMessage(array $recipients, string $subject, string $htmlBody): void
    {
        $notReady = !BrevoConfig::isReady();
        if ($notReady) throw new \RuntimeException('Brevo is not configured: set the API key on the Brevo settings page.');

        $email = $this->config->email;
        $sender = ['email' => $email->fromAddress];
        $fromName = $email->fromName ?? '';
        if ($fromName !== '') $sender['name'] = $fromName;

        $to = \array_map(
            function (array $recipient): array {
                $address = ['email' => $recipient['email']];
                if (isset($recipient['name'])) $address['name'] = $recipient['name'];
                return $address;
            },
            $recipients,
        );

        $ch = \curl_init(self::SEND_URL);
        \curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => \json_encode([
                'sender' => $sender,
                'to' => $to,
                'subject' => $subject,
                'htmlContent' => $htmlBody,
            ]),
            CURLOPT_HTTPHEADER => [
                'api-key: ' . BrevoConfig::get()->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = \curl_exec($ch);
        $curlError = \curl_error($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($curlError) throw new \RuntimeException("Brevo send curl error: {$curlError}");

        $failed = $httpCode < 200 || $httpCode >= 300;
        if ($failed) {
            $decoded = \json_decode(json: (string) $response, associative: true);
            $error = $decoded['message'] ?? ($response ?: "HTTP {$httpCode}");
            throw new \RuntimeException("Brevo send failed ({$httpCode}): {$error}");
        }
    }
}
