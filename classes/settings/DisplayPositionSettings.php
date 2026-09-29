<?php

namespace APP\plugins\generic\rankingPlugin\classes\settings;

use APP\plugins\generic\rankingPlugin\classes\RankingDisplayPosition;

class DisplayPositionSettings
{
    public function __construct(
        private $plugin,
        private int $contextId
    ) {
    }

    public function get(): array
    {
        return [
            RankingDisplayPosition::SETTING_NAME => RankingDisplayPosition::normalize(
                $this->plugin->getSetting($this->contextId, RankingDisplayPosition::SETTING_NAME)
            ),
            RankingDisplayPosition::SECTION_SETTING_NAME => RankingDisplayPosition::normalizeSection(
                $this->plugin->getSetting($this->contextId, RankingDisplayPosition::SECTION_SETTING_NAME)
            ),
        ];
    }

    public function save(array $input): void
    {
        $this->plugin->updateSetting(
            $this->contextId,
            RankingDisplayPosition::SETTING_NAME,
            RankingDisplayPosition::normalize($input[RankingDisplayPosition::SETTING_NAME] ?? null),
            'string'
        );
        $this->plugin->updateSetting(
            $this->contextId,
            RankingDisplayPosition::SECTION_SETTING_NAME,
            RankingDisplayPosition::normalizeSection($input[RankingDisplayPosition::SECTION_SETTING_NAME] ?? null),
            'int'
        );
    }
}
