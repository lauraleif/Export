<?php
namespace Export\Api\Adapter;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Export\Api\Representation\BackgroundExportRepresentation;
use Export\Entity\ExportBackgroundExports;

class BackgroundExportAdapter extends AbstractEntityAdapter
{
    public function getResourceName()
    {
        return 'export_background_exports';
    }

    public function getRepresentationClass()
    {
        return BackgroundExportRepresentation::class;
    }

    public function getEntityClass()
    {
        return ExportBackgroundExports::class;
    }

    public function hydrate(
        Request $request,
        EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        if (isset($data['o:job']['o:id'])) {
            $job = $this->getAdapter('jobs')->findEntity($data['o:job']['o:id']);
            $entity->setJob($job);
        }

        if (isset($data['created'])) {
            $entity->setCreated($data['created']);
        }

        if (isset($data['filename'])) {
            $entity->setFileName($data['filename']);
        }

        if (isset($data['extension'])) {
            $entity->setExtension($data['extension']);
        }

        if (isset($data['query'])) {
            $entity->setQuery($data['query']);
        }

        if (isset($data['resource_type'])) {
            $entity->setResourceType($data['resource_type']);
        }

        if (isset($data['resources_count'])) {
            $entity->setResourcesCount($data['resources_count']);
        }

        if (isset($data['file_uri'])) {
            $entity->setFileUri($data['file_uri']);
        }
    }
}
