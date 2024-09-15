<?php

/**
 * @author Sergey Tevs
 * @email sergey@tevs.org
 */

namespace Modules\Seo;

use Core\Traits\App;
use DI\DependencyException;
use DI\NotFoundException;
use Modules\Main\MainTrait;

trait SeoTrait {

    use App, MainTrait;

    /**
     * @var array
     */
    public array $config = [];

    /**
     * @var array|string[]
     */
    public array $metaAppName = [];

    /**
     * @var array|string[]
     */
    public array $metaTitle=[
        'itemprop'=>'name',
        'name'=>'twitter:title',
        'property'=>'og:title',
    ];

    /**
     * @var array|string[]
     */
    public array $metaDescription=[
        'name'=>'description',
        'itemprop'=>'description',
        'property'=>'og:description',
        'twitter:name'=>'twitter:description'
    ];

    /**
     * @var array|string[]
     */
    public array $metaImage=[
        'name'=>'image',
        'twitter:name'=>'twitter:image:src',
        'property'=>'og:image',
    ];

    /**
     * @var array|string[]
     */
    public array $metaUrl=[
        'name'=>'canonical',
        'twitter:name'=>'alternate',
        'property'=>'og:url',
    ];

    /**
     * @var array|string[][]
     */
    public array $general=[
        'twitter'=>[
            'twitter:card'=>'summary_large_image'
        ],
        'open_graph'=>[
            'og:type'=>'website',
        ]
    ];

    /**
     * @var array
     */
    public array $type=[
        'article'=>[
            'published_time',
            'modified_time',
            'tag'
        ],
        'product'=>[
            'plural_title',
            'condition'=>'new',
            'availability',
            'price:currency',
            'price:amount',
            'brand'
        ],
        'place'=>[
            'latitude',
            'longitude',
            'street-address',
            'locality',
            'region',
            'postal-code',
            'country-name'
        ],
        'car'=>[
            'plural_title',
            'condition'=>'new',
            'availability',
            'price:currency',
            'price:amount',
            'brand'
        ]
    ];

    /**
     * @var array|string[][]
     */
    public array $schemaList = [
        'standard' => [
            'organization.json',
            'website.json',
            'local_business.json'
        ],
        'article' => [
            'organization.json',
            'website.json',
            'local_business.json',
            'breadcrumb_list.json',
            'article.json'
        ],
        'product' => [
            'organization.json',
            'website.json',
            'local_business.json',
            'breadcrumb_list.json',
            'product.json'
        ],
        'car'=>[
            'organization.json',
            'website.json',
            'local_business.json',
            'breadcrumb_list.json',
            'car.json'
        ],
        'job'=>[
            'organization.json',
            'website.json',
            'local_business.json',
            'breadcrumb_list.json',
            'job.json'
        ]
    ];

    public array $meta = [];

    /**
     * @var string
     */
    public string $path = "";

    /**
     * @var string
     */
    public string $url = "";

    /**
     * @var string
     */
    public string $website = "";

    /**
     * @var string
     */
    public string $filePath = '';

    /**
     * @var string
     */
    public string $metaTagStart = "<meta ";

    /**
     * @var string
     */
    public string $twitter = "twitter:";

    /**
     * @var string
     */
    public string $contentStr = '" content="';

    /**
     * @var string
     */
    public string $scriptTagStart = '<script type="application/ld+json">';

    /**
     * @var string
     */
    public string $scriptTagEnd = '</script>';

    /* geo meta location
     *
     * <meta name="DC.title" content="AutoDom Pforzheim" />
<meta name="geo.region" content="DE-BW" />
<meta name="geo.placename" content="Schwetzingen" />
<meta name="geo.position" content="49.3915;8.5687" />
<meta name="ICBM" content="49.3915, 8.5687" />
    */


