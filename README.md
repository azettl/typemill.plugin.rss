# typemill.plugin.rss

This plugin allows you to add RSS feeds to all your folders. 

In the theme you additionally should add the following to your HTML head:

```
{% if item.elementType == 'folder' %}
  <link rel="alternate" type="application/rss+xml" title="{{ title }}" href="{{ item.urlAbs }}/rss" />
{% endif %}
```

Also a generic RSS feed with all posts from all folders is available just add `/rss` to your base url. The title and description of this generic RSS feed can be set in the plugin settings.
