<?php

namespace Export\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Laminas\Mvc\Application;
use Export\Form\ExportButtonForm;

class ExportButton extends AbstractHelper
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function __invoke(bool $admin, string $controller, array $query, bool $browsePage = false)
    {
        $url = null;
        $formOptions = ["admin" => $admin];

        if ($browsePage) {
            $formOptions["browse_page"] = true;
        }

        $form = $this->application->getServiceManager()->get('FormElementManager')->get(ExportButtonForm::class, $formOptions);

        if ($admin) {
            $url = $this->getView()->url('admin/export/download', [], ['query' => $query]);
        } else {
            $siteSlug = $this->getView()->getHelperPluginManager()->get('currentSite')()->slug();
            $url = $this->getView()->url('site/export', ['site-slug' => $siteSlug], ['query' => $query]);
        }

        if (empty($form->availableFormats)) {
            return '';
        }

        $form->setAttribute('action', $url);
        $form->get('controller')->setValue($controller);

        return $this->getView()->partial('export/export-button', ['form' => $form]);
    }
}
