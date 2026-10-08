<?php

namespace Tests\Unit\Services\ThreeD;

use App\Services\ThreeD\StlParserService;
use App\Services\ThreeD\ThreeDParserInterface;
use App\Services\ThreeD\ThreeMfParserService;
use Tests\TestCase;
use ZipArchive;

class ThreeMfParserServiceTest extends TestCase
{
    private ThreeMfParserService $parser;

    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new ThreeMfParserService;
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

    private function createTempFile(string $content, string $suffix = '.3mf'): string
    {
        $filePath = sys_get_temp_dir().'/3mf_test_'.uniqid().$suffix;
        file_put_contents($filePath, $content);
        $this->tempFiles[] = $filePath;

        return $filePath;
    }

    private function create3MfZip(array $files): string
    {
        $filePath = sys_get_temp_dir().'/3mf_test_'.uniqid().'.3mf';
        $zip = new ZipArchive;
        $zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();
        $this->tempFiles[] = $filePath;

        return $filePath;
    }

    public function test_implements_three_d_parser_interface(): void
    {
        $this->assertInstanceOf(ThreeDParserInterface::class, $this->parser);
    }

    public function test_parses_valid_single_mesh_3mf(): void
    {
        $modelXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<model xmlns="http://schemas.microsoft.com/3dmanufacturing/2013/01">
  <resources>
    <object id="1" type="model">
      <mesh>
        <vertices>
          <vertex x="0" y="0" z="0" />
          <vertex x="1" y="0" z="0" />
          <vertex x="1" y="1" z="0" />
          <vertex x="0" y="1" z="0" />
          <vertex x="0" y="0" z="1" />
          <vertex x="1" y="0" z="1" />
          <vertex x="1" y="1" z="1" />
          <vertex x="0" y="1" z="1" />
        </vertices>
        <triangles>
          <!-- Top -->
          <triangle v1="4" v2="5" v3="6" />
          <triangle v1="4" v2="6" v3="7" />
          <!-- Bottom -->
          <triangle v1="0" v2="2" v3="1" />
          <triangle v1="0" v2="3" v3="2" />
          <!-- Front -->
          <triangle v1="0" v2="1" v3="5" />
          <triangle v1="0" v2="5" v3="4" />
          <!-- Back -->
          <triangle v1="2" v2="3" v3="7" />
          <triangle v1="2" v2="7" v3="6" />
          <!-- Left -->
          <triangle v1="0" v2="4" v3="7" />
          <triangle v1="0" v2="7" v3="3" />
          <!-- Right -->
          <triangle v1="1" v2="2" v3="6" />
          <triangle v1="1" v2="6" v3="5" />
        </triangles>
      </mesh>
    </object>
  </resources>
  <build>
    <item objectid="1" />
  </build>
</model>
XML;

        $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Target="/3D/3dmodel.model" Id="rel0" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>
XML;

        $filePath = $this->create3MfZip([
            '_rels/.rels' => $relsXml,
            '3D/3dmodel.model' => $modelXml,
        ]);

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

        $this->assertEquals('3mf', $result['metadata']['format']);
    }

