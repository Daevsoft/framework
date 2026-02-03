<?php

declare(strict_types=1);

namespace Ds\Console;

abstract class Command
{
    /**
     * Command parameters
     */
    protected array $parameters = [];
    /**
     * Command name
     */
    protected string $name = '';

    /**
     * Command description
     */
    protected string $description = '';

    /**
     * Execute the command
     */
    abstract public function handle(): int;

    /**
     * Set command parameters
     */
    public function setParameters(array $parameters): void{
        $this->parameters = $parameters;
    }

    /**
     * Ask for user input
     */
    protected function ask(string $question, string $default = ''): string
    {
        echo $question . ' ';
        $input = trim(fgets(STDIN) ?: '');
        return $input !== '' ? $input : $default;
    }

    /**
     * Display info message
     */
    protected function info(string $message): void
    {
        echo "\033[32m" . $message . "\033[0m\n";
    }

    /**
     * Display error message
     */
    protected function error(string $message): void
    {
        echo "\033[31m" . $message . "\033[0m\n";
    }

    /**
     * Get command parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get command name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get command description
     */
    public function getDescription(): string
    {
        return $this->description;
    }
}


