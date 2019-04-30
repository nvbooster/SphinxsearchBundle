SphinxsearchBundle
==================

With this bundle you can use Sphinx to search in your Symfony2 project.

Installation
------------

### Step 1: Download SphinxsearchBundle using composer

```iakumai/sphinxsearch-bundle
composer require nvbooster/sphinxsearch-bundle
```

### Step 2: Add bundle in bundles.php
``` php
// config/bundles.php

<?php

return [
	...
	IAkumaI\SphinxsearchBundle\SphinxsearchBundle::class => ['all' => true],
];
```

### Step 3: Configure your config.yml
By default bundle does not need to be a configured, but has some options for you.

**Full configuration :**
``` yml
# app/config/config.yml
sphinxsearch:
    searchd:
        # Host name for your Sphinx daemon
        host: localhost
        # Port number for your Sphinx daemon
        port: 9312
        # If you want to connect via scoket
        socket: /path/to/socket.file
    indexes:
        # List of sphinx index names (key) and entity names (value)
        # to use it in searchEx() method
        IndexName: "Bundle:Entity"
    bridge: IAkumaI\SphinxsearchBundle\Doctrine\Bridge
    doctrine_bridge:
		entity_manager: Doctrine\ORM\EntityManagerInterface
    
```

Services
--------

- **@IAkumaI\SphinxsearchBundle\Search\Sphinxsearch** - base search engine to use Sphinx search.
- **@IAkumaI\SphinxsearchBundle\Doctrine\Bridgee** - default bridge to doctrine datebase.

Exceptions
----------

- **EmptyIndexException** - you will see this exception if try to search without indexes.
- **NoSphinxAPIException** - this exception throws if not SphinxAPI was found.

Highlight search results
------------------------

You can highlight search words in templates by use **sphinx_highlight** filter.

For example:
``` twig
<div class="text-block">
    {{ content|sphinx_highlight('IndexName', 'query word', {limit:100}) }}
</div>
```

In this example matches for **"query word"** in **content** variable will be highlighted for **IndexName** index. It use [BuildExcerpts](https://github.com/romainneutron/Sphinx-Search-API-PHP-Client/blob/master/sphinxapi.php#L1309) method for this.

Useful features
---------------

### Sphinx search by date range

For example, search link looks like http://site.ru/search/?date-start=26.09.2013&date-end=27.09.2013
``` php
// ...
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SearchController extends Controller
{
    public function indexAction(Request $request)
    {
        // Get a search service
        $sphinx = $this->get('iakumai.sphinxsearch.search');

        // Convert request parameters to \DateTime
        if ($datestart = $request->query->get('date-start')) {
            $datestart = \DateTime::createFromFormat('d.m.Y', $datestart);
        }

        if ($dateend = $request->query->get('date-end')) {
            $dateend = \DateTime::createFromFormat('d.m.Y', $dateend);
        }

        // Apply sphinx filter
        // updated - is a timestamp-attribute name in sphinx config
        $sphinx->setFilterBetweenDates('updated', $datestart, $dateend);

        return $sphinx->search($request->query->get('q', ''), array('IndexName'));
    }
}
```

Examples
--------

This code will use IndexName index to search for a query in *q*-get parameter:
``` php
// In a controller
public function searchAction(Request $request)
{
    $searchd = $this->get('iakumai.sphinxsearch.search');
    return $sphinxSearch->search($request->query->get('q', ''), array('IndexName'));
}
```

You can use all methods, that provides by [PHP SphinxAPI](https://github.com/romainneutron/Sphinx-Search-API-PHP-Client).

For example:
```php
// In a controller
public function searchAction(Request $request)
{
    $searchd = $this->get('iakumai.sphinxsearch.search');
    $searchd->setLimits(0, 100);
    return $sphinxSearch->search($request->query->get('q', ''), array('IndexName'));
}
```

Now bundle can auto convert search results to entities if you will search for one index or define a *index_name* attribute in sphinx config.
To to this, first configure index names, for example:
``` yml
# app/config/config.yml
sphinxsearch:
    indexes:
        IndexName: "Bundle:Entity"
```

To convert multiple queries please add *index_name* attribute to your sphinx.conf file, for example:
```
source Example
{
    sql_query = SELECT id, ...., 'IndexName' as 'index_name' FROM my_table
    sql_attr_string = index_name
}

index IndexName
{
    source = Example
    path = /your/own/path
}
```

Now you can execute searchEx() method:
``` php
// In a controller
public function searchAction(Request $request)
{
    $searchd = $this->get('iakumai.sphinxsearch.search');
    $results_one = $sphinxSearch->searchEx($request->query->get('q', ''), 'IndexName');
    // or for multiple indexes (index_name attribute must exists)
    $results_two = $sphinxSearch->searchEx($request->query->get('q', ''), array('IndexName', 'SeconIndexName'));
}
```

**$results_one** now will contains something like this:
```
array(10) {
  .....
  ["matches"]=>
  array(20) {
    [22]=>
    array(3) {
      ["weight"]=>
      string(1) "2"
      ["attrs"]=>
      array(0) {
      }
      ["entity"]=> ... // Here is your Bundle:Entity
    }
    .........
```

**$results_two** now will contains something like this:
```
array(10) {
  .....
  ["matches"]=>
  array(20) {
    [22]=>
    array(3) {
      ["weight"]=>
      string(1) "2"
      ["attrs"]=>
      array(0) {
        ["index_name"]=>
        string(9) "IndexName"
      }
      ["entity"]=> ... // Here is your Bundle:Entity
    }
    .........
```

### Pagerfanta adapter

This bundle also includes special adapter for excellent [Pagerfanta bundle](https://github.com/whiteoctober/WhiteOctoberPagerfantaBundle)

``` php
/** @var $sphinx \IAkumaI\SphinxsearchBundle\Search\Sphinxsearch */
$sphinx = $this->get('iakumai.sphinxsearch.search');

/** @var $sphinxDoctrineBridge \IAkumaI\SphinxsearchBundle\Doctrine\Bridge */
$sphinxDoctrineBridge = $this->get('iakumai.sphinxsearch.doctrine.bridge');
$sphinx->setBridge($sphinxDoctrineBridge); //IMPORTANT! Set doctrine bridge.

$query = 'search query';
$entityIndexType = 'Books';

$adapter = new \IAkumaI\SphinxsearchBundle\Pagerfanta\Adapter\SphinxSearchAdapter($sphinx, $query, $entityIndexType, [
    'max_results' => 1000000,
]);
$pager = new Pagerfanta($adapter);
// Use pagerfanta as always
...
```
