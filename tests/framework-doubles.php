<?php
// Only the framework boundaries used by these isolated regression tests.
// These doubles do not load in Omeka or claim to test framework integration.
namespace Zend\Mvc\Controller;

abstract class AbstractActionController
{
    public $request;
    public $response;

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }
}

namespace Zend\View\Model;

class ViewModel
{
    public $variables = [];
    public $terminal = false;

    public function setVariable($name, $value)
    {
        $this->variables[$name] = $value;
        return $this;
    }

    public function setTerminal($terminal)
    {
        $this->terminal = $terminal;
        return $this;
    }
}

namespace Omeka\Api;

class Manager
{
    public $calls = [];
    public $results = [];

    public function search($resource, array $query = [], array $options = [])
    {
        $this->calls[] = [$resource, $query];
        $content = $this->results[$resource] ?? [];
        return new class($content) {
            private $content;

            public function __construct($content)
            {
                $this->content = $content;
            }

            public function getContent()
            {
                return $this->content;
            }
        };
    }
}

namespace Omeka\Settings;

class UserSettings
{
}
