<?php

declare(strict_types=1);

namespace Osmium\Services\Brevo\Models;

/**
 * A newsletter signup as the form sends it, checked. Free of the database and of Brevo.
 */
class NewsletterRequest
{
    /**
     * @param array{email?: mixed, consent?: mixed} $input
     * @return array{status: string, email?: string} status is ok, invalid (bad email) or no_consent (box not ticked)
     */
    public static function parse(array $input): array
    {
        $email = \trim((string) ($input['email'] ?? ''));
        $emailUsable = \filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
        if (!$emailUsable) return ['status' => 'invalid'];

        $consented = !empty($input['consent']);
        if (!$consented) return ['status' => 'no_consent'];

        return ['status' => 'ok', 'email' => $email];
    }
}
