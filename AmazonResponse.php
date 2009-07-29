<?php
class AmazonResponseException extends Exception {}

class AmazonResponse {
    
    private $doc;
    private $items;

    public function __construct($xml) {
        
        $this->build_dom($xml);
        
    }

    public function getItems() {
        if(!$this->items)
            $this->items = $this->build_items($this->doc);

        return $this->items;
    }

    private function build_dom($xml) {
        $doc = DOMDocument::loadXML($xml);
        if(!$doc) throw new AmazonResponseException('Error: unable to create dom');
        
        $this->doc = $doc;
    }

    private function build_items($doc) { 
        $item_tags = $doc->getElementsByTagName('Item');
        $items = array();
        if($item_tags->length) {
            for($a = 0; $a < $item_tags->length; $a++) {
                $item = array();
                foreach($item_tags->item($a)->childNodes as $node) {
                    $item[$node->nodeName] = $this->build_attributes($node);
                }
                $items[] = $item;
            }
        }
        
        return $items;
    }

    private function build_attributes($node) {
        if($node->nodeType == XML_TEXT_NODE)
            return $node->nodeValue;
        
        $item = NULL;
        $child_length = 0;
        
        if($node->hasChildNodes())
            $child_length = $node->childNodes->length;
        
        if($node->nodeType == XML_ELEMENT_NODE && $child_length == 1) {
            return $node->nodeValue;
        } else if($child_length > 1) {
            foreach($node->childNodes as $cnode) {
                $item[$cnode->nodeName] = $this->build_attributes($cnode);
            }
        }
        
        return $item;
    }

}