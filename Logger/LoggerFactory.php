<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
declare(strict_types=1);

namespace Celebros\ConversionPro\Logger;

use Magento\Framework\ObjectManagerInterface;
use Celebros\ConversionPro\Helper\Data as Helper;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggerFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var string
     */
    private $instanceName;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * LoggerFactory constructor.
     *
     * @param objectManagerInterface $objectManager
     * @param Helper $helper
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Helper $helper,
        string $instanceName = LoggerInterface::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
        $this->helper = $helper;
    }

    /**
     * Create logger instance
     *
     * @return LoggerInterface
     */
    public function create(): LoggerInterface
    {
        if (!$this->helper->isLogEnabled()) {
            return $this->objectManager->get(NullLogger::class);
        }

        $object = $this->objectManager->get($this->instanceName);

        if (!($object instanceof LoggerInterface)) {
            throw new \InvalidArgumentException(
                sprintf(
                    '%s constructor expects the $instanceName to implement %s; received %s',
                    self::class,
                    LoggerInterface::class,
                    get_class($object)
                )
            );
        }

        return $object;
    }
}
