<?php

namespace IAkumaI\SphinxsearchBundle\Pagerfanta\Adapter;

use IAkumaI\SphinxsearchBundle\Search\Sphinxsearch;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Pagerfanta pagination adapter for SphinxSearch
 *
 * @package IAkumaI\SphinxsearchBundle\Pagerfanta\Adapter
 */
class SphinxSearchAdapter implements AdapterInterface
{
    /**
     * @var Sphinxsearch
     */
    private $sphinx;

    /**
     * @var string
     */
    private $query;

    /**
     * @var array
     */
    private $results;

    /**
     * @var array
     */
    private $options = [
        'max_results' => 1000,
        'entity' => [],
    ];

    /**
     * @param Sphinxsearch $sphinx
     * @param string       $query query string
     * @param string|array $entityIndex Index index_name attribute from config.yml
     * @param array        $options misc options
     */
    public function __construct(
        Sphinxsearch $sphinx,
        $query,
        $entityIndex,
        $options = []
    ) {
        if ($sphinx->getBridge() === null) {
            throw new \RuntimeException('Entity bridge required for Sphinxsearch. Please, use setBridge() method on Sphinxsearch object.');
        }
        $this->query = $query;
        $this->sphinx = $sphinx;
        $this->options = array_merge($this->options, $options);

        $this->options['entity'] = $entityIndex;
    }

    /**
     * Returns an slice of the results.
     *
     * @param integer $offset The offset.
     * @param integer $length The length.
     *
     * @return array|\Traversable The slice.
     */
    function getSlice($offset, $length)
    {
        $this->sphinx->SetLimits($offset, $length, $this->options['max_results']);
        $this->results = $this->sphinx->searchEx($this->query, $this->options['entity']);
        if ($this->results['total_found'] == 0 || empty($this->results['matches'])) {
            return [];
        }
        $results = array_map(
            function ($entity) {
                return $entity['entity'];
            },
            $this->results['matches']
        );

        return $results;
    }

    /**
     * Returns the number of results.
     *
     * @return integer The number of results.
     */
    function getNbResults()
    {
        if (empty($this->results)) {
            $this->sphinx->SetLimits(1, 1, $this->options['max_results']);
            $results = $this->sphinx->searchEx($this->query, $this->options['entity']);

            return $results['total_found'];
        }

        return $this->results['total_found'];
    }

    /**
     * Returns raw result from sphinx. SHOULD BE called after getSlice() method
     *
     * @return array
     */
    public function getSphinxResult()
    {
        if (empty($this->results)) {
            throw new \LogicException('Sphinx results MUST be retrieved by getSlice before calling getSphinxResult');
        }
        return $this->results;
    }
}
