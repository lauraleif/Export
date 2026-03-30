<?php

namespace Export;

use Omeka\Api\Exception\NotFoundException;

class Exporter
{
    protected $application;
    protected $fileHandle;
    protected $bibtexConfig;

    const IMPLEMENTED_FORMATS = [
        'CSV' => ['extension' => '.csv', 'mime' => 'text/csv'],
        'JSON' => ['extension' => '.json', 'mime' => 'application/json'],
        'TXT' => ['extension' => '.txt', 'mime' => 'text/plain'],
        'BibTex' => ['extension' => '.bib', 'mime' => 'application/x-bibtex'],
    ];

    public function __construct($application)
    {
        $this->application = $application;
    }

    public function downloadOne($query, $format, $type)
    {
        $services = $this->application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $resource = [];
        $resourcesQueryResult = $api->search($type, ['id' => $query])->getContent();
        if (count($resourcesQueryResult) < 1) {
            throw new NotFoundException(sprintf('No resource with id %s found', $query));
        } // @ translate
        else {
            $resources[] = $resourcesQueryResult[0];
        }

        $this->transform($resources, $format);
    }

    public function downloadBatch($query, $format, $resourceType)
    {
        $services = $this->application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $resourceIds = $query;
        foreach ($resourceIds as $resourceId) {
            $resources[] = $api->search($resourceType, ['id' => $resourceId])->getContent()[0];
        }

        $this->transform($resources, $format);
    }

    public function exportResourcesByQuery($query, $resourceType, $format)
    {
        $services = $this->application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $resources = $api->search($resourceType, $query)->getContent();

        $this->transform($resources, $format);

        return count($resources);
    }

    protected function transform($resources, $format)
    {
        if ($format == 'CSV') { // @TODO : make this better than strings
            $this->transformToCSV($resources);
        } elseif ($format == 'JSON') {
            $this->transformToJSON($resources);
        } elseif ($format == 'TXT') {
            $this->transformToTXT($resources);
        } elseif ($format == 'BibTex') {
            $this->transformToBibTex($resources);
        } else {
            fwrite($this->getFileHandle(), sprintf('Error: unknown file format : "%s."', $format));
        }
    }

    /*
     * load the JSON config of DefaultBibtexMapping.json
     * or CustomBibtexMapping.json in $this->bibtexConfig
    */
    protected function loadBibtexConfig()
    {
        $this->bibtexConfig = false;

        if (file_exists(__DIR__ . "/../dataCustomBibtexMapping.json")) {
            $this->bibtexConfig = file_get_contents(__DIR__ . "/../data/CustomBibtexMapping.json");
        }

        if (!$this->bibtexConfig) {
            if (file_exists(__DIR__ . "/../data/DefaultBibtexMapping.json")) {
                $this->bibtexConfig = file_get_contents(__DIR__ . "/../data/DefaultBibtexMapping.json");
            }
        }

        if (!$this->bibtexConfig) {
            return false;
        }

        $this->bibtexConfig = json_decode($this->bibtexConfig, true);

        return true;
    }

