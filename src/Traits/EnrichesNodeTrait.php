<?php

namespace Ozdemir\VueFinder\Traits;

use Ozdemir\VueFinder\Contracts\FilesystemServiceInterface;
use Ozdemir\VueFinder\Contracts\PathParserInterface;
use Ozdemir\VueFinder\Contracts\UrlResolverInterface;

/**
 * Trait for enriching file/directory nodes with metadata
 */
trait EnrichesNodeTrait
{
    /**
     * Enrich a single node with metadata
     * 
     * @param array $node
     * @param FilesystemServiceInterface $filesystem
     * @param PathParserInterface $pathParser
     * @param UrlResolverInterface|null $urlResolver
     * @return array
     */
    protected function enrichNode(
        array $node,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        ?UrlResolverInterface $urlResolver = null
    ): array {
        $node['basename'] = basename($node['path']);
        $node['extension'] = pathinfo($node['path'], PATHINFO_EXTENSION);

        if ($node['type'] != 'dir' && $node['extension']) {
            try {
                $node['mime_type'] = $filesystem->mimeType($node['path']);
            } catch (\Exception $exception) {
                // MIME type detection failed
            }
        }

        // Add public URL if resolver is available
        if ($urlResolver && $urlResolver->shouldHavePublicUrl($node['path'])) {
            $publicUrl = $urlResolver->resolveUrl($node['path']);
            if ($publicUrl) {
                $node['url'] = $publicUrl;
            }
        }

        return $node;
    }

    /**
     * Enrich multiple nodes with metadata
     * 
     * @param array $nodes
     * @param FilesystemServiceInterface $filesystem
     * @param PathParserInterface $pathParser
     * @param UrlResolverInterface|null $urlResolver
     * @return array
     */
    protected function enrichNodes(
        array $nodes,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        ?UrlResolverInterface $urlResolver = null
    ): array {
        return array_map(function($node) use ($filesystem, $pathParser, $urlResolver) {
            return $this->enrichNode($node, $filesystem, $pathParser, $urlResolver);
        }, $nodes);
    }
}

