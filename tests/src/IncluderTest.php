<?php
namespace Aura\Includer;

use StdClass;

class IncluderTest extends \PHPUnit_Framework_TestCase
{
    protected $includer;
    
    protected $track;
    
    protected $fakefs;
    
    protected function setUp()
    {
        $this->fakefs = __DIR__ . DIRECTORY_SEPARATOR
                      . 'fakefs' . DIRECTORY_SEPARATOR;
        
        $this->track = new StdClass;
        $this->track->files = array();
        
        $this->includer = new Includer;
        
        $this->includer->setDirs(array(
            $this->fakefs . 'dir1',
            $this->fakefs . 'dir2',
            $this->fakefs . 'dirX',
            $this->fakefs . 'dir3',
        ));
        
        $this->includer->setFiles(array(
            'file1.php',
            'file2.php',
            'fileX.php', // file is not readable or does not exist
            '../cache_file.php', // file is not in specified dir
            'file3.php',
        ));
        
        $this->includer->setVars(array(
            'track' => $this->track,
        ));
    }
    
    public function testGetDirs()
    {
        $expect = array(
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR,
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR,
            $this->fakefs . 'dirX' . DIRECTORY_SEPARATOR,
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR,
        );
        $actual = $this->includer->getDirs();
        $this->assertSame($expect, $actual);
    }

    public function testGetFiles()
    {
        $expect = array(
            'file1.php',
            'file2.php',
            'fileX.php',
            '../cache_file.php',
            'file3.php',
        );
        $actual = $this->includer->getFiles();
        $this->assertSame($expect, $actual);
    }
    
    public function testGetVars()
    {
        $actual = $this->includer->getVars();
        $this->assertSame($this->track, $actual['track']);
    }
    
    public function testSetAndGetCacheFile()
    {
        $this->includer->setCacheFile('cache_file.php');
        $this->assertSame('cache_file.php', $this->includer->getCacheFile());
    }
    
    public function testGetPaths()
    {
        $expect = array(
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR . 'file1.php',
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR . 'file2.php',
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR . 'file3.php',
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR . 'file1.php',
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR . 'file2.php',
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR . 'file3.php',
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR . 'file1.php',
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR . 'file2.php',
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR . 'file3.php',
        );
        $actual = $this->includer->getPaths(Includer::DIR_ORDER);
        $this->assertSame($expect, $actual);
        
        $expect = array(
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR . 'file1.php',
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR . 'file1.php',
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR . 'file1.php',
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR . 'file2.php',
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR . 'file2.php',
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR . 'file2.php',
            $this->fakefs . 'dir1' . DIRECTORY_SEPARATOR . 'file3.php',
            $this->fakefs . 'dir2' . DIRECTORY_SEPARATOR . 'file3.php',
            $this->fakefs . 'dir3' . DIRECTORY_SEPARATOR . 'file3.php',
        );
        $actual = $this->includer->getPaths(Includer::FILE_ORDER);
        $this->assertSame($expect, $actual);
        
        $this->setExpectedException('Aura\Includer\Exception\NoSuchOrder');
        $this->includer->getPaths('bad-order');
    }
    
    public function testLoad()
    {
        $this->includer->load();
        $expect = array(
            'dir1:file1.php',
            'dir1:file2.php',
            'dir1:file3.php',
            'dir2:file1.php',
            'dir2:file2.php',
            'dir2:file3.php',
            'dir3:file1.php',
            'dir3:file2.php',
            'dir3:file3.php',
        );
        $actual = $this->track->files;
        $this->assertSame($expect, $actual);
    }

    public function testLoad_cacheFile()
    {
        $this->includer->setCacheFile($this->fakefs . 'cache_file.php');
        $this->includer->load();
        $expect = array('cache file');
        $actual = $this->track->files;
        $this->assertSame($expect, $actual);
    }
    
    public function testRead()
    {
        $actual = $this->includer->read();
        $actual = str_replace(dirname($this->fakefs), '', $actual);
        
        $expect = <<<EXPECT
/**
 * /fakefs/dir1/file1.php
 */
\$track->files[] = basename('/fakefs/dir1') . ':' . basename('/fakefs/dir1/file1.php');

/**
 * /fakefs/dir1/file2.php
 */
\$track->files[] = basename('/fakefs/dir1') . ':' . basename('/fakefs/dir1/file2.php');

/**
 * /fakefs/dir1/file3.php
 */
\$track->files[] = basename('/fakefs/dir1') . ':' . basename('/fakefs/dir1/file3.php');

/**
 * /fakefs/dir2/file1.php
 */
\$track->files[] = basename('/fakefs/dir2') . ':' . basename('/fakefs/dir2/file1.php');

/**
 * /fakefs/dir2/file2.php
 */
\$track->files[] = basename('/fakefs/dir2') . ':' . basename('/fakefs/dir2/file2.php');

/**
 * /fakefs/dir2/file3.php
 */
\$track->files[] = basename('/fakefs/dir2') . ':' . basename('/fakefs/dir2/file3.php');

/**
 * /fakefs/dir3/file1.php
 */
\$track->files[] = basename('/fakefs/dir3') . ':' . basename('/fakefs/dir3/file1.php');

/**
 * /fakefs/dir3/file2.php
 */
\$track->files[] = basename('/fakefs/dir3') . ':' . basename('/fakefs/dir3/file2.php');

/**
 * /fakefs/dir3/file3.php
 */
\$track->files[] = basename('/fakefs/dir3') . ':' . basename('/fakefs/dir3/file3.php');


EXPECT;
        $expect = str_replace('/', DIRECTORY_SEPARATOR, $expect);
        $this->assertSame($expect, $actual);
    }
}
