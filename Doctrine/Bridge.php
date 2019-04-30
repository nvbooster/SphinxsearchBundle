<?php

namespace IAkumaI\SphinxsearchBundle\Doctrine;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Bridge to find entities for search results
 */
class Bridge implements BridgeInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * Indexes list
     * Key is index name
     * Value is entity name
     *
     * @var array
     */
    protected $indexes = [];

    /**
     * @param EntityManagerInterface $em
     * @param array                  $indexes List of search indexes with entity names
     */
    public function __construct(EntityManagerInterface $em, $indexes = [])
    {
        $this->em = $em;
        $this->setIndexes($indexes);
    }

    /**
     * Get an EntityManager
     *
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * Set an EntityManager
     *
     * @param EntityManagerInterface $em
     *
     * @throws \LogicException If entity manager already set
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        if ($this->em !== null) {
            throw new \LogicException('Entity manager can only be set before any results are fetched');
        }

        $this->em = $em;
    }

    /**
     * Set indexes list
     *
     * @param array $indexes
     */
    public function setIndexes(array $indexes)
    {
        $this->indexes = $indexes;
    }

    /**
     * Add entity list to sphinx search results
     *
     * @param  array        $results Sphinx search results
     * @param  string|array $index   Index name(s)
     *
     * @return array
     *
     * @throws \LogicException If results come with error
     * @throws \InvalidArgumentException If index name is not valid
     */
    public function parseResults(array $results, $index)
    {
        if (!empty($results['error'])) {
            throw new \LogicException('Search completed with errors');
        }

        if (is_string($index)) {
            if (!isset($this->indexes[$index])) {
                throw new \InvalidArgumentException('Unknown index name: '.$index);
            }
        } elseif (is_array($index)) {
            foreach ($index as $idx) {
                if (!isset($this->indexes[$idx])) {
                    throw new \InvalidArgumentException('Unknown index name: '.$idx);
                }
            }
        }

        if (empty($results['matches'])) {
            return $results;
        }

        $dbQueries = array_reverse(array_keys($this->indexes));

        foreach ($results['matches'] as $id => &$match) {
            $match['entity'] = false;

            if (is_string($index)) {
                $dbQueries[$index][] = $id;
            } elseif (is_array($index) && isset($match['attrs']['index_name']) && isset($this->indexes[$match['attrs']['index_name']])) {
                $dbQueries[$match['attrs']['index_name']][] = $id;
            }
        }

        foreach ($dbQueries as $index => $ids) {
            if (!isset($this->indexes[$index])) {
                continue;
            }

            $this->getEntityManager()->getRepository($this->indexes[$index])->findBy(['id' => $ids]);

            foreach ($ids as $id) {
                $results['matches'][$id]['entity'] = $this->getEntityManager()->getRepository($this->indexes[$index])->find($id);
            }
        }

        return $results;
    }
}
