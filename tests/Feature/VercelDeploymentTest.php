<?php

use App\Database\NeonPostgresConnector;

test('vercel deployment configuration routes requests through the PHP runtime', function () {
    $contents = file_get_contents(base_path('vercel.json'));

    if ($contents === false) {
        throw new RuntimeException('Unable to read vercel.json.');
    }

    $configuration = json_decode(
        $contents,
        true,
        flags: JSON_THROW_ON_ERROR,
    );

    expect($configuration)
        ->toHaveKey('framework', null)
        ->toHaveKey('outputDirectory', 'public')
        ->and($configuration['functions']['api/index.php']['runtime'])
        ->toBe('vercel-php@0.9.0')
        ->and($configuration['env']['APP_URL'])
        ->toBe('https://learning-portal-ecru.vercel.app')
        ->and($configuration['routes'])
        ->toContain([
            'src' => '/(.*)',
            'dest' => '/api/index.php',
        ]);
});

test('postgres connector adds the Neon endpoint startup option', function () {
    $connector = new class extends NeonPostgresConnector
    {
        /**
         * @param  array<string, mixed>  $configuration
         */
        public function dsn(array $configuration): string
        {
            return $this->getDsn($configuration);
        }
    };

    $dsn = $connector->dsn([
        'host' => 'ep-learning-portal-pooler.eu-central-1.aws.neon.tech',
        'port' => '5432',
        'database' => 'learning_portal',
        'charset' => 'utf8',
        'sslmode' => 'require',
    ]);

    expect($dsn)
        ->toContain("options='endpoint=ep-learning-portal'")
        ->toContain('sslmode=require');
});
