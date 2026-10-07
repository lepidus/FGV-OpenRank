<?php

namespace APP\plugins\generic\fgvOpenRank\classes\components\forms;

use APP\plugins\generic\fgvOpenRank\classes\RankingTabs;
use PKP\components\forms\FieldHTML;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldRichTextarea;
use PKP\components\forms\FieldText;
use PKP\components\forms\FieldTextarea;
use PKP\components\forms\FormComponent;

class TabSettingsForm extends FormComponent
{
    public $method = 'PUT';

    public function __construct(string $action, array $locales, string $tabId, array $values)
    {
        $this->id = "fgvOpenRankTab-{$tabId}";
        $this->action = $action;
        $this->locales = $locales;

        $this->addField(new FieldText('customTitle', [
            'label' => __('plugins.generic.fgvOpenRank.configuration.grid.column.customTitle'),
            'isMultilingual' => true,
            'value' => $values['customTitle'],
        ]))
            ->addField(new FieldTextarea('description', [
                'label' => __('plugins.generic.fgvOpenRank.configuration.grid.column.customDescription'),
                'isMultilingual' => true,
                'size' => 'small',
                'value' => $values['description'],
            ]))
            ->addField($this->getNumberField('itemsPerTab', 'plugins.generic.fgvOpenRank.configuration.settings.itemsPerTab', $values))
            ->addField($this->getNumberField('itemsPerPage', 'plugins.generic.fgvOpenRank.configuration.settings.itemsPerPage', $values));

        if ($tabId === RankingTabs::MOST_READ) {
            $this->addField($this->getNumberField('mostReadDays', 'plugins.generic.fgvOpenRank.configuration.settings.mostReadDays', $values));
        }

        if ($tabId === RankingTabs::HIGHLIGHT) {
            $this->addField(new FieldRichTextarea('highlightContent', [
                'label' => __('plugins.generic.fgvOpenRank.configuration.settings.highlightContent'),
                'isMultilingual' => true,
                'size' => 'large',
                'toolbar' => 'bold italic superscript subscript | link | blockquote bullist numlist | image | code',
                'plugins' => ['link', 'lists', 'image', 'code'],
                'value' => $values['highlightContent'],
            ]));
        }

        if ($tabId === RankingTabs::TRENDING) {
            $this->addTrendingFields($values['hasAltmetricsApiKey']);
        }
    }

    private function getNumberField(string $name, string $labelKey, array $values): FieldText
    {
        return new FieldText($name, [
            'label' => __($labelKey),
            'inputType' => 'number',
            'size' => 'small',
            'value' => $values[$name],
        ]);
    }

    private function addTrendingFields(bool $hasApiKey): void
    {
        $this->addField(new FieldText('altmetricsApiKey', [
            'label' => __('plugins.generic.fgvOpenRank.settings.altmetricsApiKey'),
            'description' => __($hasApiKey
                ? 'plugins.generic.fgvOpenRank.settings.altmetricsApiKey.stored'
                : 'plugins.generic.fgvOpenRank.settings.altmetricsApiKey.description'),
            'inputType' => 'password',
            'value' => '',
        ]));

        if (!$hasApiKey) {
            return;
        }

        $this->addField(new FieldOptions('removeAltmetricsApiKey', [
            'options' => [
                ['value' => true, 'label' => __('plugins.generic.fgvOpenRank.settings.altmetricsApiKey.remove')],
            ],
            'value' => false,
        ]))
            ->addField(new FieldHTML('manualDoisInactive', [
                'description' => __('plugins.generic.fgvOpenRank.trendingDois.inactiveWhileApiKeySet'),
            ]));
    }
}
