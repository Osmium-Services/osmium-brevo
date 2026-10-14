<?php

use Osmium\Services\Brevo\Models\BrevoConfig;
use Osmium\Services\Brevo\Models\BrevoContacts;
use Osmium\Services\Brevo\Models\NewsletterRequest;

/**
 * Newsletter signup endpoint (POST /api/brevo-newsletter). Fields: email, consent (the opt-in tick box),
 * plus the form's token and the zip honeypot. The visitor is always sent back to the page they came from
 * (or the home page) with ?newsletter=subscribed (or confirm, when double opt-in is on and the visitor must click the link in their email), invalid, no_consent or failed, for the theme to show.
 * No cookie and no session notice: the result travels in that query string.
 */

$sg = $this->osmium->superGlobal;

$requestMethod = $sg->get(type: 'SERVER', name: 'REQUEST_METHOD');
$notPost = $requestMethod !== 'POST';
if ($notPost) {
    $this->osmium->header->redirect(targetURL: '/', statusCode: 302);
}

$referrer = $_SERVER['HTTP_REFERER'] ?? '/';
$path = \parse_url(url: $referrer, component: PHP_URL_PATH) ?: '/'; // Only the path: never send a visitor to another site
$back = fn(string $status) => $this->osmium->header->redirect(targetURL: $path . '?newsletter=' . $status, statusCode: 302);

$notEnabled = !BrevoConfig::isNewsletterReady();
if ($notEnabled) $back('failed');

$honeyPot = $sg->get(type: 'POST', name: 'zip');
$botDetected = !empty($honeyPot);

$submittedToken = $sg->get(type: 'POST', name: 'token');
$sessionToken = $sg->get(type: 'SESSION', name: 'token');
$tokenIsValid = !empty($submittedToken)
    && !empty($sessionToken)
    && \hash_equals(known_string: $sessionToken, user_string: $submittedToken);

$tokenIssuedAt = $sg->get(type: 'SESSION', name: 'token_issued_at');
$secondsSinceTokenIssued = $tokenIssuedAt ? (\time() - (int) $tokenIssuedAt) : 0;
$submittedTooFast = $secondsSinceTokenIssued < 4;

unset($_SESSION['token'], $_SESSION['token_issued_at']); // One token, one submission

$looksLikeABot = $botDetected || !$tokenIsValid || $submittedTooFast;
if ($looksLikeABot) $back('failed');

$request = NewsletterRequest::parse(['email' => $sg->get(type: 'POST', name: 'email'), 'consent' => $sg->get(type: 'POST', name: 'consent')]);
$refused = $request['status'] !== 'ok';
if ($refused) $back($request['status']);

$result = BrevoContacts::addToList(email: $request['email'], listId: BrevoConfig::get()->listId);
$added = $result['success'];
if (!$added) \error_log('Brevo newsletter signup failed: ' . ($result['error'] ?? 'unknown'));

$success = BrevoConfig::isDoubleOptIn() ? 'confirm' : 'subscribed';
$back($added ? $success : 'failed');
