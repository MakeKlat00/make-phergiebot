<?php

use Phergie\Irc\Connection;

Dotenv::load(__DIR__);
Dotenv::required(['IRC_CHANS', 'IRC_HOST', 'IRC_NAME', 'IRC_NICK', 'IRC_IDENT']);

$usermodePlugin = new \Phergie\Irc\Plugin\React\UserMode\Plugin;

$plugins = [
  new \Plugins\Foo\Plugin(['usermode' => $usermodePlugin]),
  new \Plugins\Games\Plugin(),
];

return [

  // Plugins to include for all connections

  'plugins' => array_merge([
    new \Phergie\Irc\Plugin\React\Pong\Plugin,
    new \Phergie\Irc\Plugin\React\AutoJoin\Plugin(['channels' => getenv('IRC_CHANS')]),
    $usermodePlugin,
    getenv('NICKSERV_PASS') ? new \Phergie\Irc\Plugin\React\NickServ\Plugin(['password' => getenv('NICKSERV_PASS')]) : null,

    new \Phergie\Irc\Plugin\React\Command\Plugin(['prefix' => '.']),
    new \Phergie\Irc\Plugin\React\CommandHelp\Plugin([
      'plugins'  => $plugins,
    ]),
    new \Phergie\Irc\Plugin\React\Quit\Plugin(['message' => 'because %s said so']),
  ], $plugins),

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
