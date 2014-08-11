<?php

use Phergie\Irc\Connection;

$usermodePlugin = new \Phergie\Irc\Plugin\React\UserMode\Plugin;
$fooPlugin = new \Plugins\Foo\Plugin(['usermode' => $usermodePlugin]);

return [

  // Plugins to include for all connections

  'plugins' => [

    new \Phergie\Irc\Plugin\React\Pong\Plugin,
    new \Phergie\Irc\Plugin\React\AutoJoin\Plugin(['channels' => '#devbot']),
    $usermodePlugin,
//    new \Phergie\Irc\Plugin\React\NickServ\Plugin(['password' => 'nickserpass']),

    new \Phergie\Irc\Plugin\React\Command\Plugin(['prefix' => '.']),
    new \Phergie\Irc\Plugin\React\CommandHelp\Plugin([
      'plugins' => [
        $fooPlugin,
      ],
      'listText' => 'Available commands: ',
    ]),
    new \Phergie\Irc\Plugin\React\Quit\Plugin(['message' => 'because %s said so']),

    $fooPlugin,

  ],

  'connections' => [

    new Connection([

      // Required settings

      'serverHostname' => 'irc.klat00.org',
      'username' => 'Phergie',
      'realname' => 'Phergie Bot',
      'nickname' => 'Phergie_',

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
