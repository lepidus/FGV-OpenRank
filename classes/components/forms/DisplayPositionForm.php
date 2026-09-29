<?php

namespace APP\plugins\generic\rankingPlugin\classes\components\forms;

use APP\plugins\generic\rankingPlugin\classes\RankingDisplayPosition;
use PKP\components\forms\FieldOptions;
use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;

class DisplayPositionForm extends FormComponent
{
    public const FORM_DISPLAY_POSITION = 'rankingPluginDisplayPosition';

    public $id = self::FORM_DISPLAY_POSITION;
    public $method = 'PUT';

    public function __construct(string $action, array $values)
    {
        $this->action = $action;

        $this->addField(new FieldOptions(RankingDisplayPosition::SETTING_NAME, [
            'label' => __('plugins.generic.rankingPlugin.settings.displayPosition'),
            'description' => __('plugins.generic.rankingPlugin.settings.displayPosition.description'),
            'type' => 'radio',
            'options' => self::getPositionOptions(),
            'value' => $values[RankingDisplayPosition::SETTING_NAME],
        ]))
            ->addField(new FieldText(RankingDisplayPosition::SECTION_SETTING_NAME, [
                'label' => __('plugins.generic.rankingPlugin.settings.displayPositionSection'),
                'description' => __('plugins.generic.rankingPlugin.settings.displayPositionSection.description'),
                'inputType' => 'number',
                'size' => 'small',
                'showWhen' => [RankingDisplayPosition::SETTING_NAME, RankingDisplayPosition::AFTER_SECTION],
                'value' => $values[RankingDisplayPosition::SECTION_SETTING_NAME],
            ]));
    }

    public static function getPositionOptions(): array
    {
        return array_map(
            fn (string $position) => [
                'value' => $position,
                'label' => __("plugins.generic.rankingPlugin.settings.displayPosition.{$position}"),
            ],
            RankingDisplayPosition::getAll()
        );
    }
}
