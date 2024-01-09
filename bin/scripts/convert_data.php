<?php
/*

formate wandeln

https://stackoverflow.com/questions/5733041/convert-associative-array-to-xml-in-php
*/

function xml_to_array($file){
	$ob = simplexml_load_file($file);
	$json = json_encode($ob);
	return json_decode($json, true);
}

function array_to_xml($data){
	$dataTransformator = new DataTransformator();
	$domDocument = $dataTransformator->data2domDocument($data);
	$xml = $domDocument->saveXML();
	return $xml;
}


class DataTransformator {

    /**
     * Converts the $data to a \DOMDocument.
     * @param array $data
     * @param string $rootElementName
     * @param string $defaultElementName
     * @see MyNamespace\Dom\DataTransformator#data2domNode(...)
     * @return Ambigous <DOMDocument>
     */
    public function data2domDocument(array $data, $rootElementName = 'data', $defaultElementName = 'item') {
        return $this->data2domNode($data, $rootElementName, null, $defaultElementName);
    }

    /**
     * Converts the $data to a \DOMNode.
     * If the $elementContent is a string,
     * a DOMNode with a nested shallow DOMElement
     * will be (created if the argument $node is null and) returned.
     * If the $elementContent is an array,
     * the function will applied on every its element recursively and
     * a DOMNode with a nested DOMElements
     * will be (created if the argument $node is null and) returned.
     * The end result is always a DOMDocument object.
     * The casue is, that a \DOMElement object
     * "is read only. It may be appended to a document,
     * but additional nodes may not be appended to this node
     * until the node is associated with a document."
     * See {@link http://php.net/manual/en/domelement.construct.php here}).
     * 
     * @param Ambigous <string, mixed> $elementName Used as element tagname. If it's not a string $defaultElementName is used instead.
     * @param Ambigous <string, array> $elementContent
     * @param Ambigous <\DOMDocument, NULL, \DOMElement> $parentNode The parent node is
     *  either a \DOMDocument (by the method calls from outside of the method)
     *  or a \DOMElement or NULL (by the calls from inside).
     *  Once again: For the calls from outside of the method the argument MUST be either a \DOMDocument object or NULL.
     * @param string $defaultElementName If the key of the array element is a string, it determines the DOM element name / tagname.
     *  For numeric indexes the $defaultElementName is used.
     * @return \DOMDocument
     */
    protected function data2domNode($elementContent, $elementName, \DOMNode $parentNode = null, $defaultElementName = 'item') {
        $parentNode = is_null($parentNode) ? new \DOMDocument('1.0', 'utf-8') : $parentNode;
        $name = is_string($elementName) ? $elementName : $defaultElementName;
        if (!is_array($elementContent)) {
            $content = htmlspecialchars($elementContent);
            $element = new \DOMElement($name, $content);
            $parentNode->appendChild($element);
        } else {
            $element = new \DOMElement($name);
            $parentNode->appendChild($element);
            foreach ($elementContent as $key => $value) {
                $elementChild = $this->data2domNode($value, $key, $element);
                $parentNode->appendChild($elementChild);
            }
        }
        return $parentNode;
    }
}