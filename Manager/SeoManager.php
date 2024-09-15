<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo\Manager;

use DI\DependencyException;
use DI\NotFoundException;
use Modules\Seo\SeoTrait;

class SeoManager {

    use SeoTrait;

    /**
     * @var string
     */
    private string $seo = "Seo\Seo";

    /**
     * @return $this
     */
    public function initEntity(): static {
        if (!$this->getContainer()->has($this->seo)){
            $this->getContainer()->set($this->seo, function(){
                return 'Modules\Seo\Db\Models\Seo';
            });
        }

        return $this;
    }

    /**
     * @return string|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSeoEntity(): string|null {
        return $this->getContainer()->get($this->seo);
    }

}
