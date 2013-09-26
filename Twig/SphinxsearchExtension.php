<?php

namespace IAkumaI\SphinxsearchBundle\Twig;

use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;


/**
 * Twig extension for Sphinxsearch bundle
 */
class SphinxsearchExtension extends \Twig_Extension
{
    /**
     * @var Sphinxsearch
     */
    protected $searchd;

    /**
     * Constructor
     * @param Sphinxsearch $searchd
     */
    public function __construct(Sphinxsearch $searchd)
    {
        $this->searchd = $searchd;
    }

    /**
     * Highlight $text for the $query using $index
     * @param string $text Text content
     * @param string $index Sphinx index name
     * @param string $query Query to search
     * @param integer[optional] $trim If set to integer will return trimmed string of this length
     *
     * @return string
     */
    public function sphinx_highlight($text, $index, $query, $trim = false)
    {
        $result = $this->searchd->getClient()->BuildExcerpts(array($text), $index, $query);

        if (!empty($result[0])) {
            return $result[0];
        } else {
            return '';
        }
    }

    /**
     * Filters list
     * @return array
     */
    public function getFilters()
    {
        return array(
            'sphinx_highlight' => new \Twig_Filter_Function(array($this, 'sphinx_highlight')),
        );
    }
}