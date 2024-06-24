<?php
// Create a new SimpleXMLElement
$newsXML = new SimpleXMLElement("<news></news>");
$newsXML->addAttribute('title', 'value goes here');

// Add a child element with an attribute
$newsIntro = $newsXML->addChild('content');
$newsIntro->addAttribute('type', 'latest');

// Save the XML to a file (optional)
$newsXML->asXML('project.xml');

// Output the XML with proper formatting using DOMDocument
Header('Content-type: text/xml');

// Load the XML from SimpleXMLElement into a string
$xmlString = $newsXML->asXML();

// Use DOMDocument to format the output
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$doc->formatOutput = true;
$doc->loadXML($xmlString);

// Print the formatted XML
echo $doc->saveXML();
?>
