<?php

namespace Svi\TengineBundle;

use Svi\TengineBundle\Service\TemplateService;

trait BundleTrait
{

    /**
     * @return TemplateService
     */
    public function getTemplateService()
    {
        return $this->app[TemplateService::class];
    }

}