<?php

namespace App\Services\ThreeD;

use Exception;
use Illuminate\Support\Carbon;

class StlParserService implements ThreeDParserInterface
{
    /**
     * Parse an STL file and return mesh metrics and metadata.
     */
    public function parse(string $filePath): array
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            return $this->buildErrorResult('File does not exist or is not readable.');
        }

        $fileSize = filesize($filePath);
        if ($fileSize === 0 || $fileSize === false) {
            return $this->buildErrorResult('File is empty.');
        }

        try {
            // Attempt Binary STL parsing first
            if ($this->isBinaryStl($filePath, $fileSize)) {
                return $this->parseBinaryStl($filePath, $fileSize);
            }

            // Fallback to ASCII STL parsing
            return $this->parseAsciiStl($filePath);
        } catch (Exception $e) {
            return $this->buildErrorResult($e->getMessage());
        }
    }

    /**
     * Determine if file matches Binary STL format structure.
     */
    private function isBinaryStl(string $filePath, int $fileSize): bool
    {
        if ($fileSize < 84) {
            return false;
        }

        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return false;
        }

        $header = fread($handle, 80);
        $countData = fread($handle, 4);
        fclose($handle);

        if (strlen($header) !== 80 || strlen($countData) !== 4) {
            return false;
        }

        $unpacked = unpack('Vcount', $countData);
        $triangleCount = $unpacked['count'] ?? 0;

        $expectedSize = 84 + ($triangleCount * 50);

        if ($fileSize === $expectedSize && $triangleCount > 0) {
            return true;
        }

        return false;
    }

    /**
     * Parse Binary STL file.
     */
    private function parseBinaryStl(string $filePath, int $fileSize): array
    {
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return $this->buildErrorResult('Could not open file for binary reading.');
        }

        $header = fread($handle, 80);
        $countData = fread($handle, 4);
        $unpackedCount = unpack('Vcount', $countData);
        $triangleCount = $unpackedCount['count'] ?? 0;

        $vertices = [];

        for ($i = 0; $i < $triangleCount; $i++) {
            $facetData = fread($handle, 50);
            if (strlen($facetData) !== 50) {
                fclose($handle);

                return $this->buildErrorResult('Corrupted binary STL file data.');
            }

            // Normal (12 bytes), V1 (12 bytes), V2 (12 bytes), V3 (12 bytes), Attr (2 bytes)
            $unpacked = unpack('g3normal/g3v1/g3v2/g3v3/vattr', $facetData);
            if (! $unpacked) {
                fclose($handle);

                return $this->buildErrorResult('Corrupted binary STL facet data.');
            }

            $v1 = [$unpacked['v11'], $unpacked['v12'], $unpacked['v13']];
            $v2 = [$unpacked['v21'], $unpacked['v22'], $unpacked['v23']];
            $v3 = [$unpacked['v31'], $unpacked['v32'], $unpacked['v33']];

            foreach ([$v1, $v2, $v3] as $v) {
                if (is_nan($v[0]) || is_nan($v[1]) || is_nan($v[2]) ||
                    is_infinite($v[0]) || is_infinite($v[1]) || is_infinite($v[2])) {
                    fclose($handle);

                    return $this->buildErrorResult('Invalid numeric data in binary STL.');
                }
            }

            $vertices[] = $v1;
            $vertices[] = $v2;
            $vertices[] = $v3;
        }

        fclose($handle);

        $cleanHeader = trim(preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $header));

        return $this->buildSuccessResult($vertices, [
            'format' => 'binary',
            'header' => $cleanHeader,
        ]);
    }

    /**
     * Parse ASCII STL file.
     */
    private function parseAsciiStl(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return $this->buildErrorResult('Failed to read STL file content.');
        }

        $trimmed = trim($content);
        if (empty($trimmed)) {
            return $this->buildErrorResult('File is empty.');
        }

        // ASCII STL must start with solid (case-insensitive)
        if (! preg_match('/^solid\b/i', $trimmed)) {
            return $this->buildErrorResult('Invalid STL file format.');
        }

        // Match solid name if present
        $solidName = '';
        if (preg_match('/^solid\s*(.*?)$/im', $trimmed, $matches)) {
            $solidName = trim($matches[1]);
        }

        // Find all vertices in the file
        preg_match_all('/vertex\s+([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)\s+([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)\s+([+-]?(?:\d+\.?\d*|\.\d+)(?:[eE][+-]?\d+)?)/i', $content, $matches, PREG_SET_ORDER);

        $vertexCount = count($matches);
        if ($vertexCount === 0 || $vertexCount % 3 !== 0) {
            return $this->buildErrorResult('Invalid or corrupted ASCII STL file: missing or incomplete vertices.');
        }

        // Validate basic ASCII structure (facets/loops match count)
        preg_match_all('/facet\s+normal/i', $content, $facetMatches);
        $facetCount = count($facetMatches[0]);
        if ($facetCount !== $vertexCount / 3) {
            return $this->buildErrorResult('Corrupted ASCII STL file: facet and vertex count mismatch.');
        }

        $vertices = [];
        foreach ($matches as $match) {
            $x = (float) $match[1];
            $y = (float) $match[2];
            $z = (float) $match[3];

            if (is_nan($x) || is_nan($y) || is_nan($z) ||
                is_infinite($x) || is_infinite($y) || is_infinite($z)) {
                return $this->buildErrorResult('Invalid numeric data in ASCII STL.');
            }

            $vertices[] = [$x, $y, $z];
        }

        return $this->buildSuccessResult($vertices, [
            'format' => 'ascii',
            'name' => $solidName,
        ]);
    }

    /**
     * Compute metrics from vertices array and build success result.
     */
    private function buildSuccessResult(array $vertices, array $metadata): array
    {
        $vertexCount = count($vertices);
        $triangleCount = intdiv($vertexCount, 3);

        if ($triangleCount === 0) {
            return $this->buildErrorResult('STL file contains no triangles.');
        }

        $minX = INF;
        $maxX = -INF;
        $minY = INF;
        $maxY = -INF;
        $minZ = INF;
        $maxZ = -INF;

        $totalArea = 0.0;
        $totalVolume = 0.0;

        for ($i = 0; $i < $vertexCount; $i += 3) {
            $v1 = $vertices[$i];
            $v2 = $vertices[$i + 1];
            $v3 = $vertices[$i + 2];

            // Bounding box
            foreach ([$v1, $v2, $v3] as $v) {
                if ($v[0] < $minX) {
                    $minX = $v[0];
                }
                if ($v[0] > $maxX) {
                    $maxX = $v[0];
                }
                if ($v[1] < $minY) {
                    $minY = $v[1];
                }
                if ($v[1] > $maxY) {
                    $maxY = $v[1];
                }
                if ($v[2] < $minZ) {
                    $minZ = $v[2];
                }
                if ($v[2] > $maxZ) {
                    $maxZ = $v[2];
                }
            }

            // Surface area cross product
            $ax = $v2[0] - $v1[0];
            $ay = $v2[1] - $v1[1];
            $az = $v2[2] - $v1[2];
            $bx = $v3[0] - $v1[0];
            $by = $v3[1] - $v1[1];
            $bz = $v3[2] - $v1[2];

            $cx = $ay * $bz - $az * $by;
            $cy = $az * $bx - $ax * $bz;
            $cz = $ax * $by - $ay * $bx;

            $area = 0.5 * sqrt($cx * $cx + $cy * $cy + $cz * $cz);
            $totalArea += $area;

            // Signed volume
            $vSigned = ($v1[0] * ($v2[1] * $v3[2] - $v2[2] * $v3[1]) +
                        $v1[1] * ($v2[2] * $v3[0] - $v2[0] * $v3[2]) +
                        $v1[2] * ($v2[0] * $v3[1] - $v2[1] * $v3[0])) / 6.0;
            $totalVolume += $vSigned;
        }

        $width = round($maxX - $minX, 6);
        $height = round($maxY - $minY, 6);
        $depth = round($maxZ - $minZ, 6);

        $centerX = round(($minX + $maxX) / 2.0, 6);
        $centerY = round(($minY + $maxY) / 2.0, 6);
        $centerZ = round(($minZ + $maxZ) / 2.0, 6);

        return [
            'status' => 'completed',
            'parsed_at' => Carbon::now()->toIso8601String(),
            'error_message' => null,
            'vertex_count' => $vertexCount,
            'triangle_count' => $triangleCount,
            'volume' => (float) round(abs($totalVolume), 6),
            'surface_area' => (float) round($totalArea, 6),
            'width' => (float) $width,
            'height' => (float) $height,
            'depth' => (float) $depth,
            'center' => [
                'x' => (float) $centerX,
                'y' => (float) $centerY,
                'z' => (float) $centerZ,
            ],
            'bounds' => [
                'min' => [
                    'x' => (float) round($minX, 6),
                    'y' => (float) round($minY, 6),
                    'z' => (float) round($minZ, 6),
                ],
                'max' => [
                    'x' => (float) round($maxX, 6),
                    'y' => (float) round($maxY, 6),
                    'z' => (float) round($maxZ, 6),
                ],
            ],
            'metadata' => $metadata,
        ];
    }

    /**
     * Build standard error response array.
     */
    private function buildErrorResult(string $errorMessage): array
    {
        return [
            'status' => 'failed',
            'parsed_at' => null,
            'error_message' => $errorMessage,
            'vertex_count' => 0,
            'triangle_count' => 0,
            'volume' => 0.0,
            'surface_area' => 0.0,
            'width' => 0.0,
            'height' => 0.0,
            'depth' => 0.0,
            'center' => [
                'x' => 0.0,
                'y' => 0.0,
                'z' => 0.0,
            ],
            'bounds' => [
                'min' => ['x' => 0.0, 'y' => 0.0, 'z' => 0.0],
                'max' => ['x' => 0.0, 'y' => 0.0, 'z' => 0.0],
            ],
            'metadata' => [],
        ];
    }
}
