<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo\Db;

use DI\DependencyException;
use DI\NotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Modules\Database\Migration;

class Schema extends Migration{

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function create(): void {
        if (!$this->schema()->hasTable("seo")){
            $this->schema()->create("seo", function(Blueprint $table){
                $table->engine = "InnoDB";
                $table->increments("id");
                $table->string("path");
                $table->string("title");
                $table->mediumText("description");
                $table->string("canonical");
                $table->string("image");
                $table->string("video");
                $table->string("keywords");
                $table->dateTime("created_at");
                $table->dateTime("updated_at");
            });
        }
    }

    /**
     * @return void
     */
    public function update(): void {
        // comment explaining why the method is empty
    }

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function delete(): void {
        if ($this->schema()->hasTable("seo")) {
            $this->schema()->drop("seo");
        }
    }

}
