<?php

namespace Export\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Mvc\Application;

class ExportButton extends AbstractHelper
{
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function __invoke()
    {
        $mvcEvent = $this->application->getMvcEvent();
        $request = $mvcEvent->getRequest();
        $routeMatch = $mvcEvent->getRouteMatch();

        $route = $routeMatch->getMatchedRouteName();
        $params = $routeMatch->getParams();

        if($route==='admin/id' && ($params['controller']==='Omeka\Controller\Admin\Item' && $params['action']==='show')) {
           $query= [
            'id' => $params['id']
           ]; 
        } else {
            $query = $request->getQuery()->toArray();
            unset($query['page']);
        }
            $view = $this->getView();
            $url = $view->url('admin/export/download', [], ['query' => $query]);

        return '<a href="' . $url . '">Export CSV</a>';
    }
}
