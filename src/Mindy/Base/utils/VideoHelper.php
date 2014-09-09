<?php

namespace Mindy\Helper;

/**
 *  Copyright (C) 2011 by OpenHost S.L.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 **/
/**
 * Wrapper class
 *
 * @author Fran Diéguez <fran@openhost.es>
 * @version \$Id\$
 * @copyright OpenHost S.L., Mér Xuñ 01 15:58:58 2011
 * @package Panorama
 **/

//Yii::import('mindy.utils.video');

class VideoHelper
{
    /**
     * The instance of the object we will work into
     */
    private $object = null;

    private $className = null;

    /**
     * __construct()
     *
     * @param $arg
     */
    public function __construct($url = null, $options = null)
    {

        // check arguments validation
        if (!isset($url) || is_null($url)) {
            throw new InvalidArgumentException("We need a video url");
        }

        $this->url = $url;
        $domain = self::getDomain($url);
        if($domain)
            $serviceName = self::camelize($domain);
        else
            // Incoming url is incorrect
            return false;

        // If the service starts with a number prepend a "c" for avoid PHP language error
        if (preg_match("@^\d@", $serviceName)) {
            $serviceName = "c" . $serviceName;
        }
        $this->className = $serviceName;
        $file = dirname(__FILE__) . DIRECTORY_SEPARATOR . "video" . DIRECTORY_SEPARATOR . $this->className . ".php";

        // If the Video service is supported instantiate it, otherwise raise Exception
        if (file_exists($file)) {
            include($file);
            $this->object = new $this->className($url, $options);
            if (!($this->object instanceof VideoInterface)) {
                throw new Exception("Video ID not valid.");
            }
        } else {
            throw new Exception("Video service or Url not supported");
        }
    }

    /**
     * Returns the sercice object to operate directly with with
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Returns the video title for the instantiated object.
     *
     * @returns string, the title of the video
     */
    public function getTitle()
    {

        return $this->object->getTitle();

    }

    /**
     * Returns the video thumbnail url for the instantiated object.
     *
     * @returns string, the thumbnail url of the video
     */
    public function getThumbnail()
    {
        return $this->object->getThumbnail();
    }

    /**
     * Returns the video duration for the instantiated object.
     *
     * @returns int, the duration of the video
     */
    public function getDuration()
    {
        return $this->object->getDuration();
    }

    /**
     * Returns the video embed url for the instantiated object.
     *
     * @returns string, the embed of the video
     */
    public function getEmbedUrl()
    {
        return $this->object->getEmbedUrl();
    }

    /**
     * Returns the video irfor the instantiated object.
     *
     * @returns string, the id of the video
     */
    public function getVideoID()
    {
        return $this->object->getVideoID();
    }

    /**
     * Returns the video embed url for the instantiated object.
     *
     * @returns string, the embed of the video
     */
    public function getEmbedHTML($options, $defaultOptions)
    {
        return $this->object->getEmbedHTML($options, $defaultOptions);
    }

    /**
     * Returns the video embed url for the instantiated object.
     *
     * @returns string, the embed of the video
     */
    public function getFLV()
    {
        return $this->object->getFLV();
    }

    /**
     * Returns the video embed url for the instantiated object.
     *
     * @returns string, the embed of the video
     */
    public function getDownloadUrl()
    {
        return $this->object->getDownloadUrl();
    }

    /**
     * Returns the video embed url for the instantiated object.
     *
     * @returns string, the embed of the video
     */
    public function getService()
    {
        return $this->object->getService();
    }

    /**
     * Returns the video embed url for the instantiated object.
     *
     * @returns string, the embed of the video
     */
    public function getVideoDetails($width = 425, $height = 344)
    {

        return array(
            "title" => $this->object->getTitle(),
            "thumbnail" => $this->object->getThumbnail(),
            "embedUrl" => $this->object->getEmbedUrl(),
            "embedHTML" => $this->object->getEmbedHTML(),
            "FLV" => $this->object->getFLV(),
            "downloadUrl" => $this->object->getDownloadUrl(),
            "service" => $this->object->getService(),
            "duration" => $this->object->getDuration(),
        );

    }

    /**
     * Returns the given lower_case_and_underscored_word as a CamelCased word.
     *
     * @param string $lower_case_and_underscored_word Word to camelize
     *
     * @return string Camelized word. LikeThis.
     * @access public
     * @static
     * @link http://book.cakephp.org/view/572/Class-methods
     */
    public function camelize($lowerCaseAndUnderscoredWord)
    {
        return str_replace(" ", "", ucwords(str_replace("_", " ", $lowerCaseAndUnderscoredWord)));
    }

    /**
     * Returns the domain string from url
     *
     * @param $url
     */
    public static function getDomain($url = "")
    {
        $host = parse_url($url);
        if(isset($host['host'])) {
            $domainParts = preg_split("@\.@", $host["host"]);

            /**
             * If domain name has a subdomain return the second part
             * if not return the first part
             */
            $domainPartsCount = count($domainParts);

            return $domainParts[$domainPartsCount - 2];
        }

        return false;
    }

}

/**
 *  Copyright (C) 2011 by OpenHost S.L.
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 **/
/**
 * Definition of the API for all the video resource clases
 *
 * @author Fran Diéguez <fran@openhost.es>
 * @version \$Id\$
 * @copyright OpenHost S.L., Mér Xuñ 01 15:58:58 2011
 * @package Panorama\Video
 **/

/**
 * Interface to define the API for all the video resource clases
 *
 * @package Panorama\Video
 * @author Fran Diéguez
 **/
interface VideoInterface
{

    /**
     * Returns the download url for the video
     */
    public function getDownloadUrl();

    /**
     * Returns the video duration in secs
     */
    public function getDuration();

    /**
     * Returns the video embedHTML for put in a webpage
     */
    public function getEmbedHTML();

    /**
     * Returns the url of the video for embed in custom flash player
     */
    public function getEmbedUrl();

    /**
     * Returns the url of the video in FLV format
     */
    public function getFLV();

    /**
     * Returns the service name of the video
     */
    public function getService();

    /**
     * Returns the default thumbnail of this video
     */
    public function getThumbnail();

    /**
     * Returns the title of this video
     */
    public function getTitle();

    /**
     * Returns the internal video id in the particular service
     */
    public function getVideoId();

}
