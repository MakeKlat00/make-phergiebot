<?php namespace Plugins\Twitter;

use Phergie\Irc\Bot\React\AbstractPlugin;
use Phergie\Irc\Event\EventInterface as Event;
use Phergie\Irc\Bot\React\EventQueueInterface as Queue;
use WyriHaximus\Phergie\Plugin\Http\Request as HttpRequest;

class Plugin extends AbstractPlugin
{

  const ERR_INVALID_RESPONSEFORMAT = 1;

  /**
     * Accepts plugin configuration.
     *
     * Supported keys:
     *
     * responseFormat - optional pattern used to format data before sending it
     *
     * @param array $config
     * @throws \DomainException if any settings are invalid
     */
  public function __construct(array $config = array())
  {
    \Dotenv::required(['EMBEDLY_APIKEY']);
    $this->responseFormat = $this->getResponseFormat($config);
  }

  public function getSubscribedEvents()
  {
    return array(
      'url.host.twitter.com' => 'handleUrl',
    );
  }

  /**
   * Sends information about tweets back to channels that receive
   * URLs to them.
   *
   * @param string $url
   * @param \Phergie\Irc\Event\EventInterface $event
   * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
   */
  public function handleUrl($url, Event $event, Queue $queue)
  {
    $logger = $this->getLogger();
    $logger->info('handleUrl', array('url' => $url));
    $apiUrl = $this->getApiUrl($url);
    $request = $this->getApiRequest($apiUrl, $event, $queue);
    $this->getEventEmitter()->emit('http.request', array($request));
  }

  /**
   * Derives an API URL to get data for a specified tweet.
   *
   * @param string $id Video identifier
   * @return string
   */
  protected function getApiUrl($url)
  {
    return 'http://api.embed.ly/1/oembed?' . http_build_query(array(
      'url' => $url,
      'maxwidth' => 500,
      'key' => getenv('EMBEDLY_APIKEY'),
    ));
  }

  /**
   * Returns an API request to get data for a tweet.
   *
   * @param string $url API request URL
   * @param \Phergie\Irc\Bot\React\EventInterface $event
   * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
   */
  protected function getApiRequest($url, Event $event, Queue $queue)
  {
    $self = $this;
    $request = new HttpRequest(array(
      'url' => $url,
      'resolveCallback' => function($data) use ($self, $url, $event, $queue) {
        $self->resolve($url, $data, $event, $queue);
      },
      'rejectCallback' => function($error) use ($self, $url) {
        $self->reject($url, $error);
      }
    ));
    return $request;
  }

  /**
   * Handles a successful request for tweet data.
   *
   * @param string $url URL of the request
   * @param string $data Response body
   * @param \Phergie\Irc\EventInterface $event
   * @param \Phergie\Irc\Bot\React\EventQueueInterface $queue
   */
  public function resolve($url, $data, Event $event, Queue $queue)
  {
    $logger = $this->getLogger();
    $json = json_decode($data);
    $logger->info('resolve', array('url' => $url, 'json' => $json));

    if (isset($json->error)) {
      return $logger->warning(
        'Query response contained an error',
        array(
          'url' => $url,
          'error' => $json->error,
        )
      );
    }

    if (empty($json->description)) {
      return $logger->warning(
        'Query returned no results',
        array('url' => $url)
      );
    }

    $replacements = $this->getReplacements($json);
    $message = str_replace(
      array_keys($replacements),
      array_values($replacements),
      $this->responseFormat
    );
    $queue->ircPrivmsg($event->getSource(), $message);
  }

  /**
   * Returns replacements for pattern segments based on data from a given
   * tweet data object.
   *
   * @param object $tweet
   * @return array
   */
  protected function getReplacements($tweet)
  {
    $text = $tweet->description;
    $author = $tweet->author_name;
    $link = $tweet->url;

    return array(
      '%link%' => $link,
      '%text%' => $text,
      '%author%' => $author,
    );
  }

  /**
   * Handles a failed request for tweet data.
   *
   * @param string $url URL of the failed request
   * @param string $error Error describing the failure
   */
  public function reject($url, $error)
  {
    $this->getLogger()->warning(
      'Request for tweet data failed',
      array(
        'url' => $url,
        'error' => $error,
      )
    );
  }

  /**
   * Extracts a pattern for formatting tweet data from configuration.
   *
   * @param array $config
   * @return string
   * @throws \DomainException if format setting is invalid
   */
  protected function getResponseFormat(array $config)
  {
    if (isset($config['responseFormat'])) {
      if (!is_string($config['responseFormat'])) {
        throw new \DomainException(
          'responseFormat must reference a string',
          self::ERR_INVALID_RESPONSEFORMAT
        );
      }
      return $config['responseFormat'];
    }
    return '[ @%author% ] %text% -- %link%';
  }
}
