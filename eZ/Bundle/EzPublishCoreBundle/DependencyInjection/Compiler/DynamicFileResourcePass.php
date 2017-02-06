<?php
/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Disables resource tracking on dynamic configuration files.
 */
class DynamicFileResourcePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $rootDir = realpath($container->getParameter('kernel.root_dir') . '/../') . '/';
        $dynamicFiles = ['app/config/views.yml'];
        $resources = [];

        foreach ($container->getResources() as $resource) {
            $file = str_replace($rootDir, '', (string)$resource);
            if (!in_array($file, $dynamicFiles)) {
                $resources[] = $resource;
            }
        }

        $container->setResources($resources);
    }
}
