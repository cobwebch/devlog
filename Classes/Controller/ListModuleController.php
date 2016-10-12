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
use TYPO3\CMS\Backend\Template\Components\Menu\Menu;
use TYPO3\CMS\Backend\Template\Components\Menu\MenuItem;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
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
        // If the action is "delete", exit early
        if ($this->actionMethodName === 'deleteAction') {
            return;
        }

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
        $pageRenderer->addInlineLanguageLabelFile('EXT:devlog/Resources/Private/Language/JavaScript.xlf');

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

        // Add clear log menu
        /** @var Menu $menu */
        $menu = GeneralUtility::makeInstance(Menu::class);
        $menu->setIdentifier('_devlogClearMenu');

        /** @var UriBuilder $uriBuilder */
        $uriBuilder = $this->objectManager->get(UriBuilder::class);
        $uriBuilder->setRequest($this->request);

        // This menu originally has a single item
        // Additional items are created on the fly (per JavaScript) depending on the log entries in the table
        /** @var MenuItem $clearLogMenuItem */
        $clearLogMenuItem = GeneralUtility::makeInstance(MenuItem::class);
        $clearLogMenuItem->setTitle(
                LocalizationUtility::translate(
                        'clearlog',
                        'devlog'
                )
        );
        $uri = $uriBuilder->reset()->uriFor(
                'index',
                array(),
                'ListModule'
        );
        $clearLogMenuItem->setActive(true)->setHref($uri);

        $menu->addMenuItem($clearLogMenuItem);
        $this->view->getModuleTemplate()->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
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
     * Deletes log entries and redirects to list view.
     *
     * @param string $clear Type of deletion to perform ("all", "key" or "period")
     * @param string $value Subtype of deletion to perform (for "key" and "period" types)
     * @return void
     */
    public function deleteAction($clear, $value = '')
    {
        $deletedEntries = 0;
        switch ($clear) {
            case 'all':
                $deletedEntries = $this->entryRepository->deleteAll();
                break;
            case 'key':
                $deletedEntries = $this->entryRepository->deleteByKey($value);
                break;
            case 'period':
                $constant = EntryRepository::class . '::' . 'PERIOD_' . strtoupper($value);
                // Valid period, clear accordingly
                if (defined($constant)) {
                    $deletedEntries = $this->entryRepository->deleteByPeriod(constant($constant));
                // Invalid period, prepare error message
                } else {
                    $this->addFlashMessage(
                            LocalizationUtility::translate(
                                    'clearlog_invalid_period_error',
                                    'devlog'
                            ),
                            '',
                            AbstractMessage::ERROR
                    );
                    // Return to default view
                    $this->redirect('index');
                }
                break;
            default:
                // Invalid action, prepare error message
                $this->addFlashMessage(
                        LocalizationUtility::translate(
                                'clearlog_action_error',
                                'devlog'
                        ),
                        '',
                        AbstractMessage::ERROR
                );
                // Return to default view
                $this->redirect('index');
        }
        // Report on number of entries deleted
        if ($deletedEntries > 0) {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'cleared_log',
                            'devlog',
                            array($deletedEntries)
                    )
            );
        } else {
            $this->addFlashMessage(
                    LocalizationUtility::translate(
                            'clearlog_nothing_cleared',
                            'devlog',
                            array($deletedEntries)
                    )
            );
        }
        // Return to default view
        $this->redirect('index');
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

    /**
     * Returns a count of log entries, based on various grouping criteria, in JSON format.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function getCountAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->initializeForAjaxAction();

        $countByKey = $this->entryRepository->countByKey();
        $countTotal = array_sum($countByKey);
        $counts = array(
                'all' => $countTotal,
                'keys' => $countByKey,
                'periods' => $this->entryRepository->countByPeriod()
        );

        // Send the response
        $response->getBody()->write(json_encode($counts));
        return $response;
    }
}