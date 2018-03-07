<?php

namespace Circulate;

use Monolog\Logger;
use Philo\Blade\Blade;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;

class Circulate
{
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var string
     */
    protected $cachePath;
    /**
     * @var string
     */
    protected $viewsPath;
    /**
     * @var string
     */
    protected $pagesPath;
    /**
     * @var string
     */
    protected $collectionsPath;
    /**
     * @var array
     */
    protected $settings;
    /**
     * @var array
     */
    protected $pages;
    /**
     * @var array
     */
    protected $collections;

    /**
     * @param string $basePath
     * @param Logger $logger
     */
    public function __construct($basePath, $logger)
    {
        $this->logger = $logger;

        $this->settings        = $this->settings();
        $this->cachePath       = $basePath . DIRECTORY_SEPARATOR . $this->settings['cache_path'];
        $this->viewsPath       = $basePath . DIRECTORY_SEPARATOR . $this->settings['themes_path'] . DIRECTORY_SEPARATOR . $this->settings['theme'];
        $this->pagesPath       = $basePath . DIRECTORY_SEPARATOR . $this->settings['content_path'] . DIRECTORY_SEPARATOR . 'pages';
        $this->collectionsPath = $basePath . DIRECTORY_SEPARATOR . $this->settings['content_path'] . DIRECTORY_SEPARATOR . 'collections';

        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0777, true);
        }

        $this->pages       = $this->getPages();
        $this->collections = $this->getCollections();
        $routes            = $this->getRoutes();

        $request = Request::createFromGlobals();
        $slug    = $request->getPathInfo();

        if (isset($routes[$slug])) {
            $this->renderOutput($slug, $routes[$slug]);
        } else {
            $this->render404();
        }
    }

    /**
     * Get environment variables
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function env($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $default;
        }

        return $value;
    }

    /**
     * Get the settings
     *
     * @return array
     */
    protected function settings()
    {
        return [
            'site_title'   => $this->env('SITE_TITLE', ''),
            'theme'        => $this->env('THEME', 'default'),
            'cache_path'   => $this->env('CACHE_PATH', '_storage/cache'),
            'content_path' => $this->env('CONTENT_PATH', '_content'),
            'themes_path'  => $this->env('THEMES_PATH', 'themes'),
        ];
    }

    /**
     * Generate routes from pages and collections
     *
     * @return array
     */
    protected function getRoutes()
    {
        $routes = [];

        foreach ($this->collections as $collection) {
            if (!isset($routes['/' . $collection->slug()])) {
                $routes['/' . $collection->slug()] = $collection;
            }

            $routes = array_merge($routes, $collection->entries());
        }

        $routes = array_merge($routes, $this->pages);
        ksort($routes);

        return $routes;
    }

    /**
     * Get all pages
     *
     * @return array
     */
    protected function getPages()
    {
        $pages = [];

        $files = (new Finder())->in($this->pagesPath)->files()->name('index.md');

        foreach ($files as $file) {
            $doc = new Document($this->pagesPath, $file);
            $pages[$doc->slug()] = $doc;
        }

        return $pages;
    }

    /**
     * Get all collections and entries
     *
     * @return array
     */
    protected function getCollections()
    {
        $collections = [];

        $collectionDirs = (new Finder())->in($this->collectionsPath)->directories();

        foreach ($collectionDirs as $collectionDir) {
            $entries = [];

            $files = (new Finder())->in($collectionDir->getPathname())->files()->name('*.md');
            foreach ($files as $file) {
                $doc = new Document($collectionDir->getPathname(), $file, $collectionDir->getBasename());
                $entries[$doc->slug()] = $doc;
            }

            $collections[$collectionDir->getBasename()] = new Collection($collectionDir, $entries);
        }

        return $collections;
    }

    /**
     * Compile and render the correct template
     *
     * @param string $slug
     * @param Document|Collection $docOrCollection
     */
    protected function renderOutput($slug, $docOrCollection)
    {
        $isCollectionIndex = false;

        if ($docOrCollection instanceof Collection) {
            $isCollectionIndex = true;
            $meta              = $docOrCollection->meta();
            $html              = '';
        } else {
            list($meta, $html) = $docOrCollection->metaAndHtml();
        }

        $template = $this->getTemplate($slug, $docOrCollection, $meta);
        $data     = [
            'settings'    => $this->settings,
            'slug'        => $slug,
            'meta'        => $meta,
            'content'     => $html,
            'pages'       => $this->getPagesData(),
            'collections' => $this->getCollectionsData(),
        ];

        if ($isCollectionIndex) {
            $data['collection'] = $this->getCollectionData($docOrCollection);
        }

        $blade = new Blade($this->viewsPath, $this->cachePath);
        echo $blade->view()->make($template, $data)->render();
    }

    /**
     * Render the 404 template
     */
    protected function render404()
    {
        header('HTTP/1.0 404 Not Found');

        $data = [
            'settings' => $this->settings,
        ];

        $blade = new Blade($this->viewsPath, $this->cachePath);
        echo $blade->view()->make('404', $data)->render();
    }

    /**
     * Get the appropriate template for a given slug
     *
     * @param string $slug
     * @param Document|Collection $docOrCollection
     * @param array $meta
     * @return void
     */
    protected function getTemplate($slug, $docOrCollection, $meta)
    {
        $template = 'page';

        if ($slug == '/') {
            $template = 'index';
        }
        if ($docOrCollection instanceof Collection) {
            $template = 'collection-index';
        } elseif ($docOrCollection->isCollectionEntry()) {
            $template = 'collection-entry';
        }

        if (isset($meta['template']) && $meta['template'] &&
            file_exists($this->viewsPath . DIRECTORY_SEPARATOR . $meta['template'] . '.blade.php'))
        {
            $template = $meta['template'];
        }

        return $template;
    }

    /**
     * Convert pages to template data
     *
     * @return array
     */
    protected function getPagesData()
    {
        $pagesData = [];

        foreach ($this->pages as $doc) {
            list($meta, $html) = $doc->metaAndHtml();
            $meta['slug']      = $doc->slug();

            $pagesData[$doc->slug()] = $meta;
        }

        return $pagesData;
    }

    /**
     * Convert collections to template data
     *
     * @return array
     */
    protected function getCollectionsData()
    {
        $collectionsData = [];

        foreach ($this->collections as $collection) {
            $slug = '/' . $collection->slug();

            $collectionsData[$slug] = [
                'title' => $collection->title(),
                'slug'  => $slug,
            ];
        }

        return $collectionsData;
    }

    /**
     * Convert a collection to template data
     *
     * @return array
     */
    protected function getCollectionData($collection)
    {
        $collectionData = [];

        foreach ($collection->entries() as $doc) {
            list($meta, $html) = $doc->metaAndHtml();
            $meta['slug']      = $doc->slug();

            $collectionData[$doc->slug()] = $meta;
        }

        return $collectionData;
    }
}