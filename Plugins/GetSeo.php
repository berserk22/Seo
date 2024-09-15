<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo\Plugins;

use DI\DependencyException;
use DI\NotFoundException;
use Modules\Product\Db\Models\Raiting;
use Modules\Router\ApcuCache;
use Modules\Seo\SeoTrait;
use Modules\View\AbstractPlugin;

class GetSeo extends AbstractPlugin{

    use SeoTrait;

    /**
     * @param array|null $seo
     * @param array|null $breadcrumbs
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function process(array $seo = null, array $breadcrumbs = null): array {
        if (is_array($seo)) {
            $this->initializePaths();
            $this->setMetaRobots($seo);
            $seoEntity = $this->fetchSeoEntity();

            $this->processTitle($seo, $seoEntity);
            $this->processDescription($seo, $seoEntity);
            $this->initializeDefaults();

            $this->processMedia($seo, $seoEntity);
            $this->processGeneralType($seo);
            $this->processSchema($seo, $breadcrumbs);

            $this->meta['test'] = $this->getSeoModel()->getSchema();
        }
        return $this->meta;
    }

    /**
     * @param array $seo
     * @return void
     */
    private function setMetaRobots(array $seo): void {
        if (isset($seo['robots'])) {
            $this->meta['robots'] = $seo['robots'];
        }
    }

    /**
     * @param array $seo
     * @param $seoEntity
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function processTitle(array &$seo, $seoEntity): void {
        if ($seoEntity !== null && !empty($seoEntity['title'])) {
            $seo['title'] = $seoEntity['title'];
        }
        if (isset($seo['title'])) {
            $this->getTitle($seo['title'], $this->getConfig('title'), $this->getConfig('delimiter'));
        }
    }

    /**
     * @param array $seo
     * @param $seoEntity
     * @return void
     */
    private function processDescription(array &$seo, $seoEntity): void {
        if ($seoEntity !== null && !empty($seoEntity['description'])) {
            $seo['description'] = $seoEntity['description'];
        }
        if (isset($seo['description'])) {
            $this->getDescription($seo['description']);
        }
    }

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function initializeDefaults(): void {
        $this->getUrl();
        $this->getFavicon();
        $this->getAppleTouch();
    }

    /**
     * @param array $seo
     * @param $seoEntity
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function processMedia(array &$seo, $seoEntity): void {
        if ($seoEntity !== null && !empty($seoEntity['image'])) {
            $seo['image'] = $seoEntity['image'];
        }
        if (!isset($seo['type'])) {
            $seo['type'] = 'article';
        }
        $this->setImageMedia($seo);
    }

    /**
     * @param array $seo
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function setImageMedia(array $seo): void {
        if (!empty($seo['image'])) {
            $this->getMedia($seo['image'], 'image', $seo['type'], $seo['title']);
        } elseif ($this->getConfig('image') !== null) {
            $this->getMedia($this->getConfig('image'), 'image', $seo['type'], $seo['title']);
        }
    }

    /**
     * @param array $seo
     * @return void
     */
    private function processGeneralType(array $seo): void {
        if (isset($seo[$seo['type']])) {
            $this->getGeneral($seo['type'], $seo[$seo['type']]);
        }
    }

    /**
     * @param array $seo
     * @param array|null $breadcrumbs
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function processSchema(array $seo, array $breadcrumbs = null): void {
        $this->getSchema($seo['type'], array_merge($this->getConfig('schema'), $seo), $breadcrumbs);
    }




    /**
     * @param string $file
     * @param string $type
     * @param string $general_type
     * @param string $title
     * @return void
     */
    protected function getMedia(string $file, string $type, string $general_type = "", string $title = ""): void {
        if (!file_exists($this->filePath . $file)) {
            return;
        }

        if ($type === 'image') {
            $this->handleImageType($file, $general_type, $title);
        } elseif ($type === 'video') {
            // Handle video case if needed in the future
        }
    }

    /**
     * @param string $type
     * @param array|null $params
     * @return void
     */
    protected function getGeneral(string $type, array $params = null): void {
        $meta = '';

        // Handle general meta tags
        $meta .= $this->generateGeneralMetaTags($type);

        // Handle type-specific meta tags
        if (!empty($type) && is_array($params) && !empty($params)) {
            $meta .= $this->generateTypeSpecificMetaTags($type, $params);
        }

        $this->meta['title'] .= $meta;
    }



