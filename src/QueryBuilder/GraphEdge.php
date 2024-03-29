<?php

namespace SocialData\Connector\Instagram\QueryBuilder;

class GraphEdge extends GraphNode
{
    public function toEndpoints(): array
    {
        $endpoints = [];

        $children = $this->getChildEdges();
        foreach ($children as $child) {
            $endpoints[] = sprintf('/%s', implode('/', $child));
        }

        return $endpoints;
    }

    public function getChildEdges(): array
    {
        $edges = [];
        $hasChildren = false;

        foreach ($this->fields as $v) {

            if ($v instanceof GraphEdge) {
                $hasChildren = true;

                $children = $v->getChildEdges();
                foreach ($children as $childEdges) {
                    $edges[] = array_merge([$this->name], $childEdges);
                }
            }
        }

        if (!$hasChildren) {
            $edges[] = [$this->name];
        }

        return $edges;
    }

    public function compileModifiers(): void
    {
        if (count($this->modifiers) === 0) {
            return;
        }

        $processed_modifiers = [];

        foreach ($this->modifiers as $k => $v) {
            $processed_modifiers[] = sprintf('%s(%s)', urlencode($k), urlencode($v));
        }

        $this->compiledValues[] = sprintf('.%s', implode('.', $processed_modifiers));
    }

    public function compileFields(): void
    {
        if (count($this->fields) === 0) {
            return;
        }

        $processed_fields = [];

        foreach ($this->fields as $v) {
            $processed_fields[] = $v instanceof GraphEdge ? $v->asUrl() : urlencode($v);
        }

        $this->compiledValues[] = sprintf('{%s}', implode(',', $processed_fields));
    }

    public function compileUrl(): string
    {
        $append = '';

        if (count($this->compiledValues) > 0) {
            $append = implode('', $this->compiledValues);
        }

        return sprintf('%s%s', $this->name, $append);
    }
}
