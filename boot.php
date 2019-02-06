<?php
require_once "vendor/autoload.php";


sample_prof_start(50);
register_shutdown_function(function(){
sample_prof_end();              // disable profiler
$data = sample_prof_get_data(); // retrieve profiling data
file_put_contents('prof.txt', serialize($data));
});

$c = new Console_CommandLine();
$c->description = 'php-terminal-nes-emulator';
$c->version = '1.0.0';

$c->addArgument('file', [
    'description' => 'Nes ROM image.',
]);
$c->addOption('canvas', [
    'short_name' => '-c',
    'long_name' => '--canvas',
    'description' => 'Canvas to display screen.'.PHP_EOL.'Option: terminal (default), png, null',
    'action' => 'StoreString',
    'default' => 'Terminal',
]);

$filename = null;
$canvas = null;
try {
    $parsed = $c->parse();

    if (! isset($parsed->args['file'])) {
        throw new \RuntimeException('You need to pass the ROM file name.');
    }
    $filename = $parsed->args['file'];
    if (isset($parsed->options['canvas'])) {
        $canvasName = ucfirst(strtolower($parsed->options['canvas']));
        $canvasClassName = sprintf('\\Nes\\Ppu\\Canvas\\%sCanvas', $canvasName);
        if (! class_exists($canvasClassName)) {
            throw new \RuntimeException('Invalid canvas.');
        }
        $canvas = new $canvasClassName();
    }
} catch (Exception $e) {
    die($e->getMessage().PHP_EOL);
}

$nes = new \Nes\Nes($canvas);
try {
    $nes->load($filename);
} catch (Exception $e) {
    echo $e->getMessage();
}
$nes->start();
