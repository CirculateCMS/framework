<?php

if (!function_exists('asset')) {
    /**
     * Generate an absolute URL to a theme asset.
     *
     * @param string $path
     * @return string
     */
    function asset($path = '')
    {
        $url = getenv('SITE_URL') ?: '';
        $theme = getenv('THEME') ?: 'default';
        $url = rtrim($url, '/') . '/themes/' . $theme;

        if (empty($path)) {
            return $url;
        }
        
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Generate an absolute URL.
     *
     * @param string $path
     * @return string
     */
    function url($path = '')
    {
        $url = getenv('SITE_URL') ?: '';

        if (empty($path)) {
            return $url;
        }
        
        return rtrim($url, '/') . '/' . ltrim($path, '/');
    }
}
