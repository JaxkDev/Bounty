<?php

/*
 * https://github.com/pmmp/DevTools/blob/0c46527bee72324e5fee0c4ed2c7f5a324b6a4d0/src/DevTools/ConsoleScript.php#L62
 */

declare(strict_types=1);

echo "Building plugin...\n";

/** @phpstan-ignore-next-line */
$basePath = rtrim(realpath(__DIR__), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

$includedPaths = array_map(function($path) : string{
    return rtrim(str_replace("/", DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
}, ['src','resources']);

$excludedPaths = [
    ".md",
    "vendor"
];

$manifest = file_get_contents($basePath."plugin.yml");

$version = preg_match("/version:\s*(\S+)/", $manifest, $matches) ? $matches[1] : null;
if($version === null){
    throw new \RuntimeException("Could not find version in plugin.yml");
}
$name = preg_match("/name:\s*(\S+)/", $manifest, $matches) ? $matches[1] : null;
if($name === null){
    throw new \RuntimeException("Could not find name in plugin.yml");
}

$name = ((!($opt = getopt("o:")) || $opt['o'] === false) ? str_replace(".","_",("{$name}_v".$version)).".phar" : $opt["o"]);

if (!is_dir("dist")) mkdir("dist");

foreach(buildPhar(__DIR__.DIRECTORY_SEPARATOR."dist".DIRECTORY_SEPARATOR.$name, $basePath, $includedPaths, $excludedPaths, "<?php __HALT_COMPILER();") as $line){
    echo $line.PHP_EOL;
}

/**
 * @param string[]    $strings
 * @param string|null $delim
 *
 * @return string[]
 */
function preg_quote_array(array $strings, string $delim = null) : array{
    return array_map(function(string $str) use ($delim) : string{ return preg_quote($str, $delim); }, $strings); /** @phpstan-ignore-line  */
}

/**
 * @param string $pharPath
 * @param string $basePath
 * @param string[] $includedPaths
 * @param string[] $excludedPaths
 * @param string $stub
 * @param int $signatureAlgo
 *
 * @return Generator|string[]
 */
function buildPhar(string $pharPath, string $basePath, array $includedPaths, array $excludedPaths, string $stub, int $signatureAlgo = Phar::SHA1): array|Generator{
    if(file_exists($pharPath)){
        yield "Phar file already exists, overwriting...";
        try{
            Phar::unlinkArchive($pharPath);
        }catch(PharException){
            //unlinkArchive() doesn't like dodgy phars
            unlink($pharPath);
        }
    }

    yield "Output File: $pharPath";

    yield "Adding files...";

    $start = microtime(true);
    $phar = new Phar($pharPath);
    $phar->setMetadata([]);
    $phar->setStub($stub);
    $phar->setSignatureAlgorithm($signatureAlgo);
    $phar->startBuffering();

    /** @phpstan-ignore-next-line */
    $excludedSubstrings = preg_quote_array(array_merge([
        realpath($pharPath) //don't add the phar to itself
    ], $excludedPaths), '/');

    $folderPatterns = preg_quote_array([
        DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR,
        DIRECTORY_SEPARATOR.'.' //"Hidden" files, git dirs etc
    ], '/');

    $basePattern = preg_quote(rtrim($basePath, DIRECTORY_SEPARATOR), '/');
    foreach($folderPatterns as $p){
        $excludedSubstrings[] = $basePattern.'.*'.$p;
    }

    $regex = sprintf('/^(?!.*(%s))^%s(%s).*/i',
        implode('|', $excludedSubstrings),
        preg_quote($basePath, '/'),
        implode('|', preg_quote_array($includedPaths, '/'))
    );

    $directory = new RecursiveDirectoryIterator($basePath, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS | FilesystemIterator::CURRENT_AS_PATHNAME);
    $iterator = new RecursiveIteratorIterator($directory);
    $regexIterator = new RegexIterator($iterator, $regex);

    $count = count($phar->buildFromIterator($regexIterator, $basePath))+2;
    $phar->addFile("plugin.yml");
    $phar->addFile("LICENSE");
    yield "Added $count files, Compressing...";

    $phar->compressFiles(Phar::GZ);

    yield "Compressed";

    $phar->stopBuffering();

    yield "Done in ".round(microtime(true) - $start, 3)."s";
}