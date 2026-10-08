<?php

namespace App\Services\ThreeD;

interface ThreeDParserInterface
{
    /**
     * Parse a 3D model file and return analysis metadata.
     *
     * @param string $filePath
     * @return array
     */
    public function parse(string $filePath): array;
}
