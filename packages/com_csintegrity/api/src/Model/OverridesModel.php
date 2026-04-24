<?php

/**
 * @package     Csintegrity
 * @copyright   Copyright (C) 2026 Cybersalt. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

declare(strict_types=1);

namespace Cybersalt\Component\Csintegrity\Api\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\DatabaseQuery;

final class OverridesModel extends ListModel
{
    public function __construct($config = [], ?\Joomla\CMS\MVC\Factory\MVCFactoryInterface $factory = null)
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id',
                'template',
                'client_id',
                'state',
                'extension_id',
                'action',
                'modified_date',
            ];
        }

        parent::__construct($config, $factory);
    }

    protected function getListQuery(): DatabaseQuery
    {
        $db    = $this->getDatabase();
        $query = $db->getQuery(true)
            ->select($db->quoteName(['id', 'template', 'hash_id', 'extension_id', 'state', 'action', 'client_id', 'created_date', 'modified_date']))
            ->from($db->quoteName('#__template_overrides'));

        if (($template = $this->getState('filter.template')) !== null && $template !== '') {
            $query->where($db->quoteName('template') . ' = :template')
                ->bind(':template', $template);
        }

        if (($clientId = $this->getState('filter.client_id')) !== null && $clientId !== '') {
            $clientId = (int) $clientId;
            $query->where($db->quoteName('client_id') . ' = :client_id')
                ->bind(':client_id', $clientId, \Joomla\Database\ParameterType::INTEGER);
        }

        if (($state = $this->getState('filter.state')) !== null && $state !== '') {
            $state = (int) $state;
            $query->where($db->quoteName('state') . ' = :state')
                ->bind(':state', $state, \Joomla\Database\ParameterType::INTEGER);
        }

        $query->order($db->quoteName('modified_date') . ' DESC');

        return $query;
    }
}
