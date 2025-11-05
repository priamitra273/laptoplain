<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Sqids\Sqids;

class GenerateSqidsFile extends Command
{
    protected $signature = 'sqids:generate {--file=storage/app/sqids.txt}';
    protected $description = 'Generate Sqids (IDs 1–100) and save to a .txt file using env config only';

    public function handle()
    {
        // Read from .env only
        $alphabet = env('SQIDS_ALPHABET', );
        $minLength = (int) env('SQIDS_MIN_LENGTH');

        // Create Sqids instance using env values
        $sqids = new Sqids(
            alphabet: $alphabet,
            minLength: $minLength
        );

        $output = '';
        for ($i = 1; $i <= 100; $i++) {
            $encoded = $sqids->encode([$i]);
            $output .= sprintf("%d => %s\n", $i, $encoded);
        }

        $file = base_path($this->option('file'));

        // Make sure folder exists
        if (!is_dir(dirname($file))) {
            mkdir(dirname($file), 0755, true);
        }

        file_put_contents($file, $output);

        $this->info("✅ Sqids file generated successfully!");
        $this->info("📁 Location: {$file}");

        return Command::SUCCESS;
    }
}
