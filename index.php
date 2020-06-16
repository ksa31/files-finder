<?php

/**
 * Class FileFinder
 */
class FileFinder
{
    const MEGABYTE_IN_BYTES = 1024*1024;

    /** @var int Min size of file */
    protected $minFileSize;

    /** @var string Extension of file */
    protected $fileExtension;

    /** @var string Path to dir */
    private $path;

    /**
     * FileFinder constructor.
     *
     * @param string $path          Path to dir
     * @param string $fileExtension Extension of file
     * @param int    $minFileSize   Min size of file
     */
    public function __construct(string $path, string $fileExtension, int $minFileSize)
    {
        $this->path          = $path;
        $this->fileExtension = $fileExtension;
        $this->minFileSize   = $minFileSize * self::MEGABYTE_IN_BYTES;
    }

    public function run(): Generator
    {
        $directoryIterator = $this->getDirectoryIterator($this->path);
        $recursiveIterator = $this->getRecursiveIteratorIterator($directoryIterator);

        return $this->iterateFiles($recursiveIterator);
    }

    protected function iterateFiles(RecursiveIteratorIterator $iterator)
    {
        /**
         * @var string $filename
         * @var SplFileInfo $currentFile
         */
        foreach ($iterator as $filename => $currentFile) {
            if ($this->isValid($currentFile)) {
                yield $currentFile->getRealPath() => $this->humanFileSize($currentFile->getSize());
            }
        }
    }

    /**
     * Check of file by search rules
     *
     * @param SplFileInfo $file
     *
     * @return bool
     */
    protected function isValid(SplFileInfo $file): bool
    {
        return $this->checkFileExtension($file, $this->fileExtension)
            && $this->checkMinFileSize($file, $this->minFileSize);
    }

    /**
     * Check of the file extension
     *
     * @param SplFileInfo $file
     * @param string $fileExtension
     *
     * @return bool
     */
    protected function checkFileExtension(SplFileInfo $file, string $fileExtension): bool
    {
        return strtolower($file->getExtension()) === strtolower($fileExtension);
    }

    /**
     * Check of min size of file
     *
     * @param SplFileInfo $file
     * @param int $fileSize
     *
     * @return bool
     */
    protected function checkMinFileSize(SplFileInfo $file, int $fileSize): bool
    {
        return $file->getSize() > $fileSize;
    }

    protected function humanFileSize(int $size): string
    {
        if ($size >= 1<<30)
            return number_format($size / (1<<30),2).'GB';

        if ($size >= 1<<20)
            return number_format($size / (1<<20),2).'MB';

        if ($size >= 1<<10)
            return number_format($size / (1<<10),2).'KB';

        return number_format($size).' bytes';
    }

    /**
     * @param string $path
     *
     * @return RecursiveDirectoryIterator
     */
    private function getDirectoryIterator(string $path): RecursiveDirectoryIterator
    {
        return new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
    }

    /**
     * @param RecursiveDirectoryIterator $iterator
     *
     * @return RecursiveIteratorIterator
     */
    private function getRecursiveIteratorIterator(RecursiveDirectoryIterator $iterator): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator($iterator);
    }
}

$list = (new FileFinder(dirname(__FILE__), 'jpg', 5))->run();

foreach ($list as $name => $size) {
    echo $name.' - '.$size.PHP_EOL;
}
