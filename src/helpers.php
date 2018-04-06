<?php

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
