<?php

namespace APP\plugins\generic\rankingPlugin\tests\helpers;

use APP\plugins\generic\rankingPlugin\classes\HookCallback;

class HookCallbackForTests extends HookCallback
{
    private $contextId;

    public function __construct($plugin, $contextId)
    {
        parent::__construct($plugin);
        $this->contextId = $contextId;
    }

    protected function getContextId()
    {
        return $this->contextId;
    }
}
