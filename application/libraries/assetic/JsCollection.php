<?php
use Assetic\Asset\AssetCollection;
use Assetic\Filter\FilterInterface;

/*
 * We need this class because if you use JsMinPlus, it'd remove the last ";" from
 * every script. Then when the `dump` method of Assetic's AssetCollection concatenates
 * all your scripts you'd be fucked up. So whe use this one to concatenate using ";"
 */
class JsCollection extends AssetCollection {
    
    public function dump(FilterInterface $additionalFilter = null) {
        // loop through leaves and dump each asset
        $parts = array();
        foreach ($this as $asset)
            $parts[] = $asset->dump($additionalFilter);
    
        return implode(';', $parts);
    }
    
}
