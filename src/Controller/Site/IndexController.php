<?php
namespace Export\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Omeka\Stdlib\Message;
use Omeka\Api\Exception\NotFoundException;

class IndexController extends AbstractActionController
{
    protected $serviceLocator;
    public function __construct($serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    public function downloadAction()
    {
        $exporter = $this->serviceLocator->get('Export\Exporter');
        $request = $this->getRequest();
        $postParams = $request->getPost();
        $queryParams = $request->getQuery();

        if ($queryParams['id']) {
            $fileTemp = tmpfile();
            $exporter->setFileHandle($fileTemp);

            $resource_type = '';
            if (empty($postParams['format_name'])) {
                $postParams['format_name'] = 'CSV';
            }
            if (empty($postParams['controller'])) {
                $resource_type = 'item_sets';
            } else {
                if ($postParams['controller'] == 'Omeka\Controller\Site\Item') {
                    $resource_type = 'items';
                } elseif ($postParams['controller'] == 'Omeka\Controller\Site\Media') {
                    $resource_type = 'media';
                } else { // should be 'Omeka\Controller\Admin\ItemSet'
                    $resource_type = 'item_sets';
                }
            }

            try {
                $exporter->downloadOne($queryParams['id'], $postParams['format_name'], $resource_type);
            } catch (NotFoundException $e) {
                $message = new Message($e->getMessage());
                $messenger = $this->serviceLocator->get('ControllerPluginManager')->get('messenger');
                $messenger->addError($message);
                return $this->redirect()->toRoute(null, [], true);
            }

            fseek($fileTemp, 0);
            $rows = '';
            while (! feof($fileTemp)) {
                $rows .= fread($fileTemp, 1024);
            }
            fclose($fileTemp);

            $fileExtension = \Export\Exporter::IMPLEMENTED_FORMATS[$postParams['format_name']]['extension'];
            $fileMime = \Export\Exporter::IMPLEMENTED_FORMATS[$postParams['format_name']]['mime'];
            $now = date("Y-m-d_H-i-s");

            $response = $this->getResponse();
            $response->setContent($rows);
            $response->getHeaders()->addHeaderLine('Content-type', $fileMime);
            $response->getHeaders()->addHeaderLine('Content-Disposition', 'attachment; filename="omekas_export_' . $now . $fileExtension . '"');

            return $response;
        } else {
            return $this->redirect()->toRoute('site', ['site-slug' => $this->currentSite()->slug()]);
        }
    }
}
