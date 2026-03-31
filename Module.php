<?php

namespace Export;

use Export\Form\SiteSettingsFieldset;
use Laminas\EventManager\SharedEventManagerInterface;
use Omeka\Module\AbstractModule;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap(MvcEvent $event): void
    {
        parent::onBootstrap($event);

        /** @var \Omeka\Permissions\Acl $acl */
        $acl = $this->getServiceLocator()->get('Omeka\Acl');

        $acl->allow(null, ['Export\Controller\Site\Index']);
    }

    public function install(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $dirPath = OMEKA_PATH . '/files/Export';
        if (file_exists($dirPath)) {
            if (!is_dir($dirPath) || !is_readable($dirPath) || !is_writeable($dirPath)) {
                $serviceLocator->get('Omeka\Logger')->err(
                    'The directory "{path}" is not writeable.', // @translate
                    ['path' => $dirPath]
                );
            }
        } else {
            $result = @mkdir($dirPath, 0775);
            if (!$result) {
                $serviceLocator->get('Omeka\Logger')->err(
                    'The directory "{path}" is not writeable: {error}.', // @translate
                    ['path' => $dirPath, 'error' => error_get_last()['message']]
                );
            }
        }

        $sql = <<<'SQL'
            CREATE TABLE export_background_exports (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, created DATETIME NOT NULL, filename VARCHAR(255) NOT NULL, extension VARCHAR(255) NOT NULL, query VARCHAR(255) DEFAULT NULL, resource_type VARCHAR(255) NOT NULL, resources_count INT NOT NULL, file_uri VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_4E823CA13C0BE965 (filename), UNIQUE INDEX UNIQ_4E823CA1BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
            ALTER TABLE export_background_exports ADD CONSTRAINT FK_4E823CA1BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
            SQL;

        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }
    }

    public function upgrade($oldVersion, $newVersion, ServiceLocatorInterface $serviceLocator)
    {
        if (version_compare($oldVersion, '2.0.0', '<')) {
            $connection = $serviceLocator->get('Omeka\Connection');
            $storePath = OMEKA_PATH . '/files/';
            $oldPath = $storePath . 'CSV_Export';
            $newPath = $storePath . 'Export';

            if (file_exists($oldPath)) {
                if (!is_dir($oldPath) || !is_readable($oldPath) || !is_writeable($oldPath)) {
                    $serviceLocator->get('Omeka\Logger')->err(
                        'The directory "{path}" is not writeable.', // @translate
                        ['path' => $oldPath]
                    );
                } else {
                    rename($oldPath, $newPath);
                }
            } else {
                $result = @mkdir($newPath, 0775);
                if (!$result) {
                    $serviceLocator->get('Omeka\Logger')->err(
                        'The directory "{path}" is not writeable: {error}.', // @translate
                        ['path' => $newPath, 'error' => error_get_last()['message']]
                    );
                }
            }

            $sql = <<<'SQL'
                CREATE TABLE export_background_exports (id INT AUTO_INCREMENT NOT NULL, job_id INT NOT NULL, created DATETIME NOT NULL, filename VARCHAR(255) NOT NULL, extension VARCHAR(255) NOT NULL, query VARCHAR(255) DEFAULT NULL, resource_type VARCHAR(255) NOT NULL, resources_count INT NOT NULL, file_uri VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_4E823CA13C0BE965 (filename), UNIQUE INDEX UNIQ_4E823CA1BE04EA9 (job_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
                ALTER TABLE export_background_exports ADD CONSTRAINT FK_4E823CA1BE04EA9 FOREIGN KEY (job_id) REFERENCES job (id);
                SQL;

            $sqls = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($sqls as $sql) {
                $connection->exec($sql);
            }
        }
    }

    public function uninstall(ServiceLocatorInterface $serviceLocator)
    {
        $connection = $serviceLocator->get('Omeka\Connection');
        $sql = <<<'SQL'
        ALTER TABLE export_background_exports DROP FOREIGN KEY FK_4E823CA1BE04EA9;
        DROP TABLE IF EXISTS export_background_exports;
        SQL;

        $sqls = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($sqls as $sql) {
            $connection->exec($sql);
        }

        $storePath = OMEKA_PATH . '/files/Export';
        $this->removeFolder($storePath);
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $controllers = ['ItemSet', 'Item', 'Media'];

        foreach ($controllers as $controller) {

            // Browse exports
            $adminController = "Omeka\Controller\Admin\\" . $controller;
            $sharedEventManager->attach(
                $adminController,
                'view.browse.before',
                [$this, 'echoExportAdminLink']
            );
            $sharedEventManager->attach(
                $adminController,
                'view.browse.before',
                [$this, 'addAdminExportJs']
            );
            // Sidebar resource exports
            $sharedEventManager->attach(
                $adminController,
                'view.show.sidebar',
                [$this, 'echoExportButtonHtml']
            );
            // Site resource exports
            $siteController = "Omeka\Controller\Site\\" . $controller;
            $sharedEventManager->attach(
                $siteController,
                'view.show.before',
                [$this, 'echoExportButtonHtml']
            );
            $sharedEventManager->attach(
                $siteController,
                'view.show.after',
                [$this, 'echoExportButtonHtml']
            );
        }

        $sharedEventManager->attach(
            \Omeka\Form\SiteSettingsForm::class,
            'form.add_elements',
            [$this, 'handleSiteSettings']
        );
    }

    public function echoExportButtonHtml($event)
    {
        $params = $this->getServiceLocator()->get('Application')->getMvcEvent()->getRouteMatch()->getParams();

        $fromAdmin = false;

        $query = [
            'id' => $params['id'],
        ];

        if (
            $params['controller'] == 'Omeka\Controller\Admin\Item' ||
            $params['controller'] == 'Omeka\Controller\Admin\ItemSet' ||
            $params['controller'] == 'Omeka\Controller\Admin\Media'
        ) {
            $fromAdmin = true;
        }

        $publicExportButtonPosition = $this->getServiceLocator()->get('Omeka\Settings\Site')->get('export_public_button', 'no');

        if (
            $event->getName() == 'view.show.after' && $publicExportButtonPosition == 'after'
            || $event->getName() == 'view.show.before' && $publicExportButtonPosition == 'before'
            || $event->getName() == 'view.show.sidebar'
        ) {
            $view = $event->getTarget();
            echo $view->exportButton($fromAdmin, $params['controller'], $query);
        }
    }

    public function echoExportAdminLink($event)
    {
        $mvcEvent = $this->getServiceLocator()->get('Application')->getMvcEvent();
        $params = $mvcEvent->getRouteMatch()->getParams();
        $request = $mvcEvent->getRequest();

        $query = $request->getQuery()->toArray();
        $view = $event->getTarget();

        echo $view->exportButton(true, $params['controller'], $query, true);
    }

    public function addAdminExportJs($event): void
    {
        $view = $event->getTarget();
        $view->headScript()->appendFile($view->assetUrl('js/export-admin.js', 'Export'), 'text/javascript', ['defer' => 'defer']);
        $view->headLink()->appendStylesheet($view->assetUrl('css/export.css', 'Export'));
    }

    public function handleSiteSettings($event)
    {
        $services = $this->getServiceLocator();
        $forms = $services->get('FormElementManager');
        $siteSettings = $services->get('Omeka\Settings\Site');

        $fieldset = $forms->get(SiteSettingsFieldset::class);
        $fieldset->setName('export');

        $elements = $fieldset->getElements();

        $currentSettings = [];
        foreach ($elements as $name => $element) {
            $currentSettings[$name] = $siteSettings->get($name, $element->getValue());
        }

        $fieldset->populateValues($currentSettings);

        $form = $event->getTarget();
        $groups = $form->getOption('element_groups');
        if (isset($groups)) {
            $groups['export'] = $fieldset->getLabel();
            $form->setOption('element_groups', $groups);
            foreach ($fieldset->getElements() as $element) {
                $form->add($element);
            }
        } else {
            $form->add($fieldset);
        }
    }

    public function removeFolder($dir)
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = $dir . '/' . $file;

                    unlink($path);
                }
            }
            rmdir($dir);
        }
    }
}
