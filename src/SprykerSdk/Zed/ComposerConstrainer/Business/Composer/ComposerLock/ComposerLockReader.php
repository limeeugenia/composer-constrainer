<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerSdk\Zed\ComposerConstrainer\Business\Composer\ComposerLock;

use Generated\Shared\Transfer\ComposerConstraintTransfer;
use SprykerSdk\Zed\ComposerConstrainer\ComposerConstrainerConfig;

class ComposerLockReader implements ComposerLockReaderInterface
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
     * @return array
     */
    public function read(): array
    {
        return json_decode(file_get_contents($this->config->getProjectRootPath() . 'composer.lock'), true);
    }

    /**
     * @return \Generated\Shared\Transfer\ComposerConstraintTransfer[]
     */
    public function getConstraints(): array
    {
        $composerConstraints = [];
        $composerArray = $this->read();

        foreach (['packages', 'packages-dev'] as $type) {
            if (!isset($composerArray[$type])) {
                continue;
            }

            foreach ($composerArray[$type] as $package) {
                $composerConstraintTransfer = new ComposerConstraintTransfer();
                $composerConstraintTransfer
                    ->setName($package['name'])
                    ->setVersion($package['version'])
                    ->setIsDev($type === 'packages-dev');

                $composerConstraints[$package['name']] = $composerConstraintTransfer;

                if (isset($package['require'])) {
                    $this->addDefinedConstraints($composerConstraintTransfer, $package['require'], false);
                }
                if (isset($package['require-dev'])) {
                    $this->addDefinedConstraints($composerConstraintTransfer, $package['require-dev'], true);
                }
            }
        }

        ksort($composerConstraints);

        return $composerConstraints;
    }

    /**
     * @param ComposerConstraintTransfer $composerConstraintTransfer
     * @param array $packageDefinedConstraints
     * @param bool $isDev
     *
     * @return void
     */
    protected function addDefinedConstraints(ComposerConstraintTransfer $composerConstraintTransfer, array $packageDefinedConstraints, bool $isDev): void
    {
        foreach ($packageDefinedConstraints as $name => $version) {
            $composerConstraintTransfer->addDefinedConstraint(
                (new ComposerConstraintTransfer())
                    ->setName($name)
                    ->setVersion($version)
                    ->setIsDev($isDev)
            );
        }
    }
}