    protected function transformToBibTex($resources)
    {
        if (!$this->loadBibtexConfig()) {
            return false;
        }

        $resources = $this->formatData($resources);
        $bibtexEntries = [];

        foreach ($resources as $resource) {
            $typeStr = '@misc';
            $bibtexStr = $typeStr . ' {' . PHP_EOL;

            foreach ($this->bibtexConfig as $bibtexPropertyName => $bibtexPropertyConfig) {
                $propertyHandled = false;

                if (is_array($bibtexPropertyConfig) && array_key_exists(0, $bibtexPropertyConfig)) {
                    foreach ($bibtexPropertyConfig as $bibtexPropertyConfigElement) {
                        $textToAdd = $this->transformToBibtextReadMapping($bibtexPropertyConfigElement, $resource);
                        if (strlen($textToAdd) > 0) {
                            $bibtexStr .= $this->transformToBibtexOpenValueBrackets($bibtexPropertyName) . $textToAdd . ' }' . PHP_EOL;
                            $propertyHandled = true;
                            break;
                        }
                    }
                }

                if (!$propertyHandled) {
                    $textToAdd = $this->transformToBibtextReadMapping($bibtexPropertyConfig, $resource);
                    if (strlen($textToAdd) > 0) {
                        $bibtexStr .= $this->transformToBibtexOpenValueBrackets($bibtexPropertyName) . $textToAdd . ' }' . PHP_EOL;
                    }
                }
            }
            $bibtexStr .= '}' . PHP_EOL;
            $bibtexEntries[] = $bibtexStr;
        }

        $fileHandle = $this->getFileHandle();
        if ($fileHandle) {
            fwrite($fileHandle, implode('', $bibtexEntries));
        }
    }

    // reads a 'mapping' object from the json config
    // that contains info on how to map omeka properties into bibtex properties
    protected function transformToBibtextReadMapping($mappingObject, $resource)
    {
        $transformStr = '';

        if (!array_key_exists('mappings', $mappingObject)) {
            if (
                !array_key_exists('type', $mappingObject) ||
                ($mappingObject['type'] != 'accessDate' && $mappingObject['type'] != 'resourceUrl')
            ) {
                return '';
            }
        }

        if (array_key_exists('type', $mappingObject)) {
            return $this->transformToBibtextTypeHelper($mappingObject, $resource);
        }

        $separator = ' AND '; // @ translate
        if (array_key_exists('separator', $mappingObject) && is_string($mappingObject['separator'])) {
            $separator = $mappingObject['separator'];
        }

        $bFoundAtLeastOneMapping = false;
        foreach ($mappingObject['mappings'] as $mapping) {
            if (array_key_exists($mapping, $resource) && count($resource[$mapping]) > 0) {
                foreach ($resource[$mapping] as $foundMapping) {
                    if ($bFoundAtLeastOneMapping) {
                        $transformStr = $transformStr . $separator;
                    }

                    $bFoundAtLeastOneMapping = true;
                    $transformStr = $transformStr . $this->transformToBibtexEscapeString($this->extractStringValueFromProperty($foundMapping));
                }
            }
        }

        return $transformStr;
    }

