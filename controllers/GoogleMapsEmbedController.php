<?php

declare(strict_types=1);

namespace Osmium\Services\GoogleMapsEmbed\Controllers;

use Osmium\Modules\Admin\Core\AdminController;
use Osmium\Services\GoogleMapsEmbed\Models\GoogleMapsEmbedConfig;

/**
 * Google Maps Embed settings controller - a full-page form POST/redirect flow,
 * matching the Meta Pixel service.
 *
 * Routes:
 *   - index() → /admin/settings/google-maps-embed/  (GET shows the form, POST saves it)
 */
class GoogleMapsEmbedController extends AdminController
{
    private const CONFIG_FILE_PATH = 'app/config/services/google-maps-embed.json.php';
    private const DEFAULT_CONFIG = <<<'JSON'
        <?php exit(); ?>
        {
            "googleMapsEmbed": {
                "embedUrl": ""
            }
        }
        JSON;

    public function index(): void
    {
        $isPost = $this->isPost();
        if ($isPost) $this->handleSubmit();

        $this->data['admin']['config']['googleMapsEmbed'] = (array) GoogleMapsEmbedConfig::get();
        $this->data['admin']['settingsSaved'] = $_SESSION['google_maps_embed_settings_saved'] ?? false;
        $this->data['admin']['settingsError'] = $_SESSION['google_maps_embed_settings_error'] ?? false;
        unset($_SESSION['google_maps_embed_settings_saved'], $_SESSION['google_maps_embed_settings_error']);

        $this->setView('google-maps-embed/index.phtml');
    }

    private function handleSubmit(): void
    {
        $csrfValid = $this->admin->auth->validateCsrf();
        if (!$csrfValid) {
            $_SESSION['google_maps_embed_settings_error'] = 'Invalid form submission. Please try again.';
            $this->redirect('settings/google-maps-embed/');
        }

        $embedUrl = \trim($_POST['embed_url'] ?? '');

        $urlValid = GoogleMapsEmbedConfig::isValidEmbedUrl($embedUrl);
        $urlGiven = $embedUrl !== '';
        if ($urlGiven && !$urlValid) {
            $_SESSION['google_maps_embed_settings_error'] = 'The URL must start with https://www.google.com/maps/embed - copy only the src value from the Google Maps embed code.';
            $this->redirect('settings/google-maps-embed/');
        }

        $this->saveConfig($embedUrl);

        $this->admin->model->changelog->log(
            description: 'Updated Google Maps Embed settings',
            recordType: 'settings',
        );

        GoogleMapsEmbedConfig::clearCache();

        $_SESSION['google_maps_embed_settings_saved'] = true;
        $this->redirect('settings/google-maps-embed/');
    }

    private function saveConfig(string $embedUrl): void
    {
        $configExists = \file_exists(self::CONFIG_FILE_PATH);
        if (!$configExists) $this->ensureConfigDirectoryExists();

        $content = $configExists ? \file_get_contents(self::CONFIG_FILE_PATH) : self::DEFAULT_CONFIG;

        $jsonStart = \strpos(haystack: $content, needle: '{');
        $phpHeader = \substr(string: $content, offset: 0, length: $jsonStart);
        $data = \json_decode(\substr(string: $content, offset: $jsonStart), associative: true) ?? [];

        $data['googleMapsEmbed'] = ['embedUrl' => $embedUrl];

        $newJson = \json_encode(
            value: $data,
            flags: JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        \file_put_contents(self::CONFIG_FILE_PATH, $phpHeader . $newJson . "\n");
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
