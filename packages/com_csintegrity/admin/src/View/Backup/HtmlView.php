<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Administrator\View\Backup;

defined('_JEXEC') or die;

use Cybersalt\Component\Csintegrity\Administrator\Helper\BackupsHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\GenericDataException;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Toolbar\ToolbarHelper;

final class HtmlView extends BaseHtmlView
{
    public ?\stdClass $backup = null;

    public string $contents = '';

    public string $absolutePath = '';

    public string $backUrl = '';

    public string $downloadUrl = '';

    public string $restoreAction = '';

    public bool $fileExists = false;

    public function display($tpl = null): void
    {
        $id = (int) Factory::getApplication()->getInput()->getInt('id', 0);
        if ($id <= 0) {
            throw new GenericDataException(Text::_('COM_CSINTEGRITY_BACKUP_NOT_FOUND'), 404);
        }

        $row = BackupsHelper::find($id);
        if ($row === null) {
            throw new GenericDataException(Text::_('COM_CSINTEGRITY_BACKUP_NOT_FOUND'), 404);
        }

        $this->backup        = $row;
        $this->contents      = BackupsHelper::decodeContents($row);
        $this->absolutePath  = JPATH_ROOT . '/' . ltrim((string) $row->file_path, '/\\');
        $this->fileExists    = is_file($this->absolutePath);
        $this->backUrl       = Route::_('index.php?option=com_csintegrity&view=backups', false);
        $this->downloadUrl   = Route::_('index.php?option=com_csintegrity&task=backups.download&id=' . $id, false);
        $this->restoreAction = Route::_('index.php?option=com_csintegrity', false);

        HTMLHelper::_('stylesheet', 'com_csintegrity/dashboard.css', ['relative' => true, 'version' => 'auto']);
        HTMLHelper::_('script', 'com_csintegrity/dashboard.js', ['relative' => true, 'version' => 'auto', 'defer' => true]);

        ToolbarHelper::title(
            Text::sprintf('COM_CSINTEGRITY_BACKUP_TITLE', '#' . (int) $row->id),
            'archive'
        );

        parent::display($tpl);
    }
}
