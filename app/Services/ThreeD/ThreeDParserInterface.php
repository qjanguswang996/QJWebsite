<?php

namespace App\Services\ThreeD;

interface ThreeDParserInterface
{
    /**
     * Parse a 3D model file and return analysis metadata.
     */
    public function parse(string $filePath): array;
}
