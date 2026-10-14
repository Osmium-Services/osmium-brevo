<?php

declare(strict_types=1);

namespace Tests\Services\Brevo;

use Osmium\Services\Brevo\Models\BrevoContacts;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/BrevoContacts.php';

/**
 * The contacts request body.
 */
final class BrevoContactsTest extends TestCase
{
    public function test_the_payload_adds_the_email_to_one_list_and_updates_repeats(): void
    {
        $this->assertSame(
            ['email' => 'sam@example.com', 'listIds' => [7], 'updateEnabled' => true],
            BrevoContacts::payload(email: 'sam@example.com', listId: 7),
        );
    }

    public function test_the_double_opt_in_payload_names_the_template_list_and_landing_page(): void
    {
        $this->assertSame(
            ['email' => 'sam@example.com', 'includeListIds' => [7], 'templateId' => 3, 'redirectionUrl' => 'https://example.com/ok/'],
            BrevoContacts::doubleOptInPayload(email: 'sam@example.com', listId: 7, templateId: 3, redirectUrl: 'https://example.com/ok/'),
        );
    }
}
