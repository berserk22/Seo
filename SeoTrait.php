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

    private function esc(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

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
            /*'local_business.json'*/
        ],
        'article' => [
            'organization.json',
            'website.json',
            /*'local_business.json',*/
            /*'breadcrumb_list.json',*/
            'article.json'
        ],
        'product' => [
            'organization.json',
            'website.json',
            /*'local_business.json',*/
            'breadcrumb_list.json',
            'product.json'
        ],
        'car'=>[
            'organization.json',
            'website.json',
            /*'local_business.json',*/
            'breadcrumb_list.json',
            /*'car.json'*/
        ],
        'job'=>[
            'organization.json',
            'website.json',
            /*'local_business.json',*/
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
            if ($settingsGroup !== null) {
                $settings = $settingsGroup->getSettings();
                foreach ($settings as $setting){
                    $this->config[str_replace("site_", "", $setting->key)] = $setting->value;
                }
            }
        }
        if ($key !== "") {
            return $this->config[$key] ?? null;
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

    // Note: HTTP_X_FORWARDED_PROTO is trusted unconditionally; restrict to known proxy IPs at the server/firewall level.
    /**
     * @return string
     */
    public function getProtocol(): string {
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            return strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' ? 'https' : 'http';
        }
        return (int)($_SERVER['SERVER_PORT'] ?? 80) === 443 ? 'https' : 'http';
    }

    /**
     * @return void
     */
    public function initializePaths(): void {
        $this->filePath = WEB_ROOT_DIR;
        $this->path     = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // RFC 1123: max 253 Zeichen, Labels max 63 Zeichen, keine aufeinanderfolgenden Punkte
        if (strlen($host) > 253 || !preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)*[a-zA-Z0-9](?:[a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(:\d{1,5})?$/', $host)) {
            $host = 'localhost';
        }

        $this->url     = $this->getProtocol() . '://' . $host . $this->path;
        $this->website = $this->getProtocol() . '://' . $host;
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
            $robots .= $this->metaTagStart.'name="robots" content="'.$this->esc($this->meta['robots']).'" />';
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

        $escapedFull     = $this->esc($title . $site_title);
        $escapedSiteName = $this->esc($site_name);

        $this->meta['plain_title'] = $title . $site_title;

        $meta = $this->metaTagStart.'property="og:locale" content="de_DE">';
        $meta.='<title>'.$escapedFull.'</title>';
        foreach ($this->metaTitle as $key => $name){
            $meta.=$this->metaTagStart.str_replace($this->twitter, "", $key).'="'.$name.$this->contentStr.$escapedFull.'">';
        }
        if (!empty($site_name)) {
            $meta .= $this->metaTagStart.'property="og:site_name" content="'.$escapedSiteName.'">';
        }

        $meta.=$robots;
        $this->meta['title']=$meta;

        if (isset($this->meta['keywords']) && !empty($this->meta['keywords'])){
            $this->meta['keywords'] = $this->metaTagStart.'name="keywords" content="'.$this->esc($this->meta['keywords']).'">';
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
                $description = $this->meta['plain_title'] ?? '';
            }
            $escaped = $this->esc($description);
            foreach ($this->metaDescription as $key => $name){
                $meta.=$this->metaTagStart.str_replace($this->twitter, "", $key).'="'.$name.$this->contentStr.$escaped.'">';
            }
            $this->meta['description']=$meta;
        }
    }

    /**
     * @return void
     */
    public function getUrl(): void {
        $meta='';
        $escapedUrl = $this->esc($this->url);
        foreach ($this->metaUrl as $key => $name){
            $meta.=$this->metaTagStart.str_replace($this->twitter, "", $key).'="'.$name.$this->contentStr.$escapedUrl.'">';
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
            $meta_favicon='<link rel="icon" type="image/vnd.microsoft.icon" href="'.$this->esc($this->fileModified($this->filePath.$favicon)).'" /><link rel="shortcut icon" type="image/x-icon" href="'.$this->esc($this->fileModified($this->filePath.$favicon)).'" />';
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

        $favicon = $this->getConfig('favicon');
        if (!is_array($apple)){
            $apple = json_decode($apple, true);
        }
        elseif (empty($apple) && file_exists($this->filePath.$favicon)){
            $apple[57] = $favicon;
            $apple[72] = $favicon;
            $apple[76] = $favicon;
            $apple[114] = $favicon;
            $apple[120] = $favicon;
            $apple[144] = $favicon;
            $apple[152] = $favicon;
            $apple[180] = $favicon;
        }

        foreach ($apple as $size => $file){
            if (file_exists($this->filePath.$file)){
                $meta_apple.='<link rel="apple-touch-icon" sizes="'.(int)$size.'x'.(int)$size.'" href="'.$this->esc($this->fileModified($this->filePath.$file)).'" />';
            }
        }
        if ($meta_apple!==''){
            $meta_apple=$this->metaTagStart.'name="mobile-web-app-capable" content="yes" />'.$meta_apple;
        }
        else {
            $meta_apple=$this->metaTagStart.'name="mobile-web-app-capable" content="no" />';
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
        return $this->website . $file;
    }

    /**
     * @return string
     */
    public function getSchemaDir(): string {
        // SeoTrait liegt in modules/Seo/ → 2 Ebenen hoch = Projekt-Root
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'schema' . DIRECTORY_SEPARATOR;
    }

    /**
     * @param string $file
     * @param string $general_type
     * @param string $title
     * @return void
     */
    public function handleImageType(string $file, string $general_type, string $title): void {
        $size = $this->getImageSize($file);

        $meta = $this->generateMetaTags($file, $title);

        if ($general_type === 'article') {
            $meta .= $this->addImageDimensions($size);
        }

        $this->meta['image'] = $meta;
    }

    /**
     * @param string $file
     * @return int[]
     */
    public function getImageSize(string $file): array {
        $size = @getimagesize($this->filePath . $file);
        return $size ?: [0, 0];
    }

    /**
     * @param string $file
     * @param string $title
     * @return string
     */
    public function generateMetaTags(string $file, string $title): string {
        $meta = '';
        $escapedUrl   = $this->esc($this->fileModified($this->filePath . $file));
        $escapedTitle = $this->esc($title);

        foreach ($this->metaImage as $key => $name) {
            $meta .= $this->metaTagStart . str_replace($this->twitter, "", $key) . '="' . $name . $this->contentStr . $escapedUrl . '">';
        }

        if (!empty($title)) {
            $meta .= $this->metaTagStart . 'name="twitter:image:alt" content="' . $escapedTitle . '">';
            $meta .= $this->metaTagStart . 'property="og:image:alt" content="' . $escapedTitle . '">';
        }

        return $meta;
    }

    /**
     * @param array $size
     * @return string
     */
    public function addImageDimensions(array $size): string {
        return $this->metaTagStart . 'property="og:image:width" content="' . (int)$size[0] . '">' .
            $this->metaTagStart . 'property="og:image:height" content="' . (int)$size[1] . '">';
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
                    $meta .= $this->metaTagStart . $name . '="' . $this->esc($k) . $this->contentStr . $this->esc($type) . '" />';
                } else {
                    $meta .= $this->metaTagStart . $name . '="' . $this->esc($k) . $this->contentStr . $this->esc($this->getTwitterCardContent($type, $k, $content)) . '" />';
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
                $meta .= $this->metaTagStart . 'property="' . $this->esc($this->getTypeMetaKey($type, $param)) . $this->contentStr . $this->esc((string)$params[$param]) . '" />';
            }
        }

        return $meta;
    }

}
