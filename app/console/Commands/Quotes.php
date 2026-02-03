<?php
namespace App\Console\Commands;

use Ds\Console\Command;

class Quotes extends Command {
    protected string $name = 'quotes:inspire';
    protected string $description = 'Display an inspiring quote';

    public function handle(): int {
        $quotes = [
            "The best way to get started is to quit talking and begin doing. - Walt Disney",
            "Don't let yesterday take up too much of today. - Will Rogers",
            "It's not whether you get knocked down, it's whether you get up. - Vince Lombardi",
            "If you are working on something exciting, it will keep you motivated. - Unknown",
            "Success is not in what you have, but who you are. - Bo Bennett"
        ];

        $randomIndex = array_rand($quotes);
        $this->info($quotes[$randomIndex]);

        return 0;
    }
}