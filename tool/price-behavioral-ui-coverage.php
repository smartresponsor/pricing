<?php

declare(strict_types=1);

$projectDir = dirname(__DIR__);
$surfaceRoots = [
    'src/Controller',
    'config/routes',
    'templates',
    'assets',
];

$unexpected = [];
foreach ($surfaceRoots as $relativeRoot) {
    $root = $projectDir.'/'.$relativeRoot;
    if (!is_dir($root)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file instanceof SplFileInfo || !$file->isFile()) {
            continue;
        }

        $relativePath = str_replace('\\', '/', substr($file->getPathname(), strlen($projectDir) + 1));
        $unexpected[] = $relativePath;
    }
}

if ([] !== $unexpected) {
    sort($unexpected);
    fwrite(
        STDERR,
        "Behavioral/UI coverage producer requires an explicit denominator update because application surfaces now exist:\n"
        .implode("\n", array_map(static fn (string $path): string => '- '.$path, $unexpected))
        ."\n",
    );
    exit(1);
}

$coverageDirectory = $projectDir.'/var/coverage';
if (!is_dir($coverageDirectory) && !mkdir($coverageDirectory, 0777, true) && !is_dir($coverageDirectory)) {
    fwrite(STDERR, "Cannot create var/coverage.\n");
    exit(2);
}

$evidence = [
    'schema' => 'behavioral-ui-coverage-v2',
    'generatedAt' => (new DateTimeImmutable('now'))->format(DateTimeInterface::ATOM),
    'producer' => ['kind' => 'repository_script', 'script' => 'test:behavioral-coverage'],
    'dimensions' => [
        'functional' => ['eligible' => [], 'covered' => []],
        'behavioral' => ['eligible' => [], 'covered' => []],
        'ui' => ['eligible' => [], 'covered' => []],
        'critical' => ['eligible' => [], 'covered' => []],
    ],
];

try {
    $json = json_encode($evidence, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
} catch (JsonException $exception) {
    fwrite(STDERR, 'Cannot encode behavioral/UI coverage evidence: '.$exception->getMessage()."\n");
    exit(2);
}

if (false === file_put_contents($coverageDirectory.'/behavioral-ui.json', $json)) {
    fwrite(STDERR, "Cannot write var/coverage/behavioral-ui.json.\n");
    exit(2);
}
