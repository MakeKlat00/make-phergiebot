<?php

use Phergie\Irc\Connection;
use Phergie\Irc\Plugin\React\EventFilter\Plugin as EventFilterPlugin;
use Phergie\Irc\Plugin\React\EventFilter as Filters;
use WyriHaximus\Phergie\Plugin\Url\Mime;

Dotenv::load(__DIR__);
Dotenv::required(['IRC_CHANS', 'IRC_HOST', 'IRC_NAME', 'IRC_NICK', 'IRC_IDENT']);

$client = new \Phergie\Irc\Client\React\Client();
$client->on('connect.end', function(\Phergie\Irc\ConnectionInterface $connection, \Psr\Log\LoggerInterface $logger) use ($client) {
    $logger->debug('Connection to ' . $connection->getServerHostname() . ' lost, attempting to reconnect');
    $client->addConnection($connection);
});

$usermodePlugin = new \Phergie\Irc\Plugin\React\UserMode\Plugin;

$commandPlugins = [
  new Plugins\User\Plugin(['userMode' => $usermodePlugin]),
  new Plugins\Games\Plugin(),
];

return [

  // Client override
  'client' => $client,

  // Plugins to include for all connections

  'plugins' => array_merge([
    // dependencies
    new \WyriHaximus\Phergie\Plugin\Dns\Plugin,
    new \WyriHaximus\Phergie\Plugin\Http\Plugin,
    new \WyriHaximus\Phergie\Plugin\Url\Plugin([
      'handler' => new \Plugins\Url\MimeAwareUrlHandler(
        '%title%',
        [new Mime\Html]
      ),
    ]),
    $usermodePlugin,

    // runtime essentials
    new \Phergie\Irc\Plugin\React\Pong\Plugin,
    new \Phergie\Irc\Plugin\React\AutoJoin\Plugin(['channels' => getenv('IRC_CHANS')]),
    new \Phergie\Irc\Plugin\React\NickServ\Plugin(['password' => getenv('NICKSERV_PASS') ?: 'foobar']),

    new EventFilterPlugin(array(
      'filter' => new Phergie\Irc\Plugin\React\EventFilter\UserModeFilter($usermodePlugin, array('q', 'a', 'o')),
      'plugins' => array(
        new \Phergie\Irc\Plugin\React\JoinPart\Plugin,
        new \Phergie\Irc\Plugin\React\Quit\Plugin(['message' => 'because %s said so']),
      ),
    )),

    // commands
    new \Phergie\Irc\Plugin\React\Command\Plugin(['prefix' => '.']),
    new \Phergie\Irc\Plugin\React\CommandHelp\Plugin([
      'plugins'  => $commandPlugins,
    ]),
    new \Phergie\Irc\Plugin\React\YouTube\Plugin(array('key' => getenv('GOOGLE_APIKEY') ?: '')),
    new Plugins\Twitter\Plugin,
    new Plugins\KickJoin\Plugin,
  ], $commandPlugins),

  'connections' => [

    new Connection([
      'serverHostname' => getenv('IRC_HOST'),
      'username' => getenv('IRC_NAME'),
      'realname' => getenv('IRC_NICK'),
      'nickname' => getenv('IRC_IDENT'),
    ]),

  ]

];