    /**
     * @param string $key
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getConfig(string $key = ""): mixed {
        if (empty($this->config)){
            $this->config = $this->getContainer()->get('config')->getSetting("site");
            $this->config['schema'] = $this->getContainer()->get('config')->getSetting("schema");
            $settingsGroup = $this->getMainManager()->getSettingsGroupEntity()::where("key", "=", "general")->first();
            $settings = $settingsGroup->getSettings();
            foreach ($settings as $setting){
                $this->config[str_replace("site_", "", $setting->key)] = $setting->value;
            }
        }
        if ($key !== "") {
            return $this->config[$key];
        }
        else {
            return $this->config;
        }
    }

    /**
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSeoManager(): mixed {
        return $this->getContainer()->get('Seo\Manager');
    }

    /**
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSeoModel(): mixed {
        return $this->getContainer()->get('Seo\Model');
    }

    /**
     * @return string
     */
    public function getProtocol(): string {
        return $_SERVER['SERVER_PORT'] === '443' ? 'https' : 'http';
    }

    /**
     * @return void
     */
    public function initializePaths(): void {
        $this->filePath = WEB_ROOT_DIR;
        $this->path = $_SERVER['REQUEST_URI'];
        $this->url = $this->getProtocol() . '://' . $_SERVER['HTTP_HOST'] . $this->path;
        $this->website = $this->getProtocol() . '://' . $_SERVER['HTTP_HOST'];
    }

    /**
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function fetchSeoEntity(): mixed {
        return $this->getSeoManager()->getSeoEntity()::where('path', '=', $this->path)->first();
    }

    /**
     * @param string $title
     * @param string $site_name
     * @param string $delimiter
     * @return void
     */
    public function getTitle(string $title, string $site_name="", string $delimiter = "|"): void {
        $robots = '';
        if (isset($this->meta['robots']) && !empty($this->meta['robots'])){
            $robots .= $this->metaTagStart.'name="robots" content="'.$this->meta['robots'].'" />';
            unset($this->meta['robots']);
        }
        else {
            if (!empty($_GET) && !isset($_GET['page'])) {
                $robots .= $this->metaTagStart.'name="robots" content="noindex, nofollow" />';
            }
            elseif (isset($_GET['page'])) {
                $robots .= $this->metaTagStart.'name="robots" content="noindex, follow" />';
            }
            else {
                $robots .= $this->metaTagStart.'name="robots" content="index, follow, max-snippet:-1" />';
            }
        }

        if (!empty($site_name)) {
            $site_title = " " . $delimiter . " " . $site_name;
        }
        else {
            $site_title = "";
        }

        if (strlen($title.$site_title)>45) {
            $site_title = "";
        }

        $meta = $this->metaTagStart.'property="og:locale" content="de_DE">';
        $meta.='<title>'.$title.$site_title.'</title>';
        foreach ($this->metaTitle as $key => $name){
            $meta.=$this->metaTagStart.str_replace($this->twitter, "", $key).'="'.$name.$this->contentStr.$title.$site_title.'">';
        }
        if (!empty($site_name)) {
            $meta .= $this->metaTagStart.'"property"="og:site_name" content="' . $site_name . '">';
        }

        $meta.=$robots;
        $this->meta['title']=$meta;

        if (isset($this->meta['keywords']) && !empty($this->meta['keywords'])){
            $this->meta['keywords'] = $this->metaTagStart.'name="keywords" content="'.$this->meta['keywords'].'">';
        }
    }

    /**
     * @param string $description
     * @return void
     */
    public function getDescription(string $description): void {
        if (!empty($description)){
            $meta='';
            if ($description === '...'){
                $description = $this->meta['title'];
            }
            foreach ($this->metaDescription as $key => $name){
                $meta.=$this->metaTagStart.str_replace($this->twitter, "", $key).'="'.$name.$this->contentStr.$description.'">';
            }
            $this->meta['description']=$meta;
        }
    }

    /**
     * @return void
     */
    public function getUrl(): void {
        $meta='';
        foreach ($this->metaUrl as $key => $name){
            $meta.=$this->metaTagStart.str_replace($this->twitter, "", $key).'="'.$name.$this->contentStr.$this->url.'">';
        }
        $this->meta['url']=$meta;
    }

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFavicon(): void {
        $favicon = $this->getConfig('favicon');
        $meta_favicon = "";
        if (file_exists($this->filePath.$favicon)){
            $meta_favicon='<link rel="icon" type="image/vnd.microsoft.icon" href="'.$this->fileModified($this->filePath.$favicon).'" /><link rel="shortcut icon" type="image/x-icon" href="'.$this->fileModified($this->filePath.$favicon).'" />';
        }
        $this->meta['favicon']=$meta_favicon;
    }

