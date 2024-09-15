<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo\Manager;

use Modules\Seo\SeoTrait;

class SeoModel {

    use SeoTrait;

    protected array $schema = [];

    public function getMeta(){
        // comment explaining why the method is empty
    }

    /**
     * @return string
     */
    public function getSchema(): string {
        $scriptSchema = "";
        foreach($this->schema as $schema){
            $scriptSchema.=$schema."\n";
        }
        return $scriptSchema;
    }

    public function getMedia(){
        // comment explaining why the method is empty
    }

    /**
     * @param string $type
     * @param string $schema
     * @return void
     */
    public function setSchema(string $type, string $schema): void {
        $this->schema[$type] = $schema;
    }

}
