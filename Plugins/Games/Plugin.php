<?php namespace Plugins\Games;

use Phergie\Irc\Plugin\React\Command\CommandEvent;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Bot\React\PluginInterface;

class Plugin implements PluginInterface
{

  public function getSubscribedEvents()
  {
    return array(
      'command.roulette'       => 'handleRouletteCommand',
      'command.roulette.help'  => 'handleRouletteHelp',
    );
  }

  public function handleRouletteCommand(CommandEvent $event, EventQueueInterface $queue)
  {
    $connection = $event->getConnection();
    $nick = $event->getNick();
    $source = $event->getSource();

    // Ignore events sent directly to the bot rather than to a channel
    if ($connection->getNickname() === $source) {
      return;
    }

    if ((rand() % 6) == 0) {
      $queue->ircNotice($source, "*PAN*");
      $queue->ircKick($source, $nick, "Pan t'es mort !");
    } else {
      $queue->ircNotice($source, "*CLICK*");
    }

  }

  public function handleRouletteHelp(CommandEvent $event, EventQueueInterface $queue)
  {
    $channel = $event->getSource();
    $message = '.roulette: Pan ! Va tu survivre a la roulette ?';
    $queue->ircPrivmsg($channel, $message);
  }
}
