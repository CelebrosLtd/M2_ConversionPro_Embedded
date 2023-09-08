<?php
/**
 * Celebros (C) 2023. All Rights Reserved.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish correct extension functionality.
 * If you wish to customize it, please contact Celebros.
 */
namespace Celebros\ConversionPro\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;

abstract class LogAbstract extends Action
{
    /**
     * Authorization level of a basic admin session.
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Celebros_ConversionPro::config_conversionpro';

    /**
     * @var string
     */
    protected $fileName = '';

    /**
     * @var string
     */
    protected $filePath = '';

    /**
     * @var File
     */
    protected $file;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @return void
     */
    public function __construct(
        Context $context,
        File $file,
        DirectoryList $directoryList
    ) {
        parent::__construct($context);
        $this->file = $file;
        $this->directoryList = $directoryList;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if ($this->fileName = $this->getRequest()->getParam('filename', false)) {
            $this->filePath = $this->directoryList->getPath('log') . '/' . $this->fileName;
            if ($this->file->isExists($this->filePath)) {
                $this->logFileProcess();
            }
        } else {
            $this->messageManager->addError(__("An error occurred. Log filename is not defined"));
        }

        $this->_redirect(
            $this->_redirect->getRefererUrl()
        );
    }

    /**
     * Process log file
     *
     * @return void
     */
    abstract public function logFileProcess();
}
