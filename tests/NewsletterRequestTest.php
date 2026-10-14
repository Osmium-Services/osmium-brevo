<?php

declare(strict_types=1);

namespace Tests\Services\Brevo;

use Osmium\Services\Brevo\Models\NewsletterRequest;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../models/NewsletterRequest.php';

/**
 * A newsletter signup as the form sends it.
 */
final class NewsletterRequestTest extends TestCase
{
    public function test_a_valid_email_with_consent_is_accepted(): void
    {
        $this->assertSame(['status' => 'ok', 'email' => 'sam@example.com'], NewsletterRequest::parse(['email' => ' sam@example.com ', 'consent' => '1']));
    }

    public function test_a_bad_or_missing_email_is_invalid(): void
    {
        $this->assertSame(['status' => 'invalid'], NewsletterRequest::parse(['email' => 'not-an-email', 'consent' => '1']));
        $this->assertSame(['status' => 'invalid'], NewsletterRequest::parse(['consent' => '1']));
    }

    public function test_an_unticked_consent_box_is_refused(): void
    {
        foreach ([null, '', '0'] as $consent) {
            $this->assertSame(['status' => 'no_consent'], NewsletterRequest::parse(['email' => 'sam@example.com', 'consent' => $consent]), (string) $consent);
        }
    }
}
