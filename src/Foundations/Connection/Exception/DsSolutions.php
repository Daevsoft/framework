<?php
namespace Ds\Foundations\Connection\Exception;

use Spatie\ErrorSolutions\Contracts\Solution;

class DsSolutions implements Solution
{
    private $description;
    private $title;
    private $url;

    public function __construct($title, $description, $url = null)
    {
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
    }
    public function getSolutionTitle(): string
    {
        return $this->title;
    }

    public function getSolutionDescription(): string
    {
        return $this->description;
    }

    public function getDocumentationLinks(): array
    {
        return [
            'Read Docs' => $this->url ?? 'https://github.com/daevsoft/dsframework',
        ];
    }
}
