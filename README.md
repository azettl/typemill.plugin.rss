# typemill.plugin.rss

This plugin allows you to add RSS feeds to all your folders. 

In the theme you additionally should add the following to your HTML head:

```
{% if item.elementType == 'folder' %}
			<link rel="alternate" type="application/rss+xml" title="{{ title }}" href="{{ item.urlAbs }}/rss" />
{% endif %}
```
