<?php
namespace Export\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class ExportDetail extends AbstractHelper
{
    public function __invoke($export)
    {
        $resourceTypeMap = [
            'item_sets' => ['query' => 'item-set', 'display' => 'Item set'],
            'items' => ['query' => 'item', 'display' => 'Item'],
            'media' => ['query' => 'media', 'display' => 'Media'],
        ];

        $queriedResources = $resourceTypeMap[$export->resourceType()]['query'] . '?' . $export->query();

        return $this->getView()->partial(
            'export/admin/detail',
            [
                'id' => $export->id(),
                'filename' => $export->filename(),
                'extension' => $export->extension(),
                'resourceType' => $resourceTypeMap[$export->resourceType()]['display'],
                'resourcesCount' => $export->resourcesCount(),
                'query' => $queriedResources,
                'job' => $export->job(),
                'fileUri' => $export->fileUri(),
            ]
        );
    }
}
