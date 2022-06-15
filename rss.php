<?php

namespace Plugins\rss;

use \Typemill\Plugin;
use \Typemill\Models\WriteMeta;
use \Typemill\Models\WriteCache;
use \Typemill\Settings;

class rss extends Plugin
{

    # subscribe to the events
    public static function getSubscribedEvents()
    {
        return array(
			'onPagePublished'		=> 'onPagePublished',
			'onPageUnpublished'		=> 'onPageUnpublished',
			'onPageSorted'			=> 'onPageSorted',
			'onPageDeleted'			=> 'onPageDeleted',
            'onPageReady'           => 'onPageReady'
        );
    }

	# at any of theses events, delete the old rss cache files
	public function onPagePublished($item)
	{
		$this->updateRssXmls();
	}
	public function onPageUnpublished($item)
	{
		$this->updateRssXmls();
	}
	public function onPageSorted($inputParams)
	{
		$this->updateRssXmls();
	}
	public function onPageDeleted($item)
	{
		$this->updateRssXmls();
	}
    public function onPageReady($pagedata)
    {
        $data = $pagedata->getData();

        if(isset($data['item']->folderContent) && is_array($data['item']->folderContent) && method_exists($this, 'addMeta'))
        {
            $this->addMeta('rss', '<link rel="alternate" type="application/rss+xml" title="' . $data['title'] . '" href="' . $data['item']->urlAbs . '/rss">');
        }
    }

    public static function addNewRoutes()
    {
        $routes = [];
        
        $writeCache = new WriteCache();
        $navigation = $writeCache->getCache('cache', 'navigation.txt');

        foreach($navigation as $pageData){
            if(isset($pageData->folderContent) && is_array($pageData->folderContent)){
                $routes[] = [
                    'httpMethod'    => 'get', 
                    'route'         => $pageData->urlRelWoF . '/rss', 
                    'class'         => 'Plugins\rss\rssController:' . $pageData->slug
                ];
            }
        }
		
		$routes[] = [
			'httpMethod'    => 'get', 
			'route'         => '/rss', 
			'class'         => 'Plugins\rss\rssController:all'
		];
       
        return $routes;
    }

    private function updateRssXmls()
    {
        $writeCache     = new WriteCache();
        $settingsArray  = Settings::loadSettings();
        $settings       = $settingsArray['settings'];
        $navigation     = $writeCache->getCache('cache', 'navigation.txt');

		$allItems = [];
        foreach($navigation as $pageData){
            if(isset($pageData->folderContent) && is_array($pageData->folderContent)){
                # initiate object for metadata
                $writeMeta = new WriteMeta();
                $metadata  = $writeMeta->getPageMeta($settings, $pageData);

                $items = [];
                foreach($pageData->folderContent as $childData){
                    $childMetadata  = $writeMeta->getPageMeta($settings, $childData);

                    $allItems[
						(isset($childMetadata['meta']['manualdate'])) ? $childMetadata['meta']['manualdate'] . '-' . $childMetadata['meta']['time'] : $childMetadata['meta']['modified'] . '-' . $childMetadata['meta']['time']
					] = $items[] = [
                        'title'         => htmlspecialchars($childData->name, ENT_XML1),
                        'link'          => $childData->urlAbs,
                        'description'   => htmlspecialchars($childMetadata['meta']['description'], ENT_XML1)
                    ];
                }
                
                $rssXml = $this->getRssXml(
                    htmlspecialchars($pageData->name, ENT_XML1),
                    $pageData->urlAbs,
                    htmlspecialchars($metadata['meta']['description'], ENT_XML1),
                    $items
                );

                $writeCache->updateCache('cache', $pageData->slug . '.rss', false, $rssXml);
            }
        }
		
		$uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER))->withUserInfo('');
		krsort($allItems);
		$rssXml = $this->getRssXml(
			htmlspecialchars($settings['plugins']['rss']['mainrsstitle'], ENT_XML1),
			$uri->getBaseUrl(),
			htmlspecialchars($settings['plugins']['rss']['mainrssdescription'], ENT_XML1),
			$allItems
		);

		$writeCache->updateCache('cache', 'all.rss', false, $rssXml);
    }

    private function getRssXml(string $title, string $link, string $description, array $items)
    {
        $itemsXml = '';
        foreach($items as $item){
            $itemsXml .= '
                <item>
                    <title>' . $item['title'] . '</title>
                    <link>' . $item['link'] . '</link>
                    <description>' . $item['description'] . '</description>
                </item>
                ';
        }

        return '
            <?xml version="1.0"?>
            <rss version="2.0">
                <channel>
                    <title>' . $title . '</title>
                    <link>' . $link . '</link>
                    <description>' . $description . '</description>
                    ' . $itemsXml . '
                </channel>
            </rss>
        ';
    }
}
