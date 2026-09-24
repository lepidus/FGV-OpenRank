<?php

namespace APP\plugins\generic\rankingPlugin\classes\components\forms;

use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;

class TrendingDoiForm extends FormComponent
{
    public const FORM_TRENDING_DOI = 'rankingPluginTrendingDoi';

    public $id = self::FORM_TRENDING_DOI;
    public $method = 'POST';

    public function __construct(string $action)
    {
        $this->action = $action;

        $this->addField(new FieldText('doi', [
            'label' => __('plugins.generic.rankingPlugin.trendingDois.doi'),
            'description' => __('plugins.generic.rankingPlugin.trendingDois.invalidDoi'),
            'isRequired' => true,
            'value' => '',
        ]));
    }
}
