<?php
/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;

    $dev = MODX_BASE_PATH . 'Extras/FacetSearch/';
    /** @var xPDOCacheManager $cache */
    $cache = $modx->getCacheManager();
    if (file_exists($dev) && $cache) {
        if (!is_link($dev . 'assets/components/facetsearch')) {
            $cache->deleteTree(
                $dev . 'assets/components/facetsearch/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_ASSETS_PATH . 'components/facetsearch/', $dev . 'assets/components/facetsearch');
        }
        if (!is_link($dev . 'core/components/facetsearch')) {
            $cache->deleteTree(
                $dev . 'core/components/facetsearch/',
                ['deleteTop' => true, 'skipDirs' => false, 'extensions' => []]
            );
            symlink(MODX_CORE_PATH . 'components/facetsearch/', $dev . 'core/components/facetsearch');
        }
    }
}

return true;