<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\Provider;

use Gaufrette\Filesystem;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Sonata\MediaBundle\CDN\CDNInterface;
use Sonata\MediaBundle\Generator\GeneratorInterface;
use Sonata\MediaBundle\Metadata\MetadataBuilderInterface;
use Sonata\MediaBundle\Model\MediaInterface;
use Sonata\MediaBundle\Thumbnail\ThumbnailInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class YouTubeProvider extends BaseVideoProvider
{
    public function __construct(
        string $name,
        Filesystem $filesystem,
        CDNInterface $cdn,
        GeneratorInterface $pathGenerator,
        ThumbnailInterface $thumbnail,
        ClientInterface $client,
        RequestFactoryInterface $requestFactory,
        ?MetadataBuilderInterface $metadata = null,
        private bool $html5 = false,
    ) {
        parent::__construct($name, $filesystem, $cdn, $pathGenerator, $thumbnail, $client, $requestFactory, $metadata);
    }

    public function getProviderMetadata(): MetadataInterface
    {
        return new Metadata(
            $this->getName(),
            $this->getName().'.description',
            null,
            'SonataMediaBundle',
            ['class' => 'fa fa-youtube']
        );
    }

    public function getHelperProperties(MediaInterface $media, string $format, array $options = []): array
    {
        // Override html5 value if $options['html5'] is a boolean
        if (!isset($options['html5'])) {
            $options['html5'] = $this->html5;
        }

        // documentation : http://code.google.com/apis/youtube/player_parameters.html

        $default_player_url_parameters = [
            // Values: 0 or 1. Default is 1. Sets whether the player should load related
            // videos once playback of the initial video starts. Related videos are
            // displayed in the "genie menu" when the menu button is pressed. The player
            // search functionality will be disabled if rel is set to 0.
            'rel' => 0,

            // Values: 0 or 1. Default is 0. Sets whether or not the initial video will autoplay
            // when the player loads.
            'autoplay' => 0,

            // Values: 0 or 1. Default is 0. In the case of a single video player, a setting of 1
            // will cause the player to play the initial video again and again. In the case of a
            // playlist player (or custom player), the player will play the entire playlist and
            // then start again at the first video.
            'loop' => 0,

            // Values: 0 or 1. Default is 0. Setting this to 1 will enable the Javascript API.
            // For more information on the Javascript API and how to use it, see the JavaScript
            // API documentation.
            'enablejsapi' => 0,

            // Value can be any alphanumeric string. This setting is used in conjunction with the
            // JavaScript API. See the JavaScript API documentation for details.
            'playerapiid' => null,

            // Values: 0 or 1. Default is 0. Setting to 1 will disable the player keyboard controls.
            // Keyboard controls are as follows:
            //      Spacebar: Play / Pause
            //      Arrow Left: Jump back 10% in the current video
            //      Arrow Right: Jump ahead 10% in the current video
            //      Arrow Up: Volume up
            //      Arrow Down: Volume Down
            'disablekb' => 0,

            // Values: 0 or 1. Default is 0. Setting to 1 enables the "Enhanced Genie Menu". This
            // behavior causes the genie menu (if present) to appear when the user's mouse enters
            // the video display area, as opposed to only appearing when the menu button is pressed.
            'egm' => 0,

            // Values: 0 or 1. Default is 0. Setting to 1 enables a border around the entire video
            // player. The border's primary color can be set via the color1 parameter, and a
            // secondary color can be set by the color2 parameter.
            'border' => 0,

            // Values: Any RGB value in hexadecimal format. color1 is the primary border color, and
            // color2 is the video control bar background color and secondary border color.
            'color1' => null,
            'color2' => null,

            // Values: 0 or 1. Default is 0. Setting to 1 enables the fullscreen button. This has no
            // effect on the Chromeless Player. Note that you must include some extra arguments to
            // your embed code for this to work.
            'fs' => 1,

            // Values: A positive integer. This parameter causes the player to begin playing the video
            // at the given number of seconds from the start of the video. Note that similar to the
            // seekTo function, the player will look for the closest keyframe to the time you specify.
            // This means sometimes the play head may seek to just before the requested time, usually
            // no more than ~2 seconds
            'start' => 0,

            // Values: 0 or 1. Default is 0. Setting to 1 enables HD playback by default. This has no
            // effect on the Chromeless Player. This also has no effect if an HD version of the video
            // is not available. If you enable this option, keep in mind that users with a slower
            // connection may have an sub-optimal experience unless they turn off HD. You should ensure
            // your player is large enough to display the video in its native resolution.
            'hd' => 1,

            // Values: 0 or 1. Default is 1. Setting to 0 disables the search box from displaying when
            // the video is minimized. Note that if the rel parameter is set to 0 then the search box
            // will also be disabled, regardless of the value of showsearch.
            'showsearch' => 0,

            // Values: 0 or 1. Default is 1. Setting to 0 causes the player to not display information
            // like the video title and rating before the video starts playing.
            'showinfo' => 0,

            // Values: 1 or 3. Default is 1. Setting to 1 will cause video annotations to be shown by
            // default, whereas setting to 3 will cause video annotation to not be shown by default.
            'iv_load_policy' => 1,

            // Values: 1. Default is based on user preference. Setting to 1 will cause closed captions
            // to be shown by default, even if the user has turned captions off.
            'cc_load_policy' => 1,

            // Values: 'window' or 'opaque' or 'transparent'.
            // When wmode=window, the Flash movie is not rendered in the page.
            // When wmode=opaque, the Flash movie is rendered as part of the page.
            // When wmode=transparent, the Flash movie is rendered as part of the page.
            'wmode' => 'window',
        ];

        $default_player_parameters = [
            // Values: 0 or 1. Default is 0. Setting to 1 enables a border around the entire video
            // player. The border's primary color can be set via the color1 parameter, and a
            // secondary color can be set by the color2 parameter.
            'border' => $default_player_url_parameters['border'],

            // Values: 'allowfullscreen' or empty. Default is 'allowfullscreen'. Setting to empty value disables
            //  the fullscreen button.
            'allowFullScreen' => true,

            // The allowScriptAccess parameter in the code is needed to allow the player SWF to call
            // functions on the containing HTML page, since the player is hosted on a different domain
            // from the HTML page.
            'allowScriptAccess' => $options['allowScriptAccess'] ?? 'always',

            // Values: 'window' or 'opaque' or 'transparent'.
            // When wmode=window, the Flash movie is not rendered in the page.
            // When wmode=opaque, the Flash movie is rendered as part of the page.
            // When wmode=transparent, the Flash movie is rendered as part of the page.
            'wmode' => $default_player_url_parameters['wmode'],
        ];

        $player_url_parameters = array_merge($default_player_url_parameters, $options['player_url_parameters'] ?? []);

        $box = $this->getBoxHelperProperties($media, $format, $options);

        $player_parameters = array_merge($default_player_parameters, $options['player_parameters'] ?? [], [
            'width' => $box->getWidth(),
            'height' => $box->getHeight(),
        ]);

        $params = [
            'html5' => $options['html5'],
            'player_url_parameters' => http_build_query($player_url_parameters),
            'player_parameters' => $player_parameters,
        ];

        return $params;
    }

    public function updateMetadata(MediaInterface $media, bool $force = false): void
    {
        $url = \sprintf('https://www.youtube.com/oembed?url=%s&format=json', $this->getReferenceUrl($media));

        try {
            $metadata = $this->getMetadata($media, $url);
        } catch (\RuntimeException) {
            $media->setEnabled(false);
            $media->setProviderStatus(MediaInterface::STATUS_ERROR);

            return;
        }

        $media->setProviderMetadata($metadata);

        if ($force) {
            $media->setName($metadata['title']);
            $media->setAuthorName($metadata['author_name']);
        }

        $media->setHeight($metadata['height']);
        $media->setWidth($metadata['width']);
        $media->setContentType('video/x-flv');
    }

    public function getDownloadResponse(MediaInterface $media, string $format, string $mode, array $headers = []): Response
    {
        return new RedirectResponse($this->getReferenceUrl($media), 302, $headers);
    }

    public function getReferenceUrl(MediaInterface $media): string
    {
        $providerReference = $media->getProviderReference();

        if (null === $providerReference) {
            throw new \InvalidArgumentException('Unable to generate reference url for media without provider reference.');
        }

        return \sprintf('https://www.youtube.com/watch?v=%s', $providerReference);
    }

    protected function doTransform(MediaInterface $media): void
    {
        $this->fixBinaryContent($media);

        if (null === $media->getBinaryContent()) {
            return;
        }

        $media->setProviderName($this->name);
        $media->setProviderStatus(MediaInterface::STATUS_OK);
        $media->setProviderReference($media->getBinaryContent());

        $this->updateMetadata($media, true);
    }

    private function fixBinaryContent(MediaInterface $media): void
    {
        if (null === $media->getBinaryContent()) {
            return;
        }

        if (11 === \strlen($media->getBinaryContent())) {
            return;
        }

        if (1 === preg_match(
            '{^(?:https?://)?(?:www\.)?(?:m\.)?(?:youtu\.be/|youtube\.com/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)/))(?<video_id>[^\#\?&\'>]+)}',
            $media->getBinaryContent(),
            $matches
        )) {
            $media->setBinaryContent($matches['video_id']);
        }
    }
}
