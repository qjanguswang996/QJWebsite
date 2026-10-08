<?php

namespace Tests\Unit\Services\ThreeD;

use App\Services\ThreeD\StlParserService;
use App\Services\ThreeD\ThreeDParserInterface;
use Tests\TestCase;

class StlParserServiceTest extends TestCase
{
    private StlParserService $parser;
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new StlParserService();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        parent::tearDown();
    }

    private function createTempFile(string $content, string $prefix = 'stl_test_'): string
    {
        $filePath = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($filePath, $content);
        $this->tempFiles[] = $filePath;
        return $filePath;
    }

    public function test_implements_three_d_parser_interface(): void
    {
        $this->assertInstanceOf(ThreeDParserInterface::class, $this->parser);
    }

    public function test_parses_valid_ascii_stl(): void
    {
        $asciiStl = <<<'STL'
solid cube
  facet normal 0 0 1
    outer loop
      vertex 0 0 1
      vertex 1 0 1
      vertex 1 1 1
    endloop
  endfacet
  facet normal 0 0 1
    outer loop
      vertex 0 0 1
      vertex 1 1 1
      vertex 0 1 1
    endloop
  endfacet
  facet normal 0 0 -1
    outer loop
      vertex 0 0 0
      vertex 0 1 0
      vertex 1 1 0
    endloop
  endfacet
  facet normal 0 0 -1
    outer loop
      vertex 0 0 0
      vertex 1 1 0
      vertex 1 0 0
    endloop
  endfacet
  facet normal -1 0 0
    outer loop
      vertex 0 0 0
      vertex 0 0 1
      vertex 0 1 1
    endloop
  endfacet
  facet normal -1 0 0
    outer loop
      vertex 0 0 0
      vertex 0 1 1
      vertex 0 1 0
    endloop
  endfacet
  facet normal 1 0 0
    outer loop
      vertex 1 0 0
      vertex 1 1 0
      vertex 1 1 1
    endloop
  endfacet
  facet normal 1 0 0
    outer loop
      vertex 1 0 0
      vertex 1 1 1
      vertex 1 0 1
    endloop
  endfacet
  facet normal 0 -1 0
    outer loop
      vertex 0 0 0
      vertex 1 0 0
      vertex 1 0 1
    endloop
  endfacet
  facet normal 0 -1 0
    outer loop
      vertex 0 0 0
      vertex 1 0 1
      vertex 0 0 1
    endloop
  endfacet
  facet normal 0 1 0
    outer loop
      vertex 0 1 0
      vertex 0 1 1
      vertex 1 1 1
    endloop
  endfacet
  facet normal 0 1 0
    outer loop
      vertex 0 1 0
      vertex 1 1 1
      vertex 1 1 0
    endloop
  endfacet
endsolid cube
STL;

        $filePath = $this->createTempFile($asciiStl);
        $result = $this->parser->parse($filePath);

        $this->assertEquals('completed', $result['status']);
        $this->assertNotNull($result['parsed_at']);
        $this->assertNull($result['error_message']);

        $this->assertEquals(36, $result['vertex_count']);
        $this->assertEquals(12, $result['triangle_count']);

        $this->assertEquals(1.0, $result['volume']);
        $this->assertEquals(6.0, $result['surface_area']);

        $this->assertEquals(1.0, $result['width']);
        $this->assertEquals(1.0, $result['height']);
        $this->assertEquals(1.0, $result['depth']);

        $this->assertEquals(['x' => 0.5, 'y' => 0.5, 'z' => 0.5], $result['center']);
        $this->assertEquals(['x' => 0.0, 'y' => 0.0, 'z' => 0.0], $result['bounds']['min']);
        $this->assertEquals(['x' => 1.0, 'y' => 1.0, 'z' => 1.0], $result['bounds']['max']);

        $this->assertEquals('ascii', $result['metadata']['format']);
        $this->assertEquals('cube', $result['metadata']['name']);
    }

    public function test_parses_valid_binary_stl(): void
    {
        $triangles = [
            [[0,0,1],[1,0,1],[1,1,1]],
            [[0,0,1],[1,1,1],[0,1,1]],
            [[0,0,0],[0,1,0],[1,1,0]],
            [[0,0,0],[1,1,0],[1,0,0]],
            [[0,0,0],[0,0,1],[0,1,1]],
            [[0,0,0],[0,1,1],[0,1,0]],
            [[1,0,0],[1,1,0],[1,1,1]],
            [[1,0,0],[1,1,1],[1,0,1]],
            [[0,0,0],[1,0,0],[1,0,1]],
            [[0,0,0],[1,0,1],[0,0,1]],
            [[0,1,0],[0,1,1],[1,1,1]],
            [[0,1,0],[1,1,1],[1,1,0]],
        ];

        $header = str_pad("Binary STL Cube Test File", 80, "\0");
        $binaryData = $header . pack('V', count($triangles));

        foreach ($triangles as $t) {
            // Normal (12 bytes, dummy 0,0,0)
            $binaryData .= pack('g3', 0.0, 0.0, 0.0);
            // Vertices v1, v2, v3
            $binaryData .= pack('g3', ...$t[0]);
            $binaryData .= pack('g3', ...$t[1]);
            $binaryData .= pack('g3', ...$t[2]);
            // Attribute byte count (2 bytes)
            $binaryData .= pack('v', 0);
        }

        $filePath = $this->createTempFile($binaryData);
        $result = $this->parser->parse($filePath);

        $this->assertEquals('completed', $result['status']);
        $this->assertNotNull($result['parsed_at']);
        $this->assertNull($result['error_message']);

        $this->assertEquals(36, $result['vertex_count']);
        $this->assertEquals(12, $result['triangle_count']);

        $this->assertEquals(1.0, $result['volume']);
        $this->assertEquals(6.0, $result['surface_area']);

        $this->assertEquals(1.0, $result['width']);
        $this->assertEquals(1.0, $result['height']);
        $this->assertEquals(1.0, $result['depth']);

        $this->assertEquals(['x' => 0.5, 'y' => 0.5, 'z' => 0.5], $result['center']);
        $this->assertEquals(['x' => 0.0, 'y' => 0.0, 'z' => 0.0], $result['bounds']['min']);
        $this->assertEquals(['x' => 1.0, 'y' => 1.0, 'z' => 1.0], $result['bounds']['max']);

        $this->assertEquals('binary', $result['metadata']['format']);
    }

    public function test_fails_on_invalid_stl(): void
    {
        $invalidContent = "This is a plain text document, not an STL file.";
        $filePath = $this->createTempFile($invalidContent);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertNull($result['parsed_at']);
        $this->assertNotEmpty($result['error_message']);
        $this->assertEquals(0, $result['vertex_count']);
        $this->assertEquals(0, $result['triangle_count']);
    }

    public function test_fails_on_empty_stl(): void
    {
        $filePath = $this->createTempFile('');

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertNull($result['parsed_at']);
        $this->assertNotEmpty($result['error_message']);
        $this->assertEquals(0, $result['vertex_count']);
        $this->assertEquals(0, $result['triangle_count']);
    }

    public function test_fails_on_corrupted_binary_stl(): void
    {
        // Header says 10 triangles, but file only provides 1 triangle
        $header = str_pad("Corrupted Binary STL", 80, "\0");
        $binaryData = $header . pack('V', 10);
        $binaryData .= pack('g3', 0.0, 0.0, 0.0);
        $binaryData .= pack('g3', 0, 0, 0);
        $binaryData .= pack('g3', 1, 0, 0);
        $binaryData .= pack('g3', 0, 1, 0);
        $binaryData .= pack('v', 0);

        $filePath = $this->createTempFile($binaryData);
        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertNull($result['parsed_at']);
        $this->assertNotEmpty($result['error_message']);
    }

    public function test_fails_on_corrupted_ascii_stl(): void
    {
        // Missing vertices in facet
        $corruptedAscii = <<<'STL'
solid corrupted
  facet normal 0 0 1
    outer loop
      vertex 0 0 1
      vertex 1 0 1
    endloop
  endfacet
endsolid corrupted
STL;

        $filePath = $this->createTempFile($corruptedAscii);
        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertNull($result['parsed_at']);
        $this->assertNotEmpty($result['error_message']);
    }

    public function test_fails_on_non_existent_file(): void
    {
        $result = $this->parser->parse('/path/to/non_existent_file.stl');

        $this->assertEquals('failed', $result['status']);
        $this->assertNull($result['parsed_at']);
        $this->assertNotEmpty($result['error_message']);
    }
}
