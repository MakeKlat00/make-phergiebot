<?php namespace Plugins\KickJoin;

use Phergie\Irc\Bot\React\PluginInterface;
use Phergie\Irc\Event\EventInterface as Event;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;

class Plugin implements PluginInterface
{

  public function getSubscribedEvents()
  {
    return array(
      'irc.received.kick' => 'onKick',
    );
  }

  public function onKick(Event $event, Queue $queue)
  {
    $connection = $event->getConnection();
    $params = $event->getParams();

    if ($connection->getNickname() === $params['user']) {
      $queue->ircJoin($params['channel']);
    }

  }
}