    /**
     * @return void
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getAppleTouch(): void {
        $apple = $this->getConfig('apple');
        $meta_apple='';

        if (!is_array($apple)){
            $apple = json_decode($apple, true);
        }

        foreach ($apple as $size => $file){
            if (file_exists($this->filePath.$file)){
                $meta_apple.='<link rel="apple-touch-icon" sizes="'.$size.'×'.$size.'" href="'.$this->fileModified($this->filePath.$file).'" />';
            }
        }
        if ($meta_apple!==''){
            $meta_apple=$this->metaTagStart.'name="apple-mobile-web-app-capable" content="yes" />'.$meta_apple;
        }
        else {
            $meta_apple=$this->metaTagStart.'name="apple-mobile-web-app-capable" content="no" />';
        }
        $this->meta['apple']=$meta_apple;
    }

    /**
     * @param string $variable
     * @return string
     */
    public function fileModified(string $variable): string {
        $file = str_replace($this->filePath, '', $variable);
        if (file_exists($variable)) {
            return $this->website.$file.'?rev='.filemtime($variable);
        }
        return $this->website.$file.'?rev='.time();
    }

    /**
     * @param string $file
     * @param string $general_type
     * @param string $title
     * @return void
     */
    public function handleImageType(string $file, string $general_type, string $title): void {
        $url = false;
        $size = $this->getImageSize($file, $url);

        $meta = $this->generateMetaTags($file, $url, $title);

        if ($url === false && $general_type === 'article') {
            $meta .= $this->addImageDimensions($size);
        }

        $this->meta['image'] = $meta;
    }

    /**
     * @param string $file
     * @param bool $url
     * @return int[]
     */
    public function getImageSize(string $file, bool &$url): array {
        $size = getimagesize($this->filePath . $file);
        if (is_bool($size) && $size === false && $file !== "") {
            $size = getimagesize($file);
            $url = true;
        }
        return $size ?: [0, 0]; // Return a default size if getimagesize fails
    }

    /**
     * @param string $file
     * @param bool $url
     * @param string $title
     * @return string
     */
    public function generateMetaTags(string $file, bool $url, string $title): string {
        $meta = '';
        foreach ($this->metaImage as $key => $name) {
            $meta .= $this->metaTagStart . str_replace($this->twitter, "", $key) . '="' . $name . $this->contentStr . (!$url ? $this->fileModified($this->filePath . $file) : $file) . '">';
        }

        if (!empty($title)) {
            $meta .= $this->metaTagStart . 'name="twitter:image:alt" content="' . $title . '">';
            $meta .= $this->metaTagStart . 'property="og:image:alt" content="' . $title . '">';
        }

        return $meta;
    }

    /**
     * @param array $size
     * @return string
     */
    public function addImageDimensions(array $size): string {
        return $this->metaTagStart . 'property="og:image:width" content="' . $size[0] . '">' .
            $this->metaTagStart . 'property="og:image:height" content="' . $size[1] . '">';
    }

    /**
     * @param string $type
     * @return string
     */
    public function generateGeneralMetaTags(string $type): string {
        $meta = '';

        foreach ($this->general as $key => $item) {
            $name = $key === 'twitter' ? 'name' : 'property';

            foreach ($item as $k => $content) {
                if ($k === 'og:type' && !empty($type)) {
                    $meta .= $this->metaTagStart . $name . '="' . $k . $this->contentStr . $type . '" />';
                } else {
                    $meta .= $this->metaTagStart . $name . '="' . $k . $this->contentStr . $this->getTwitterCardContent($type, $k, $content) . '" />';
                }
            }
        }

        return $meta;
    }

    /**
     * @param string $type
     * @param array $params
     * @return string
     */
    public function generateTypeSpecificMetaTags(string $type, array $params): string {
        $meta = '';

        foreach ($this->type[$type] as $param) {
            if (isset($params[$param])) {
                $meta .= $this->metaTagStart . 'property="' . $this->getTypeMetaKey($type, $param) . $this->contentStr . $params[$param] . '" />';
            }
        }

        return $meta;
    }

}
