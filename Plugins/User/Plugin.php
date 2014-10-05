<?php namespace Plugins\User;

use Phergie\Irc\Plugin\React\Command\CommandEvent;
use Phergie\Irc\Event\UserEvent;
use Phergie\Irc\Bot\React\EventQueueInterface;
use Phergie\Irc\Bot\React\AbstractPlugin;
use React\EventLoop\LoopInterface;

class Plugin extends AbstractPlugin
{
  /**
   * @var \Phergie\Irc\Plugin\React\UserMode\Plugin
   */
  protected $userMode;

  public function __construct(array $config)
  {
    // Validate $config['userMode']

    $this->userMode = $config['userMode'];

    $this->status = [
      0 => "account or user does not exist",
      1 => "account exists but user is not logged in",
      2 => "user is not logged in but recognized (see ACCESS)",
      3 => "user is logged in",
    ];
  }

  public function getSubscribedEvents()
  {
    return array(
      'command.register' => 'handleRegisterCommand',
      'command.register.help' => 'handleRegisterHelp',
      'irc.received.notice' => 'handleNotice',
    );
  }

  public function handleRegisterCommand(CommandEvent $event, EventQueueInterface $queue)
  {
    $connection = $event->getConnection();
    $nick = $event->getNick();
    $params = $event->getParams();
    $source = $event->getCommand() === 'PRIVMSG'
      ? $params['receivers']
      : $params['nickname'];

    // Ignore events sent directly to the bot rather than to a channel
    if ($connection->getNickname() === $source) {
      return;
    }

    $queue->ircPrivmsg('NickServ', "ACC $nick *");
  }

  public function handleNotice(UserEvent $event, EventQueueInterface $queue)
  {
    if (strpos($event->getMessage(), ' ACC ') === false) return;
    $text = $event->getParams()['text'];
    preg_match("/(?<nick>.*) -> (?<account>.*) ACC (?<status>\d)/ui", $text, $matches);
    $msg = "{$matches['nick']} ({$matches['account']}) has NickServ status '" . $this->status[$matches['status']] . "'";
    $this->getLogger()->debug($msg);
    $queue->ircPrivmsg('#devbot', $msg);
  }

  public function handleRegisterHelp(CommandEvent $event, EventQueueInterface $queue)
  {
    $channel = $event->getSource();
    $message = '.register: register yourself with the bot (checks with NickServ to prevent id-theft)';
    $queue->ircPrivmsg($channel, $message);
  }
}