    // read specified type in mapping object to do something custom with it
    protected function transformToBibtextTypeHelper($mappingObject, $resource)
    {
        $transformStr = '';

        $type = $mappingObject['type'];

        $separator = ' AND '; // @ translate
        if (array_key_exists('separator', $mappingObject) && is_string($mappingObject['separator'])) {
            $separator = $mappingObject['separator'];
        }

        if ($type == "month") {
            if (!is_array($mappingObject["mappings"]) || !(count($mappingObject["mappings"]) > 0)) {
                return '';
            }

            $date = false;
            $mapping = $mappingObject["mappings"][0];
            if (array_key_exists($mapping, $resource)) {
                $bIsFirst = true;
                foreach ($resource[$mapping] as $propertyElement) {
                    $value = $this->transformToBibtexEscapeString($this->extractStringValueFromProperty($propertyElement));
                    $date = date_create($value);
                    if (!$date) {
                        continue;
                    }
                    if (!$bIsFirst) {
                        $transformStr = $transformStr . $separator;
                    }

                    $transformStr = $transformStr . date_format($date, 'M');

                    $isFirst = false;
                }
            }

            return '';
        } elseif ($type == "year") {
            if (!is_array($mappingObject["mappings"]) || !(count($mappingObject["mappings"]) > 0)) {
                return '';
            }

            $date = false;
            $mapping = $mappingObject["mappings"][0];
            if (array_key_exists($mapping, $resource)) {
                $bIsFirst = true;
                foreach ($resource[$mapping] as $propertyElement) {
                    $value = $this->extractStringValueFromProperty($propertyElement);

                    // returns false if it fails
                    $date = date_create($value);
                    if (!$date) {
                        continue;
                    }

                    if (!$bIsFirst) {
                        $transformStr = $transformStr . $separator;
                    }

                    $transformStr = $transformStr . date_format($date, 'Y');

                    $isFirst = false;
                }
            }
        } elseif ($type == "format") {
            // user must have specified a format
            if (!array_key_exists('format', $mappingObject)) {
                return '';
            }

            $args = [];
            foreach ($mappingObject['mappings'] as $mapping) {

                // every argument must exist
                if (!array_key_exists($mapping, $resource)) {
                    return '';
                }

                $currentArg = '';

                foreach ($resource[$mapping] as $resourceValue) {
                    // normally there should be only one value per mapping but just in case...
                    if ($currentArg != '') {
                        $currentArg = $currentArg . $separator;
                    } // @ translate

                    $currentArg = $currentArg . $this->transformToBibtexEscapeString($this->extractStringValueFromProperty($resourceValue));
                }

                // add the current arg to the list of args
                $args[] = $currentArg;
            }

            // format may be ill-formated by user so be safe
            try {
                $transformStr = vsprintf($mappingObject['format'], $args);
            } catch (\ValueError $e) {
                return '';
            }
        } elseif ($type == "resourceUrl") {
            return sprintf("\\url{%s}", $resource["@id"]);
        } elseif ($type == "accessDate") {
            return sprintf(
                "Accessed on: %s", // @translate
                date_format(date_create(), 'Y-d-m')
            );
        }

        return $transformStr;
    }

    // print "MyValue   = {" with the right spaces
    protected function transformToBibtexOpenValueBrackets($name)
    {
        $printedOut = "\t" . $name . ' = { ';
        return $printedOut;
    }

    // transform éric in {/'e}ric for example
    protected function transformToBibtexEscapeString($string)
    {
        $escapedString = "";

        $map = [
            "#",
            "\\#",
            "$",
            "\\$",
            "%",
            "\\%",
            "&",
            "\\&",
            "~",
            "\\~{}",
            "_",
            "\_",
            "^",
            "\\^{}",
            "\\",
            "\\textbackslash",
            "{",
            "\\{",
            "}",
            "\\}",
            "é",
            "\\'{e}",
            "É",
            "\\'{E}",
            "È",
            "\\`{E}",
            "Ê",
            "\\^{E}",
            "ê",
            "\\^{e}",
            "è",
            "\\`{e}",
            "ë",
            "\\\"{e}",
            "Ë",
            "\\\"{E}",
            "à",
            "\\`{a}",
            "á",
            "\\'{a}",
            "â",
            "\\^{a}",
            "À",
            "\\`{A}",
            "Â",
            "\\^{A}",
            "Á",
            "\\'{A}",
            "ä",
            "\\\"{a}",
            "Ä",
            "\\\"{A}",
            "о́",
            "\\'{o}",
            "ò",
            "\\`{o}",
            "ô",
            "\\^{o}",
            "ö",
            "\\\"{o}",
            "Ö",
            "\\\"{O}",
            "Ó",
            "\\'{O}",
            "Ò",
            "\\`{O}",
            "Ô",
            "\\^{O}",
            "ù",
            "\\`{u}",
            "û",
            "\\^{u}",
            "ú",
            "\\'{u}",
            "ü",
            "\\\"{u}",
            "Ü",
            "\\\"{U}",
            "Ú",
            "\\'{U}",
            "Ù",
            "\\`{U}",
            "Û",
            "\\^{U}",
            "î",
            "\\^{i}",
            "í",
            "\\'{i}",
            "ì",
            "\\`{i}",
            "ï",
            "\\\"{i}",
            "Ï",
            "\\\"{I}",
            "Î",
            "\\^{I}",
            "Ì",
            "\\`{I}",
            "Í",
            "\\'{I}",
            "ŷ",
            "\\^{y}",
            "ý",
            "\\'{y}",
            "ỳ",
            "\\`{y}",
            "ÿ",
            "\\\"{y}",
            "Ÿ",
            "\\\"{Y}",
            "Ŷ",
            "\\^{Y}",
            "Ý",
            "\\'{Y}",
            "Ỳ",
            "\\`{Y}",
            "å",
            "\\aa",
            "Å",
            "\\AA",
            "œ",
            "\\oe",
            "Œ",
            "\\OE",
            "æ",
            "\\ae",
            "Æ",
            "\\AE",
            "ß",
            "\\ss",
            "ẞ",
            "\\SS",
            "ø",
            "\\o",
            "Ø",
            "\\O",
            "ł",
            "\\l",
            "Ł",
            "\\L",
        ];

        $bFound = false;

        foreach (mb_str_split($string) as $char) {
            for ($i = 0; $i < count($map); $i = $i + 2) {
                if ($char == $map[$i]) {
                    $escapedString = $escapedString . $map[$i + 1];
                    $bFound = true;
                    break;
                }
            }
            if (!$bFound) {
                $escapedString = $escapedString . $char;
            }
        }
        return $escapedString;
    }

