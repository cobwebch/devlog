<?php
namespace Devlog\Devlog\Controller;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Devlog\Devlog\Domain\Model\ExtensionConfiguration;
use Devlog\Devlog\Domain\Repository\EntryRepository;
use Devlog\Devlog\Template\Components\Buttons\ExtendedLinkButton;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Controller for the "Developer's Log" backend module
 *
 * @author Francois Suter (Cobweb) <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_devlog
 */
class ListModuleController extends ActionController
{

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * @var EntryRepository
     */
    protected $entryRepository;

    /**
     * Devlog extension configuration
     *
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration = null;

    /**
     * Injects an instance of the entry repository.
     *
     * @param EntryRepository $entryRepository
     * @return void
     */
    public function injectLogRepository(EntryRepository $entryRepository)
    {
        $this->entryRepository = $entryRepository;
    }

    /**
     * @param ExtensionConfiguration $extensionConfiguration
     */
    public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Initializes the template to use for all actions.
     *
     * @return void
     */
    protected function initializeAction()
    {
        $this->defaultViewObjectName = BackendTemplateView::class;
    }

    /**
     * Performs initializations of certain objects during calls in an AJAX context.
     *
     * In this particular context, the Extbase bootstrapping does not occur.
     * Some objects must be instantiated "manually".
     *
     * @return void
     */
    protected function initializeForAjaxAction()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->entryRepository = $this->objectManager->get(EntryRepository::class);
    }

    /**
     * Initializes the view before invoking an action method.
     *
     * @param ViewInterface $view The view to be initialized
     * @return void
     * @api
     */
    protected function initializeView(ViewInterface $view)
    {
        if ($view instanceof BackendTemplateView) {
            parent::initializeView($view);
        }
        $pageRenderer = $view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->addCssFile(
                ExtensionManagementUtility::extRelPath('devlog') . 'Resources/Public/StyleSheet/Devlog.css'
        );
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Devlog/ListModule');
        $pageRenderer->addInlineSettingArray(
                'DevLog',
                $this->extensionConfiguration->toArray()
        );
        // Add open in new window button
        $newWindowIcon = $this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-window-open', Icon::SIZE_SMALL);
        $newWindowButton = GeneralUtility::makeInstance(ExtendedLinkButton::class);
        $newWindowButton->setIcon($newWindowIcon)
                ->setTarget('_blank')
                ->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.openInNewWindow', 'lang'))
                ->setHref(
                        $this->uriBuilder->uriFor('index')
                );
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar()->addButton(
                $newWindowButton,
                ButtonBar::BUTTON_POSITION_RIGHT
        );
    }

    /**
     * Displays the list of all available log entries.
     *
     * @return void
     */
    public function indexAction()
    {

    }

    /**
     * Returns the list of all log entries, in JSON format.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getAllAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initializeForAjaxAction();

        // Get all the entries and make them into an array for JSON encoding
        $entries = $this->entryRepository->findAll();
        // Send the response
        $response->getBody()->write(json_encode($entries));
        return $response;
    }

    /**
     * Returns the list of all log entries after a given timestamp.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getNewAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initializeForAjaxAction();
        $requestParameters = $request->getQueryParams();

        // Get all the entries and make them into an array for JSON encoding
        $entries = $this->entryRepository->findAfterDate(
                $requestParameters['timestamp']
        );
        // Send the response
        $response->getBody()->write(json_encode($entries));
        return $response;
    }
}