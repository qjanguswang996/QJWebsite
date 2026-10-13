<?php

namespace App\Services\ThreeD;

use Exception;
use Illuminate\Support\Carbon;
use SimpleXMLElement;
use ZipArchive;

class ThreeMfParserService implements ThreeDParserInterface
{
    /**
     * Parse a 3MF model file and return analysis metadata.
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
            return $this->parse3MfFile($filePath);
        } catch (Exception $e) {
            return $this->buildErrorResult($e->getMessage());
        }
    }

    /**
     * Parse 3MF ZIP archive and process geometry.
     */
    private function parse3MfFile(string $filePath): array
    {
        $zip = new ZipArchive;
        $res = $zip->open($filePath);
        if ($res !== true) {
            return $this->buildErrorResult('Invalid or corrupted 3MF file (failed to open ZIP archive).');
        }

        // Validate Zip Slip security
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName !== false && $this->isZipSlipPath($entryName)) {
                $zip->close();

                return $this->buildErrorResult('Invalid ZIP archive: potential Zip Slip path traversal detected.');
            }
        }

        // Find model files inside ZIP archive
        $modelFiles = $this->findModelFiles($zip);

        if (empty($modelFiles)) {
            $zip->close();

            return $this->buildErrorResult('Missing 3D model XML file in 3MF archive.');
        }

        $allVertices = [];
        $modelMetadata = [
            'format' => '3mf',
            'model_files' => $modelFiles,
        ];

        foreach ($modelFiles as $modelPath) {
            $xmlContent = $zip->getFromName($modelPath);
            if ($xmlContent === false || trim($xmlContent) === '') {
                $zip->close();

                return $this->buildErrorResult("3D model XML file [{$modelPath}] is empty or unreadable.");
            }

            $modelVertices = $this->parseModelXml($xmlContent);
            if (is_string($modelVertices)) {
                // Returned error message
                $zip->close();

                return $this->buildErrorResult($modelVertices);
            }

            $allVertices = array_merge($allVertices, $modelVertices);
        }

        $zip->close();

        if (empty($allVertices)) {
            return $this->buildErrorResult('3MF file contains no valid mesh geometry or triangles.');
        }

        return $this->buildSuccessResult($allVertices, $modelMetadata);
    }

    /**
     * Determine if ZIP entry path contains Zip Slip path traversal.
     */
    private function isZipSlipPath(string $path): bool
    {
        if (str_contains($path, '../') || str_contains($path, '..\\') || $path === '..') {
            return true;
        }

        if (str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[a-zA-Z]:/', $path)) {
            return true;
        }

        return false;
    }

    /**
     * Locate model XML files using OPC relationships or convention paths.
     */
    private function findModelFiles(ZipArchive $zip): array
    {
        $modelFiles = [];

        // Check _rels/.rels
        $relsContent = $zip->getFromName('_rels/.rels');
        if ($relsContent !== false && trim($relsContent) !== '') {
            $xml = $this->loadXmlSafely($relsContent);
            if ($xml !== null && isset($xml->Relationship)) {
                foreach ($xml->Relationship as $rel) {
                    $type = (string) ($rel['Type'] ?? '');
                    $target = (string) ($rel['Target'] ?? '');
                    if ($target !== '' && (str_contains($type, '3dmodel') || str_ends_with(strtolower($target), '.model'))) {
                        $cleanTarget = ltrim(str_replace('\\', '/', $target), '/');
                        if ($zip->locateName($cleanTarget) !== false && ! in_array($cleanTarget, $modelFiles, true)) {
                            $modelFiles[] = $cleanTarget;
                        }
                    }
                }
            }
        }

        // Fallback search
        if (empty($modelFiles)) {
            if ($zip->locateName('3D/3dmodel.model') !== false) {
                $modelFiles[] = '3D/3dmodel.model';
            } else {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if ($name !== false && str_ends_with(strtolower($name), '.model')) {
                        $cleanName = ltrim(str_replace('\\', '/', $name), '/');
                        if (! in_array($cleanName, $modelFiles, true)) {
                            $modelFiles[] = $cleanName;
                        }
                    }
                }
            }
        }

        return $modelFiles;
    }

    /**
     * Parse 3D model XML content securely into triangle vertices.
     *
     * @return array|string Array of vertices or error message string
     */
    private function parseModelXml(string $xmlContent): array|string
    {
        $xml = $this->loadXmlSafely($xmlContent);
        if ($xml === null) {
            return 'Invalid or malformed XML file in 3MF archive.';
        }

        // Extract objects in resources
        $objectElements = $xml->xpath('//*[local-name()="object"]');
        if (! is_array($objectElements)) {
            $objectElements = [];
        }

        $objectsRegistry = [];

        foreach ($objectElements as $objElem) {
            $attributes = $objElem->attributes();
            $id = isset($attributes['id']) ? (string) $attributes['id'] : null;
            if ($id === null || $id === '') {
                continue;
            }

            $meshElem = $objElem->xpath('./*[local-name()="mesh"]');
            $componentsElem = $objElem->xpath('./*[local-name()="components"]');

            $objectData = [
                'id' => $id,
                'vertices' => [],
                'triangles' => [],
                'components' => [],
            ];

            if (! empty($meshElem)) {
                $mesh = $meshElem[0];
                $vertexElems = $mesh->xpath('./*[local-name()="vertices"]/*[local-name()="vertex"]');
                if (is_array($vertexElems)) {
                    foreach ($vertexElems as $vElem) {
                        $vAttr = $vElem->attributes();
                        if (! isset($vAttr['x'], $vAttr['y'], $vAttr['z'])) {
                            return 'Corrupted 3MF mesh: missing vertex coordinates.';
                        }

                        $xStr = (string) $vAttr['x'];
                        $yStr = (string) $vAttr['y'];
                        $zStr = (string) $vAttr['z'];

                        if (! is_numeric($xStr) || ! is_numeric($yStr) || ! is_numeric($zStr)) {
                            return 'Invalid numeric data in 3MF vertex coordinates.';
                        }

                        $x = (float) $xStr;
                        $y = (float) $yStr;
                        $z = (float) $zStr;

                        if (is_nan($x) || is_nan($y) || is_nan($z) || is_infinite($x) || is_infinite($y) || is_infinite($z)) {
                            return 'Invalid numeric data in 3MF vertex coordinates.';
                        }

                        $objectData['vertices'][] = [$x, $y, $z];
                    }
                }

                $triangleElems = $mesh->xpath('./*[local-name()="triangles"]/*[local-name()="triangle"]');
                $vertexCount = count($objectData['vertices']);
                if (is_array($triangleElems)) {
                    foreach ($triangleElems as $tElem) {
                        $tAttr = $tElem->attributes();
                        if (! isset($tAttr['v1'], $tAttr['v2'], $tAttr['v3'])) {
                            return 'Corrupted 3MF mesh: missing triangle vertex indices.';
                        }

                        $v1Str = (string) $tAttr['v1'];
                        $v2Str = (string) $tAttr['v2'];
                        $v3Str = (string) $tAttr['v3'];

                        if (! ctype_digit($v1Str) || ! ctype_digit($v2Str) || ! ctype_digit($v3Str)) {
                            return 'Corrupted 3MF mesh: triangle references non-numeric vertex index.';
                        }

                        $v1 = (int) $v1Str;
                        $v2 = (int) $v2Str;
                        $v3 = (int) $v3Str;

                        if ($v1 < 0 || $v1 >= $vertexCount ||
                            $v2 < 0 || $v2 >= $vertexCount ||
                            $v3 < 0 || $v3 >= $vertexCount) {
                            return 'Corrupted 3MF mesh: triangle references non-existent vertex index.';
                        }

                        $objectData['triangles'][] = [$v1, $v2, $v3];
                    }
                }
            } elseif (! empty($componentsElem)) {
                $compElems = $componentsElem[0]->xpath('./*[local-name()="component"]');
                if (is_array($compElems)) {
                    foreach ($compElems as $cElem) {
                        $cAttr = $cElem->attributes();
                        $targetObjId = isset($cAttr['objectid']) ? (string) $cAttr['objectid'] : null;
                        $transformStr = isset($cAttr['transform']) ? (string) $cAttr['transform'] : null;

                        if ($targetObjId !== null && $targetObjId !== '') {
                            $objectData['components'][] = [
                                'objectid' => $targetObjId,
                                'transform' => $transformStr,
                            ];
                        }
                    }
                }
            }

            $objectsRegistry[$id] = $objectData;
        }

        // Find build items
        $itemElements = $xml->xpath('//*[local-name()="build"]/*[local-name()="item"]');
        if (! is_array($itemElements)) {
            $itemElements = [];
        }

        $allTrianglesVertices = [];
        $identityMatrix = [1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0];

        if (! empty($itemElements)) {
            foreach ($itemElements as $itemElem) {
                $itemAttr = $itemElem->attributes();
                $objId = isset($itemAttr['objectid']) ? (string) $itemAttr['objectid'] : null;
                $transformStr = isset($itemAttr['transform']) ? (string) $itemAttr['transform'] : null;

                if ($objId !== null && isset($objectsRegistry[$objId])) {
                    $itemMatrix = $this->parseTransformMatrix($transformStr);
                    $res = $this->resolveObjectVertices($objId, $itemMatrix, $objectsRegistry, $allTrianglesVertices);
                    if (is_string($res)) {
                        return $res;
                    }
                }
            }
        } else {
            // Fallback: If build is empty, resolve all mesh objects
            foreach ($objectsRegistry as $objId => $objData) {
                if (! empty($objData['triangles'])) {
                    $res = $this->resolveObjectVertices($objId, $identityMatrix, $objectsRegistry, $allTrianglesVertices);
                    if (is_string($res)) {
                        return $res;
                    }
                }
            }
        }

        return $allTrianglesVertices;
    }

    /**
     * Recursively resolve object vertices applying transformation matrices.
     */
    private function resolveObjectVertices(string $objId, array $matrix, array $objectsRegistry, array &$allTrianglesVertices, int $depth = 0): ?string
    {
        if ($depth > 20) {
            return '3MF contains cyclic or excessively deep component references.';
        }

        if (! isset($objectsRegistry[$objId])) {
            return "Referenced object ID [{$objId}] not found in 3MF resources.";
        }

        $objData = $objectsRegistry[$objId];

        // Process mesh
        if (! empty($objData['triangles'])) {
            $vertices = $objData['vertices'];
            foreach ($objData['triangles'] as $tri) {
                $v1 = $this->transformVertex($vertices[$tri[0]], $matrix);
                $v2 = $this->transformVertex($vertices[$tri[1]], $matrix);
                $v3 = $this->transformVertex($vertices[$tri[2]], $matrix);

                $allTrianglesVertices[] = $v1;
                $allTrianglesVertices[] = $v2;
                $allTrianglesVertices[] = $v3;
            }
        }

        // Process nested components
        if (! empty($objData['components'])) {
            foreach ($objData['components'] as $comp) {
                $childId = $comp['objectid'];
                $compMatrix = $this->parseTransformMatrix($comp['transform']);
                $combinedMatrix = $this->combineTransforms($compMatrix, $matrix);

                $err = $this->resolveObjectVertices($childId, $combinedMatrix, $objectsRegistry, $allTrianglesVertices, $depth + 1);
                if (is_string($err)) {
                    return $err;
                }
            }
        }

        return null;
    }

    /**
     * Parse 12-element 4x3 matrix string into array of 12 floats.
     */
    private function parseTransformMatrix(?string $transformStr): array
    {
        $identity = [1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0, 1.0, 0.0, 0.0, 0.0];
        if ($transformStr === null || trim($transformStr) === '') {
            return $identity;
        }

        $parts = preg_split('/\s+/', trim($transformStr));
        if (count($parts) !== 12) {
            return $identity;
        }

        $matrix = [];
        foreach ($parts as $p) {
            if (! is_numeric($p)) {
                return $identity;
            }
            $matrix[] = (float) $p;
        }

        return $matrix;
    }

    /**
     * Combine child and parent affine matrices.
     */
    private function combineTransforms(array $childMatrix, array $parentMatrix): array
    {
        $B = $childMatrix;
        $A = $parentMatrix;

        return [
            $B[0] * $A[0] + $B[1] * $A[3] + $B[2] * $A[6],
            $B[0] * $A[1] + $B[1] * $A[4] + $B[2] * $A[7],
            $B[0] * $A[2] + $B[1] * $A[5] + $B[2] * $A[8],

            $B[3] * $A[0] + $B[4] * $A[3] + $B[5] * $A[6],
            $B[3] * $A[1] + $B[4] * $A[4] + $B[5] * $A[7],
            $B[3] * $A[2] + $B[4] * $A[5] + $B[5] * $A[8],

            $B[6] * $A[0] + $B[7] * $A[3] + $B[8] * $A[6],
            $B[6] * $A[1] + $B[7] * $A[4] + $B[8] * $A[7],
            $B[6] * $A[2] + $B[7] * $A[5] + $B[8] * $A[8],

            $B[9] * $A[0] + $B[10] * $A[3] + $B[11] * $A[6] + $A[9],
            $B[9] * $A[1] + $B[10] * $A[4] + $B[11] * $A[7] + $A[10],
            $B[9] * $A[2] + $B[10] * $A[5] + $B[11] * $A[8] + $A[11],
        ];
    }

    /**
     * Apply 4x3 affine transformation matrix to a 3D vertex.
     */
    private function transformVertex(array $v, array $M): array
    {
        $x = $v[0];
        $y = $v[1];
        $z = $v[2];

        $tx = $x * $M[0] + $y * $M[3] + $z * $M[6] + $M[9];
        $ty = $x * $M[1] + $y * $M[4] + $z * $M[7] + $M[10];
        $tz = $x * $M[2] + $y * $M[5] + $z * $M[8] + $M[11];

        return [$tx, $ty, $tz];
    }

    /**
     * Safely load XML string preventing XXE and external entity inclusion.
     */
    private function loadXmlSafely(string $xmlContent): ?SimpleXMLElement
    {
        if (preg_match('/<!DOCTYPE/i', $xmlContent) || preg_match('/<!ENTITY/i', $xmlContent)) {
            return null;
        }

        $disableEntities = true;
        if (\PHP_VERSION_ID < 80000) {
            $disableEntities = libxml_disable_entity_loader(true);
        }

        $internalErrors = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_string($xmlContent, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOENT);
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            if (\PHP_VERSION_ID < 80000) {
                libxml_disable_entity_loader($disableEntities);
            }

            return $xml !== false ? $xml : null;
        } catch (Exception) {
            libxml_clear_errors();
            libxml_use_internal_errors($internalErrors);

            if (\PHP_VERSION_ID < 80000) {
                libxml_disable_entity_loader($disableEntities);
            }

            return null;
        }
    }

    /**
     * Build success result array matching STL parser format.
     */
    private function buildSuccessResult(array $vertices, array $metadata): array
    {
        $vertexCount = count($vertices);
        $triangleCount = intdiv($vertexCount, 3);

        if ($triangleCount === 0) {
            return $this->buildErrorResult('3MF file contains no triangles.');
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
     * Build error result array.
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
