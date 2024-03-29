<?php

namespace Sunnysideup\UnderConstruction\Api;

use SilverStripe\Assets\Image;

use SilverStripe\Control\Controller;

use SilverStripe\Control\Director;

use SilverStripe\Core\Config\Config;


use SilverStripe\ORM\ArrayList;
use SilverStripe\SiteConfig\SiteConfig;

use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ViewableData;


use Sunnysideup\UnderConstruction\Tasks\GoOffline;

use Sunnysideup\UnderConstruction\Tasks\GoOnline;

class CalculatedValues extends ViewableData
{
    /**
     * @var string
     */
    private const UNDER_CONSTRUCTION_FOLDER_NAME = 'offline';

    /**
     * @var string
     */
    private const UNDER_CONSTRUCTION_FILE_NAME = 'offline.php';

    protected $sc;

    private static $under_construction_bg_options = [
        '#000' => 'black',
        '#222' => 'off black',
        '#ddd' => 'off white',
        '#fff' => 'white',
        'linear-gradient(to left, rgb(195, 20, 50), rgb(36, 11, 54))' => 'witching hour',
        'linear-gradient(to left, rgb(189, 195, 199), rgb(44, 62, 80))' => 'grade grey',
        'linear-gradient(to left, rgb(55, 59, 68), rgb(66, 134, 244))' => 'dark ocean',
        'linear-gradient(to left, rgb(30, 150, 0), rgb(255, 242, 0), rgb(255, 0, 0))' => 'rastafari',
        'linear-gradient(to left, rgb(253, 200, 48), rgb(243, 115, 53))' => 'citrus peel',
        'linear-gradient(to left, rgb(0, 0, 0), rgb(15, 155, 15))' => 'terminal',
        'linear-gradient(to left, rgb(0, 0, 70), rgb(28, 181, 224))' => 'vision of grandeur',
        'linear-gradient(to left, rgb(247, 151, 30), rgb(255, 210, 0))' => 'learning and leading',
        'linear-gradient(to left, rgb(93, 65, 87), rgb(168, 202, 186))' => 'forever lost',
        'linear-gradient(to left, rgb(0, 4, 40), rgb(0, 78, 146))' => 'frost',
    ];

    private static $under_construction_fg_options = [
        '#fff' => 'white',
        '#ddd' => 'off white',
        '#222' => 'off black',
        '#000' => 'black',
    ];

    public function __construct(SiteConfig $siteConfig)
    {
        parent::__construct();
        $this->sc = $siteConfig;
    }

    public static function go_offline_link(): string
    {
        return '/dev/tasks/' . Config::inst()->get(GoOffline::class, 'segment');
    }

    public static function go_online_link(): string
    {
        return '/dev/tasks/' . Config::inst()->get(GoOnline::class, 'segment');
    }

    public function getSiteConfig(): SiteConfig
    {
        return $this->sc;
    }

    public function CreateFiles()
    {
        //create html
        if ($this->CreateDirAndTest()) {
            $this->CreateFilesInner();
        }
    }

    public function CreateDirAndTest(): bool
    {
        $this->CreateFilesInner();
        $dir = dirname($this->UnderConstructionFilePath());
        @mkdir($dir);
        $messages = [];
        if (!file_exists($dir)) {
            $messages[] = 'Could not create offline folder (' . $this->UnderConstructionFolderName() . ').';
        }
        if (!is_writable($dir)) {
            $messages[] = 'Could not writes files in offline folder (' . $this->UnderConstructionFolderName() . ').';
        }
        if (!file_exists($this->getHtAccessPath())) {
            $messages[] = 'Could not find .htaccess file (' . $this->getHtAccessPath() . ').';
        }
        if (!is_writable($this->getHtAccessPath())) {
            $messages[] = 'Could not write .htaccess file (' . $this->getHtAccessPath() . ').';
        }
        if (!file_exists($this->UnderConstructionFilePath())) {
            $messages[] = 'Offline file does not exist yet (' . $this->UnderConstructionFilePath() . ').';
        }
        if (!is_writable($this->UnderConstructionFilePath())) {
            $messages[] = 'Offline file can not be altered (' . $this->UnderConstructionFilePath() . ').';
        }
        if (count($messages) === 0) {
            $outcome = true;
            $messages[] = 'All files are in order.';
        } else {
            $outcome = false;
        }
        $this->sc->UnderConstructionOutcome = implode(' ', $messages);
        $this->sc->write();

        return $outcome;
    }

