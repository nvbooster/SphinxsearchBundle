<?php

namespace IAkumaI\SphinxsearchBundle\Twig;

use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;
use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

/**
 * Twig extension for Sphinxsearch bundle
 */
class SphinxsearchExtension extends AbstractExtension
{
    /**
     * @var Sphinxsearch
     */
    protected $searchd;

    /**
     * @param Sphinxsearch $searchd
     */
    public function __construct(Sphinxsearch $searchd)
    {
        $this->searchd = $searchd;
    }

    /**
     * Highlight $text for the $query using $index
     *
     * @param string $text Text content
     * @param string $index Sphinx index name
     * @param string $query Query to search
     * @param array  $options Options to pass to SphinxAPI
     *
     * @return string
     */
    public function sphinx_highlight($text, $index, $query, $options = [])
    {
        $result = $this->searchd->getClient()->BuildExcerpts([(string) $text], $index, $query, $options);

        if (!empty($result[0])) {
            return $result[0];
        } else {
            return '';
        }
    }

    /**
     * {@inheritDoc}
     *
     * @see \Twig\Extension\ExtensionInterface::getFilters()
     */
    public function getFilters()
    {
        return [
            new TwigFilter('sphinx_highlight', [$this, 'sphinx_highlight'], ['is_safe' => ['html']]),
        ];
    }
}
