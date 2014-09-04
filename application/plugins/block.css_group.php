<?php

function smarty_block_css_group($params, $content, $template, &$repeat) {
    // On open tag, do nothing
    if ($repeat)
        return;

    $CI = & get_instance();
    $CI->load->library('assetic');

    // Clear the assets (so we don't include the assets of previous groups)
    $CI->assetic->clear();

    $output = '';
    $environment = defined('ENVIRONMENT') ? ENVIRONMENT : 'development';
    $html = new DOMDocument();
    $group = $params['name'];

    $html->loadHTML($content);

    foreach ($html->getElementsByTagName('*') as $element)
    	if ($element->nodeName == 'link') {
        	$href = $element->attributes->getNamedItem('href');
        	$CI->assetic->add_css($href->nodeValue, $group);
    	} else if ($element->nodeName == 'style')
    		$CI->assetic->add_style($element->nodeValue, $group);

    $result = $CI->assetic->get_css_assets($environment == 'development');
    foreach ($result as $item)
        if (isset($item['url']))
            $output .= '<link rel="stylesheet" type="text/css" href="' . $item['url'] . '" />' . PHP_EOL;
        else
            $output .= '<style>' . $item['content'] . '</style>' . PHP_EOL;

    return $output;
}