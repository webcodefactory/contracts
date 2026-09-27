<?php

declare(strict_types=1);

namespace Alumateria\Contracts\Tests\DataExchange\Export;

use Alumateria\Contracts\DataExchange\Export\ExportColumns;
use PHPUnit\Framework\TestCase;

final class ExportColumnsTest extends TestCase
{
    public function testRowFollowsColumnOrderAndFillsMissingCellsWithNull(): void
    {
        $columns = ExportColumns::fromList(['a', 'b', 'c']);

        $row = $columns->row(['c' => 3, 'a' => 'x']);

        self::assertSame(['a' => 'x', 'b' => null, 'c' => 3], $row->cells());
        self::assertSame(['x', null, 3], $row->values());
        self::assertCount(3, $columns);
    }

    public function testWithAppendsColumns(): void
    {
        $columns = ExportColumns::fromList(['a'])->with(['b', 'c']);

        self::assertSame(['a', 'b', 'c'], $columns->keys());
    }

    public function testRejectsUnknownCells(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown export columns: zzz');

        ExportColumns::fromList(['a'])->row(['zzz' => 1]);
    }

    public function testRejectsDuplicateEmptyOrNoColumns(): void
    {
        foreach ([['a', 'a'], ['a', ''], []] as $keys) {
            try {
                ExportColumns::fromList($keys);
                self::fail('Expected an exception for '.json_encode($keys));
            } catch (\InvalidArgumentException) {
                self::assertTrue(true);
            }
        }
    }

    public function testRejectsNonScalarCells(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ExportColumns::fromList(['a'])->row(['a' => ['nested']]);
    }
}
