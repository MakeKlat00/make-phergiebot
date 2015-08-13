<?php namespace Plugins\Url;

use WyriHaximus\Phergie\Plugin\Url\Mime;
use WyriHaximus\Phergie\Plugin\Url\UrlInterface;
use WyriHaximus\Phergie\Plugin\Url\UrlHandlerInterface;

/**
 * Default URL handler to create a message about a
 *
 * @category Phergie
 * @package Plugins\Url
 */
class MimeAwareUrlHandler implements UrlHandlerInterface
{
    const HTTP_STATUS_OK = 200;

    /**
     * Pattern used to format feed items
     *
     * @var string
     */
    protected $pattern;

    protected $mimes;

    /**
     * Default pattern used to format feed items if none is provided via
     * configuration
     *
     * @var string
     */
    const DEFAULT_PATTERN = '[ %url% ] %composed-title%';

    /**
     * Accepts format pattern.
     *
     * @param array $mimes
     */
    public function __construct(array $mimes = null)
    {
        foreach ($mimes as $mime) {
            $mime['pattern'] = !empty($mime['pattern']) ? $mime['pattern'] : static::DEFAULT_PATTERN;
            $mime['mimes'] = !empty($mime['mimes']) ? $mime['mimes'] : [new Mime\Html];
        }

        $this->mimes = $mimes;
    }

    public function handle(UrlInterface $url)
    {
        $headers = $url->getHeaders();
        $matches = 0;
        if ($url->getCode() == static::HTTP_STATUS_OK) {
            if (isset($headers['content-type'][0])) {
                foreach ($this->mimes as $mime) {
                    foreach ($mime['mimes'] as $mimeToCheck) {
                        if ($mimeToCheck->matches($headers['content-type'][0])) {
                            $pattern = $mime['pattern'];
                            $matches++;
                        }
                    }
                }
            }
        }
        if (!$matches) return;

        $replacements = $this->getDefaultReplacements($url);
        $replacements = $this->extract($replacements, $url);

        $formatted = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $pattern
        );

        return $formatted;
    }

    public function getDefaultReplacements(UrlInterface $url)
    {
        $headers = $url->getHeaders();

        $replacements = array(
            '%url%' => $url->getUrl(),
            '%url-short%' => $url->getShortUrl(),
            '%http-status-code%' => $url->getCode(),
            '%timing%' => $url->getTiming(),
            '%timing2%' => round($url->getTiming(), 2),
            '%title%' => '',
            '%composed-title%' => '',
        );

        /**
         * Selection of response headers from: http://en.wikipedia.org/wiki/List_of_HTTP_header_fields#Response_Headers
         */
        foreach (array(
            'age',
            'content-type',
            'content-length',
            'content-language',
            'date',
            'etag',
            'expires',
            'last-modified',
            'server',
            'x-powered-by',
        ) as $header) {
            $replacements['%header-' . $header . '%'] = isset($headers[$header][0]) ? $headers[$header][0] : '';
        }

        return $replacements;
    }

    public function extract($replacements, UrlInterface $url)
    {
        $headers = $url->getHeaders();

        if ($url->getCode() == static::HTTP_STATUS_OK) {
            if (isset($headers['content-type'][0])) {
                foreach ($this->mimes as $mime) {
                    foreach ($mime['mimes'] as $mimeToCheck) {
                        if ($mimeToCheck->matches($headers['content-type'][0])) {
                            $replacements = $mimeToCheck->extract($replacements, $url);
                        }
                    }
                }
            }
        }

        return $replacements;
    }
}