    protected function transformToJSON($items)
    {
        // the API already returns JSON
        fwrite($this->getFileHandle(), json_encode($items));
    }

    protected function transformToTXT($resources)
    {
        // string that will be printed out to file
        $resourcesStr = '';

        $resources = $this->formatData($resources);

        $services = $this->application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        foreach ($resources as $resource) {
            // if not first resource we're printing, skip a line
            if ($resourcesStr != '') {
                $resourcesStr = $resourcesStr . PHP_EOL;
            }

            // look for resource template, in case property labels are renamed by it
            $bHasResourceTemplate = false;
            $resourceTemplate = null;

            if (array_key_exists('o:resource_template', $resource) && !is_null($resource['o:resource_template'])) {
                $bHasResourceTemplate = true;

                $resourceTemplate = $this->formatData($api->search(
                    'resource_templates',
                    ['id' => $resource['o:resource_template']['o:id']]
                )
                    ->getContent()[0]);
            }

            // looping through properties to find something like dcterms:title
            // properties like dcterms:title are an array of all the values of the corresponding property
            // with each element of the array containing 'property_id' and 'property_label'
            foreach ($resource as $property_name => $propertyValue) {
                if (is_array($propertyValue)) {
                    // keep track of whether or no this is the first value of one property we're printin out
                    // because in that case we need to print 'PropertyLabel = ' and then we need separators
                    // ex: 'Date = 01-01-01 ; 01-02-01'
                    $bIsFirstPropertyOfLabel = true;
                    $bIsProperty = false;

                    foreach ($propertyValue as $propertyValueElement) {
                        if (
                            is_array($propertyValueElement) &&
                            array_key_exists('property_id', $propertyValueElement)
                            && array_key_exists('property_label', $propertyValueElement)
                        ) {
                            $bIsProperty = true;

                            // we found a property like dcterms:title!

                            if ($bIsFirstPropertyOfLabel) {

                                // if first value of property, then add the "Title = " text.
                                // we must first check if the label was renamed or not by the resource template
                                if ($bHasResourceTemplate) {
                                    $correspondingResourceTemplateProperty = null;
                                    foreach ($resourceTemplate['o:resource_template_property'] as $resourceTemplateProperty) {
                                        if ($resourceTemplateProperty['o:property']['o:id'] == $propertyValueElement['property_id']) {
                                            if (
                                                array_key_exists('o:alternate_label', $resourceTemplateProperty) &&
                                                $resourceTemplateProperty['o:alternate_label']
                                            ) {
                                                $correspondingResourceTemplateProperty = $resourceTemplateProperty;
                                            }
                                            break;
                                        }
                                    }
                                    if (!is_null($correspondingResourceTemplateProperty)) {
                                        $resourcesStr = $resourcesStr . $correspondingResourceTemplateProperty['o:alternate_label'] . ' = ';
                                    } else {
                                        $resourcesStr = $resourcesStr . $propertyValueElement['property_label'] . ' = ';
                                    }
                                } else {
                                    $resourcesStr = $resourcesStr . $propertyValueElement['property_label'] . ' = ';
                                }

                                $bIsFirstPropertyOfLabel = false;
                            } else {
                                $resourcesStr = $resourcesStr . " ; ";
                            }
                            $resourcesStr = $resourcesStr . $this->extractStringValueFromProperty($propertyValueElement);
                        }
                    }
                    if ($bIsProperty) {
                        $resourcesStr = $resourcesStr . PHP_EOL;
                    }
                }
            }
        }

        fwrite($this->getFileHandle(), $resourcesStr);
    }