    /**
     * arraylist of ips with two values: Ip and IpEscaped
     * @return ArrayList [description]
     */
    public function UnderConstructionIpAddresses(): ArrayList
    {
        $array = explode(',', $this->sc->UnderConstructionExcludedIps);
        $al = ArrayList::create();
        foreach ($array as $ip) {
            $ip = trim($ip);
            $ipEscaped = str_replace('.', '\\.', $ip);
            $al->push(ArrayData::create(['Ip' => $ip, 'IpEscaped' => $ipEscaped]));
        }

        return $al;
    }

    /**
     * something like /var/www/mysite/public/offline/offline.php
     */
    public function UnderConstructionFilePath(): string
    {
        return Controller::join_links(
            Director::baseFolder(),
            Director::publicDir(),
            $this->UnderConstructionFolderName(),
            $this->UnderConstructionFileName()
        );
    }

    /**
     * something like https://mysite.com/offline/offline.php.
     */
    public function UnderConstructionUrlPath(): string
    {
        return Controller::join_links(
            Director::absoluteBaseURL(),
            $this->UnderConstructionFolderName(),
            $this->UnderConstructionFileName()
        );
    }

    /**
     * something like /var/www/mysite/public/offline/offline.php.img
     */
    public function UnderConstructionImagePath(): string
    {
        $extension = $this->sc->UnderConstructionImage()->getExtension();

        return $this->UnderConstructionFilePath() . '.' . $extension;
    }

    /**
     * something like offline
     */
    public function UnderConstructionFolderName(): string
    {
        return self::UNDER_CONSTRUCTION_FOLDER_NAME;
    }

    /**
     * something like offline
     */
    public function UnderConstructionFileName(): string
    {
        return self::UNDER_CONSTRUCTION_FILE_NAME;
    }

    /**
     * something like offline.php.png.
     */
    public function UnderConstructionImageName(): string
    {
        $path = $this->UnderConstructionImagePath();
        if (file_exists($path)) {
            return basename($path);
        }
        return '';
    }

    /**
     * something like offline.php.png.
     */
    public function UnderConstructionOriginalImagePath(): string
    {
        if ($this->sc->UnderConstructionImageID) {
            if ($this->sc->UnderConstructionImage()->exists()) {
                $name = $this->sc->UnderConstructionImage()->getFilename();
                return Controller::join_links(
                    Director::baseFolder(),
                    Director::publicDir(),
                    ASSETS_DIR,
                    $name
                );
            }
        }
        return '';
    }

    public function getHtAccessContent(): string
    {
        // SSViewer::config()->update('theme_enabled', true);
        Requirements::clear();
        Config::modify()->set(SSViewer::class, 'source_file_comments', false);
        $txt = $this->renderWith('Sunnysideup\\UnderConstruction\\UnderConstructionHtAccess');
        // SSViewer::config()->update('theme_enabled', false);

        $array = explode(PHP_EOL, $txt);

        return PHP_EOL . implode(PHP_EOL, $array) . PHP_EOL;
    }

    public function UnderConstructionIsReady(): bool
    {
        return file_exists($this->UnderConstructionFilePath());
    }

    public function UnderConstructionForegroundColour()
    {
        return $this->sc->UnderConstructionForegroundColour ?: '#333';
    }

    public function UnderConstructionBackgroundColour()
    {
        return $this->sc->UnderConstructionBackgroundColour ?: '#333';
    }

    public function getHtAccessPath(): string
    {
        return Controller::join_links(Director::baseFolder(), Director::publicDir(), '.htaccess');
    }

    public function CreateFilesInner()
    {
        // SSViewer::config()->update('theme_enabled', false);
        Config::modify()->set(SSViewer::class, 'source_file_comments', false);
        Requirements::clear();
        $html = $this->renderWith('Sunnysideup\\UnderConstruction\\UnderConstructionPage');
        // SSViewer::config()->update('theme_enabled', false);
        $fileName = $this->UnderConstructionFilePath();
        if (file_exists($fileName)) {
            unlink($fileName);
        }
        //delete timestamp
        if (file_exists($fileName . '.txt')) {
            unlink($fileName . '.txt');
        }
        //create image
        file_put_contents($fileName, $html);
        $image = $this->sc->UnderConstructionImage();
        if ($image && $image->exists()) {
            $originalImagePath = $this->UnderConstructionOriginalImagePath();
            $newImagePath = $this->UnderConstructionImagePath();
            if (file_exists($newImagePath)) {
                unlink($newImagePath);
            }
            copy($originalImagePath, $newImagePath);
        }
    }
}
