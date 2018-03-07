<?php

namespace Circulate;

use Parsedown;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class Document
{
    /**
     * @var string
     */
    protected $basePath;
    /**
     * @var SplFileInfo
     */
    protected $file;
    /**
     * @var string
     */
    protected $slug;
    /**
     * @var string
     */
    protected $collection;

    /**
     * @param string $basePath
     * @param SplFileInfo $file
     * @param string $collection
     */
    public function __construct($basePath, $file, $collection = '')
    {
        $this->basePath   = $basePath;
        $this->file       = $file;
        $this->collection = $collection;
        $this->slug       = $this->generateSlug();
    }

    /**
     * @return string
     */
    protected function generateSlug()
    {
        $path = $this->file->getPath();
        if ($this->collection) {
            $path = $this->file->getPathname();
        }

        $slug = str_replace($this->basePath, '', $path);
        $slug = preg_replace('/(\/.*?\.)/', '/', $slug);
        $slug = preg_replace('/\.md$/', '', $slug);

        if ($this->collection) {
            $slug = '/' . $this->collection . $slug;
        }
        if (!$slug) {
            $slug = '/';
        }

        return $slug;
    }

    /**
     * @return string
     */
    public function file()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function slug()
    {
        return $this->slug;
    }

    /**
     * @return boolean
     */
    public function isCollectionEntry()
    {
        return $this->collection != '';
    }

    /**
     * @return array
     */
    public function metaAndHtml()
    {
        $content               = file_get_contents($this->file->getPathname());
        list($meta, $markdown) = explode('---', $content, 2);
        $meta                  = Yaml::parse($meta);
        $parsedown             = new Parsedown();
        $html                  = $parsedown->text($markdown);

        if (!isset($meta['title'])) {
            $meta['title'] = '';
        }

        return [$meta, $html];
    }
}