<?php

declare(strict_types=1);

namespace Osmium\Services\Brevo\Models;

/**
 * Adapts Brevo to core's mail.providers hook (see ServiceHooks /
 * MailerFactory).
 */
class BrevoMailProvider
{
    public const ID = 'brevo';

    /**
     * mail.providers - advertise Brevo and whether it can send now.
     */
    public static function provider(array $payload): array
    {
        return [
            'id' => self::ID,
            'label' => 'Brevo',
            'ready' => BrevoConfig::isReady(),
            'settingsRoute' => 'settings/brevo/',
            'mailer' => BrevoMailer::class,
        ];
    }
}
