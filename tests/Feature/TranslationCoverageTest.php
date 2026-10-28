<?php

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

it('ships every literal interface message in each configured language', function () {
    $parser = (new ParserFactory)->createForNewestSupportedVersion();
    $finder = new NodeFinder;
    $compiler = app('blade.compiler');
    $parseErrors = [];
    $keys = array_fill_keys([
        '7 days', '30 days', '60 days', '90 days', '1 year', 'Manual',
        'New Server', 'Operating system', 'Architecture', 'Kernel',
        'CPU cores', 'Memory', 'Docker version', 'Compose version', 'Up since',
        'This private key is currently used by a server, application, or Git app and cannot be deleted.',
        'Other', 'Application', 'Service', 'Database', 'Docker Hub',
        'GitHub Container Registry', 'GitLab Container Registry',
        'Google Artifact Registry', 'Azure Container Registry', 'AWS ECR',
    ], true);

    foreach ([app_path(), resource_path('views')] as $directory) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            if ($source === false) {
                throw new RuntimeException("Cannot read translation source: {$file->getPathname()}");
            }

            if (str_ends_with($file->getFilename(), '.blade.php')) {
                $source = $compiler->compileString($source);
            }

            try {
                $calls = $finder->findInstanceOf($parser->parse($source) ?? [], FuncCall::class);
            } catch (\PhpParser\Error $error) {
                $parseErrors[str_replace(base_path().'/', '', $file->getPathname())] = $error->getMessage();

                continue;
            }

            foreach ($calls as $call) {
                if (! $call->name instanceof Name || ! in_array($call->name->toString(), ['__', 'trans_choice'], true)) {
                    continue;
                }

                $argument = $call->args[0]->value ?? null;
                if ($argument instanceof String_) {
                    $keys[$argument->value] = true;
                }
            }
        }
    }

    expect($parseErrors)->toBe([]);

    $missingByLocale = [];
    foreach (array_keys(config('app.available_locales')) as $locale) {
        $translations = json_decode(file_get_contents(lang_path("{$locale}.json")), true, flags: JSON_THROW_ON_ERROR);
        $missing = array_keys(array_diff_key($keys, $translations));
        if ($missing !== []) {
            $missingByLocale[$locale] = ['count' => count($missing), 'examples' => array_slice($missing, 0, 2)];
        }
    }

    expect($missingByLocale)->toBe([]);
});
