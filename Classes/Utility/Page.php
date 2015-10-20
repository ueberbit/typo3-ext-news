<?php

namespace GeorgRinger\News\Utility;

/**
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
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Page Utility class
 *
 * @package TYPO3
 * @subpackage tx_news
 */
class Page
{

    /**
     * Find all ids from given ids and level
     *
     * @param string $pidList comma separated list of ids
     * @param integer $recursive recursive levels
     * @return string comma separated list of ids
     */
    public static function extendPidListByChildren($pidList = '', $recursive = 0)
    {
        $recursive = (int)$recursive;
        if ($recursive <= 0) {
            return $pidList;
        }

        $queryGenerator = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\QueryGenerator::class);
        $recursiveStoragePids = $pidList;
        $storagePids = GeneralUtility::intExplode(',', $pidList);
        foreach ($storagePids as $startPid) {
            $pids = $queryGenerator->getTreeList($startPid, $recursive, 0, 1);
            if (strlen($pids) > 0) {
                $recursiveStoragePids .= ',' . $pids;
            }
        }
        return $recursiveStoragePids;
    }

    /**
     * Set properties of an object/array in cobj->LOAD_REGISTER which can then
     * be used to be loaded via TS with register:name
     *
     * @param string $properties comma separated list of properties
     * @param mixed $object object or array to get the properties
     * @param string $prefix optional prefix
     * @return void
     */
    public static function setRegisterProperties($properties, $object, $prefix = 'news')
    {
        if (!empty($properties) && !is_null(($object))) {
            $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
            $items = GeneralUtility::trimExplode(',', $properties, true);

            $register = array();
            foreach ($items as $item) {
                $key = $prefix . ucfirst($item);
                try {
                    $register[$key] = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getProperty($object, $item);
                } catch (\Exception $e) {
                    GeneralUtility::devLog($e->getMessage(), 'news', GeneralUtility::SYSLOG_SEVERITY_WARNING);
                }
            }
            $cObj->LOAD_REGISTER($register, '');
        }
    }

    /**
     * Return a page tree
     *
     * @param integer $pageUid page to start with
     * @param integer $treeLevel count of levels
     * @return PageTreeView
     * @throws \Exception
     */
    public static function pageTree($pageUid, $treeLevel)
    {
        if (TYPO3_MODE !== 'BE') {
            throw new \Exception('Page::pageTree does only work in the backend!');
        }

        /* @var $tree PageTreeView */
        $tree = GeneralUtility::makeInstance(PageTreeView::class);
        $tree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));

        $treeStartingRecord = BackendUtility::getRecord('pages', $pageUid);
        BackendUtility::workspaceOL('pages', $treeStartingRecord);

        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        // Creating top icon; the current page
        $tree->tree[] = array(
            'row' => $treeStartingRecord,
            'HTML' => $iconFactory->getIconForRecord('pages', $treeStartingRecord, Icon::SIZE_SMALL)->render()
        );

        $tree->getTree($pageUid, $treeLevel, '');
        return $tree;
    }

}
