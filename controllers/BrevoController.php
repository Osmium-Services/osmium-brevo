<?php

declare(strict_types=1);

namespace Osmium\Services\Brevo\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\Brevo\Models\BrevoConfig;

/**
 * Brevo settings controller - full-page form POST/redirect, same shape as
 * TurnstileController.
 *
 * Routes:
 *   - index() → /admin/settings/brevo/  (GET shows the form, POST saves it)
 */
class BrevoController extends AdminController
{
    private const CONFIG_FILE_PATH = 'app/config/services/brevo.json.php';

    public function index(): void
    {
        $isPost = $this->isPost();
        if ($isPost) $this->handleSubmit();

        $this->data['admin']['config']['brevo'] = (array) BrevoConfig::get();
        $this->data['admin']['settingsSaved'] = $_SESSION['brevo_settings_saved'] ?? false;
        $this->data['admin']['settingsError'] = $_SESSION['brevo_settings_error'] ?? false;
        unset($_SESSION['brevo_settings_saved'], $_SESSION['brevo_settings_error']);

        $this->setView('brevo/index.phtml');
    }

    private function handleSubmit(): void
    {
        $csrfValid = $this->admin->auth->validateCsrf();
        if (!$csrfValid) {
            $_SESSION['brevo_settings_error'] = 'Invalid form submission. Please try again.';
            $this->redirect('settings/brevo/');
        }

        $postedApiKey = \trim($_POST['api_key'] ?? '');
        $apiKey = $postedApiKey === '' ? (string) (BrevoConfig::get()->apiKey ?? '') : $postedApiKey; // Blank keeps the stored secret

        $newsletterEnabled = !empty($_POST['newsletter_enabled']);
        $listId = \max(0, (int) ($_POST['list_id'] ?? 0));
        $doiTemplateId = \max(0, (int) ($_POST['doi_template_id'] ?? 0));
        $doiRedirectUrl = \trim((string) ($_POST['doi_redirect_url'] ?? ''));

        $this->saveConfig(apiKey: $apiKey, newsletterEnabled: $newsletterEnabled, listId: $listId, doiTemplateId: $doiTemplateId, doiRedirectUrl: $doiRedirectUrl);

        $this->admin->model->changelog->log(
            description: 'Updated Brevo settings',
            recordType: 'settings',
        );

        BrevoConfig::clearCache();

        $_SESSION['brevo_settings_saved'] = true;
        $this->redirect('settings/brevo/');
    }

    private function saveConfig(string $apiKey, bool $newsletterEnabled, int $listId, int $doiTemplateId, string $doiRedirectUrl): void
    {
        $configExists = \file_exists(self::CONFIG_FILE_PATH);
        if (!$configExists) $this->ensureConfigDirectoryExists();

        $newJson = \json_encode(
            value: ['brevo' => ['apiKey' => $apiKey, 'newsletterEnabled' => $newsletterEnabled, 'listId' => $listId, 'doiTemplateId' => $doiTemplateId, 'doiRedirectUrl' => $doiRedirectUrl]],
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, "<?php exit(); ?>\n" . $newJson . "\n");
    }

    private function ensureConfigDirectoryExists(): void
    {
        $dir = \dirname(self::CONFIG_FILE_PATH);
        $alreadyExists = \is_dir($dir);
        if (!$alreadyExists) \mkdir(directory: $dir, permissions: 0755, recursive: true);
    }

    private function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
}
