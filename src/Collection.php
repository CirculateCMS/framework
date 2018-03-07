<?php

namespace Circulate;

use Symfony\Component\Finder\SplFileInfo;

class Collection
{
    /**
     * @var SplFileInfo
     */
    protected $dir;
    /**
     * @var array
     */
    protected $entries;

    /**
     * @param SplFileInfo $dir
     * @param array $entries
     */
    public function __construct($dir, $entries)
    {
        $this->dir     = $dir;
        $this->entries = $entries;
    }

    /**
     * @return string
     */
    public function slug()
    {
        return $this->dir->getBasename();
    }

    /**
     * @return array
     */
    public function entries()
    {
        return $this->entries;
    }

    /**
     * @return string
     */
    public function title()
    {
        $title = ltrim($this->slug(), '/');
        $title = str_replace(['-', '_'], ' ', $title);
        $title = ucwords($title);

        return $title;
    }

    /**
     * @return array
     */
    public function meta()
    {
        return [
            'title' => $this->title(),
        ];
    }
}