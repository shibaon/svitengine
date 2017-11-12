<?php

namespace Svi\TengineBundle;

use Svi\TengineBundle\Service\TemplateService;

class Bundle extends \Svi\Service\BundlesService\Bundle
{

    protected function getServices()
    {
        return [
            TemplateService::class,
        ];
    }

}