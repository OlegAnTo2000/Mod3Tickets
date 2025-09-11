<?php

namespace Tickets\Processors\Mgr\Author;

use \xPDO\Om\xPDOQuery;
use \xPDO\Om\xPDOObject;
use \MODX\Revolution\modX;
use \Tickets\TicketAuthor;
use MODX\Revolution\modUser;
use MODX\Revolution\modUserProfile;
use \MODX\Revolution\Processors\Model\GetListProcessor;

class GetList extends GetListProcessor
{
    public $classKey = TicketAuthor::class;
    public $defaultSortField = 'rating';
    public $defaultSortDirection = 'DESC';

    /**
     * @return bool
     */
    public function initialize()
    {
        $parent = parent::initialize();

        if ($this->getProperty('sort') == 'stars') {
            $dir = $this->getProperty('dir', 'DESC');
            $this->setProperty('sort', "stars_tickets {$dir}, stars_comments");
        }

        return $parent;
    }


    /**
     * @param xPDOQuery $c
     *
     * @return xPDOQuery
     */
    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $c->innerJoin(modUser::class, 'User');
        $c->innerJoin(modUserProfile::class, 'UserProfile');
        $c->select($this->modx->getSelectColumns($this->classKey, $this->classKey));
        $c->select(array(
            'username' => 'User.username',
            'fullname' => 'UserProfile.fullname',
            'active'   => 'User.active',
            'blocked'  => 'UserProfile.blocked',
        ));

        if ($query = $this->getProperty('query', null)) {
            $c->where(array(
                'User.username:LIKE' => "%{$query}%",
                'OR:UserProfile.fullname:LIKE' => "%{$query}%",
            ));
        }

        return $c;
    }


    /**
     * @param \xPDO\Om\xPDOObject $object
     *
     * @return array
     */
    public function prepareRow(xPDOObject $object)
    {
        $array = parent::prepareRow($object);

        if (empty($array['fullname'])) {
            $array['fullname'] = $array['username'];
        }
        $array['stars'] = $array['stars_tickets'] + $array['stars_comments'];

        return $array;
    }
}
