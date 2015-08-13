<?php

use Phergie\Irc\Connection;
use Phergie\Irc\Plugin\React\EventFilter\Plugin as EventFilterPlugin;
use Phergie\Irc\Plugin\React\EventFilter as Filters;
use WyriHaximus\Phergie\Plugin\Url\Mime;

(new josegonzalez\Dotenv\Loader(__DIR__ . '/.env'))
    ->parse()
    ->expect('IRC_CHANS', 'IRC_HOST', 'IRC_NAME', 'IRC_NICK', 'IRC_IDENT', 'EMBEDLY_APIKEY')
    ->putenv();

$client = new \Phergie\Irc\Client\React\Client();
$client->on('connect.end', function (\Phergie\Irc\ConnectionInterface $connection, \Psr\Log\LoggerInterface $logger) use ($client) {
    $logger->debug('Connection to ' . $connection->getServerHostname() . ' lost, attempting to reconnect');
    $client->addConnection($connection);
});

$usermodePlugin = new MakeKlat00\Phergie\Irc\Plugin\ChanModes\Plugin;

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
            'handler' => new \Plugins\Url\MimeAwareUrlHandler([
                [
                    'pattern' => '%title%',
                    'mimes' => [new Mime\Html]
                ],
                [
                    'pattern' => '%image-mime% %image-width%×%image-height%',
                    'mimes' => [new Mime\Image]
                ],
            ]),
        ]),
        $usermodePlugin,

        // runtime essentials
        new \Phergie\Irc\Plugin\React\Pong\Plugin,
        new \Phergie\Irc\Plugin\React\AutoJoin\Plugin(['channels' => getenv('IRC_CHANS')]),
        new \Phergie\Irc\Plugin\React\NickServ\Plugin(['password' => getenv('NICKSERV_PASS') ?: '']),
        new \Plugins\KickJoin\Plugin,

        // Restricted
        new EventFilterPlugin(array(
            'filter' => new Phergie\Irc\Plugin\React\EventFilter\OrFilter([
                new Phergie\Irc\Plugin\React\EventFilter\UserFilter(["*!*@foray-jero.me"]),
                new Plugins\EventFilter\ChanModesFilter($usermodePlugin, array('q', 'a', 'o')),
            ]),
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

        // Url handlers
        new \Phergie\Irc\Plugin\React\YouTube\Plugin(array('key' => getenv('GOOGLE_APIKEY') ?: '')),
        new \Plugins\Twitter\Plugin(['key' => getenv('EMBEDLY_APIKEY')]),

        // commands
        new \Phergie\Irc\Plugin\React\Command\Plugin(['prefix' => '.']),
        new \Phergie\Irc\Plugin\React\CommandHelp\Plugin([
            'plugins' => $commandPlugins,
        ]),
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
