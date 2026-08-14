<?php

declare(strict_types=1);

namespace Edulume\Core\Tests\Integration\Theming;

use Edulume\Core\Application\Theming\CompiledStylesheet;
use Edulume\Core\Infrastructure\Theming\StylesheetEnqueuer;
use Edulume\Core\Infrastructure\Theming\UploadsStylesheetWriter;
use Edulume\Core\Infrastructure\Wp\Container;
use WP_UnitTestCase;

/**
 * Proves the stylesheet actually lands on disk inside the uploads directory, is not rewritten
 * when unchanged, and leaves nothing behind when it is.
 */
final class UploadsStylesheetWriterTest extends WP_UnitTestCase
{
    private UploadsStylesheetWriter $writer;

    public function set_up(): void
    {
        parent::set_up();

        $this->writer = new UploadsStylesheetWriter();

        foreach (glob($this->writer->directoryPath() . '/*.css') ?: [] as $path) {
            wp_delete_file($path);
        }
    }

    /** @test */
    public function it_writes_the_stylesheet_into_the_uploads_directory(): void
    {
        $stylesheet = CompiledStylesheet::of(":root {\n  --edulume-accent: #123a6b;\n}\n");

        $url = $this->writer->write($stylesheet);

        $this->assertFileExists($this->writer->directoryPath() . '/' . $stylesheet->fileName());
        $this->assertSame($this->writer->urlFor($stylesheet), $url);
        $this->assertStringContainsString('/uploads/' . UploadsStylesheetWriter::DIRECTORY_NAME . '/', $url);
    }

    /** @test */
    public function it_writes_exactly_what_it_was_given(): void
    {
        $css = ":root {\n  --edulume-accent: #123a6b;\n}\n";
        $stylesheet = CompiledStylesheet::of($css);

        $this->writer->write($stylesheet);

        $this->assertSame($css, file_get_contents($this->writer->directoryPath() . '/' . $stylesheet->fileName()));
    }

    /** @test */
    public function it_leaves_an_identical_file_alone(): void
    {
        $stylesheet = CompiledStylesheet::of(":root {\n  --edulume-accent: #123a6b;\n}\n");

        $this->writer->write($stylesheet);
        $path = $this->writer->directoryPath() . '/' . $stylesheet->fileName();
        $writtenAt = filemtime($path);

        $this->writer->write($stylesheet);

        $this->assertSame($writtenAt, filemtime($path));
    }

    /** @test */
    public function it_prunes_every_stylesheet_except_the_one_it_keeps(): void
    {
        $old = CompiledStylesheet::of(":root {\n  --edulume-accent: #123a6b;\n}\n");
        $new = CompiledStylesheet::of(":root {\n  --edulume-accent: #7a2e6b;\n}\n");

        $this->writer->write($old);
        $this->writer->write($new);
        $this->writer->pruneOthers($new);

        $this->assertFileDoesNotExist($this->writer->directoryPath() . '/' . $old->fileName());
        $this->assertFileExists($this->writer->directoryPath() . '/' . $new->fileName());
    }

    /** @test */
    public function it_publishes_and_enqueues_the_compiled_stylesheet(): void
    {
        $container = new Container();

        $enqueuer = new StylesheetEnqueuer(
            $container->publishStylesheet(),
            $container->settingsRepository(),
            $container->compileStylesheet(),
        );

        $url = $enqueuer->republish();

        $this->assertSame($url, get_option(StylesheetEnqueuer::PUBLISHED_URL_OPTION));
        $this->assertSame(
            $enqueuer->pendingVersion(),
            get_option(StylesheetEnqueuer::PUBLISHED_VERSION_OPTION),
            'The published file is stale immediately after publishing.',
        );

        $enqueuer->enqueue();

        $this->assertTrue(wp_style_is(StylesheetEnqueuer::HANDLE, 'enqueued'));
    }
}
