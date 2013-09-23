<?php
namespace Aura\Includer;

class Includer
{
    const DIR_ORDER = 'dir_order';
    
    const FILE_ORDER = 'file_order';
    
    protected $cache_file;
    
    protected $dirs = array();
    
    protected $files = array();
    
    // a closure for a scope-limited include
    protected $limited_include;
    
    protected $vars = array();
    
    public function __construct()
    {
        $this->limited_include = function ($__FILE__, array $__VARS__) {
            unset($__VARS__['__FILE__']);
            extract($__VARS__);
            unset($__VARS__);
            include $__FILE__;
        };
    }
    
    public function setDirs(array $dirs)
    {
        $this->dirs = array();
        $this->addDirs($dirs);
    }
    
    public function addDirs(array $dirs)
    {
        foreach ($dirs as $dir) {
            $this->addDir($dir);
        }
    }
    
    public function addDir($dir)
    {
        $dir = str_replace('/', DIRECTORY_SEPARATOR, $dir);
        $dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $this->dirs[] = $dir;
    }
    
    public function getDirs()
    {
        return $this->dirs;
    }
    
    public function setFiles(array $files)
    {
        $this->files = array();
        $this->addFiles($files);
    }
    
    public function addFiles(array $files)
    {
        foreach ($files as $file) {
            $this->addFile($file);
        }
    }
    
    public function addFile($file)
    {
        $file = str_replace('/', DIRECTORY_SEPARATOR, $file);
        $this->files[] = $file;
    }
    
    public function getFiles()
    {
        return $this->files;
    }
    
    public function setCacheFile($cache_file)
    {
        $this->cache_file = $cache_file;
    }
    
    public function getCacheFile()
    {
        return $this->cache_file;
    }
    
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }
    
    public function getVars()
    {
        return $this->vars;
    }
    
    public function getPaths($order = self::DIR_ORDER)
    {
        if ($order == self::DIR_ORDER) {
            return $this->getPathsByDirOrder();
        }
        
        if ($order == self::FILE_ORDER) {
            return $this->getPathsByFileOrder();
        }
        
        throw new Exception\NoSuchOrder;
    }
    
    protected function getPathsByDirOrder()
    {
        $paths = array();
        foreach ($this->dirs as $dir) {
            foreach ($this->files as $file) {
                $this->addRealPath($paths, $dir, $file);
            }
        }
        return $paths;
    }
    
    protected function getPathsByFileOrder()
    {
        $paths = array();
        foreach ($this->files as $file) {
            foreach ($this->dirs as $dir) {
                $this->addRealPath($paths, $dir, $file);
            }
        }
        return $paths;
    }
    
    protected function addRealPath(&$paths, $dir, $file)
    {
        // do we have read access to the real path to the file?
        $path = realpath($dir . $file);
        if (! $path) {
            // no, don't retain it
            return;
        }
        
        // is the file actually in the specified directory?
        // this will fail with symlinks.
        $dir_len = strlen($dir);
        if (substr($path, 0, $dir_len) != $dir) {
            // not actually in the directory, don't retain it
            return;
        }
        
        // retain the real path
        $paths[] = $path;
    }
    
    // includes the files
    public function load($order = self::DIR_ORDER)
    {
        $limited_include = $this->limited_include;
        
        if ($this->cache_file) {
            $limited_include($this->cache_file, $this->vars);
            return;
        }
        
        $paths = $this->getPaths($order);
        foreach ($paths as $path) {
            $limited_include($path, $this->vars);
        }
    }
    
    // reads and concatenates all file contents, stripping PHP tags.
    public function read($order = self::DIR_ORDER)
    {
        $text = '';
        $paths = $this->getPaths($order);
        foreach ($paths as $path) {
            $text .= $this->readFileContents($path);
        }
        return $text;
    }
    
    protected function readFileContents($path)
    {
        // get the file contents
        $text = file_get_contents($path);
        
        // trim all whitespace
        $text = trim($text);
        
        // strip any leading php tag
        if (substr($text, 0, 5) == '<?php') {
            $text = substr($text, 5);
        }
        
        // strip any trailing php tag
        if (substr($text, -2) == '?>') {
            $text = substr($text, 0, -2);
        }
        
        // replace __FILE__ constant with actual file name string. this is
        // because we are concatenating from multiple files. probably better
        // to do token replacement rather than string replacement.
        $text = str_replace('__FILE__', "'$path'", $text);
        
        // replace__DIR__ constant with actual directory name string. this is
        // because we are concatenating from multiple files. probably better
        // to do token replacement rather than string replacement.
        $text = str_replace('__DIR__', "'" . dirname($path) . "'", $text);
        
        // return with a leading comment and trailing newlines
        return '/**' . PHP_EOL
              . ' * ' . $path . PHP_EOL
              . ' */' . PHP_EOL
              . trim($text) . PHP_EOL . PHP_EOL;
    }
}
