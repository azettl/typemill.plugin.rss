<?php

namespace Plugins\rss;

use \Typemill\Models\WriteCache;

class rssController
{
    public function __call($name, $arguments) 
    {
        $writeCache = new WriteCache();
        $rssXml     = $writeCache->getCache('cache', $name . '.rss');

        header('Content-Type: text/xml');
        die(trim($rssXml));
    }
}