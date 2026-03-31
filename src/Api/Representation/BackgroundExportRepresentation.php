<?php
namespace Export\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class BackgroundExportRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLd()
    {
        return [
            'created' => $this->getDateTime($this->created()),
            'filename' => $this->filename(),
            'extension' => $this->extension(),
            'query' => $this->query(),
            'resource_type' => $this->resourceType(),
            'resources_count' => $this->resourcesCount(),
            'file_uri' => $this->fileUri(),
        ];
    }

    public function getJsonLdType()
    {
        return 'export_background_exports';
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function filename()
    {
        return $this->resource->getFilename();
    }

    public function extension()
    {
        return $this->resource->getExtension();
    }

    public function query()
    {
        return $this->resource->getQuery();
    }

    public function resourceType()
    {
        return $this->resource->getResourceType();
    }

    public function resourcesCount()
    {
        return $this->resource->getResourcesCount();
    }

    public function fileUri()
    {
        return $this->resource->getFileUri();
    }

    public function job()
    {
        return $this->getAdapter('jobs')
            ->getRepresentation($this->resource->getJob());
    }
}
