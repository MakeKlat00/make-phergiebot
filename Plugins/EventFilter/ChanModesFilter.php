<?php
/**
 * Phergie (http://phergie.org)
 *
 * @link https://github.com/phergie/phergie-irc-plugin-react-eventfilter for the canonical source repository
 * @copyright Copyright (c) 2008-2014 Phergie Development Team (http://phergie.org)
 * @license http://phergie.org/license Simplified BSD License
 * @package Phergie\Irc\Plugin\React\EventFilter
 */

namespace Plugins\EventFilter;

use Phergie\Irc\Event\EventInterface;
use Phergie\Irc\Event\UserEventInterface;
use MakeKlat00\Phergie\Irc\Plugin\ChanModes\Plugin as ChanModesPlugin;
use Phergie\Irc\Plugin\React\EventFilter\ChannelFilter;

/**
 * Forwards events that either are not user-specific or originate from
 * users with specified modes.
 *
 * @category Phergie
 * @package Phergie\Irc\Plugin\React\EventFilter
 */
class ChanModesFilter extends ChannelFilter
{
    /**
     * Plugin used to obtain user mode information
     *
     * @var \MakeKlat00\Phergie\Irc\Plugin\ChanModes\Plugin
     */
    protected $chanModes;

    /**
     * List of modes for users from which to allow events
     *
     * @var array
     */
    protected $modes;

    /**
     * Accepts a plugin used to obtain user mode information and modes to
     * filter.
     *
     * @param \MakeKlat00\Phergie\Irc\Plugin\ChanModes\Plugin $chanModes
     * @param array $modes Enumerated array of letters corresponding to modes
     *        of users from which to allow events
     */
    public function __construct(ChanModesPlugin $chanModes, array $modes)
    {
        $this->chanModes = $chanModes;
        $this->modes = $modes;
    }

    /**
     * Filters events that are not user-specific or are from users with
     * specified modes.
     *
     * @param \Phergie\Irc\Event\EventInterface $event
     * @return boolean|null TRUE if the event originated from a user with a matching mode
     *         associated with this filter, FALSE if the event originated from a user
     *         without a matching mode, or NULL if the event did not originate from a user.
     */
    public function filter(EventInterface $event)
    {
        if (!$event instanceof UserEventInterface) {
            return null;
        }

        $channels = $this->getChannels($event);
        $nick = $event->getNick();
        if (empty($channels) || $nick === null) {
            return null;
        }

        $connection = $event->getConnection();

        foreach ($channels as $channel) {
            $userModes = $this->chanModes->getUserModes($connection, $channel, $nick);
            $commonModes = array_intersect($userModes, $this->modes);
            if ($commonModes) {
                return true;
            }
        }

        return false;
    }
}
