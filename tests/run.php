<?php
error_reporting(E_ALL);
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

require __DIR__ . '/framework-doubles.php';
require __DIR__ . '/../src/Controller/IndexController.php';

$tests = [];

function check($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function controller($method, array $post = [])
{
    $api = new Omeka\Api\Manager;
    $controller = new Export\Controller\IndexController([], $api, new Omeka\Settings\UserSettings, null);
    $controller->request = new class($method, $post) {
        private $method;
        private $post;

        public function __construct($method, $post)
        {
            $this->method = $method;
            $this->post = $post;
        }

        public function isPost()
        {
            return $this->method === 'POST';
        }

        public function getPost($name, $default = null)
        {
            return $this->post[$name] ?? $default;
        }
    };
    $controller->response = new class {
        public $status = 200;
        public $content = '';
        public $headers = [];

        public function setStatusCode($status)
        {
            $this->status = $status;
            return $this;
        }

        public function setContent($content)
        {
            $this->content = $content;
            return $this;
        }

        public function getHeaders()
        {
            return $this;
        }

        public function addHeaderLine($name, $value)
        {
            $this->headers[$name] = $value;
            return $this;
        }
    };
    return [$controller, $api];
}

function renderCsv(array $collection, array $properties)
{
    ob_start();
    try {
        include __DIR__ . '/../view/export/index/download.phtml';
        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

function csvRows($csv)
{
    $stream = fopen('php://memory', 'w+');
    fwrite($stream, $csv);
    rewind($stream);
    $rows = [];
    while (($row = fgetcsv($stream, 0, ',', '"', '')) !== false) {
        $rows[] = $row;
    }
    fclose($stream);
    return $rows;
}

$tests['GET is rejected before querying items'] = function () {
    [$controller, $api] = controller('GET');
    $response = $controller->downloadAction();
    check($response === $controller->response, 'Expected an HTTP response');
    check($response->status === 405, 'Expected HTTP 405');
    check(($response->headers['Allow'] ?? null) === 'POST', 'Expected Allow: POST');
    check($api->calls === [], 'Invalid requests must not query the API');
};

foreach ([null, '', 'abc', '0', '-1', '1.5', '1e2', [], ['1'], true, false, 1.5, (string) PHP_INT_MAX . '0'] as $index => $invalid) {
    $tests['invalid item-set selection ' . $index . ' is rejected'] = function () use ($invalid) {
        [$controller, $api] = controller('POST', ['add_to_item_set' => $invalid]);
        $_REQUEST = ['add_to_item_set' => $invalid];
        try {
            $response = $controller->downloadAction();
        } finally {
            $_REQUEST = [];
        }
        check($response === $controller->response && $response->status === 400, 'Expected HTTP 400');
        check($api->calls === [], 'Invalid selection must not query the API');
    };
}

$tests['missing POST selection cannot fall back to request parameters'] = function () {
    [$controller, $api] = controller('POST');
    $_REQUEST = ['add_to_item_set' => '12'];
    try {
        $response = $controller->downloadAction();
    } finally {
        $_REQUEST = [];
    }
    check($response === $controller->response && $response->status === 400, 'Expected HTTP 400');
    check($api->calls === [], 'Missing POST selection must not query the API');
};

$tests['POST selection controls the exported item set'] = function () {
    [$controller, $api] = controller('POST', ['add_to_item_set' => '12']);
    // Query-string/cookie values must not override the submitted selection.
    $_REQUEST = ['add_to_item_set' => '999'];
    $api->results = [
        'items' => [['o:id' => 7, 'o:media' => [['o:id' => 8]]]],
        'media' => [['o:id' => 8, 'o:filename' => 'image.jpg']],
        'properties' => [['o:term' => 'dcterms:title']],
    ];
    try {
        $view = $controller->downloadAction();
    } finally {
        $_REQUEST = [];
    }
    check($api->calls === [
        ['items', ['item_set_id' => 12]],
        ['media', ['id' => 8]],
        ['properties', ['term' => '']],
    ], 'Expected API queries scoped to the POST selection');
    check($view->terminal, 'Download must bypass the HTML layout');
    check($view->variables['collection'][0]['media:link'] === 'image.jpg;', 'Media filename was lost');
    check($view->variables['properties'] === ['dcterms:title'], 'Property list was lost');
};

$tests['items without media export empty media columns'] = function () {
    [$controller, $api] = controller('POST', ['add_to_item_set' => 12]);
    $api->results['items'] = [['o:id' => 7, 'o:media' => []]];
    $view = $controller->downloadAction();
    $item = $view->variables['collection'][0];
    check($item['media:link'] === '' && $item['media:full'] === '', 'Expected empty media columns');
    check($api->calls === [['items', ['item_set_id' => 12]], ['properties', ['term' => '']]], 'Unexpected API query');
};

$tests['CSV has unique headers and a labelled JSON column'] = function () {
    $rows = csvRows(renderCsv([
        ['o:id' => 1, 'dcterms:title' => [['@value' => 'First']]],
        ['o:id' => 2, 'dcterms:description' => [['@value' => 'Later property']]],
    ], ['dcterms:title', 'dcterms:description']));
    check($rows[0] === ['o:id', 'dcterms:title', 'dcterms:description', 'item:full'], 'Unexpected or duplicate CSV headers');
    check($rows[1][0] === '1' && $rows[1][1] === 'First' && $rows[1][2] === '', 'First row is misaligned');
    check($rows[2][0] === '2' && $rows[2][1] === '' && $rows[2][2] === 'Later property', 'Later property was lost');
    foreach (array_slice($rows, 1) as $row) {
        check(count($row) === 4, 'CSV row width does not match its header');
        check(json_decode($row[3], true)['o:id'] === (int) $row[0], 'JSON column does not describe the item');
    }
};

$tests['CSV round-trips quotes, backslashes, commas and newlines'] = function () {
    $value = "A comma, a quote \" and a backslash\\\"\nSecond line";
    $rows = csvRows(renderCsv([['o:id' => 1, 'dcterms:title' => [['@value' => $value]]]], ['dcterms:title']));
    check($rows[1][1] === $value, 'CSV escaping corrupted the literal value');
};

$tests['custom vocabulary properties cannot collide with the JSON header'] = function () {
    $rows = csvRows(renderCsv([[
        'o:id' => 1,
        'item:full' => [['@value' => 'Custom property']],
        'item:full:json' => [['@value' => 'Another custom property']],
    ]], ['item:full', 'item:full:json']));
    check($rows[0] === ['o:id', 'item:full', 'item:full:json', 'item:full:json:json'], 'Generated JSON header collides with a property');
    check($rows[1][1] === 'Custom property' && $rows[1][2] === 'Another custom property', 'Custom properties were lost');
    check(json_decode($rows[1][3], true)['o:id'] === 1, 'Expected item JSON in the final column');
};

$tests['CSV retains multiple values and separates item JSON from media JSON'] = function () {
    $rows = csvRows(renderCsv([[
        'o:id' => 1,
        'dcterms:title' => [['@value' => 'First'], ['@value' => 'Second']],
        'media:full' => '{"o:id":8};',
    ]], ['dcterms:title']));
    check($rows[0] === ['o:id', 'dcterms:title', 'media:full', 'item:full'], 'Unexpected headers');
    check($rows[1][1] === 'First;Second', 'Multiple values were lost');
    check($rows[1][2] === '{"o:id":8};', 'Media JSON was lost');
    $item = json_decode($rows[1][3], true);
    check(!array_key_exists('media:full', $item), 'Media JSON should remain in its own column');
    check(count($item['dcterms:title']) === 2, 'Full item JSON lost values');
};

$tests['empty item set retains the no-results response'] = function () {
    check(strpos(renderCsv([], []), 'No results.') === 0, 'Expected the no-results message');
};

$tests['production PHP compiles without warnings or deprecations'] = function () {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__), FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $path = $file->getPathname();
        if (strpos($path, DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR) !== false
            || !in_array($file->getExtension(), ['php', 'phtml'], true)) {
            continue;
        }
        $output = [];
        exec(escapeshellarg(PHP_BINARY) . ' -d error_reporting=-1 -d display_errors=1 -l ' . escapeshellarg($path) . ' 2>&1', $output, $status);
        $result = implode("\n", $output);
        check($status === 0 && !preg_match('/Deprecated:|Warning:|Fatal error:|Parse error:/', $result), $result);
    }
};

// Buffer runner output so repeated template header() calls remain valid in CLI.
ob_start();
$failed = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "PASS: $name\n";
    } catch (Throwable $error) {
        ++$failed;
        echo "FAIL: $name: ", $error->getMessage(), "\n";
    }
}
echo count($tests), " tests, $failed failures\n";
ob_end_flush();
exit($failed ? 1 : 0);
