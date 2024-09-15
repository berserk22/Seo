<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo;

use Core\Module\Provider;
use DI\DependencyException;
use DI\NotFoundException;
use Modules\Database\MigrationCollection;
use Modules\Seo\Db\Schema;
use Modules\Seo\Manager\SeoManager;
use Modules\Seo\Manager\SeoModel;
use Modules\View\PluginManager;
use Modules\View\ViewManager;

class ServiceProvider extends Provider {

    /**
     * @var array
     */
    protected array $plugins = [
        'getSeo'=>'\Modules\Seo\Plugins\GetSeo'
    ];

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function afterInit(): void {
        $container = $this->getContainer();
        if ($container->has('Modules\Database\ServiceProvider::Migration::Collection')) {
            /* @var $databaseMigration MigrationCollection  */
            $container->get('Modules\Database\ServiceProvider::Migration::Collection')->add(new Schema($this));
        }

        if ($container->has('ViewManager::View')) {
            /** @var $viewer ViewManager */
            $viewer = $container->get('ViewManager::View');
            $plugins = function(){
                $pluginManager = new PluginManager();
                $pluginManager->addPlugins($this->plugins);
                return $pluginManager->getPlugins();
            };
            $viewer->setPlugins($plugins());
        }

        if (!$container->has('Seo\Model')){
            $this->getContainer()->set('Seo\Model', function(){
                return new SeoModel($this);
            });
        }

        if (!$container->has('Seo\Manager')){
            $this->getContainer()->set('Seo\Manager', function(){
                $manager = new SeoManager($this);
                return $manager->initEntity();
            });
        }

    }
}
