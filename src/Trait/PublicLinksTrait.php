<?php

namespace Ozdemir\VueFinder\Trait;

/**
 * Trait for handling public links
 */
trait PublicLinksTrait
{
    /**
     * Set public links for a node
     * 
     * @param array $node
     * @param array|null $publicLinks
     * @return void
     */
    protected function setPublicLinks(array &$node, ?array $publicLinks): void
    {
        if (!$publicLinks || $node['type'] == 'dir') {
            return;
        }

        foreach ($publicLinks as $publicLink => $domain) {
            $publicLink = str_replace('/', '\/', $publicLink);

            if (preg_match('/^'.$publicLink.'/i', $node['path'])) {
                $node['url'] = preg_replace('/^'.$publicLink.'/i', $domain, $node['path']);
            }
        }
    }
}

