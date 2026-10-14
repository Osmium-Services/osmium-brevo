<?php

declare(strict_types=1);

namespace Osmium\Services\Brevo\Models;

/**
 * Adds a contact to a Brevo list through the contacts API (JSON POST with an api-key header).
 * Plain curl, like BrevoMailer.
 */
class BrevoContacts
{
    private const CONTACTS_URL = 'https://api.brevo.com/v3/contacts';
    private const DOUBLE_OPT_IN_URL = 'https://api.brevo.com/v3/contacts/doubleOptinConfirmation';

    /**
     * The request body. updateEnabled makes a repeat signup update the existing contact instead of failing.
     *
     * @return array{email: string, listIds: int[], updateEnabled: bool}
     */
    public static function payload(string $email, int $listId): array
    {
        return ['email' => $email, 'listIds' => [$listId], 'updateEnabled' => true];
    }

    /**
     * The double opt-in request body: Brevo emails the template, and lists the contact only once they click.
     *
     * @return array{email: string, includeListIds: int[], templateId: int, redirectionUrl: string}
     */
    public static function doubleOptInPayload(string $email, int $listId, int $templateId, string $redirectUrl): array
    {
        return ['email' => $email, 'includeListIds' => [$listId], 'templateId' => $templateId, 'redirectionUrl' => $redirectUrl];
    }

    /**
     * Lists the contact straight away, or - when double opt-in is set up - sends the confirmation email instead.
     *
     * @return array{success: bool, error?: string}
     */
    public static function addToList(string $email, int $listId): array
    {
        try {
            $config = BrevoConfig::get();
            $confirmFirst = BrevoConfig::isDoubleOptIn();
            $url = $confirmFirst ? self::DOUBLE_OPT_IN_URL : self::CONTACTS_URL;
            $payload = $confirmFirst
                ? self::doubleOptInPayload(email: $email, listId: $listId, templateId: (int) $config->doiTemplateId, redirectUrl: $config->doiRedirectUrl)
                : self::payload(email: $email, listId: $listId);
            self::post(url: $url, payload: $payload);
            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private static function post(string $url, array $payload): void
    {
        $notReady = !BrevoConfig::isNewsletterReady();
        if ($notReady) throw new \RuntimeException('Brevo newsletter is not configured: set the API key and list ID on the Brevo settings page.');

        $json = \json_encode($payload);

        $ch = \curl_init($url);
        \curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'api-key: ' . BrevoConfig::get()->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ]);
        $response = \curl_exec($ch);
        $curlError = \curl_error($ch);
        $httpCode = \curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($curlError) throw new \RuntimeException("Brevo contacts curl error: {$curlError}");

        $failed = $httpCode < 200 || $httpCode >= 300;
        if ($failed) {
            $decoded = \json_decode(json: (string) $response, associative: true);
            $error = $decoded['message'] ?? ($response ?: "HTTP {$httpCode}");
            throw new \RuntimeException("Brevo contacts failed ({$httpCode}): {$error}");
        }
    }
}