    /**
     * @param string $type
     * @param string $key
     * @param string $content
     * @return string
     */
    public function getTwitterCardContent(string $type, string $key, string $content): string {
        if ($type === 'product' && $key === 'twitter:card') {
            return 'summary';
        }

        return $content;
    }

    /**
     * @param string $type
     * @param string $param
     * @return string
     */
    public function getTypeMetaKey(string $type, string $param): string {
        return $type === 'place' ? 'og:' . $param : $type . ':' . $param;
    }



    /**
     * @param string|null $type
     * @param array|null $schema_setting
     * @param array|null $breadcrumbs
     * @return void
     */
    protected function getSchema(string $type = null, array $schema_setting = null, array $breadcrumbs = null): void {
        $schema_setting['breadcrumbs'] = $breadcrumbs;
        if (empty($type)){
            $type = 'standard';
        }
        $dir = realpath(__DIR__.DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."config".DIRECTORY_SEPARATOR."schema".DIRECTORY_SEPARATOR;
        $schema = '';
        if (isset($this->schema_list[$type])){
            $tmp_schema = '';
            foreach ($this->schema_list[$type] as $file){
                if (file_exists($dir.$file)){
                    $content = json_decode(file_get_contents($dir.$file));
                    $category = str_replace('.json', '', $file);
                    $tmp_schema.=$this->$category($content, $schema_setting);
                }
            }
            $schema .= $tmp_schema;
        }
        $this->meta['schema'] = $schema;
    }

    /**
     * @param object $content
     * @param array $schema_setting
     * @return string
     */
    private function organization(object $content, array $schema_setting): string {
        $tmp_schema = "";
        $content->name = $schema_setting['organization']['name'];
        $content->alternateName = $schema_setting['organization']['alternateName'];
        $content->url = $schema_setting['organization']['url'];
        $content->logo = $schema_setting['organization']['logo'];
        if (isset($schema_setting['contact'])){
            $content->contactPoint->telephone = $schema_setting['contact']['telephone'];
            $content->contactPoint->contactType = $schema_setting['contact']['contactType'];
        }
        else {
            unset($content->{"contactPoint"});
        }
        if (isset($schema_setting['social'])){
            $links = [];
            foreach ($schema_setting['social'] as $link){
                $links[] = $link;
            }
            $content->sameAs = $links;
        }
        else {
            unset($content->{"sameAs"});
        }
        $tmp_schema === ''?$tmp_schema = $this->scriptTagStart.json_encode($content).$this->scriptTagEnd:$tmp_schema.=$this->scriptTagStart.json_encode($content).$this->scriptTagEnd;

        return $tmp_schema;
    }

    /**
     * @param object $content
     * @param array $schema_setting
     * @return string
     */
    private function website(object $content, array $schema_setting): string {
        $tmp_schema="";
        $content->name = $schema_setting['website']['name'];
        $content->url = $schema_setting['website']['url'];
        if (isset($schema_setting['website']['target'])){
            $content->potentialAction->target = $schema_setting['website']['target'].$content->potentialAction->target;
        }
        else {
            unset($content->{"potentialAction"});
        }
        $tmp_schema === ''?$tmp_schema = $this->scriptTagStart.json_encode($content).$this->scriptTagEnd:$tmp_schema.=$this->scriptTagStart.json_encode($content).$this->scriptTagEnd;
        return $tmp_schema;
    }

    /**
     * @param object $content
     * @param array $schema_setting
     * @return string
     */
    private function article(object $content, array $schema_setting): string {
        $tmp_schema = "";
        if (isset($schema_setting['url']) && isset($schema_setting['website']['url'])){
            $content->mainEntityOfPage->{'@id'} = $schema_setting['website']['url'].$schema_setting['url'];
        }
        $content->headline = $schema_setting['title'];
        $content->description = $schema_setting['description'];
        if (!empty($schema_setting['image'])){
            $content->image = $schema_setting['image'];
        }
        else {
            unset($content->{'image'});
        }
        $content->author->name = $schema_setting['organization']['name'];
        $content->publisher->name = $schema_setting['organization']['name'];
        $content->publisher->logo->url = $schema_setting['organization']['logo'];
        if (isset($schema_setting['published'])){
            $content->datePublished = $schema_setting['published'];
        }
        if(isset($schema_setting['modified'])){
            $content->dateModified = $schema_setting['modified'];
        }
        $tmp_schema === ''?$tmp_schema = $this->scriptTagStart.json_encode($content).$this->scriptTagEnd:$tmp_schema.=$this->scriptTagStart.json_encode($content).$this->scriptTagEnd;
        return $tmp_schema;
    }

    /**
     * @param object $content
     * @param array $schema_setting
     * @return string
     */
    private function product(object $content, array $schema_setting): string {
        $tmp_schema = "";
        $content->name = $schema_setting['title'];
        $content->image = $schema_setting['image'];
        $content->description = $schema_setting['description'];
        $content->brand->name = $schema_setting['product']['brand'];
        $content->sku = $schema_setting['product']['sku'];
        $content->ean = $schema_setting['product']['ean'];
        $content->gtin13 = $schema_setting['product']['gtin13'];

        $content->offers->url = $schema_setting['website']['url'].$schema_setting['product']['url'];
        $content->offers->priceCurrency = $schema_setting['product']['price:currency'];
        $content->offers->price = $schema_setting['product']['price:amount'];

        if ($schema_setting['product']['availability'] === 'instock'){
            $content->offers->availability = 'https://schema.org/InStock';
        }
        elseif ($schema_setting['product']['availability'] === 'preorder'){
            $content->offers->availability = 'https://schema.org/PreOrder';
        }
        $content->offers->itemCondition = 'https://schema.org/NewCondition';
        $content->offers->priceValidUntil = $schema_setting['product']['price_valid'];

        $raitings = Raiting::where('product_id', '=', $schema_setting['id'])->orderBy('id', 'DESC')->get();
        if ($raitings->count() > 0){
            $max = 1;
            $min = 5;
            $sum = 0;
            foreach ($raitings as $raiting){
                $sum+=$raiting->raiting;
                if ($max < $raiting->raiting){
                    $max = $raiting->raiting;
                    $raiting_id = $raiting->id;
                }
                if ($min > $raiting->raiting){
                    $min = $raiting->raiting;
                }
            }

            $content->aggregateRating->ratingValue = round($sum/$raitings->count(),1);
            $content->aggregateRating->bestRating = $max;
            $content->aggregateRating->worstRating = $min;
            $content->aggregateRating->ratingCount = $raitings->count();
            $content->aggregateRating->reviewCount = 1;

            $review_raiting = Raiting::find($raiting_id);

            $content->review->reviewRating->ratingValue = round($sum/$raitings->count(),1);
            $content->review->reviewRating->bestRating = $max;
            $content->review->reviewRating->worstRating = $min;
            $content->review->datePublished = date("Y-m-d", strtotime($review_raiting->created_at));

            $content->review->name = $review_raiting->name;
            $content->review->reviewBody = $review_raiting->comment;
            $content->review->author->name = $review_raiting->name;
            $content->review->publisher->name = $schema_setting['organization']['name'];
        }
        else {
            unset($content->{'aggregateRating'});
            unset($content->{'review'});
        }
        $tmp_schema === ''?$tmp_schema = $this->scriptTagStart.json_encode($content).$this->scriptTagEnd:$tmp_schema.=$this->scriptTagStart.json_encode($content).$this->scriptTagEnd;
        return $tmp_schema;
    }

    /**
     * @return ApcuCache|string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getApcuCache(): ApcuCache|string {
        return $this->getContainer()->get('Router\ApcuCache');
    }

    /**
     * @param string $type
     * @param array|string|null $obj
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getSiteUrl(string $type, array|string $obj = null): string {
        $routers = $this->getApcuCache()->get('routers');
        if (empty($obj)) {
            $obj = [];
        }
        if (isset($routers[$type])){
            if(str_contains($routers[$type][0]['route'], '{')){
                preg_match('/{([^*]+)}/', $routers[$type][0]['route'], $match);
                $tmp_key = explode(':', $match[1])[0];
                return str_replace($match[0], $obj[$tmp_key], $routers[$type][0]['route']);
            }
            else {
                return $routers[$type][0]['route'];
            }
        }
        return $this->getApp()->getRouteCollector()->getRouteParser()->urlFor($type, $obj);
    }
}
