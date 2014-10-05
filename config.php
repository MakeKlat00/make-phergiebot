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
  new \Plugins\User\Plugin(['userMode' => $usermodePlugin]),
  new \Plugins\Games\Plugin(),
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
        '%image-mime% %image-width%×%image-height%',
        [new Mime\Image]
      ),
    ]),
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
      'filter' => new Phergie\Irc\Plugin\React\EventFilter\UserModeFilter($usermodePlugin, array('o')),
      'plugins' => array(
        new \Phergie\Irc\Plugin\React\JoinPart\Plugin,
        new \Phergie\Irc\Plugin\React\Quit\Plugin(['message' => 'because %s said so']),
      ),
    )),

    // new EventFilterPlugin(array(
    //   'filter' => new Filters\AndFilter(array(
    //     new Filters\NotFilter(
    //       new Filters\UserFilter(array('Hades!*@*'))
    //     ),
    //     new Filters\NotFilter(
    //       new Filters\UserFilter(array('*bot!*@*'))
    //     ),
    //   )),
    //   'plugins' => array(
    //     new \Plugins\Twitter\Plugin,
    //   ),
    // )),

    // commands
    new \Phergie\Irc\Plugin\React\Command\Plugin(['prefix' => '.']),
    new \Phergie\Irc\Plugin\React\CommandHelp\Plugin([
      'plugins'  => $commandPlugins,
    ]),
    new \Phergie\Irc\Plugin\React\YouTube\Plugin(array('key' => getenv('GOOGLE_APIKEY') ?: '')),
    new \Plugins\Twitter\Plugin,
    new \Plugins\KickJoin\Plugin,
  ], $commandPlugins),

  'connections' => [

    new Connection([

      // Required settings

      'serverHostname' => getenv('IRC_HOST'),
      'username' => getenv('IRC_NAME'),
      'realname' => getenv('IRC_NICK'),
      'nickname' => getenv('IRC_IDENT'),

      // Optional settings

      // 'hostname' => 'user server name goes here if needed',
      // 'serverport' => 6697,
      // 'password' => 'password goes here if needed',
      // 'options' => [
      //   'transport' => 'ssl',
      //   'force-ipv4' => true,
      // ]

    ]),

  ]

];
