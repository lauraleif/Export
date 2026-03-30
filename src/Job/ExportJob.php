<?php

namespace Export\Job;

use Omeka\Job\AbstractJob;
use Datetime;

class ExportJob extends AbstractJob
{
    public function perform()
    {
        $services = $this->getServiceLocator();
        $logger = $services->get('Omeka\Logger');
        $store = $this->getServiceLocator()->get('Omeka\File\Store');
        $api = $this->getServiceLocator()->get('Omeka\ApiManager');
        $exporter = $services->get('Export\Exporter');

        $logger->info('Job started');

        $date = new Datetime('now');
        $now = time();

        $tmpFilename = tempnam(sys_get_temp_dir(), 'omekas_export');
        $fileHandler = fopen($tmpFilename, 'w');
        $exporter->setFileHandle($fileHandler);
        $resourceType = $this->getArg('resource_type');
        $queryJob = $this->getArg('query_job');
        $queryString = $this->getArg('query_string');

        $resourcesCount = $exporter->exportResourcesByQuery($queryJob, $resourceType, $this->getArg('format_name'));
        if ($resourcesCount == 0) {
            $logger->info("None resources to export");
            return;
        }

        fclose($fileHandler);

        $fileExtension = \Export\Exporter::IMPLEMENTED_FORMATS[$this->getArg('format_name')]['extension'] ?? "";

        $filename = sprintf("omekas_%s", $now);
        $exportFile = sprintf("Export/%s%s", $filename, $fileExtension);
        $store->put($tmpFilename, $exportFile);
        $fileUri = $store->getUri($exportFile);

        unlink($tmpFilename);

        $exportBackup = [
            'filename' => $filename,
            'extension' => $fileExtension,
            'resource_type' => $resourceType,
            'resources_count' => $resourcesCount,
            'query' => $queryString,
            'o:job' => ['o:id' => $this->job->getId()],
            'file_uri' => $fileUri,
            'created' => $date,
        ];

        $api->create('export_background_exports', $exportBackup);

        $logger->info(sprintf("%d resource(s) has been exported", $resourcesCount));
        $logger->info(sprintf("The file is available at the following address: $fileUri"));
        $logger->info('Job ended');
    }
}
