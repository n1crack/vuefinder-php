<?php

namespace Ozdemir\VueFinder\Trait;

use Ozdemir\VueFinder\Interface\FilesystemServiceInterface;
use Ozdemir\VueFinder\Interface\PathParserInterface;

/**
 * Trait for enriching file/directory nodes with metadata
 */
trait EnrichesNodeTrait
{
    use PublicLinksTrait;

    /**
     * Enrich a single node with metadata
     * 
     * @param array $node
     * @param FilesystemServiceInterface $filesystem
     * @param PathParserInterface $pathParser
     * @param array|null $publicLinks
     * @return array
     */
    protected function enrichNode(
        array $node,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        ?array $publicLinks = null
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

        $this->setPublicLinks($node, $publicLinks);

        return $node;
    }

    /**
     * Enrich multiple nodes with metadata
     * 
     * @param array $nodes
     * @param FilesystemServiceInterface $filesystem
     * @param PathParserInterface $pathParser
     * @param array|null $publicLinks
     * @return array
     */
    protected function enrichNodes(
        array $nodes,
        FilesystemServiceInterface $filesystem,
        PathParserInterface $pathParser,
        ?array $publicLinks = null
    ): array {
        return array_map(function($node) use ($filesystem, $pathParser, $publicLinks) {
            return $this->enrichNode($node, $filesystem, $pathParser, $publicLinks);
        }, $nodes);
    }
}

