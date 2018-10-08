# LargeArrayWriter

It was originally created for recording SiteMap files with their limitations of 50 MB and 50,000 lines per 1 file. Later useful for recording all sorts of feeds.

## Simple Version (1.0)
### Usage

```php
$writer = new LargeArrayWriter(
"sitemap_%NUM%.xml",
"/tmp/" ,
true,
"<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n",
"</urlset>",
50000,
50 * 1000 * 1000,
1000
);
```
Add string to file:
```php
$writer->addString(<url><loc>URL</loc><lastmod>DATE</lastmod><changefreq>daily</changefreq></url>\n);
```
After all, finalize writer:
```php
$files = $writer->finalize();
```

## Extended Version (2.0)

### FileNumerator
### Saver
### LAWriter