<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Zed\ComposerConstrainer\Business\Finder\SourceFinder;

use Generated\Shared\Transfer\UsedModuleCollectionTransfer;
use Generated\Shared\Transfer\UsedModuleTransfer;
use SprykerSdk\Zed\ComposerConstrainer\Business\Finder\UsedModuleFinderInterface;
use SprykerSdk\Zed\ComposerConstrainer\ComposerConstrainerConfig;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class SourceFinder implements UsedModuleFinderInterface
{
    /**
     * @var \SprykerSdk\Zed\ComposerConstrainer\ComposerConstrainerConfig
     */
    protected $config;

    /**
     * @param \SprykerSdk\Zed\ComposerConstrainer\ComposerConstrainerConfig $config
     */
    public function __construct(ComposerConstrainerConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return \Generated\Shared\Transfer\UsedModuleCollectionTransfer
     */
    public function find(): UsedModuleCollectionTransfer
    {
        $usedModuleCollectionTransfer = new UsedModuleCollectionTransfer();

        if (!is_dir($this->config->getSourceDirectory())) {
            return $usedModuleCollectionTransfer;
        }

        foreach ($this->createFinder() as $splFileInfo) {
            $usedModuleCollectionTransfer = $this->addUsedModules($usedModuleCollectionTransfer, $splFileInfo);
        }

        return $usedModuleCollectionTransfer;
    }

    /**
     * @return \Symfony\Component\Finder\Finder|\Symfony\Component\Finder\SplFileInfo[]
     */
    protected function createFinder(): Finder
    {
        return (new Finder())->files()->in($this->config->getSourceDirectory());
    }

    /**
     * @param \Generated\Shared\Transfer\UsedModuleCollectionTransfer $usedModuleCollectionTransfer
     * @param \Symfony\Component\Finder\SplFileInfo $splFileInfo
     *
     * @return \Generated\Shared\Transfer\UsedModuleCollectionTransfer
     */
    protected function addUsedModules(UsedModuleCollectionTransfer $usedModuleCollectionTransfer, SplFileInfo $splFileInfo): UsedModuleCollectionTransfer
    {
        $fileContent = $splFileInfo->getContents();

        if (preg_match_all('/(namespace\s|use\s|@uses\s\\\\|@param\s\\\\|@return\s\\\\|@see\s\\\\)(?<organization>\w*)\\\\(Client|Glue|Shared|Yves|Zed)\\\\(?<module>\w*)\\\\/', $fileContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if (in_array($match['organization'], ['Generated', 'Orm'], true)) {
                    continue;
                }
                $usedModuleTransfer = new UsedModuleTransfer();
                $usedModuleTransfer
                    ->setOrganization($match['organization'])
                    ->setModule($match['module']);

                $usedModuleCollectionTransfer->addUsedModule($usedModuleTransfer);
            }
        }

        return $usedModuleCollectionTransfer;
    }
}