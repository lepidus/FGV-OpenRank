<?php

namespace APP\plugins\generic\fgvOpenRank\classes\components\forms;

use PKP\components\forms\FieldText;
use PKP\components\forms\FormComponent;

class TrendingDoiForm extends FormComponent
{
    public const FORM_TRENDING_DOI = 'fgvOpenRankTrendingDoi';

    public $id = self::FORM_TRENDING_DOI;
    public $method = 'POST';

    public function __construct(string $action)
    {
        $this->action = $action;

        $this->addField(new FieldText('doi', [
            'label' => __('plugins.generic.fgvOpenRank.trendingDois.doi'),
            'description' => __('plugins.generic.fgvOpenRank.trendingDois.invalidDoi'),
            'isRequired' => true,
            'value' => '',
        ]));
    }
}
