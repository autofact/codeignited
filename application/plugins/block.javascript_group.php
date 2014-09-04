<?php

function smarty_block_javascript_group($params, $content, $template, &$repeat) {
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

    foreach ($html->getElementsByTagName('script') as $element) {
        $src = $element->attributes->getNamedItem('src');
        if (isset($src))
            $CI->assetic->add_js($src->nodeValue, $group);
        else
            $CI->assetic->add_script($element->nodeValue, $group);
    }

    $result = $CI->assetic->get_javascript_assets($environment == 'development');
    foreach ($result as $item)
        if (isset($item['url']))
            $output .= '<script type="text/javascript" src="' . $item['url'] . '"></script>' . PHP_EOL;
        else
            $output .= '<script type="text/javascript">' . $item['content'] . '</script>' . PHP_EOL;

    return $output;
}