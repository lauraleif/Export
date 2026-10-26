<?php
namespace Export\Controller;

use Export\Form\ImportForm;
use Omeka\Api\Manager as ApiManager;
use Omeka\Settings\UserSettings;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var UserSettings
     */
    protected $userSettings;
    protected $serviceLocator;
    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var ApiManager
     */
    protected $api;

    /**
     * @param array $config
     * @param Manager $api
     * @param UserSettings $userSettings
     */
    public function __construct(array $config, ApiManager $api, UserSettings $userSettings, $serviceLocator)
    {
        $this->config = $config;
        $this->api = $api;
        $this->userSettings = $userSettings;
        $this->serviceLocator = $serviceLocator;
    }

    public function indexAction()
    {
        $view = new ViewModel;
        $form = $this->getForm(ImportForm::class);
        $view->form = $form;
        return $view;
    }
    protected function getData($criteria, $field, $type)
    {
        $api = $this->api;
        $query[$field] = $criteria;
        $items = $api->search($type, $query)->getContent();
        $out = $this->formatData($items);

        return $out;
    }
    protected function formatData($rawData)
    {
        $arr = json_encode($rawData, true);
        $items = json_decode($arr, true);
        return $items ;
    }
    public function downloadAction()
    {
        $request = $this->getRequest();
        if (!$request->isPost()) {
            $response = $this->getResponse();
            $response->getHeaders()->addHeaderLine('Allow', 'POST');
            return $response->setStatusCode(405)->setContent('Export requires a POST request.');
        }

        $selection = $request->getPost('add_to_item_set');
        $itemSetId = (is_string($selection) || is_int($selection))
            ? filter_var($selection, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
            : false;
        if ($itemSetId === false) {
            return $this->getResponse()->setStatusCode(400)->setContent('Select a valid item set to export.');
        }

        $view = new ViewModel;
        $items = $this->getData($itemSetId, 'item_set_id', 'items');
        $itemMedia = [];
        foreach ($items as $item) {
            if (array_key_exists('o:media', $item) && !empty($item['o:media'])) {
                $mediaIds = $item['o:media'];
                $mediaOut = "";
                $mediaJson = "";
                foreach ($mediaIds as $mediaId) {
                    $id = $mediaId['o:id'];
                    $media = $this->getData($id, 'id', 'media');
                    foreach ($media as $medium) {
                        $mediaOut = $mediaOut . $medium['o:filename'] . ";";
                        $mediaJson = $mediaJson . json_encode($medium) . ";";
                        $item['media:link'] = $mediaOut;
                        $item['media:full'] = $mediaJson;
                    }
                }
            } else {
                $item['media:link'] = "";
                $item['media:full'] = "";
            }
            array_push($itemMedia, $item);
        }
        $properties = $this->getData("", 'term', 'properties');
        $propertyNames = [];
        foreach ($properties as $property) {
            $p = $property['o:term'];
            array_push($propertyNames, $p);
        }
        $view->setVariable('collection', $itemMedia);
        $view->setVariable('properties', $propertyNames);
        $view->setTerminal(true);
        return $view;
    }
}
