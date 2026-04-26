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
                $table->string("path")->index();
                $table->string("title")->nullable();
                $table->mediumText("description")->nullable();
                $table->string("canonical")->nullable();
                $table->string("image")->nullable();
                $table->string("video")->nullable();
                $table->string("keywords")->nullable();
                $table->dateTime("created_at");
                $table->dateTime("updated_at");
            });
        }
    }

    /**
     * @return void
     */
    public function update(): void {
        if ($this->schema()->hasTable("seo")) {
            $this->schema()->table("seo", function(Blueprint $table) {
                if (!$this->schema()->hasIndex('seo', 'seo_path_index')) {
                    $table->index("path");
                }
                foreach (['title', 'canonical', 'image', 'video', 'keywords'] as $col) {
                    if ($this->schema()->hasColumn('seo', $col)) {
                        $table->string($col)->nullable()->change();
                    }
                }
                if ($this->schema()->hasColumn('seo', 'description')) {
                    $table->mediumText("description")->nullable()->change();
                }
            });
        }
    }

    /**
     * @return void
     */
    public function delete(): void {
        // comment explaining why the method is empty
    }

}
