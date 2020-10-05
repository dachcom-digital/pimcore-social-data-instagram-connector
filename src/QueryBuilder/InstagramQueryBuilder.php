<?php

namespace SocialData\Connector\Instagram\QueryBuilder;

final class InstagramQueryBuilder
{
    /**
     * @var GraphNode
     */
    protected $graphNode;

    /**
     * @param string|null $graphEndpoint
     */
    public function __construct($graphEndpoint = '')
    {
        if (isset($graphEndpoint)) {
            $this->graphNode = new GraphNode($graphEndpoint);
        }
    }

    /**
     * @param string $graphNodeName
     *
     * @return InstagramQueryBuilder
     */
    public function node($graphNodeName)
    {
        return new static($graphNodeName);
    }

    /**
     * @param string $edgeName
     * @param array  $fields
     *
     * @return GraphEdge
     */
    public function edge($edgeName, array $fields = [])
    {
        return new GraphEdge($edgeName, $fields);
    }

    /**
     * @param array|string $fields
     *
     * @return InstagramQueryBuilder
     */
    public function fields($fields)
    {
        if (!is_array($fields)) {
            $fields = func_get_args();
        }

        $this->graphNode->fields($fields);

        return $this;
    }

    /**
     * @param int $limit
     *
     * @return InstagramQueryBuilder
     */
    public function limit($limit)
    {
        $this->graphNode->limit($limit);

        return $this;
    }

    /**
     * @param array $data
     *
     * @return InstagramQueryBuilder
     */
    public function modifiers(array $data)
    {
        $this->graphNode->modifiers($data);

        return $this;
    }

    /**
     * @return string
     */
    public function asEndpoint()
    {
        return $this->graphNode->asUrl();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->asEndpoint();
    }
}
