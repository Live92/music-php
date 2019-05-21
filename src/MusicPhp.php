<?php

/*
 * This file is part of the guanguans/music-php.
 *
 * (c) 琯琯 <yzmguanguan@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

/*
 * This file is part of the guanguans/music-php.
 *
 * (c) 琯琯 <yzmguanguan@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Guanguans\MusicPhp;

use Metowolf\Meting;
use GuzzleHttp\Client;
use Guanguans\MusicPhp\Exception\Exception;
use Guanguans\MusicPhp\Exception\HttpException;
use Guanguans\MusicPhp\Contract\MusicPhpInterface;

/**
 * Class MusicPhp
 * @package Guanguans\MusicPhp
 */
class MusicPhp implements MusicPhpInterface
{
    protected $platforms = ['tencent', 'netease', 'kugou', 'baidu'];

    protected $hideFields = ['id', 'pic_id', 'url_id', 'lyric_id', 'url'];

    protected $guzzleOptions = [];

    /**
     * MusicPhp constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param $keyword
     * @return array
     */
    public function searchAll($keyword)
    {
        $songAll = [];

        foreach ($this->platforms as $platform) {
            $songAll = array_merge($songAll, $this->search($platform, $keyword));
        }

        return $songAll;
    }

    /**
     * @param $platform
     * @param $keyword
     * @return mixed
     */
    public function search($platform, $keyword)
    {
        $meting = $this->getMeting($platform);
        $songs  = json_decode($meting->format()->search($keyword), true);

        foreach ($songs as $key => &$song) {
            $detail = json_decode($meting->format()->url($song['url_id']), true);
            if ($detail['url']) {
                $song = array_merge($song, $detail);
            } else {
                unset($songs[$key]);
            }
        }

        return $songs;
    }

    /**
     * @param $platform
     * @return \Metowolf\Meting
     */
    public function getMeting($platform)
    {
        return new Meting($platform);
    }

    /**
     * @param array $songs
     * @param       $keyword
     * @return array
     */
    public function formatAll(array $songs, $keyword)
    {
        foreach ($songs as $key => &$song) {
            $song = $this->format($song, $keyword);
            array_unshift($song, "<info>$key</info>");
        }

        unset($song);

        return $songs;
    }

    /**
     * @param array $song
     * @param       $keyword
     * @return array
     */
    public function format(array $song, $keyword)
    {
        foreach ($this->hideFields as $hideField) {
            unset($song[$hideField]);
        }

        $song['name']   = str_replace($keyword, "<info>$keyword</info>", $song['name']);
        $song['album']  = str_replace($keyword, "<info>$keyword</info>", $song['album']);
        $song['artist'] = implode(',', $song['artist']);
        $song['artist'] = str_replace($keyword, "<info>$keyword</info>", $song['artist']);

        if ('baidu' === $song['source']) {
            $song['size'] = '';
            $br           = $song['br'];
            unset($song['br']);
            $song['br'] = $br;
        }

        if (!empty($song['size'])) {
            $song['size'] = sprintf('%.1f', $song['size'] / 1048576) . 'M';
        }

        return $song;
    }

    /**
     * @param array $song
     * @throws \Guanguans\MusicPhp\Exception\HttpException
     */
    public function download(array $song)
    {
        try {
            $this->getHttpClient()->get($song['url'], ['save_to' => './' . $song['name'] . '.mp3']);
        } catch (Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return \GuzzleHttp\Client
     */
    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    /**
     * @param array $options
     */
    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }
}