    public function test_parses_multi_object_and_components_with_transforms(): void
    {
        $modelXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<model xmlns="http://schemas.microsoft.com/3dmanufacturing/2013/01">
  <resources>
    <!-- Object 1: Single Triangle -->
    <object id="1" type="model">
      <mesh>
        <vertices>
          <vertex x="0" y="0" z="0" />
          <vertex x="1" y="0" z="0" />
          <vertex x="0" y="1" z="0" />
        </vertices>
        <triangles>
          <triangle v1="0" v2="1" v3="2" />
        </triangles>
      </mesh>
    </object>
    <!-- Object 2: Component referencing Object 1 translated by x=+10 -->
    <object id="2" type="model">
      <components>
        <component objectid="1" transform="1 0 0 0 1 0 0 0 1 10 0 0" />
      </components>
    </object>
  </resources>
  <build>
    <!-- Build places Object 1 at origin and Object 2 translated -->
    <item objectid="1" />
    <item objectid="2" />
  </build>
</model>
XML;

        $filePath = $this->create3MfZip([
            '3D/3dmodel.model' => $modelXml,
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(6, $result['vertex_count']);
        $this->assertEquals(2, $result['triangle_count']);

        // Width spanned from x=0 to x=11
        $this->assertEquals(11.0, $result['width']);
        $this->assertEquals(1.0, $result['height']);
        $this->assertEquals(0.0, $result['depth']);

        $this->assertEquals(['x' => 0.0, 'y' => 0.0, 'z' => 0.0], $result['bounds']['min']);
        $this->assertEquals(['x' => 11.0, 'y' => 1.0, 'z' => 0.0], $result['bounds']['max']);
    }

    public function test_parses_3mf_with_opc_rels(): void
    {
        $modelXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<model xmlns="http://schemas.microsoft.com/3dmanufacturing/2013/01">
  <resources>
    <object id="1" type="model">
      <mesh>
        <vertices>
          <vertex x="0" y="0" z="0" />
          <vertex x="2" y="0" z="0" />
          <vertex x="0" y="2" z="0" />
        </vertices>
        <triangles>
          <triangle v1="0" v2="1" v3="2" />
        </triangles>
      </mesh>
    </object>
  </resources>
  <build>
    <item objectid="1" />
  </build>
</model>
XML;

        $relsXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Target="3D/custom_model.model" Id="rel0" Type="http://schemas.microsoft.com/3dmanufacturing/2013/01/3dmodel"/>
</Relationships>
XML;

        $filePath = $this->create3MfZip([
            '_rels/.rels' => $relsXml,
            '3D/custom_model.model' => $modelXml,
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(1, $result['triangle_count']);
        $this->assertEquals(2.0, $result['width']);
        $this->assertEquals(2.0, $result['height']);
    }

    public function test_fails_on_non_existent_file(): void
    {
        $result = $this->parser->parse('/path/to/non_existent_file.3mf');

        $this->assertEquals('failed', $result['status']);
        $this->assertNotEmpty($result['error_message']);
    }

    public function test_fails_on_empty_file(): void
    {
        $filePath = $this->createTempFile('');

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertNotEmpty($result['error_message']);
    }

    public function test_fails_on_invalid_zip_archive(): void
    {
        $filePath = $this->createTempFile('This is not a valid ZIP file.');

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertNotEmpty($result['error_message']);
    }

    public function test_fails_on_missing_model_file(): void
    {
        $filePath = $this->create3MfZip([
            'readme.txt' => 'No model files here.',
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Missing 3D model XML file', $result['error_message']);
    }

    public function test_fails_on_corrupted_xml(): void
    {
        $corruptedXml = '<model><resources><object><mesh><unclosed_tag>';

        $filePath = $this->create3MfZip([
            '3D/3dmodel.model' => $corruptedXml,
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('XML', $result['error_message']);
    }

    public function test_fails_on_corrupted_mesh_indices(): void
    {
        $modelXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<model xmlns="http://schemas.microsoft.com/3dmanufacturing/2013/01">
  <resources>
    <object id="1" type="model">
      <mesh>
        <vertices>
          <vertex x="0" y="0" z="0" />
          <vertex x="1" y="0" z="0" />
        </vertices>
        <triangles>
          <!-- Vertex index 99 is out of bounds (only 2 vertices exist) -->
          <triangle v1="0" v2="1" v3="99" />
        </triangles>
      </mesh>
    </object>
  </resources>
  <build>
    <item objectid="1" />
  </build>
</model>
XML;

        $filePath = $this->create3MfZip([
            '3D/3dmodel.model' => $modelXml,
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('triangle references non-existent vertex index', $result['error_message']);
    }

    public function test_security_prevents_zip_slip(): void
    {
        $filePath = $this->create3MfZip([
            '../evil.txt' => 'Zip slip attempt',
            '3D/3dmodel.model' => '<model></model>',
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('Zip Slip', $result['error_message']);
    }

    public function test_security_prevents_xxe(): void
    {
        $xxeXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE model [
  <!ENTITY xxe SYSTEM "file:///etc/passwd">
]>
<model xmlns="http://schemas.microsoft.com/3dmanufacturing/2013/01">
  <resources>
    <object id="1" type="model">
      <mesh>
        <vertices>
          <vertex x="0" y="0" z="0" />
          <vertex x="1" y="0" z="0" />
          <vertex x="0" y="1" z="0" />
        </vertices>
        <triangles>
          <triangle v1="0" v2="1" v3="2" />
        </triangles>
      </mesh>
    </object>
  </resources>
  <build>
    <item objectid="1" />
  </build>
</model>
XML;

        $filePath = $this->create3MfZip([
            '3D/3dmodel.model' => $xxeXml,
        ]);

        $result = $this->parser->parse($filePath);

        $this->assertEquals('failed', $result['status']);
        $this->assertStringContainsString('XML', $result['error_message']);
    }

    public function test_output_format_parity_with_stl(): void
    {
        $modelXml = <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<model xmlns="http://schemas.microsoft.com/3dmanufacturing/2013/01">
  <resources>
    <object id="1" type="model">
      <mesh>
        <vertices>
          <vertex x="0" y="0" z="0" />
          <vertex x="1" y="0" z="0" />
          <vertex x="0" y="1" z="0" />
        </vertices>
        <triangles>
          <triangle v1="0" v2="1" v3="2" />
        </triangles>
      </mesh>
    </object>
  </resources>
  <build>
    <item objectid="1" />
  </build>
</model>
XML;

        $filePath3mf = $this->create3MfZip([
            '3D/3dmodel.model' => $modelXml,
        ]);

        $stlContent = <<<'STL'
solid test
  facet normal 0 0 1
    outer loop
      vertex 0 0 0
      vertex 1 0 0
      vertex 0 1 0
    endloop
  endfacet
endsolid test
STL;

        $filePathStl = $this->createTempFile($stlContent, '.stl');

        $stlParser = new StlParserService;

        $result3mf = $this->parser->parse($filePath3mf);
        $resultStl = $stlParser->parse($filePathStl);

        // Verify keys parity
        $this->assertEquals(array_keys($resultStl), array_keys($result3mf));
        $this->assertEquals(array_keys($resultStl['center']), array_keys($result3mf['center']));
        $this->assertEquals(array_keys($resultStl['bounds']), array_keys($result3mf['bounds']));
        $this->assertEquals(array_keys($resultStl['bounds']['min']), array_keys($result3mf['bounds']['min']));
        $this->assertEquals(array_keys($resultStl['bounds']['max']), array_keys($result3mf['bounds']['max']));

        // Verify metrics parity for identical geometry
        $this->assertEquals($resultStl['vertex_count'], $result3mf['vertex_count']);
        $this->assertEquals($resultStl['triangle_count'], $result3mf['triangle_count']);
        $this->assertEquals($resultStl['volume'], $result3mf['volume']);
        $this->assertEquals($resultStl['surface_area'], $result3mf['surface_area']);
        $this->assertEquals($resultStl['width'], $result3mf['width']);
        $this->assertEquals($resultStl['height'], $result3mf['height']);
        $this->assertEquals($resultStl['depth'], $result3mf['depth']);
        $this->assertEquals($resultStl['bounds'], $result3mf['bounds']);
    }
}
