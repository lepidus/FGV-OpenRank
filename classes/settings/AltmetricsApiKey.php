<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\core\Application;
use APP\plugins\generic\rankingPlugin\classes\clients\Altmetrics;
use APP\plugins\generic\rankingPlugin\classes\DataEncryption;
use Exception;

class AltmetricsApiKey
{
    public const SETTING_NAME = 'altmetricsApiKey_trending';

    private $dataEncryption;
    private $altmetricsClient;

    public function __construct(
        private $plugin,
        private int $contextId,
        $dataEncryption = null,
        $altmetricsClient = null
    ) {
        $this->dataEncryption = $dataEncryption;
        $this->altmetricsClient = $altmetricsClient;
    }

    public function has(): bool
    {
        try {
            return $this->decrypt() !== null;
        } catch (Exception $e) {
            return false;
        }
    }

    public function get(): ?string
    {
        try {
            return $this->decrypt();
        } catch (Exception $e) {
            error_log(sprintf(
                '[rankingPlugin] Failed to decrypt Altmetric API key for context %s: %s',
                $this->contextId,
                $e->getMessage()
            ));
            return null;
        }
    }

    public function validate(array $input, ?string $issn): array
    {
        $apiKey = $this->getSubmittedKey($input);
        if ($apiKey === '' || $this->isRemoving($input) || empty($issn)) {
            return [];
        }

        try {
            $this->getAltmetricsClient()->fetchBestScoreSubmissions($issn, 1, $apiKey);
        } catch (Exception $error) {
            error_log($error->getMessage());
            return ['altmetricsApiKey' => [__('plugins.generic.rankingPlugin.settings.altmetricsApiKey.invalid')]];
        }

        return [];
    }

    public function save(array $input): void
    {
        if ($this->isRemoving($input)) {
            $this->plugin->updateSetting($this->contextId, self::SETTING_NAME, '', 'string');
            return;
        }

        $apiKey = $this->getSubmittedKey($input);
        if ($apiKey === '') {
            return;
        }

        $this->plugin->updateSetting(
            $this->contextId,
            self::SETTING_NAME,
            $this->getDataEncryption()->encryptString($apiKey),
            'string'
        );
    }

    private function decrypt(): ?string
    {
        $storedKey = $this->plugin->getSetting($this->contextId, self::SETTING_NAME);
        if (empty($storedKey)) {
            return null;
        }

        return $this->getDataEncryption()->decryptString($storedKey);
    }

    private function getSubmittedKey(array $input): string
    {
        return trim((string) ($input['altmetricsApiKey'] ?? ''));
    }

    private function isRemoving(array $input): bool
    {
        return filter_var($input['removeAltmetricsApiKey'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function getAltmetricsClient()
    {
        return $this->altmetricsClient ??= new Altmetrics(Application::get()->getHttpClient());
    }

    private function getDataEncryption(): DataEncryption
    {
        return $this->dataEncryption ??= new DataEncryption();
    }
}