    // takes in a json (with arrays only) representing a resource property element like dcterms::title[0]
    // and extracts its value (so like "My Great Book" or if it's a URL then the label of the URL, etc.)
    protected function extractStringValueFromProperty($property)
    {
        if ($property['type'] == "literal") {
            return $property['@value'];
        } elseif ($property['type'] == "uri") {
            return $property['o:label'];
        } elseif ($property['type'] == "resource") {
            return $property['display_title'];
        } elseif (
            str_contains($property['type'], "customvocab") ||
            str_contains($property['type'], "valuesuggest")
        ) {
            if (array_key_exists('@value', $property)) {
                return $property['@value'];
            } elseif (array_key_exists('o:label', $property)) {
                return $property['o:label'];
            } elseif (array_key_exists('display_title', $property)) {
                return $property['display_title'];
            }
        }

        return '';
    }

    protected function transformToCSV($resources)
    {
        $resources = $this->formatData($resources, true);
        $itemMedia = [];
        foreach ($resources as $resource) {
            if (array_key_exists('o:media', $resource) && !empty($resource['o:media'])) {
                $mediaIds = $resource['o:media'];
                $mediaOut = "";
                $mediaJson = "";
                foreach ($mediaIds as $mediaId) {
                    $id = $mediaId['o:id'];
                    $media = $this->getData($id, 'id', 'media');
                    foreach ($media as $medium) {
                        $mediaOut = $mediaOut . $medium['o:filename'] . ";";
                        $mediaJson = $mediaJson . json_encode($medium) . ";";
                        $resource['media:link'] = $mediaOut;
                    }
                }
            } else {
                $resource['media:link'] = "";
            }
            array_push($itemMedia, $resource);
        }

        $properties = $this->getData("", 'term', 'properties');
        $propertyNames = [];
        foreach ($properties as $property) {
            $propertyNames[] = $property['o:term'];
        }

        $collection = $itemMedia;
        $resultCount = count($collection);
        if ($resultCount === 0) {
            return;
        }

        $collectionHeaders = array_keys($collection[0]);
        $header = $collectionHeaders;
        foreach ($propertyNames as $p) {
            if (!in_array($p, $header, true)) {
                $header[] = $p;
            }
        }

        $temp = tmpfile();
        $usedColumns = array_fill_keys($header, false);

        foreach ($collection as $item) {
            if (!is_array($item)) {
                continue;
            }
            $outputItem = [];
            foreach ($header as $column) {
                if (array_key_exists($column, $item)) {
                    $row = $item[$column];
                    if (is_array($row)) {
                        if (isset($row['@id'])) {
                            $labelEntity = $this->getLabelFromRepresentation($row['@id']);
                            if (isset($labelEntity)) {
                                $valueToPush = $labelEntity;
                            }
                        } elseif (array_key_exists('@value', $row)) {
                            $valueToPush = $row['@value'];
                        } else {
                            $multiRow = "";
                            foreach ($row as $single) {
                                if (is_array($single)) {
                                    if (isset($single['@id'])) {
                                        $labelEntity = $this->getLabelFromRepresentation($single['@id']);
                                        if ($labelEntity) {
                                            $multiRow .= ";" . $labelEntity;
                                        }
                                    } elseif (array_key_exists('@value', $single)) {
                                        $multiRow .= ";" . $single['@value'];
                                    }
                                } else {
                                    $multiRow .= ";" . $single;
                                }
                            }
                            $valueToPush = ltrim($multiRow, ";");
                        }
                    } else {
                        $valueToPush = $row;
                    }
                } else {
                    $valueToPush = "";
                }

                if (is_string($valueToPush)) {
                    if (trim($valueToPush) !== "") {
                        $usedColumns[$column] = true;
                    }
                } elseif ($valueToPush !== null && $valueToPush !== "") {
                    $usedColumns[$column] = true;
                }

                $outputItem[] = $valueToPush;
            }
            fputcsv($temp, $outputItem);
        }

        rewind($temp);

        $finalHeader = [];
        $keepIndexes = [];
        foreach ($header as $i => $col) {
            if (!empty($usedColumns[$col])) {
                $finalHeader[] = $col;
                $keepIndexes[] = $i;
            }
        }

        $finalOutput = $this->getFileHandle();
        fputcsv($finalOutput, $finalHeader);

        while (($row = fgetcsv($temp)) !== false) {
            $filteredRow = [];
            foreach ($keepIndexes as $idx) {
                $filteredRow[] = array_key_exists($idx, $row) ? $row[$idx] : "";
            }
            fputcsv($finalOutput, $filteredRow);
        }

        fclose($temp);
    }

    protected function getData($criteria, $field, $type)
    {
        $services = $this->application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');

        $query[$field] = $criteria;
        $resources = $api->search($type, $query)->getContent();
        $out = $this->formatData($resources);
        return $out;
    }

    protected function formatData($rawData, $needCleanUp = false)
    {
        $arr = json_encode($rawData, true);
        $resources = json_decode($arr, true);
        if ($needCleanUp) {
            $resources = $this->cleanUp($resources);
        }
        return $resources;
    }

    public function setFileHandle($fileHandle)
    {
        $this->fileHandle = $fileHandle;
    }

    public function getFileHandle()
    {
        return $this->fileHandle;
    }

    private function cleanUp($resources)
    {
        $keysToClean = ['@context', 'o:primary_media', 'o:thumbnail', 'thumbnail_display_urls', 'o:owner'];
        foreach ($resources as &$resource) {
            foreach ($keysToClean as $key) {
                unset($resource[$key]);
            }
            if (is_array($resource['@type'])) {
                foreach ($resource['@type'] as $key => $data) {
                    if (strpos($data, 'dctype:') === 0) {
                        unset($resource['@type'][$key]);
                        break;
                    }
                }
            }
        }
        return $resources;
    }

    private function getLabelFromRepresentation($apiString)
    {
        $services = $this->application->getServiceManager();
        $api = $services->get('Omeka\ApiManager');
        $logger = $services->get('Omeka\Logger');

        $target = explode("api/", $apiString);
        if (!isset($target[1])) {
            return null;
        }
        $representationInfo = explode('/', $target[1]);
        $targetRepresentation = $representationInfo[0];
        $id = $representationInfo[1];
        if ($targetRepresentation == 'media') {
            return $id;
        }

        $request = $api->read($targetRepresentation, $id);
        if (!isset($request)) {
            $logger->warn(sprintf('Request failed for %s (%d)', $targetRepresentation, $id));
            return null;
        }
        $response = $request->getContent();

        $methods = ['title', 'label'];
        foreach ($methods as $method) {
            if (method_exists($response, $method)) {
                $value = $response->$method();
                if ($value !== null) {
                    return $value;
                }
            }
        }

        return null;
    }
}
